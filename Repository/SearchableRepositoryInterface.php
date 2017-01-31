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

use Bluemesa\Bundle\SearchBundle\Search\SearchQueryInterface;
use Doctrine\ORM\Query;

/**
 * SearchableVialRepository
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
interface SearchableRepositoryInterface
{
    /**
     * Get search Query
     * 
     * @param  SearchQueryInterface $search
     * @return Query
     */
    public function getSearchQuery(SearchQueryInterface $search);
        
    /**
     * Get search result count
     * 
     * @param  SearchQueryInterface $search
     * @return integer
     */
    public function getSearchResultCount(SearchQueryInterface $search);
}
