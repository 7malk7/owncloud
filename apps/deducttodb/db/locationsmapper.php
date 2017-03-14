<?php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

class LocationsMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'locations');
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_locations` ' .
                'WHERE `id` = ?';
        try {
            return $this->findEntity($sql, [$id]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByOnodeId($onodeId) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_locations` ' .
                'WHERE `onodeid` = ?';
        try {
            return $this->findEntities($sql, [$onodeId]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function deleteById($id) {
        $sql = 'DELETE FROM `*PREFIX*deduct_locations` ' .
                'WHERE `id` = ?';
        $stmt = $this->execute($sql, [$id]);
    }

    public function findAll($limit = null, $offset = null) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_locations`';
        return $this->findEntities($sql, $limit, $offset);
    }

    public function observationNodeCount($onodeId) {
        $sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_locations` ' .
                'WHERE `onode_id` = ?';
        $stmt = $this->execute($sql, [$onodeId]);

        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row['count'];
    }

}

?>