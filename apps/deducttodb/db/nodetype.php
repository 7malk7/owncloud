<?php

namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Nodetype extends Entity implements JsonSerializable {

	protected $value;
	protected $name;

	public function jsonSerialize() {
		return [
				'$value' => $this->value,
				'$name' => $this->name
		];
	}
}

?>