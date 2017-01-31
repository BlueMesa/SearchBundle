<?php

/*
 * This file is part of the SearchBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\SearchBundle\Search;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\CssSelector\Parser\Token;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * ACLSearchQuery class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
abstract class ACLSearchQuery extends SearchQuery implements ACLSearchQueryInterface
{
    /**
     * @Serializer\Exclude
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @Serializer\Exclude
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;


    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPermissions()
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN') ? false : array('VIEW');
    }

    /**
     * {@inheritdoc}
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage = null)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
}
