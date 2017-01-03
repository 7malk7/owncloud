<?php
namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\DeductFileMapper;
use OCA\DeductToDB\Db\DeductFile;

class FilesCommand extends BaseCommand{
	
	public function __construct($fileName, $xml, $db){
		parent::__construct($fileName, $xml, $db);
	}
	
	function execute($app, $mode, $versionFlag){
		$file = new DeductFile();
		
		$file->setPath($this->fileName);
		$file->setType($mode);
			
		$root = $app->getContainer()->query('ServerContainer')->getRootFolder();
		 
		$finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);
		
		$owner = \OC\Files\Filesystem::getOwner($this->fileName);
		
		$file->setCreator($owner);
		$stat = \OC\Files\Filesystem::stat($this->fileName);
		
		$file->setCreatedat(date('Y-m-d H:i:s', $stat['ctime']));
		$file->setModified(date('Y-m-d H:i:s', $stat['mtime']));
		
		$mapper = new DeductFileMapper($app->getContainer()->getServer()->getDb());
		$mapper->insert($file);
	}

}

?>