<?php

/*
 * This file is part of the SearchBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\SearchBundle\Event;


final class SearchControllerEvents
{
    /**
     * This event fires before the index action is performed. It allows modification of the request before any other
     * operations are performed.
     *
     * @Event
     */
    const RESULT_INITIALIZE = 'bluemesa.controller.search_result_initialize';

    /**
     * @Event
     */
    const RESULT_QUERY = 'bluemesa.controller.search_result_query';

    /**
     * @Event
     */
    const RESULT_FETCHED = 'bluemesa.controller.search_result_fetched';

    /**
     * @Event
     */
    const RESULT_COMPLETED = 'bluemesa.controller.search_result_completed';
}
