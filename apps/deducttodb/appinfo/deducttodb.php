<?php

namespace OCA\DeductToDB\AppInfo;

use \OCP\AppFramework\App;
use \OCA\DeductToDB\Hooks\FileHooks;
use OCA\DeductToDB\Storage\FileReader;
use OCA\DeductToDB\Storage\XmlFactory;
use \OC\Files\Node\File;
use \OC\Files\Node\Folder;
use \OC\Files\Filesystem;

class DeductToDB extends App {

    private $root;
    private $userfolder;
    private $logger;

    public function __construct(array $urlParams = array()) {
        parent::__construct('phphook', $urlParams);

        $container = $this->getContainer();
        $this->root = $container->query('ServerContainer')->getRootFolder();
        $this->userfolder = $container->query('ServerContainer')->getUserFolder();
        $this->logger = $container->getServer()->getLogger();


        /**
         * Controllers
         */
        $container->registerService('FileHooks', function($c) {
            return new FileHooks(
                    $c->query('ServerContainer')->getRootFolder()
            );
        });

        /**
         * Storage Layer
         */
// 		$container->registerService ( 'FileReader', function ($c) {
// 			return new FileReader( $c->query ( 'RootStorage' ) );
// 		} );

        $container->registerService('RootStorage', function ($c) {
            return $c->query('ServerContainer')->getRootFolder();
        });

        $container->registerService('XmlFactory', function ($c) {
            return new XmlFactory($c->query('RootStorage'));
        });

//         \OCP\Util::connectHook('OC_Filesystem', 'post_create', 'OCA\PhpHook\Hooks\FileHooks', 'created');
// 		\OCP\Util::connectHook('OC_Filesystem', 'post_write', 'OCA\PhpHook\Hooks\FileHooks', 'updated');
// 		\OCP\Util::connectHook('OC_Filesystem', 'post_update', 'OCA\PhpHook\Hooks\FileHooks', 'updated');
// 		\OCP\Util::connectHook('OC_Filesystem', 'delete', 'OCA\PhpHook\Hooks\FileHooks', 'fs_deleted');
// 		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', 'OCA\PhpHook\Hooks\FileHooks', 'fs_post_deleted');
// 		\OCP\Util::connectHook('OC_Filesystem', 'post_write', 'OCA\PhpHook\Hooks\FileHooks', 'fs_deleted');
// 		\OCP\Util::connectHook('\OCP\Trashbin', 'preDelete', 'OCA\PhpHook\Hooks\FileHooks', 'trash_pre_deleted');
// 		\OCP\Util::connectHook('\OCP\Trashbin', 'delete', 'OCA\PhpHook\Hooks\FileHooks', 'trash_deleted');
// 		\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', 'OCA\PhpHook\Hooks\FileHooks', 'trashbin_post_restore');
// 		\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_moveToTrash', 'OCA\PhpHook\Hooks\FileHooks', 'post_movetotrash');
// 		\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', 'OCA\PhpHook\Hooks\FileHooks', 'trashbin_post_restore');
    }

    public function initFiles($folder) {
        $listing = $folder->getDirectoryListing();

        $classFolder = 'OC\Files\Node\Folder';
        $classFile = 'OC\Files\Node\File';
        $root = Filesystem::getRoot();

        foreach ($listing as $value) {

            if ($value instanceof $classFolder) {
                $this->logger->error("init folder:", array('app' => $value->getPath()));
                $this->initFiles($value);
            }
            if ($value instanceof $classFile) {
                $fileName = $value->getPath();
                $fileName = str_replace($root, '', $fileName);
                $this->logger->error("init file:", array('app' => $fileName));
                \OCA\DeductToDB\Hooks\FileHooks::writeFileEntry($fileName, 'init');
            }
        }
    }

    public function initialization() {

        if (!$this->root) {
            return;
        }

        $rootDir = $this->root->getDirectoryListing();

        if (!$this->userfolder) {
            return;
        }

        $userDir = $this->userfolder->getDirectoryListing();

        $classFolder = 'OC\Files\Node\Folder';
        $classFile = 'OC\Files\Node\File';
        $root = Filesystem::getRoot();

        foreach ($userDir as $value) {

            if ($value instanceof $classFolder) {
                $this->logger->error("init folder:", array('app' => $value->getPath()));
                $this->initFiles($value);
            }
            if ($value instanceof $classFile) {
                $fileName = $value->getPath();
                //$regexp = '/'.$root.'/';
                //preg_replace($pattern, '', $fileName);
                $fileName = str_replace($root, '', $fileName);
                $this->logger->error("init file:", array('app' => $fileName));
                \OCA\DeductToDB\Hooks\FileHooks::writeFileEntry($fileName, 'init');
            }
        }
    }
}
