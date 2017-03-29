<?php

/**
 * ownCloud - hookextract
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexy Yurchanka <maly@abat.de>
 * @copyright Alexy Yurchanka 2016
 */
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\HookExtract\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
            ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
            ['name' => 'page#preselect', 'url' => '/preselect', 'verb' => 'POST'],
            ['name' => 'page#select', 'url' => '/select', 'verb' => 'POST'],
            ['name' => 'page#xlsdwnl', 'url' => '/xls', 'verb' => 'POST'],
            ['name' => 'page#do_echo', 'url' => '/echo', 'verb' => 'POST'],
    	    ['name' => 'page#timers', 'url' => '/timers', 'verb' => 'POST'],
            ['name' => 'page#upload', 'url' => '/upload', 'verb' => 'POST'],
            ['name' => 'page#jobsdownload', 'url' => '/jobsdownload', 'verb' => 'POST'],
            ['name' => 'page#maintenance', 'url' => '/maintenance', 'verb' => 'POST'],
    ]
];
