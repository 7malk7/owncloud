<?php
namespace OCA\DeductToDB\Commands;

class BaseCommand implements ICommand{
	protected $xml;
	protected $db;
	protected $fileName;
	
	public function __construct($fileName, $xml, $db){
		$this->xml = $xml;
		$this->db = $db;
		$this->fileName = $fileName;
	}
	
	function execute($app, $mode, $versionFlag){
		
	}
}

?>