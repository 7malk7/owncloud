<?php
namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\ObservationNodeMapper;
use OCA\DeductToDB\Db\ObservationNode;

class ObservationNodeCommand extends BaseCommand{

	public function __construct($fileName, $xml, $db){
		parent::__construct($fileName, $xml, $db);
	}

	function execute($app, $mode, $versionFlag){

		$node = new ObservationNode();

		$root = $app->getContainer()->query('ServerContainer')->getRootFolder();
			
		$finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);

		$owner = \OC\Files\Filesystem::getOwner($this->fileName);

		$stat = \OC\Files\Filesystem::stat($this->fileName);

		//$xml  = $app->getContainer()->query('XmlFactory')->makeXml($finfo->getId());
		$mainTag = (string)$this->xml->name;
		$localFileName = substr(strrchr($this->fileName, "/"), 1);
		
		if($mainTag == 'node'){
			$node->setTitle(trim((string)$this->xml->title));
			$node->setUuid((string)$this->xml->uuid);
			
			$node->setCreatedby((string)$this->xml->created_by);
			$node->setCreatedat((string)$this->xml->created_at);
			
			$localType = (string)$this->xml->type;
			$node->setType("1");
		}
        
		
		
		
		//$node->setTitle((string)$this->xml->attributes()->version);
			
		$mapper = new ObservationNodeMapper($this->db);
		$mapper->insert($entity);

	}

}

?>