<?php

namespace OCA\DeductToDB\Storage;

class FileReader{
private $storage;

public function __construct($storage){
	$this->storage = $storage;
}

public function getContent($id) {
	// check if file exists and write to it if possible
	try {
		$file = $this->storage->getById($id);
		//if($file instanceof \OCP\Files\File) {
			return $file[0]->getContent();
		//} else {
		//	throw new StorageException('Can not read from folder');
		//}
	} catch(\OCP\Files\NotFoundException $e) {
		throw new StorageException('File does not exist');
	}
}

}