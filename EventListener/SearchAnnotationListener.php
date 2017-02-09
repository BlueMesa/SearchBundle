<?php

/*
 * This file is part of the SearchBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\SearchBundle\EventListener;

use Bluemesa\Bundle\SearchBundle\Controller\Annotations\Search;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * The CrudAnnotationListener handles CRUD annotations for controllers.
 *
 * @DI\Service("bluemesa.search.listener.annotation")
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "kernel.controller",
 *         "method" = "onKernelController",
 *         "priority" = 10
 *     }
 * )
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class SearchAnnotationListener
{
    /**
     * @var ParamConverterManager
     */
    protected $manager;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "manager" = @DI\Inject("sensio_framework_extra.converter.manager"),
     *     "reader" = @DI\Inject("annotation_reader"),
     *     "router" = @DI\Inject("router")
     * })
     *
     * @param ParamConverterManager $manager  A ParamConverterManager instance
     * @param Reader                $reader   A Reader instance
     * @param RouterInterface       $router   A RouterInterface instance
     */
    public function __construct(ParamConverterManager $manager, Reader $reader, RouterInterface $router)
    {
        $this->manager = $manager;
        $this->reader = $reader;
        $this->router = $router;
    }

    /**
     * Modifies the ParamConverterManager instance.
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $request = $event->getRequest();

        if (is_array($controller)) {
            $c = new \ReflectionClass(ClassUtils::getClass($controller[0]));
            $m = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && is_callable($controller, '__invoke')) {
            /** @var object $controller */
            $c = new \ReflectionClass(ClassUtils::getClass($controller));
            $m = new \ReflectionMethod($controller, '__invoke');
        } else {
            return;
        }

        /** @var Search $controllerAnnotation */
        $controllerAnnotation = $this->reader->getClassAnnotation($c, Search::class);
        /** @var Search $actionAnnotation */
        $actionAnnotation = $this->reader->getMethodAnnotation($m, Search::class);
        if (! $controllerAnnotation && ! $actionAnnotation) {
            return;
        }

        $annotation = Search::merge($controllerAnnotation, $actionAnnotation);

        $action = $this->getActionName($annotation, $m);
        $type = $this->getFormType($annotation, $c, $m);
        $realm = $this->getSearchRealm($annotation);
        $route = $this->getRedirectRoute($annotation);
        $simple = ! $event->isMasterRequest();

        $this->addRequestAttribute($request, 'search_action', $action);
        $this->addRequestAttribute($request, 'search_type', $type);
        $this->addRequestAttribute($request, 'search_realm', $realm);
        $this->addRequestAttribute($request, 'search_simple', $simple);
        if (null !== $route) {
            $this->addRequestAttribute($request, 'search_unique_redirect', $route);
        }
    }

    /**
     * @param Search $annotation
     * @param \ReflectionMethod $m
     *
     * @return string
     * @throws \LogicException
     */
    private function getActionName(Search $annotation, \ReflectionMethod $m)
    {
        $action = $annotation->getAction();
        if (null === $action) {
            $method = $m->getName();
            $action = str_replace("Action", "", $method);
        }
        if (! in_array($action, array('search', 'advanced', 'result'))) {
            $message  = "The action '" . $action;
            $message .= "' is not one of the allowed CRUD actions ('search', 'advanced', 'result').";
            throw new \LogicException($message);
        }

        return $action;
    }

    /**
     * @param Search             $annotation
     * @param \ReflectionClass   $c
     * @param \ReflectionMethod  $m
     *
     * @return string
     * @throws \LogicException
     */
    private function getFormType(Search $annotation, \ReflectionClass $c, \ReflectionMethod $m)
    {
        $type = $annotation->getFormType();
        if (null === $type) {
            $method = $m->getName();
            $namespace = $c->getNamespaceName() . "\\";
            $type = str_replace("\\Controller\\", "\\Form\\", $namespace) .
                ucfirst(str_replace("Action", "Type", $method));
        }
        if (! class_exists($type)) {
            $namespace = $c->getNamespaceName() . "\\";
            $controller = $c->getShortName();
            $type = str_replace("\\Controller\\", "\\Form\\", $namespace) .
                str_replace("Controller", "Type", $controller);
        }
        if (! class_exists($type)) {
            $message  = "Cannot find form ";
            $message .= $type;
            $message .= ". Please specify the form FQCN using form_type parameter.";
            throw new \LogicException($message);
        }

        return $type;
    }

    /**
     * @param Search $annotation
     *
     * @return string
     * @throws \LogicException
     */
    private function getSearchRealm(Search $annotation)
    {
        $realm = $annotation->getRealm();
        if (null === $realm) {
            $message  = "The search realm has to be specified.";
            throw new \LogicException($message);
        }

        return $realm;
    }

    /**
     * @param Search $annotation
     *
     * @return string
     * @throws RouteNotFoundException
     */
    private function getRedirectRoute(Search $annotation)
    {
        $route = $annotation->getUniqueResultRoute();
        if (null !== $route) {
            try {
                $this->router->generate($route);
            } catch (\Exception $e) {
                if ($e instanceof RouteNotFoundException) {
                    throw $e;
                }
            }
        }

        return $route;
    }

    /**
     * @param Request $request
     * @param string  $attribute
     * @param string  $value
     */
    private function addRequestAttribute(Request $request, $attribute, $value)
    {
        if (! $request->attributes->has($attribute)) {
            $request->attributes->set($attribute, $value);
        }
    }
}
