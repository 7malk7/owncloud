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

namespace OCA\HookExtract\AppInfo;

use OCP\AppFramework\App;

require_once __DIR__ . '/autoload.php';

$app = new Hookextract($_REQUEST);
$container = $app->getContainer();

\OCP\Backgroundjob::addRegularTask('\OCA\HookExtract\Cron\ExtractTask', 'run');
//\OCP\Backgroundjob::addRegularTask('\OCA\HookExtract\Cron\MaintenanceTask', 'run');

// $rootFolder = $container->getRootFolder();
// $userFolder = $container->getUserFolder();
// $appFolder = $container->getAppFolder();

\OCP\App::addNavigationEntry([
		// the string under which your app will be referenced in owncloud
		'id' => 'hookextract',

		// sorting weight for the navigation. The higher the number, the higher
		// will it be listed in the navigation
		'order' => 10,

		// the route that will be shown on startup
		'href' => \OCP\Util::linkToRoute('hookextract.page.index'),

		// the icon that will be shown in the navigation
		// this file needs to exist in img/
		'icon' => \OCP\Util::imagePath('hookextract', 'app.svg'),

		// the title of your application. This will be used in the
		// navigation or on the settings page of your app
		'name' => \OCP\Util::getL10N('hookextract')->t('Hook Extract')
]);

// $container->query('OCP\INavigationManager')->add(function () use ($container) {
// 	$urlGenerator = $container->query('OCP\IURLGenerator');
// 	$l10n = $container->query('OCP\IL10N');
// 	return [
// 		// the string under which your app will be referenced in owncloud
// 		'id' => 'hookextract',

// 		// sorting weight for the navigation. The higher the number, the higher
// 		// will it be listed in the navigation
// 		'order' => 10,

// 		// the route that will be shown on startup
// 		'href' => $urlGenerator->linkToRoute('hookextract.page.index'),

// 		// the icon that will be shown in the navigation
// 		// this file needs to exist in img/
// 		'icon' => $urlGenerator->imagePath('hookextract', 'app.svg'),

// 		// the title of your application. This will be used in the
// 		// navigation or on the settings page of your app
// 		'name' => $l10n->t('Hook Extract'),
// 	];
// });