<?php

namespace OCA\DeductToDB\Hooks;

use \OCA\DeductToDB\AppInfo\DeductToDB;
use \OCA\DeductToDB\Commands\ObservationFormCommand;
use OCA\DeductToDB\Commands\PhotoCommand;
use OCA\DeductToDB\Commands\ProjectCommand;
use OCA\DeductToDB\Commands\ObservationNodeCommand;
use OCA\DeductToDB\Db\NodetypeMapper;

class FileHooks {

    private $rootFolder;

    public function __construct($rootFolder) {
        $this->rootFolder = $rootFolder;
    }

    public function register() {
        $callback = function($node) {
            // your code that executes before $user is deleted
            $app = new DeductToDB();
            $c = $app->getContainer();
            $logger = $c->getServer()->getLogger();
            $logger->error("test callback", array('app' => ''));
// 	    $storage = $c->getRootFolder();
// 	    try {
//             	try {
//                 	$file = $storage->get('/myfile.txt');
//             	} catch(\OCP\Files\NotFoundException $e) {
//                 	$storage->touch('/myfile.txt');
//                 	$file = $storage->get('/myfile.txt');
//             }
//             // the id can be accessed by $file->getId();
//             $file->putContent('Test');
//         	} catch(\OCP\Files\NotPermittedException $e) {
//             		// you have to create this exception by yourself ;)
//             		throw new StorageException('Cant write to file');
//         	}            
        };
        $this->rootFolder->listen('\OC\Files', 'postCreate', $callback);
    }

    public static function created($node) {

        FileHooks::writeFileEntry($node['path'], 'created');
    }

    public static function writeFileEntry($nodePath, $mode) {
        $app = new DeductToDB();
        $c = $app->getContainer();

        $fileName = preg_replace('/\.v\d+/', '', $nodePath);
        $regMatch = preg_match('/\.v\d+/', $nodePath);

        $connection = $c->getServer()->getDatabaseConnection();
        $db = $c->getServer()->getDb();
        $root = $c->getServer()->getUserFolder();
        $logger = $c->getServer()->getLogger();
        $logger->error("maintenance function", array('app' => $mode));

        $finfo = \OC\Files\Filesystem::getFileInfo($fileName);

        if (!$finfo || $mode == "predelete") { // for deleted files
            $name = substr($fileName, strrpos($fileName, '/') + 1);
            $type = substr($name, strrpos($name, '.') + 1);

            if ($type == 'jpeg') {
                $photoCommand = new PhotoCommand($fileName, "", $db);
                $photoCommand->executeForDeleted();
                return;
            }

            if (strpos($fileName, "project.xml") > 0) {
                $projectCommand = new ProjectCommand($fileName, "", $db);
                $projectCommand->executeForDeleted();
                return;
            }

            if (preg_match('/[A-Z0-9]{8}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{12}\_.*\.xml/', $fileName)) {
                $nodeType = new NodetypeMapper($db);
                $nodes = $nodeType->findAll();
                for ($i = 0; $i < count($nodes); $i++) {
                    $regexp = '/[A-Z0-9]{8}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{12}\_' . $nodes[$i]->getName() . '_Node.*\.xml/';
                    if (strpos($fileName, "Node") > 0 && preg_match($regexp, $fileName)) {
                        $nodeCommand = new ObservationNodeCommand($fileName, "", $db);
                        $nodeCommand->executeForDeleted();
                        return;
                    }
                    $regexp = '/[A-Z0-9]{8}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{12}\_' . $nodes[$i]->getName() . '_Form.*\.xml/';
                    if (strpos($fileName, "Form") > 0 && preg_match($regexp, $fileName)) {
                        $formCommand = new ObservationFormCommand($fileName, "", $db);
                        $formCommand->executeForDeleted();
                        return;
                    }
                }
            }
            return;
        }

        $fileType = $finfo->getType();

        $xml = $c->query('XmlFactory')->makeXml($finfo->getId());

        $strategy = \OCA\DeductToDB\Storage\XmlFactory::makeStrategy($xml, $fileName, $app, $mode);
        $strategy->execute($regMatch);
    }

    public static function updated($node) {


        FileHooks::writeFileEntry($node['path'], 'update');
    }

    public static function fs_deleted($node) {



        FileHooks::writeFileEntry($node['path'], 'predelete');

        //$container->query('Scanner')->update($node, null);
    }

    public static function fs_deleted1($node) {


        FileHooks::writeFileEntry($node['path'], 'predelete');

        //$container->query('Scanner')->update($node, null);
    }

    public static function fs_post_deleted($node) {



        FileHooks::writeFileEntry($node['path'], 'fs_post_deleted');
    }

    public static function trash_pre_deleted($node) {


        FileHooks::writeFileEntry($node['path'], 'trash_pre_deleted');
    }

    public static function trash_deleted($node) {


        FileHooks::writeFileEntry($node['path'], 'trash_deleted');
    }

    public static function trashbin_post_restore($node) {


        FileHooks::writeFileEntry($node['filePath'], 'restore');
    }

    public static function post_movetotrash($node) {

        FileHooks::writeFileEntry($node['filePath'], 'post_movetotrash');
    }

}
