<?php
namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class User extends Entity implements JsonSerializable {

	protected $name;

	public function jsonSerialize() {
		return [
				'name' => $this->name
		];
	}
}

?>