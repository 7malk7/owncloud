<?php
namespace OCA\DeductToDB\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Params extends Entity implements JsonSerializable {

	//public $id;
	protected $name;
	protected $value;
	protected $modifiedat;
	protected $modifiedby;

	public function jsonSerialize() {
		return [
				'name' => $this->name,								
				'value' => $this->value,				
				'modifiedat' => $this->modifiedat,
				'modifiedby' => $this->modifiedby
		];
	}
}

?>