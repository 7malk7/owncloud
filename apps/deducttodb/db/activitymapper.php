<?php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

class ActivityMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'activity');
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*activity` ' .
                'WHERE `activity_id` = ?';

        try {
            return $this->findEntity($sql, [$id]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByType($type) {
        $sql = 'SELECT DISTINCT `file` FROM `*PREFIX*activity` ' .
                'WHERE `type` = ?';

        try {
            return $this->findEntities($sql, [$type]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }
    
      public function findByFolder($folderName) {
        $sql = 'SELECT DISTINCT `file` FROM `*PREFIX*activity` ' .
                'WHERE `type` = "file_created" AND `file` LIKE ? ';

        try {
            return $this->findEntities($sql, [$folderName]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }
}
