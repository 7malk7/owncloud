<?php
namespace OCA\DeductToDB\Hooks;

use \OCA\DeductToDB\AppInfo\DeductToDB;

class FileHooks {

    private $rootFolder;

    public function __construct($rootFolder){
        $this->rootFolder = $rootFolder;
    }

    public function register() {
        $callback = function($node) {
            // your code that executes before $user is deleted
	    $app = new DeductToDB();
        $c = $app->getContainer();
        $logger = $c->getServer()->getLogger();
    	$logger->error("test callback", array('app' => '' ));
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

    public static function created($node){
    	
    	FileHooks::writeFileEntry($node['path'], 'created');
    	 
    }
    
    public static function writeFileEntry($nodePath, $mode){
    	$app = new DeductToDB();
    	$c = $app->getContainer();
    	
    	$fileName = preg_replace ( '/\.v\d+/' , '' , $nodePath );
    	$regMatch = preg_match ( '/\.v\d+/' , $nodePath );
    	
    	$connection = $c->getServer()->getDatabaseConnection();
    	$root = $c->getServer()->getUserFolder();
    	$logger = $c->getServer()->getLogger();
    	$logger->error("test 1", array('app' => $mode ));
    	
    	$finfo = \OC\Files\Filesystem::getFileInfo($fileName);    	
    	
    	if(!$finfo){
    		return;
    	}
    	
    	$fileType = $finfo->getType();
    	
    	if($fileType == "dir") {
    		
    	}else{
    	
    		$xml  = $c->query('XmlFactory')->makeXml($finfo->getId());
    	
    		$strategy = \OCA\DeductToDB\Storage\XmlFactory::makeStrategy($xml, $fileName, $app, $mode);
    	
    		$strategy->execute($regMatch);
    	}
    	
//     	$file = new File();
    	 
//     	$file->setPath($nodePath);
//     	$file->setType($mode);
    	 
    	 
//     	$root = $c->query('ServerContainer')->getRootFolder();
    	
    	
    	 
//     	$finfo = \OC\Files\Filesystem::getFileInfo($nodePath);
    	 
//     	$owner = \OC\Files\Filesystem::getOwner($nodePath);
    	 
//     	$file->setCreator($owner);
//     	$stat = \OC\Files\Filesystem::stat($nodePath);
    	 
//     	$file->setCreatedat(date('Y-m-d H:i:s', $stat['ctime']));
//     	$file->setModified(date('Y-m-d H:i:s', $stat['mtime']));    	
    	 
//     	//$mapper = new FileMapper($c->getServer()->getDb());
//     	//$mapper->insert($file);
    	
//     	$xml  = $c->query('XmlFactory')->makeXml($finfo->getId());
    	
    	
    	
//     	if($reader instanceof XmlReader){
//     	   $entity = $reader->getDbEntity();
    	
//     	   $mapper = new ProjectsMapper($c->getServer()->getDb());
//     	   $mapper->insert($entity);
    	   
//     	   $userMapper = new UserMapper($c->getServer()->getDb());
//     	   if(!$userMapper->find($owner)){
//     	   	  $newUser = new User();
//     	   	  $newUser->setName($owner);
    	   	      	   	  
//     	   	  $userMapper->insert($newUser);
//     	   }
//     	}
    }
    
    public static function updated($node){

    	
    	FileHooks::writeFileEntry($node['path'], 'update');
    }
    
    public static function fs_deleted($node){
    	

    	
    	FileHooks::writeFileEntry($node['path'], 'predelete');
    	
    	//$container->query('Scanner')->update($node, null);
    }
    
    public static function fs_deleted1($node){

    	 
    	FileHooks::writeFileEntry($node['path'], 'predelete');
    	 
    	//$container->query('Scanner')->update($node, null);
    }
    
    public static function fs_post_deleted($node){

    	
    	
    	FileHooks::writeFileEntry($node['path'], 'fs_post_deleted');
    }
    
    public static function trash_pre_deleted($node){

    	
    	FileHooks::writeFileEntry($node['path'], 'trash_pre_deleted');
    }
    
    public static function trash_deleted($node){

    	
    	FileHooks::writeFileEntry($node['path'], 'trash_deleted');
    }
    
    public static function trashbin_post_restore($node){

    	
    	FileHooks::writeFileEntry($node['filePath'], 'restore');
    }
    
    public static function post_movetotrash($node){
    	 
    	FileHooks::writeFileEntry($node['filePath'], 'post_movetotrash');
    	
    }
    
    

}