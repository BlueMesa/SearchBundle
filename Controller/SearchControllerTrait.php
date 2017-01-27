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


use Bluemesa\Bundle\SearchBundle\Request\SearchHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait SearchControllerTrait
{
    /**
     * @return SearchHandler
     */
    public function getSearchHandler()
    {
        /** @var ContainerInterface $container */
        $container = $this->container;

        /** @var SearchHandler $handler */
        $handler = $container->get('bluemesa.search.handler');

        return $handler;
    }
}
