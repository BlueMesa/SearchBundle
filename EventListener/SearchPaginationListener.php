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

use Bluemesa\Bundle\CoreBundle\EventListener\PaginationListener;
use Bluemesa\Bundle\SearchBundle\Event\ResultActionEvent;
use Bluemesa\Bundle\SearchBundle\Repository\SearchableRepositoryInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\Event;


/**
 * The SearchPaginationListener handles Pagination annotation for controllers.
 *
 * @DI\Service("bluemesa.search.listener.pagination")
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.search_result_query",
 *         "method" = "onPaginate",
 *         "priority" = 100
 *     }
 * )
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class SearchPaginationListener extends PaginationListener
{
    /**
     * @param  Event  $event
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function getPaginationTarget(Event $event)
    {
        if (! $event instanceof ResultActionEvent) {
            throw new \InvalidArgumentException("The event " . get_class($event) .
                " must be an instance of ResultActionEvent.");
        }

        $searchQuery = $event->getQuery();
        $repository = $event->getRepository();

        if ($repository instanceof SearchableRepositoryInterface) {
            $count = $repository->getSearchResultCount($searchQuery);
            $query = $repository->getSearchQuery($searchQuery)->setHint('knp_paginator.count', $count);
        } else {
            throw new \InvalidArgumentException("Repository " . get_class($repository) .
                " must implement SearchableRepositoryInterface.");
        }

        return $query;
    }
}
