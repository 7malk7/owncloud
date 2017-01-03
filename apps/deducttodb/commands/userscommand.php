<?php
namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\UserMapper;
use OCA\DeductToDB\Db\User;

class UsersCommand extends BaseCommand{

	public function __construct($fileName, $xml, $db){
		parent::__construct($fileName, $xml, $db);
	}

	function execute($app, $mode, $versionFlag){
				
		$owner = \OC\Files\Filesystem::getOwner($this->fileName);
		
		$userMapper = new UserMapper($this->db);
		
		$user = $userMapper->find((string)$this->xml->created_by);
		if(!$user){
		   $newUser = new User();
		   $newUser->setName((string)$this->xml->created_by);
		   $userMapper->insert($newUser);
		}		

	}

}

?>