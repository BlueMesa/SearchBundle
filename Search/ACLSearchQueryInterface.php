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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * SearchQuery class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
interface ACLSearchQueryInterface
{
    /**
     * Set the Token storage
     * 
     * @param TokenStorageInterface  $tokenStorage
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage = null);

    /**
     * Set the Authorization checker
     *
     * @param AuthorizationCheckerInterface  $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker = null);

    /**
     * Get a user from the Security Context
     *
     * @return mixed
     */
    public function getUser();
    
    /**
     * Get search permission mask
     *
     * @return mixed
     */
    public function getPermissions();
}
