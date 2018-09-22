<?php
namespace Database {
	use PDO;

	class Statement extends \PDOStatement {
		private $_connection;

		protected function __construct(PDO $connection) {
			$this->_connection = $connection;
		}

		public function fetchCol() {
			return $this->fetchAll(PDO::FETCH_COLUMN);
		}
	}
}