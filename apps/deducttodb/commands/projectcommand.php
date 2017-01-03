<?php
namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\ProjectsMapper;
use OCA\DeductToDB\Db\Projects;

class ProjectCommand extends BaseCommand{

	public function __construct($fileName, $xml, $db){
		parent::__construct($fileName, $xml, $db);
	}

	function execute($app, $mode, $versionFlag){
		
		$project = new Projects();
		
		$root = $app->getContainer()->query('ServerContainer')->getRootFolder();
		 
		$finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);
		
		$owner = \OC\Files\Filesystem::getOwner($this->fileName);
		
		$stat = \OC\Files\Filesystem::stat($this->fileName);
		
		//$xml  = $app->getContainer()->query('XmlFactory')->makeXml($finfo->getId());

		$project->setName((string)$this->xml->name);
		$project->setCreatedby((string)$this->xml->created_by);
		$project->setCreatedat((string)$this->xml->created_at);
		$project->setOwner((string)$this->xml->owner);
		$project->setUuid((string)$this->xml->uuid);
		$project->setVersion((string)$this->xml->attributes()->version);
		$folderName = split("/", $this->fileName);
		if(count($folderName) >= 2){
			$project->setFoldername($folderName[1]);
		}
		 
		$mapper = new ProjectsMapper($this->db);
		
		if(!$mapper->findByUuid((string)$this->xml->uuid)){
			$mapper->insert($project);
		}

	}

}

?>