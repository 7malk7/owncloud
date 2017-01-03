<?php
namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Projects extends Entity implements JsonSerializable {

	
	//public $id;
	protected $name;
	protected $createdby;
	protected $createdat;
	protected $owner;
	protected $uuid;
	protected $version;
	protected $foldername;

	public function jsonSerialize() {
		return [
				'name' => $this->name,
				'createdby' => $this->createdby,
				'createdat' => $this->createdat,
				'owner' => $this->owner,
				'uuid' => $this->uuid,
				'version' => $this->version,
				'foldername' => $this->foldername
		];
	}
}

?>