<?php
namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;


class DeductFileMapper extends Mapper {
	
	public function __construct(IDb $db) {
		parent::__construct($db, 'deduct_files');
	}
	
	
	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 */
	public function find($id) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_files` ' .
				'WHERE `id` = ?';
		return $this->findEntity($sql, [$id]);
	}
	
	
	public function findAll($limit=null, $offset=null) {
		$sql = 'SELECT * FROM `*PREFIX*files`';
		return $this->findEntities($sql, $limit, $offset);
	}
	
	
	public function filesNameCount($path) {
		$sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_files` ' .
				'WHERE `path` = ?';
		$stmt = $this->execute($sql, [$name]);
	
		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row['count'];
	}

}


