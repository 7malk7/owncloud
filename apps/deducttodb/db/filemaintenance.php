<?php
namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class FileMaintenance extends Entity implements JsonSerializable{

	protected $path;
	protected $lastupdate;
	protected $etag;
        protected $deleted;

	public function jsonSerialize() {
		return [
				'$path' => $this->path,
				'$last_update' => $this->lastupdate,
                                '$etag' => $this->etag,
                                '$deleted' => $this->deleted
		];
	}
}
