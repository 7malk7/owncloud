<?php

namespace OCA\DeductToDB\Storage;

use OCA\DeductToDB\Db\Projects;

class XmlReader{

	private $xml;	

	public function __construct($xml){
		$this->xml = $xml;
	}
	
// 	<project version="2">
// 	<name>Training</name>
// 	<created_by>hammelburg1</created_by>
// 	<created_at>2016-09-20T15:34:22Z</created_at>
// 	<owner>hammelburg1</owner>
// 	<uuid>59C3FBF0-CACE-49CE-9CA3-3B27C293CE28</uuid>
// 	<infocube/>
// 	</project>
	
	
	public function getDbEntity(){
		
		$project = new Projects();

		$project->setName((string)$this->xml->name);
  	    $project->setCreatedby((string)$this->xml->created_by);
	    $project->setCreatedat((string)$this->xml->created_at);
	    $project->setOwner((string)$this->xml->owner);
	    $project->setUuid((string)$this->xml->uuid);
	    $project->setVersion((string)$this->xml->attributes()->version);
	    
	    return $project;
		
	}


}



?>