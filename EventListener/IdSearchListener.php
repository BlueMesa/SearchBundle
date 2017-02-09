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

use Bluemesa\Bundle\SearchBundle\Event\ResultActionEvent;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\View\View;
use JMS\DiExtraBundle\Annotation as DI;


/**
 * The IdSearchListener handles ID searches.
 *
 * @DI\Service("bluemesa.search.listener.id")
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.search_result_query",
 *         "method" = "onSearchResultQuery",
 *         "priority" = 1000
 *     }
 * )
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class IdSearchListener
{
    /**
     * @param ResultActionEvent $event
     */
    public function onSearchResultQuery(ResultActionEvent $event)
    {
        $request = $event->getRequest();
        if (! $route = $request->get('search_unique_redirect')) {
            return;
        }

        $query = $event->getQuery();
        $repository = $event->getRepository();
        $terms = array_values($query->getTerms());

        if ((count($terms) == 1)&&(is_numeric($terms[0]))&&($repository instanceof EntityRepository)) {
            $entity = $repository->find($terms[0]);
            if (null !== $entity) {
                $event->setView(View::createRouteRedirect($route, array('id' => $entity->getId())));
            }
        }
    }
}
