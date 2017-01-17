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
 * Description of deductArchiveMapper
 *
 * @author Aleh Kalenchanka <malk@abat.de>
 */
class paramsMapper extends Mapper {

    public function __construct(IDb $db) {
        parent::__construct($db, 'deduct_params');
    }

    public function findByName($name, $limit = 1, $offset = null) {
        $sql = 'select * from `*PREFIX*deduct_params`  ' .
                ' WHERE name =  "' . $name . '"';
        $entity = $this->findEntities($sql, []);
        if (!$entity) {
            throw new DoesNotExistException('No Entry found');
        }
        return $entity[0]->getValue();
    }
    
    public function findManyByName($name, $limit = 1, $offset = null) {
    	$sql = 'select * from `*PREFIX*deduct_params`  ' .
    			' WHERE name =  "' . $name . '"';
    	$entities = $this->findEntities($sql, []);
    	if (!$entities) {
    		throw new DoesNotExistException('No Entry found');
    	}
    	return $entities;
    }

    public function findByNameWithDefault($name, $defaultVal, $limit = 1, $offset = null) {
    	try{
    		$found = $this->findByName($name);
    		if($found == "empty"){
    			$found = $defaultVal;
    		}
    		return $found;
    	}
    	catch(DoesNotExistException $exc) {
    		return $defaultVal;
    	}
    }

    public function findEntityByName($name, $limit = 1, $offset = null) {
        $sql = 'select * from `*PREFIX*deduct_params`  ' .
                ' WHERE name =  "' . $name . '"';

        $entity = $this->findEntities($sql, [], $limit, $offset);
        if (!$entity) {
            throw new DoesNotExistException('No Entry found');
        }
        return $entity[0];
    }

    public function setByName($name, $value, $limit = null, $offset = null) {
        $params = new Params();
        $params->setName($name);
        $params->setValue($value);

        try {
            $found = $this->findByName($name);
            try {
                $found_entity = $this->findEntityByName($name);
                $found_entity->setValue($value);
                return $this->update($found_entity);
            } catch (DoesNotExistException $exc) {
                return null;
            }
        } catch (DoesNotExistException $exc) {
            try {
                return $this->insert($params);
            } catch (DoesNotExistException $exc) {
                return null;
            }
        }
    }

}

