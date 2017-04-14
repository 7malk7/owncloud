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

    /**
     * Find archive entries by date
     * @param type $datefrom
     * @param type $dateto
     * @param type $limit
     * @param type $offset
     * @return type
     */
    public function findByDate($datefrom, $dateto, $user, $limit = null, $offset = null) {
        $sql = 'select * from `*PREFIX*deduct_entry_archive`  ' .
                ' WHERE date_entry >= "' . $datefrom .
                '" AND date_entry <=  "' . $dateto . '" AND creator = "' . $user . '"';
        try {
            return $this->findEntities($sql, [], $limit, $offset);
        } catch (DoesNotExistException $exc) {
            return null;
        }
    }

    /**
     * Insert data from Excel to DB
     * @param array $keys
     * @param array $data
     */
    public function insertFromArray(array $keys, array $data) {
        try {
            $rows = $this->prepareData($keys, $data);
            foreach ($rows as $row) {
                $entryArchiveEntity = new entryarchive();
                $entryArchiveEntity->setFormid($row[0]);
                $entryArchiveEntity->setKey($row[1]);
                $entryArchiveEntity->setValue($row[2]);
                $entryArchiveEntity->setModified($row[3]);
                $entryArchiveEntity->setDateEntry($row[4]);
                $entryArchiveEntity->setCreator($row[5]);
                $this->insert($entryArchiveEntity);
            }
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
        
        return count($rows);
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
        $creator = \OC::$server->getUserSession()->getUser()->getUID();
        foreach ($data as $dataRow) {
            $counter++;
            preg_match('/\d{4}-\d{2}-\d{2}/', $dataRow[3], $dateMatch);
            $date = isset($dateMatch[0]) ? $dateMatch[0] : "";
            for ($i = 0; $i < count($keys); $i++) {
                $dbRow = array();
                array_push($dbRow, $counter, $keys[$i], $dataRow[$i], $today, $date, $creator);
                $dbData[] = $dbRow;
                unset($dbRow);
            }
        }

        return $dbData;
    }

}
