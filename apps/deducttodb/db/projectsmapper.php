<?php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

class ProjectsMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'deduct_projects');
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_projects` ' .
                'WHERE `id` = ?';

        try {
            return $this->findEntity($sql, [$id]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByUuid($uuid) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_projects` ' .
                'WHERE `UUID` = ?';

        try {
            return $this->findEntity($sql, [$uuid]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByFolder($folder) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_projects` ' .
                'WHERE `foldername` = ?';
        try {
            return $this->findEntity($sql, [$folder]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findAll($limit = null, $offset = null) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_projects`';
        return $this->findEntities($sql, [], $limit, $offset);
    }

    public function filesNameCount($path) {
        $sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_projects` ' .
                'WHERE `path` = ?';
        $stmt = $this->execute($sql, [$name]);

        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row['count'];
    }

    public function deleteByUUID($uuid) {
        $sql = 'DELETE FROM `*PREFIX*deduct_projects` ' .
                'WHERE `uuid` = ?';
        $stmt = $this->execute($sql, [$uuid]);
    }

}
