<?php

namespace OCA\DeductToDB\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class FileMaintenance extends Entity implements JsonSerializable {

    protected $path;
    protected $lastupdate;
    protected $hash;
    protected $deleted;

    public function jsonSerialize() {
        return [
            '$path' => $this->path,
            '$last_update' => $this->lastupdate,
            '$hash' => $this->hash,
            '$deleted' => $this->deleted
        ];
    }

}
