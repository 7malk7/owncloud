<?php
namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;
use OCP\IDb;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;


class ObservationNodeMapper extends Mapper {

	public function __construct(IDb $db) {
		parent::__construct($db, 'deduct_observation_node');
	}


	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 */
	public function find($id) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_observation_node` ' .
				'WHERE `id` = ?';
		return $this->findEntity($sql, [$id]);
	}
	
	public function findByUuid($uuid) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_observation_node` ' .
				'WHERE `uuid` = ?';
		try{
			return $this->findEntity($sql, [$uuid]);
		}
		catch(DoesNotExistException $exc) {
			return null;
		}
	}

	public function findAll($limit=null, $offset=null) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_observation_node`';
		return $this->findEntities($sql, $limit, $offset);
	}
	
	public function selectUsersByInterval($from, $to, $limit = null, $offset = null) {
	
		$sql = 'select `onode`.createdby from `*PREFIX*deduct_forms` '.
				' inner JOIN `*PREFIX*deduct_observation_node` as onode on onode.uuid = `*PREFIX*deduct_forms`.uuid ' .
				' where `*PREFIX*deduct_forms`.createdat >= "' . $from . '" AND `*PREFIX*deduct_forms`.createdat <= "' . $to . '" ';
	
		try {
			return $this->findEntities($sql, [], $limit, $offset);
		} catch (DoesNotExistException $exc) {
			return null;
		}
	}


	public function NodesTitleCount($title) {
		$sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*deduct_observation_node` ' .
				'WHERE `title` = ?';
		$stmt = $this->execute($sql, [$title]);

		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row['count'];
	}

}


