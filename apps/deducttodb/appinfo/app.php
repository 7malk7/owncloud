<?php
/**
 * ownCloud - Deducttodb
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexy Yurchanka <maly@abatgroup.de>
 * @copyright Alexy Yurchanka 2016
 */

namespace OCA\DeductToDB\AppInfo;

//use OCP\AppFramework\App;
use \OCA\DeductToDB\App;

require_once __DIR__ . '/autoload.php';

$app = new Deducttodb($_REQUEST);
$container = $app->getContainer();
$container->query('FileHooks')->register();

\OCP\App::addNavigationEntry([
	// the string under which your app will be referenced in owncloud
	'id' => 'Deducttodb',

	// sorting weight for the navigation. The higher the number, the higher
	// will it be listed in the navigation
	'order' => 10,

	// the route that will be shown on startup
	//'href' => \OCP\Util::linkToRoute('Deducttodb.page.index'),

	// the icon that will be shown in the navigation
	// this file needs to exist in img/
	'icon' => \OCP\Util::imagePath('deducttodb', 'app.svg'),

	// the title of your application. This will be used in the
	// navigation or on the settings page of your app
	'name' => \OCP\Util::getL10N('Deducttodb')->t('DeductToDB')
]);


$logger = $container->getServer()->getLogger();

if(array_key_exists('mode', $_REQUEST) && $_REQUEST['mode'] == 'init'){
	$app->initialization();

}else{

	\OCP\Util::connectHook('OC_Filesystem', 'post_create', 'OCA\DeductToDB\Hooks\FileHooks', 'created');
	\OCP\Util::connectHook('OC_Filesystem', 'post_write', 'OCA\DeductToDB\Hooks\FileHooks', 'updated');
	\OCP\Util::connectHook('OC_Filesystem', 'post_update', 'OCA\DeductToDB\Hooks\FileHooks', 'updated');

	\OCP\Util::connectHook('OC_Filesystem', 'delete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('OC_Filesystem', 'post_delete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_post_deleted');

	\OCP\Util::connectHook('OC_Filesystem', 'copy', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('OC_Filesystem', 'post_copy', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');

	\OCP\Util::connectHook('OC_Filesystem', 'post_write', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('OC_Filesystem', 'delete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('\OCP\Versions', 'preDelete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('\OCP\Trashbin', 'preDelete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('OC_Filesystem', 'post_delete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('\OCP\Versions', 'delete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('\OCP\Trashbin', 'delete', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');
	\OCP\Util::connectHook('\OCP\Versions', 'rollback', 'OCA\DeductToDB\Hooks\FileHooks', 'fs_deleted');

	\OCP\Util::connectHook('\OCP\Trashbin', 'preDelete', 'OCA\DeductToDB\Hooks\FileHooks', 'trash_pre_deleted');
	\OCP\Util::connectHook('\OCP\Trashbin', 'delete', 'OCA\DeductToDB\Hooks\FileHooks', 'trash_deleted');
	\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', 'OCA\DeductToDB\Hooks\FileHooks', 'trashbin_post_restore');

	\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_moveToTrash', 'OCA\DeductToDB\Hooks\FileHooks', 'post_movetotrash');
	\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', 'OCA\DeductToDB\Hooks\FileHooks', 'trashbin_post_restore');

}