<?php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

class PhotosMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'deduct_photos');
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_photos` ' .
                'WHERE `id` = ?';
        try {
            return $this->findEntity($sql, [$id]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByName($name) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_photos` ' .
                'WHERE `name` = ?';
        try {
            return $this->findEntity($sql, [$name]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByPath($path) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_photos` ' .
                'WHERE `path` = ?';
        try {
            return $this->findEntity($sql, [$path]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findAll($limit = null, $offset = null) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_photos`';
        return $this->findEntities($sql, $limit, $offset);
    }

    public function observationNodeCount($onodeId) {
        $sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_photos` ' .
                'WHERE `onode_id` = ?';
        $stmt = $this->execute($sql, [$onodeId]);

        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row['count'];
    }

    public function deletePhotoByOnodeid($onodeid) {
        $sql = 'DELETE FROM `*PREFIX*deduct_photos` ' .
                'WHERE `onodeid` = ?';
        $stmt = $this->execute($sql, [$onodeid]);
    }

}