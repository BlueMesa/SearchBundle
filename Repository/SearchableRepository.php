<?php

/*
 * This file is part of the SearchBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\SearchBundle\Repository;

use Bluemesa\Bundle\AclBundle\Repository\EntityRepository;
use Bluemesa\Bundle\SearchBundle\Search\SearchQueryInterface;
use Bluemesa\Bundle\SearchBundle\Search\ACLSearchQueryInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

/**
 * SearchableRepository
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
abstract class SearchableRepository extends EntityRepository implements SearchableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSearchQuery(SearchQueryInterface $search)
    {
        $qb = $this->getSearchQueryBuilder($search);
        $permissions = $search instanceof ACLSearchQueryInterface ? $search->getPermissions() : array();
        $user = $search instanceof ACLSearchQueryInterface ? $search->getUser() : null;
        
        return (false === $permissions) ? $qb->getQuery() : $this->getAclFilter()->apply($qb, $permissions, $user);
    }
        
    /**
     * Get search QueryBuilder
     * 
     * @param  SearchQueryInterface $search
     * @return QueryBuilder
     */
    protected function getSearchQueryBuilder(SearchQueryInterface $search)
    {
        $qb = $this->createQueryBuilder('e');
        $expr = $this->getSearchExpression($search);
        return (null !== $expr) ? $qb->add('where', $expr) : $qb;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSearchResultCount(SearchQueryInterface $search)
    {
        return $this->getSearchResultCountQuery($search)
                ->getSingleScalarResult();
    }
    
    /**
     * Get search result count Query
     * 
     * @param  SearchQueryInterface $search
     * @return Query
     */
    protected function getSearchResultCountQuery(SearchQueryInterface $search)
    {
        $qb = $this->getSearchResultCountQueryBuilder($search);
        $permissions = $search instanceof ACLSearchQueryInterface ? $search->getPermissions() : array();
        $user = $search instanceof ACLSearchQueryInterface ? $search->getUser() : null;
        
        return (false === $permissions) ? $qb->getQuery() : $this->getAclFilter()->apply($qb, $permissions, $user);
    }
    
    /**
     * Get search result count QueryBuilder
     * 
     * @param  SearchQueryInterface $search
     * @return QueryBuilder
     */
    protected function getSearchResultCountQueryBuilder(SearchQueryInterface $search)
    {
        $qb = $this->createQueryBuilder('e')->select('count(e.id)');
        $expr = $this->getSearchExpression($search);
        return (null !== $expr) ? $qb->add('where', $expr) : $qb;
    }
    
    /**
     * Create DQL expression from search terms
     * 
     * @param  SearchQueryInterface $search
     * @return Expr\Base
     */
    protected function getSearchExpression(SearchQueryInterface $search)
    {        
        if ((count($search->getTerms()) + count($search->getExcluded())) < 1) {
            return null;
        }

        $eb = $this->getEntityManager()->getExpressionBuilder();
        
        $expr = $eb->andX();
        foreach ($search->getTerms() as $term) {
            $subexpr = $eb->orX();
            foreach ($this->getSearchFields($search) as $field) {
                $subexpr->add($eb->like($field, '\'%' . $term . '%\''));
            }
            $expr->add($subexpr);
        }
        foreach ($search->getExcluded() as $term) {
            $subexpr = $eb->andX();
            foreach ($this->getSearchFields($search) as $field) {
                $subexpr->add($eb->not($eb->like($field, '\'%' . $term . '%\'')));
            }
            $expr->add($subexpr);
        }
        
        return $expr;
    }
    
    /**
     * Get fields to search
     * 
     * @param  SearchQueryInterface $search
     * @return array
     */
    protected function getSearchFields(SearchQueryInterface $search)
    {
        $fields = array('e.id');
        
        return $fields;
    }
}
