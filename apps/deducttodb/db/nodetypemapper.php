<?php
namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;


class NodetypeMapper extends Mapper {

	public function __construct(IDb $db) {
		parent::__construct($db, 'deduct_nodetype');
	}


	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 */
	public function find($id) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_nodetype` ' .
				'WHERE `id` = ?';
		try{
			return $this->findEntity($sql, [$id]);
		}
		catch(DoesNotExistException $exc) {
			return null;
		}
	}
	
	public function findByName($name) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_nodetype` ' .
				'WHERE `name` = ?';
		try{
			return $this->findEntity($sql, [$name]);
		}
		catch(DoesNotExistException $exc) {
			return null;
		}
	}


	public function findAll($limit=null, $offset=null) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_nodetype`';
		return $this->findEntities($sql,[] ,$limit, $offset);
	}


	public function observationNodeCount($name) {
		$sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_nodetype` ' .
				'WHERE `name` = ?';
		$stmt = $this->execute($sql, [$name]);

		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row['count'];
	}

}

?>