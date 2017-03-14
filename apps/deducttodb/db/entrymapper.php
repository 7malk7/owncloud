<?php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

class EntryMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'deduct_entry');
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_entry` ' .
                'WHERE `id` = ?';
        try {
            return $this->findEntity($sql, [$id]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }
    
      public function findByFormId($formid) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_entry` ' .
                'WHERE `formid` = ?';
        try {
            return $this->findEntities($sql, [$formid]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByFormIdAndKey($formid, $key) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_entry` ' .
                'WHERE `formid` = ? and `key` = ? ';
        try {
            return $this->findEntity($sql, [$formid, $key]);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findByFormType($formtype, $datefrom, $dateto, $user, $limit = null, $offset = null) {
    	$sql = "";
    	if($formtype == "*")
    	{
    		$sql = 'select `*PREFIX*deduct_entry`.* from `*PREFIX*deduct_entry` '.
    				'inner join `*PREFIX*deduct_forms` on `*PREFIX*deduct_entry`.formid = `*PREFIX*deduct_forms`.id and '.
    				' '.
    				' `*PREFIX*deduct_forms`.createdat >= "'. $datefrom .'" AND '.
    				'`*PREFIX*deduct_forms`.createdat <= "'. $dateto .'" inner ' .
    				' join `*PREFIX*deduct_observation_node` on `*PREFIX*deduct_observation_node`.uuid = `*PREFIX*deduct_forms`.`uuid`'.
    				' and `*PREFIX*deduct_observation_node`.createdby = "' . $user . '"';
//     		$sql = 'select `*PREFIX*deduct_entry`.* from `*PREFIX*deduct_entry` inner join `*PREFIX*deduct_forms` ' .
//     	'  on `*PREFIX*deduct_entry`.formid = `*PREFIX*deduct_forms`.id ' .
//     				'  and `*PREFIX*deduct_forms`.createdat >= "' . $datefrom .
//     				'" AND `*PREFIX*deduct_forms`.createdat <=  "' . $dateto . '"';
    	}
    	else{
//         	$sql = 'select `*PREFIX*deduct_entry`.* from `*PREFIX*deduct_entry` inner join `*PREFIX*deduct_forms` ' .
//         '  on `*PREFIX*deduct_entry`.formid = `*PREFIX*deduct_forms`.id ' .
//                 ' and `*PREFIX*deduct_forms`.type = "' . $formtype . '" and `*PREFIX*deduct_forms`.createdat >= "' . $datefrom .
//                 '" AND `*PREFIX*deduct_forms`.createdat <=  "' . $dateto . '"';

    		$sql = 'select `*PREFIX*deduct_entry`.* from `*PREFIX*deduct_entry` '.
    				'inner join `*PREFIX*deduct_forms` on `*PREFIX*deduct_entry`.formid = `*PREFIX*deduct_forms`.id and '.
    				'`*PREFIX*deduct_forms`.type = "' . $formtype . '" and '.
    				' `*PREFIX*deduct_forms`.createdat >= "'. $datefrom .'" AND '.
    				'`*PREFIX*deduct_forms`.createdat <= "'. $dateto .'" inner ' .
    				' join `*PREFIX*deduct_observation_node` on `*PREFIX*deduct_observation_node`.uuid = `*PREFIX*deduct_forms`.`uuid`'.
    				' and `*PREFIX*deduct_observation_node`.createdby = "' . $user . '"';
    	}
    	
        try {
            return $this->findEntities($sql, [], $limit, $offset);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    public function findAll($limit = null, $offset = null) {
        $sql = 'SELECT * FROM `*PREFIX*deduct_entry`';
        return $this->findEntities($sql, $limit, $offset);
    }

    public function formEntriesCount($formId) {
        $sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_entry` ' .
                'WHERE `formid` = ?';
        $stmt = $this->execute($sql, [$formId]);

        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row['count'];
    }
    
     public function deleteEntriesByFormid($formId) {
        $sql = 'DELETE FROM `*PREFIX*deduct_entry` ' .
                'WHERE `formid` = ?';
        $stmt = $this->execute($sql, [$formId]);
    }

}

?>