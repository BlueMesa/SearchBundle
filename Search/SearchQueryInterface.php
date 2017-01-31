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

/**
 * SearchQuery class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
interface SearchQueryInterface
{
    /**
     * Get search terms
     * 
     * @return array
     */
    public function getTerms();

    /**
     * Set search terms
     * 
     * @param mixed $terms
     */
    public function setTerms($terms);
    
    /**
     * Get excluded terms
     * 
     * @return array
     */
    public function getExcluded();

    /**
     * Set excluded terms
     * 
     * @param mixed $excluded
     */
    public function setExcluded($excluded);
    
    /**
     * Is search advanced
     * 
     * @return boolean
     */
    public function isAdvanced();

    /**
     * Set advanced
     * 
     * @param boolean $advanced
     */
    public function setAdvanced($advanced);
    
    /**
     * Get options
     * 
     * @return array
     */
    public function getOptions();
    
    /**
     * Get entity class
     * 
     * @return string
     */
    public function getEntityClass();
}
