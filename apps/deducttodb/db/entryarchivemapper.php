<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Description of enrtyArchiveMapper
 *
 * @author Aleh Kalenchanka <malk@abat.de>
 */
class EntryArchiveMapper extends Mapper {

    /**
     * Constructor
     * @param IDb $db
     */
    public function __construct(IDb $db) {
        parent::__construct($db, 'deduct_entry_archive');
    }

    public function findByDate($datefrom, $dateto, $limit=null, $offset=null){
    	$sql = 'select * from `*PREFIX*deduct_entry_archive`  '.
    			' WHERE date_entry >= "'.$datefrom.
    			'" AND date_entry <=  "'. $dateto .'"';
    	try{
    		return $this->findEntities($sql, [] ,$limit, $offset);
    	}
    	catch(DoesNotExistException $exc) {
    		return null;
    	}
    }

    /**
     * Insert data from Excel to DB
     * @param array $keys
     * @param array $data
     */
    public function insertFromArray(array $keys, array $data) {
        $values = $this->prepareData($keys, $data);
        $sql = "INSERT INTO `*PREFIX*deduct_entry_archive` (`formid`, `key`, `value`, `modified`, `date_entry`) "
                . "VALUES " . $values;
        return $stmt = $this->execute($sql);
    }

    /**
     * Select max formid value from DB
     * @return int
     */
    private function selectMaxCounter() {
        $sql = "SELECT MAX(formid) AS `counter` FROM `*PREFIX*deduct_entry_archive`";
        try {
            $stmt = $this->execute($sql);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            if (!empty($row['counter'])) {
                return (int) $row['counter'];
            } else {
                return 0;
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
     * Prepare a string from array for INSERT statement
     * @param array $keys
     * @param array $data
     * @return string
     */
    private function prepareData(array $keys, array $data) {
        $counter = $this->selectMaxCounter();
        $dbData = array();
        $today = date("Y-m-d");
        foreach ($data as $dataRow) {
            $counter++;
            preg_match('/\d{4}-\d{2}-\d{2}/', $dataRow[3], $dateMatch);
            $date = isset($dateMatch[0]) ? $dateMatch[0] : "";
            for ($i = 0; $i < count($keys); $i++) {
                $dbRow = '( ' . $counter . ', '
                        . "'" . $keys[$i] . "'" . ', '
                        . "'" . $dataRow[$i] . "'" . ', '
                        . "'" . $today . "'" . ', '
                        . "'" . $date . "'" . ' )';
                $dbData[] = $dbRow;
                unset($dbRow);
            }
        }
        $values = implode(', ', $dbData);
        return $values;
    }

}
