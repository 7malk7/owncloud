<?php

namespace OCA\DeductToDB\Strategies;

use OCA\DeductToDB\Commands\ObservationFormCommand;

class FormFileStrategy extends BaseStrategy{

	public function __construct($fileName, $app, $mode){
		parent::__construct($fileName, $app, $mode);

		$this->app = $app;

		$this->container = $app->getContainer();
		$this->db = $this->container->getServer()->getDb();

		$finfo = \OC\Files\Filesystem::getFileInfo($fileName);

		$this->xml = $this->container->query('XmlFactory')->makeXml($finfo->getId());
		$this->mode = $mode;
		$this->fileName = $fileName;

		$this->addCommand(new ObservationFormCommand($this->fileName, $this->xml, $this->db));

	}

	// 	public function addCommand($command){
	// 		$this->commands[] = $command;
	// 	}

}


?>