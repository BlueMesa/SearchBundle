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
use Bluemesa\Bundle\SearchBundle\Search\ACLSearchQueryInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/**
 * The CrudFilterListener handles Filter annotation for controllers.
 *
 * @DI\Service("bluemesa.search.listener.query")
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.search_result_query",
 *         "method" = "onSearchResultQuery",
 *         "priority" = 900
 *     }
 * )
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class SearchQueryListener
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;


    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "authorizationChecker" = @DI\Inject("security.authorization_checker"),
     *     "tokenStorage" = @DI\Inject("security.token_storage")
     * })
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @throws \Exception
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker,
                                TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param ResultActionEvent $event
     */
    public function onSearchResultQuery(ResultActionEvent $event)
    {
        $query = $event->getQuery();

        if ($query instanceof ACLSearchQueryInterface) {
            $query->setAuthorizationChecker($this->authorizationChecker);
            $query->setTokenStorage($this->tokenStorage);
        }
    }
}
