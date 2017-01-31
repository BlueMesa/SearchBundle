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

/**
 * SearchQuery class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
abstract class SearchQuery implements SearchQueryInterface
{
    /**
     * Search terms
     * 
     * @Serializer\Type("array")
     * 
     * @var array
     */
    protected $terms;
    
    /**
     * Excluded terms
     * 
     * @Serializer\Type("array")
     * 
     * @var array
     */
    protected $excluded;
    
    /**
     * Is search advanced
     * 
     * @Serializer\Type("boolean")
     * 
     * @var boolean
     */
    protected $advanced;


    /**
     * Construct SearchQuery
     *
     * @param boolean $advanced
     */
    public function __construct($advanced = false) {
        $this->terms = array();
        $this->excluded = array();
        $this->advanced = $advanced;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTerms() {
        return $this->terms;
    }

    /**
     * {@inheritdoc}
     */
    public function setTerms($terms) {
        if (is_array($terms)) {
            $this->terms = $terms;
        } elseif (trim($terms) != '') {
            $this->terms = explode(' ', $terms);
        } else {
            $this->terms = array();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExcluded() {
        return $this->excluded;
    }

    /**
     * {@inheritdoc}
     */
    public function setExcluded($excluded) {
        if (is_array($excluded)) {
            $this->excluded = $excluded;
        } elseif (trim($excluded) != '') {
            $this->excluded = explode(' ', $excluded);
        } else {
            $this->excluded = array();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAdvanced() {
        return $this->advanced;
    }

    /**
     * {@inheritdoc}
     */
    public function setAdvanced($advanced) {
        $this->advanced = $advanced;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions() {
        return array();
    }
}
