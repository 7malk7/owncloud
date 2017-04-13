<?php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

class FileMaintenanceMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'deduct_file_maintenance');
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_file_maintenance` ' .
                'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }

    public function findByPath($path) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_file_maintenance` ' .
                'WHERE `path` = ?';
        try {
            return $this->findEntity($sql, [$path]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findAll($limit = null, $offset = null) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_file_maintenance`';
        return $this->findEntities($sql, $limit, $offset);
    }

}
