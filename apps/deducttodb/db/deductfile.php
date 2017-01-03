<?php
namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class DeductFile extends Entity implements JsonSerializable {

	//public $id;
	protected $path;
	protected $type;
	protected $creator;
	protected $createdat;
	protected $modified;

	public function jsonSerialize() {
		return [
				//'id' => $this->id,
				'path' => $this->path,
				'type' => $this->type,
				'creator' => $this->creator,
				'createdat' => $this->createdat,
				'modified' => $this->modified
		];
	}
}