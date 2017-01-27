<?php

/*
 * This file is part of the CRUD Bundle.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\SearchBundle\Controller\Annotations;


/**
 * Action Annotation
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class Search
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $form_type;

    /**
     * @var string
     */
    public $realm;


    /**
     * Action Annotation constructor.
     */
    public function __construct()
    {
        $this->name = null;
        $this->type = null;
        $this->realm = null;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->form_type;
    }

    /**
     * @return string
     */
    public function getRealm()
    {
        return $this->realm;
    }

    public static function merge(Search $a = null, Search $b = null)
    {
        $c = new static();

        if ($a === null) {
            if ($b === null) {
                return $c;
            } else {
                return $b;
            }
        }

        if ($b === null) {
            return $a;
        }

        $c->name = $b->name !== null ? $b->name : $a->name;
        $c->form_type = $b->form_type !== null ? $b->form_type : $a->form_type;
        $c->realm = $b->realm !== null ? $b->realm : $a->realm;

        return $c;
    }
}
