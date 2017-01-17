<?php

namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Forms extends Entity implements JsonSerializable {
	
	protected $onodeid;
	protected $path;
	protected $type;
	protected $title;
	protected $createdat;
	protected $owner;
	protected $foldername;
	protected $uuid;

	public function jsonSerialize() {
		return [
				'$onodeid' => $this->onodeid,
				'$type' => $this->type,
				'$path' => $this->path,
				'$title' => $this->title,
				'$createdat' => $this->createdat,
				'$owner' => $this->owner,
				'$foldername' => $this->foldername,
				'$uuid' => $this->uuid
		];
	}
}

?>