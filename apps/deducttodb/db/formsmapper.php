<?php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

class FormsMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'deduct_forms');
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_forms` ' .
                'WHERE `id` = ?';
        try {
            return $this->findEntity($sql, [$id]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByPath($path) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_forms` ' .
                'WHERE `path` = ?';
        try {
            return $this->findEntity($sql, [$path]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }
    
       public function deleteByOnodeId($onodeid) {
        $sql = 'DELETE FROM `*PREFIX*deduct_forms` ' .
                'WHERE `onodeid` = ?';
        $stmt = $this->execute($sql, [$onodeid]);
    }


    public function findAll($limit = null, $offset = null) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_forms`';
        return $this->findEntities($sql, $limit, $offset);
    }

    public function selectFormsbyInterval($from, $to, $limit = null, $offset = null) {

// 		$sql = 'select * from oc_forms inner join (oc_observation_node) on ' .
// 		     '(oc_observation_node.createdat >= "' . $from . '" AND oc_observation_node.createdat <= "' .
// 		     $to
// 		     . '" AND oc_observation_node.id = oc_forms.onodeid)';
        //$sql = 'select * from `*PREFIX*deduct_forms` where createdat >= "' . $from . '" AND createdat <= "' . $to . '"';
        $sql = 'select `*PREFIX*deduct_forms`.* from `*PREFIX*deduct_forms` '.
        		' inner JOIN `*PREFIX*deduct_observation_node` as onode on onode.uuid = `*PREFIX*deduct_forms`.uuid ' .
        		' where `*PREFIX*deduct_forms`.createdat >= "' . $from . '" AND `*PREFIX*deduct_forms`.createdat <= "' . $to . '" ';

        try {
            return $this->findEntities($sql, [], $limit, $offset);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function observationNodeCount($onodeId) {
        $sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_forms` ' .
                'WHERE `onodeid` = ?';
        $stmt = $this->execute($sql, [$onodeId]);

        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row['count'];
    }

}

?>