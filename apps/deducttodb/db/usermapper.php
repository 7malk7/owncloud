<?php
namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;


class UserMapper extends Mapper {

	public function __construct(IDb $db) {
		parent::__construct($db, 'deduct_user');
	}


	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 */
	public function find($name) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_user` ' .
				'WHERE `name` = ?';
		try{
			return $this->findEntity($sql, [$name]);
		}
		catch(DoesNotExistException $exc) {
			return null;
		}
	}


	public function findAll($limit=null, $offset=null) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_user`';
		return $this->findEntities($sql, $limit, $offset);
	}


	public function filesNameCount($path) {
		$sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_user` ' .
				'WHERE `path` = ?';
		$stmt = $this->execute($sql, [$name]);

		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row['count'];
	}

}


?>