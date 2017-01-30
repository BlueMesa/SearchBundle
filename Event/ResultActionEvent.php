<?php

/*
 * This file is part of the CRUD Bundle.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\SearchBundle\Event;


use Bluemesa\Bundle\CoreBundle\Event\EntityEventInterface;
use Bluemesa\Bundle\SearchBundle\Search\SearchQueryInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ResultActionEvent extends SearchEvent implements EntityEventInterface
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var SearchQueryInterface
     */
    private $query;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var mixed
     */
    private $entities;


    /**
     * IndexActionEvent constructor.
     *
     * @param Request                    $request
     * @param FormInterface|null         $form
     * @param SearchQueryInterface|null  $query
     * @param ObjectRepository|null      $repository
     * @param array|null                 $entities
     * @param View                       $view
     */
    public function __construct(Request $request, FormInterface $form = null, SearchQueryInterface $query = null,
                                ObjectRepository $repository = null, $entities = null, View $view = null)
    {
        $this->request = $request;
        $this->form = $form;
        $this->query = $query;
        $this->repository = $repository;
        $this->entities = $entities;
        $this->view = $view;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * @return SearchQueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param SearchQueryInterface $query
     */
    public function setQuery(SearchQueryInterface $query = null)
    {
        $this->query = $query;
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param EntityRepository $repository
     */
    public function setRepository(EntityRepository $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc)
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * {@inheritdoc)
     */
    public function setEntities($entities)
    {
        $this->entities = $entities;
    }
}
