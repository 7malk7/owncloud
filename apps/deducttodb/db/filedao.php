<?php
// db/authordao.php

namespace OCA\DeductToDB\Db;

use OCP\IDBConnection;

class FilesDAO {

	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	public function find($id) {
		$sql = 'SELECT * FROM `*PREFIX*deduct_files` ' .
				'WHERE `id` = ?';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $id, \PDO::PARAM_INT);
		$stmt->execute();

		$row = $stmt->fetch();

		$stmt->closeCursor();
		return $row;
	}

}