<?php

namespace OCA\DeductToDB\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Entry extends Entity implements JsonSerializable {

    protected $formid;
    protected $type;
    protected $value;
    protected $valuedefault;
    protected $modified;
    protected $order;
    protected $key;

    public function jsonSerialize() {
        return [
            'formid' => $this->formid,
            'type' => $this->type,
            'value' => $this->value,
            'valuedefault' => $this->valuedefault,
            'modified' => $this->modified,
            'order' => $this->order,
            'key' => $this->key
        ];
    }

}

?>