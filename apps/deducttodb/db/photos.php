<?php

namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Photos extends Entity implements JsonSerializable {

	protected $onodeid;
	protected $path;
	protected $latitude;
	protected $longtitude;
	protected $gpsaccuracy;

	public function jsonSerialize() {
		return [
				'$onodeid' => $this->onodeid,
				'$path' => $this->path,
				'$latitude' => $this->latitude,
				'$longtitude' => $this->longtitude,
				'$GPS_accuracy' => $this->gpsaccuracy
		];
	}
}

?>