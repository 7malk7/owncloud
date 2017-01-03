<?php
namespace OCA\DeductToDB\Strategies;

use OCA\DeductToDB\Commands\FilesCommand;

class BaseStrategy implements IStrategy{
	protected $xml;
	protected $db;
	protected $mode;
	protected $fileName;
	protected $commands = array();
	
	private $app;
	private $container;

	public function __construct($fileName, $app, $mode){
		$this->app = $app;
		
		$this->container = $app->getContainer();
		$this->db = $this->container->getServer()->getDb();
		
		$finfo = \OC\Files\Filesystem::getFileInfo($fileName);
		
		$this->xml = $this->container->query('XmlFactory')->makeXml($finfo->getId());
		$this->mode = $mode;
		$this->fileName = $fileName;
		
		$this->addCommand(new FilesCommand($this->fileName, $this->xml, $this->db));
		
	}
	
	public function addCommand($command){
		$this->commands[] = $command;
	}
	
	public function execute($versionFlag){
		foreach ($this->commands as $command){
			$command->execute($this->app, $this->mode, $versionFlag);
		}
	}

}

?>