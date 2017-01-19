<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OCA\DeductToDB\Db;

use OCP\AppFramework\Db\Entity;
use JsonSerializable;

/**
 * Description of entryArchive
 *
 * @author Aleh Kalenchanka <malk@abat.de>
 */
class entryarchive extends Entity {

    protected $formid;
    protected $type;
    protected $key;
    protected $value;
    protected $valuedefault;
    protected $modified;
    protected $order;
    protected $dateEntry;
    protected $creator;

    public function jsonSerialize() {
        return [
            'formid' => $this->formid,
            'type' => $this->type,
            'key' => $this->key,
            'value' => $this->value,
            'valuedefault' => $this->valuedefault,
            'modified' => $this->modified,
            'order' => $this->order,
            'dateEntry' => $this->dateEntry,
            'creator' => $this->creator,
        ];
    }

}
