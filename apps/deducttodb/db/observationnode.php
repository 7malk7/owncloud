<?php
namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class ObservationNode extends Entity implements JsonSerializable {


	//public $id;
	protected $type;
	protected $createdby;
	protected $createdat;
	protected $uuid;
	protected $title;

	public function jsonSerialize() {
		return [
				'type' => $this->type,
				'createdby' => $this->createdby,
				'createdat' => $this->createdat,				
				'uuid' => $this->uuid,
				'title' => $this->title
		];
	}
}

?>