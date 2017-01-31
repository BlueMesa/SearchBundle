<?php

/*
 * This file is part of the SearchBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\SearchBundle\Request;

use Bluemesa\Bundle\CoreBundle\Doctrine\ObjectManagerRegistry;
use Bluemesa\Bundle\SearchBundle\Event\ResultActionEvent;
use Bluemesa\Bundle\SearchBundle\Event\SearchControllerEvents;
use Bluemesa\Bundle\SearchBundle\Repository\SearchableRepositoryInterface;
use Bluemesa\Bundle\SearchBundle\Search\SearchQuery;
use Bluemesa\Bundle\SearchBundle\Search\SearchQueryInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use FOS\RestBundle\View\View;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;


/**
 * Class SearchHandler
 *
 * @DI\Service("bluemesa.search.handler")
 *
 * @package Bluemesa\Bundle\SearchBundle\Request
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class SearchHandler
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var ObjectManagerRegistry
     */
    protected $registry;

    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var SessionInterface
     */
    protected $session;


    /**
     * SearchHandler constructor.
     *
     * @DI\InjectParams({
     *     "dispatcher" = @DI\Inject("event_dispatcher"),
     *     "registry" = @DI\Inject("bluemesa.core.doctrine.registry"),
     *     "factory" = @DI\Inject("form.factory"),
     *     "router" = @DI\Inject("router"),
     *     "serializer" = @DI\Inject("serializer"),
     *     "session" = @DI\Inject("session")
     * })
     *
     * @param EventDispatcherInterface  $dispatcher
     * @param ObjectManagerRegistry     $registry
     * @param FormFactoryInterface      $factory
     * @param RouterInterface           $router
     * @param SerializerInterface       $serializer
     * @param SessionInterface          $session
     */
    public function __construct(EventDispatcherInterface $dispatcher,
                                ObjectManagerRegistry $registry,
                                FormFactoryInterface $factory,
                                RouterInterface $router,
                                SerializerInterface $serializer,
                                SessionInterface $session)
    {
        $this->dispatcher = $dispatcher;
        $this->registry = $registry;
        $this->factory = $factory;
        $this->router = $router;
        $this->serializer = $serializer;
        $this->session = $session;
    }


    /**
     * This method calls a proper handler for the incoming request
     *
     * @param  Request $request
     * @return View
     * @throws \LogicException
     */
    public function handle(Request $request)
    {
        $action = $request->get('search_action');
        switch($action) {
            case 'search':
                $result = $this->handleSearchAction($request);
                break;
            case 'result':
                $result =  $this->handleResultAction($request);
                break;
            default:
                $message  = "The action '" . $action;
                $message .= "' is not one of the allowed search actions ('search', 'result').";
                throw new \LogicException($message);
        }

        return $result;
    }

    /**
     * This method calls a proper handler for the incoming request
     *
     * @param  Request $request
     * @return View
     */
    public function handleSearchAction($request)
    {
        $type = $request->get('search_type');
        $realm = $request->get('search_realm');
        $simple = $request->get('search_simple');
        $name = 'search_' . $realm . ($simple ? '_simple' : '_advanced');
        $form = $this->factory->createNamed($name, $type, null, array('simple' => $simple));

        return View::create(array('form' => $form->createView(), 'realm' => $realm));
    }

    /**
     * This method calls a proper handler for the incoming request
     *
     * @param  Request $request
     * @return View
     */
    public function handleResultAction(Request $request)
    {
        $type = $request->get('search_type');
        $realm = $request->get('search_realm');
        $name_simple = 'search_' . $realm . '_simple';
        $name_advanced = 'search_' . $realm . '_advanced';

        if ($request->request->has($name_simple)) {
            $form = $this->factory->createNamed($name_simple, $type);
        } elseif ($request->request->has($name_advanced)) {
            $form = $this->factory->createNamed($name_advanced, $type);
        } else {
            $form = $this->factory->create($type);
        }

        $event = new ResultActionEvent($request, $form);
        $this->dispatcher->dispatch(SearchControllerEvents::RESULT_INITIALIZE, $event);

        if (null !== $event->getView()) {
            return $event->getView();
        }

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $query = $form->getData();
            } else {
                throw new BadRequestHttpException("Invalid data has been submitted to the search form.");
            }
            $this->saveSearchQuery($realm, $query);
        } else {
            $query = $this->loadSearchQuery($realm);
        }
        $repository = $this->registry->getManager()->getRepository($query->getEntityClass());

        $event = new ResultActionEvent($request, $form, $query, $repository);
        $this->dispatcher->dispatch(SearchControllerEvents::RESULT_QUERY, $event);

        if (null === $entities = $event->getEntities()) {
            if ($repository instanceof SearchableRepositoryInterface) {
                $entities = $this->handleSearchableRepository($repository, $query);
            } else {
                $entities = $this->handleNonSearchableRepository($repository, $query);
            }
        }

        $event = new ResultActionEvent($request, $form, $query, $repository, $entities);
        $this->dispatcher->dispatch(SearchControllerEvents::RESULT_FETCHED, $event);

        if (null === $view = $event->getView()) {
            $view = View::create(array('entities' => $entities, 'query' => $query));
        }

        $event = new ResultActionEvent($request, $form, $query, $repository, $entities, $view);
        $this->dispatcher->dispatch(SearchControllerEvents::RESULT_COMPLETED, $event);

        /** @var View $view */
        return $view;
    }

    /**
     * @param SearchableRepositoryInterface $repository
     * @param SearchQueryInterface $query
     *
     * @return array
     * @throws NotFoundHttpException
     */
    protected function handleSearchableRepository(SearchableRepositoryInterface $repository,
                                                  SearchQueryInterface $query)
    {
        if (count($query->getTerms()) == 0) {
            throw new NotFoundHttpException();
        }

        return $repository->getSearchQuery($query)->getResult();
    }

    /**
     * @param ObjectRepository $repository
     * @param SearchQueryInterface $query
     *
     * @throws \InvalidArgumentException
     * @return null
     */
    protected function handleNonSearchableRepository(ObjectRepository $repository,
                                                     SearchQueryInterface $query)
    {
        throw new \InvalidArgumentException("Repository " . get_class($repository) .
            " must implement SearchableRepositoryInterface.");
    }

    /**
     * Load search query from session
     *
     * @param  string      $realm
     * @return SearchQuery
     */
    protected function loadSearchQuery($realm)
    {
        $serializedQuery = $this->session->get($realm . '_search_query');

        /** @var SearchQuery $query */
        $query = $this->serializer->deserialize($serializedQuery['object'], $serializedQuery['class'], 'json');

        return $query;
    }

    /**
     * Save search query in session
     *
     * @param  string      $realm
     * @param  SearchQuery $searchQuery
     */
    protected function saveSearchQuery($realm, SearchQuery $searchQuery)
    {
        $serializedQuery = array(
            'object' => $this->serializer->serialize($searchQuery, 'json'),
            'class' => get_class($searchQuery)
        );
        $this->session->set($realm . '_search_query', $serializedQuery);
    }
}
