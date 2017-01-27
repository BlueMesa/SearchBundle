<?php

/*
 * This file is part of the CRUD Bundle.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\SearchBundle\EventListener;

use Bluemesa\Bundle\SearchBundle\Controller\Annotations\Paginate;
use Bluemesa\Bundle\SearchBundle\Event\ResultActionEvent;
use Bluemesa\Bundle\SearchBundle\Repository\SearchableRepositoryInterface;
use Doctrine\Common\Annotations\Reader;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * The SearchPaginationListener handles Pagination annotation for controllers.
 *
 * @DI\Service("bluemesa.search.listener.pagination")
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.search_result_query",
 *         "method" = "onSearchResultQuery",
 *         "priority" = 100
 *     }
 * )
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class SearchPaginationListener
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "reader" = @DI\Inject("annotation_reader"),
     *     "paginator" = @DI\Inject("knp_paginator"),
     * })
     *
     * @param  Reader              $reader
     * @param  PaginatorInterface  $paginator
 d     */
    public function __construct(Reader $reader, PaginatorInterface $paginator)
    {
        $this->reader = $reader;
        $this->paginator = $paginator;
    }

    /**
     * @param  ResultActionEvent $event
     * @throws \InvalidArgumentException
     *
     */
    public function onSearchResultQuery(ResultActionEvent $event)
    {
        $request = $event->getRequest();
        $controller = $this->getController($request);

        if (is_array($controller)) {
            $m = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && is_callable($controller, '__invoke')) {
            $m = new \ReflectionMethod($controller, '__invoke');
        } else {
            return;
        }

        /** @var Paginate $paginateAnnotation */
        $paginateAnnotation = $this->reader->getMethodAnnotation($m, Paginate::class);
        if (! $paginateAnnotation) {
            return;
        }

        $maxResults = $paginateAnnotation->getMaxResults();
        $page = $request->get('page', 1);
        $searchQuery = $event->getQuery();
        $repository = $event->getRepository();

        if ($repository instanceof SearchableRepositoryInterface) {
            $count = $repository->getSearchResultCount($searchQuery);
            $query = $repository->getSearchQuery($searchQuery)->setHint('knp_paginator.count', $count);
            $options = array('distinct' => false);
        } else {
            throw new \InvalidArgumentException("Repository " . get_class($repository) .
                " must implement SearchableRepositoryInterface.");
        }

        $entities = $this->paginator->paginate($query, $page, $maxResults, $options);
        $event->setEntities($entities);
    }

    /**
     * @param  Request  $request
     * @return array
     */
    private function getController($request)
    {
        return explode("::", $request->get('_controller'));
    }
}
