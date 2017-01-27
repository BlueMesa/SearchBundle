<?php

/*
 * This file is part of the SearchBundle.
 *
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\SearchBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Bluemesa\Bundle\CoreBundle\Controller\AbstractController;
use Bluemesa\Bundle\SearchBundle\Repository\SearchableRepositoryInterface;

use Bluemesa\Bundle\SearchBundle\Search\SearchQuery;
use Bluemesa\Bundle\SearchBundle\Search\SearchQueryInterface;

use Bluemesa\Bundle\SearchBundle\Form\SearchType;
use Bluemesa\Bundle\SearchBundle\Form\AdvancedSearchType;
use Symfony\Component\HttpFoundation\Request;

/**
 * SearchController
 *
 * @Route("/search")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
abstract class SearchController extends AbstractController
{
    /**
     * Render advanced search form
     *
     * @Template()
     * @Route("/") 
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function advancedAction()
    {
        $form = $this->createForm($this->getAdvancedSearchForm());
        return array(
            'form' => $form->createView(),
            'realm' => $this->getSearchRealm()
        );
    }

    /**
     * Render quick search form
     *
     * @Route("/simple/")
     * @Template()
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction()
    {
        $form = $this->createForm($this->getSearchForm());
        return array(
            'form' => $form->createView(),
            'realm' => $this->getSearchRealm()
        );
    }

    /**
     * Handle search result
     *
     * @Route("/result/")
     * @Template()
     *
     * @param  Request $request
     * @return array
     */
    public function resultAction(Request $request)
    {
        $form = $this->createForm($this->getSearchForm(), $this->createSearchQuery());
        $advancedForm = $this->createForm($this->getAdvancedSearchForm(), $this->createSearchQuery(true));

        if ($request->getMethod() == 'POST') {
            
            $form->handleRequest($request);
            $advancedForm->handleRequest($request);
            
            if ($form->isValid()) {
                $searchQuery = $form->getData();
            } elseif ($advancedForm->isValid()) {
                $searchQuery = $advancedForm->getData();
            } else {
                throw $this->createNotFoundException(); 
            }
            
            $this->saveSearchQuery($searchQuery);
            
        } else {
            $searchQuery = $this->loadSearchQuery();
        }

        $repository = $this->getObjectManager()->getRepository($searchQuery->getEntityClass());
        
        if ($repository instanceof SearchableRepositoryInterface) {
            return $this->handleSearchableRepository($request, $repository, $searchQuery);
        } else {
            return $this->handleNonSearchableRepository($request, $repository, $searchQuery);
        }
    }
    
    /**
     * Load search query from session
     * 
     * @return SearchQuery
     */
    protected function loadSearchQuery()
    {
        $serializer = $this->get('serializer');
        $serializedQuery = $this->getSession()->get(
                $this->getSearchRealm() . '_search_query', 
                $this->createSearchQuery()
        );
        
        return $serializer->deserialize($serializedQuery['object'], $serializedQuery['class'], 'json');
    }
    
    /**
     * Save search query in session
     * 
     * @param type $searchQuery
     */
    protected function saveSearchQuery($searchQuery)
    {
        $serializer = $this->get('serializer');
        $serializedQuery = array(
            'object' => $serializer->serialize($searchQuery, 'json'),
            'class' => get_class($searchQuery)
        );
        $this->getSession()->set($this->getSearchRealm() . '_search_query', $serializedQuery);
    }
    
    /**
     * Handle non-searchable repository classes
     * 
     * @param mixed $repository
     * @param mixed $searchQuery
     * @return mixed
     */
    protected function handleNonSearchableRepository(Request $request, $repository, $searchQuery)
    {
        throw $this->createNotFoundException();
    }
    
    /**
     * Handle searchable repository classes
     *
     * @param Request $request
     * @param mixed   $repository
     * @param mixed   $searchQuery
     * @return mixed
     */    
    protected function handleSearchableRepository(Request $request, $repository, $searchQuery)
    {        
        $terms = $searchQuery->getTerms();
        $excluded = $searchQuery->getExcluded();
        $options = $searchQuery->getOptions();
        
        if (count($terms) == 0) {
            $this->createNotFoundException();
        }
        
        $resultCount = $repository->getSearchResultCount($searchQuery);
        $result = $repository->getSearchQuery($searchQuery)
                             ->setHint('knp_paginator.count', $resultCount);
        $paginator  = $this->getPaginator();
        $page = $this->getCurrentPage($request);
        $entities = $paginator->paginate($result, $page, 25);

        return array('entities' => $entities,
                     'query' => implode(' ', $terms),
                     'exclude' => implode(' ', $excluded),
                     'options' => $options);
    }
    
    /**
     * Get search Query
     * 
     * @param boolean $advanced
     * @return SearchQueryInterface
     */
    abstract protected function createSearchQuery($advanced = false);
    
    /**
     * Get search form
     * 
     * @return SearchType
     */
    protected function getSearchForm()
    {
        return SearchType::class;
    }
    
    /**
     * Get advanced search form
     * 
     * @return AdvancedSearchType
     */
    protected function getAdvancedSearchForm()
    {
        return AdvancedSearchType::class;
    }
    
    /**
     * Get search realm
     * 
     * @return string
     */
    abstract protected function getSearchRealm();
}
