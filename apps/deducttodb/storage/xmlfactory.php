<?php

namespace OCA\DeductToDB\Storage;

use OCA\DeductToDB\Strategies\ProjectFileStrategy;
use OCA\DeductToDB\Strategies\NodeFileStrategy;
use OCA\DeductToDB\Strategies\FormFileStrategy;
use OCA\DeductToDB\Strategies\BaseStrategy;
use OCA\DeductToDB\Strategies\PhotoStrategy;
use OCA\DeductToDB\Db\NodetypeMapper;

class XmlFactory{
	
	private $storage;
	
	public function __construct($storage){
		$this->storage = $storage;
	}
	
	public function makeXml($id){
	
		$content = $this->getContent($id);
	
		$xml = simplexml_load_string($content);
	
		return $xml;
	}
	
	public static function makeStrategy($xml, $fileName, $app, $mode){

                $name = substr($fileName, strrpos($fileName, '/') + 1);
                $mimeType = \OC\Files\Filesystem::getMimeType($fileName);
                
                if(!$xml && strpos($name, "Project") !== false){
			return new ProjectFileStrategy($fileName, $app, $mode);
		}
                
                if(!$xml && $mimeType == 'image/jpeg'){
			return new PhotoStrategy($fileName, $app, $mode);
		}
                
                if(!$xml){
			return new BaseStrategy($fileName, $app, $mode);
		}
		/*if((string)$xml->getName() == 'project' && strpos($fileName, "project.xml") > 0) {
			return new ProjectFileStrategy($fileName, $app, $mode);
		}*/
		if(preg_match ( '/[A-Z0-9]{8}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{12}\_.*\.xml/' , $fileName )){
			$container = $app->getContainer();
			$db = $container->getServer()->getDb();
			$nodeType = new NodetypeMapper($db);
			$nodes = $nodeType->findAll();
			for($i = 0; $i < count($nodes); $i++){
				$regexp = '/[A-Z0-9]{8}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{12}\_' . $nodes[$i]->getName() . '_Node.*\.xml/';
				if((string)$xml->getName() == 'node' && preg_match ( $regexp , $fileName )){
					return new NodeFileStrategy($fileName, $app, $mode);
				}
				$regexp = '/[A-Z0-9]{8}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{4}\-[A-Z0-9]{12}\_' . $nodes[$i]->getName() . '_Form.*\.xml/';
				if((string)$xml->getName() == 'form' && preg_match ( $regexp , $fileName ) ){
					return new FormFileStrategy($fileName, $app, $mode);
				}
			}
		}
		return new BaseStrategy($fileName, $app, $mode);
	}
	
// 	public function makeReader($id){
		
// 		$content = $this->getContent($id);
		
// 		$xml = simplexml_load_string($content);
		
// 		if(!$xml){
// 			return array();
// 		}
		
// 		if((string)$xml->name == 'Project'){
// 		   return new XmlReader($xml);
// 		}
// 		return array();
// 	}
	
	private function getContent($id) {
		// check if file exists and read if possible
		try {
			$file = $this->storage->getById($id);
			$class = "OC\\Files\\Node\\File";
			if($file[0] instanceof $class) {
				return $file[0]->getContent();
			}else {
				//folder
			}
		} catch(\OCP\Files\NotFoundException $e) {
			throw new StorageException('File does not exist');
		}
		catch(\Exception $exc) {
			
		}
	}
	
	
}



?>