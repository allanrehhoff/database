<?php
/**
* Represents a connection between the server and the database.
* Easily perform SQL queries without writing (more than neccesary) SQL.
* Credits to Mikkel Jensen & Victoria Hansen from whom I in cold blood have undeterred copied some of this code from.
* @author Allan Thue Rehhoff
* @version 2.0
*/
namespace Database {
	class Connection extends \PDO {
		/**
		* @var (object) Internal PDO connection
		*/
		private $_connection;

		/**
		* @var (boolean) True if a transaction has started, false otherwise.
		*/
		private $transactionStarted = false;

		/**
		*@var (object)  The singleton instance of the this class.
		*/
		private static $singletonInstance;

		/**
		* @var (object) Holds the last prepared statement after execution.
		*/
		public $statement;

		/**
		* @var (int) Number of affected rows from last query
		*/
		public $rowCount;

		/**
		* @var (int) Number of queries executed.
		*/
		public $queryCount = 0;

		/**
		* @var (string) Last query attempted to be executed.
		*/
		public $lastQuery;

		/**
		* Initiate a new database connection through PDO.
		* @param (string) $hostname Hostname to connect to
		* @param (string) $username Username to use for authentication
		* @param (string) $password Password to use for authentication
		* @param (string) $database Name of the database to use
		* @return (void)
		* @throws Exception
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public function __construct($hostname, $username, $password, $database) {
			if (extension_loaded("pdo") === false) {
				throw new Exception("Oh god! PDO does not appear to be enabled for this server.");
			}

			try {
				parent::__construct("mysql:host=".$hostname.";dbname=".$database, $username, $password, [self::ATTR_ERRMODE => self::ERRMODE_EXCEPTION]);
				$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
				$this->setAttribute(self::ATTR_STATEMENT_CLASS, ["Database\Statement", [$this]]);
				$this->query("SET NAMES utf8mb4");
			} catch (PDOException $exception) {
				throw new Exception($exception->getMessage(), $exception->getCode());
			}

			self::$singletonInstance = $this;
		}

		/**
		* This should most likely close the connection when you're done using the \Database\Connection
		* @author Allan Thue Rehhoff
		* @return void
		* @since 1.3
		*/
		public function __destruct() {
			$this->statement = null;
		}

		/**
		* Allow methods not implemented by this class to be called on the connection
		* @author Allan Thue Rehhoff
		* @todo Consider removing the \Database\Connection::getConnection(); method now that we have this.
		* @since 1.3
		*/
		public function __call($method, $params = []) {
			if(method_exists($this, $method)) {
				return call_user_func_array([$this, $method], $params);
			} else {
				throw new Exception("PDO::".$method." no such method.");
			}
		} 

		/**
		* Retrieve the latest initiated \Database\Connection instance.
		* @return (object)
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public static function getInstance() {
			return self::$singletonInstance;
		}

		/**
		* Retrieve the connection instance used by the current \Database\Connection instance.
		* You should rarely have a use for this though.
		* @since 1.0
		* @return (object)
		* @author Allan Thue Rehhoff
		*/
		public function getConnection() {
			return $this;
		}

		/**
		* Turns off autocommit mode. Until changes made to the database are committed.
		* @author Allan Thue Rehhoff
		* @return (boolean)
		* @since 2.4
		*/
		public function beginTransaction() {
			return $this->transactionStarted = parent::beginTransaction();
		}

		/**
		* Helping wrapper function for PDO::beginTranstion();
		* @see Database\Connection::beginTransaction();
		* @author Allan Thue Rehhoff
		* @return (boolean)
		* @since 1.3
		*/
		public function transaction() {
			return $this->beginTransaction();
		}

		/**
		* Commits a transaction, returning the database connection to autocommit mode.
		* @author Allan Thue Rehhoff
		* @throws PDOException
		* @return (bool)
		* @since 1.3
		*/
		public function commit() {
			if($this->transactionStarted === true) {
				return parent::commit();
			} else {
				throw new PDOException("Attempted to commit when not in transaction, or transaction failed to start.");
			}
		}

		/**
		* Rolls back the current transaction
		* @author Allan Thue Rehhoff
		* @throws PDOException
		* @return (bool)
		* @since 1.3
		*/
		public function rollback() {
			if($this->transactionStarted === true) {
				return parent::rollBack();
			} else {
				throw new PDOException("Attempted rollback when not in transaction.");
			}
		}

		/**
		* Wrapper around PDO::prepare(); to provide support for the IN keyword.
		* @param (string) $sql The parameterized SQL string to query against the database
		* @param (array) $filters Arguments to pass along with the query
		* @since 2.3
		* @author Allan Thue Rehhoff
		* @return Returns a prepared SQL statement
		*/
		public function prepare($sql, $driverOptions = []) {
			if(!empty($this->filters)) {
				foreach($this->filters as $column => $filter) {
					if(is_array($filter) === true) {
						$tmparr = [];
						foreach ($filter as $i => $item) {
							$key = "val".$i;
							$tmparr[$key]  = $item;
							$this->filters[$key] = $item;
						}

						// (:val0, :val1, :val2)
						$in = "(:".implode(", :", array_keys($tmparr)).')';
						$sql = str_replace(":".$column, $in, $sql);
						unset($this->filters[$column]);
					}
				}
			}

			return parent::prepare($sql, $driverOptions);
		}

		/**
		* Execute a parameterized SQL query.
		* @param (string) $sql The parameterized SQL string to query against the database
		* @param (array) $filters Arguments to pass along with the query.
		* @param (int) $fetchMode Set fetch mode for the query performed. Must be one of PDO::FETCH_* default is PDO::FETCH_OBJECT
		* @return (object) The prepared PDO statement after execution.
		* @throws Exception
		* @author Allan Thue Rehhoff
		* @todo Find and alternative to casting errorcodes to integers for handling error codes.
		* @since 1.0
		*/
		public function query($sql, $filters = null, $fetchMode = self::FETCH_OBJ) {
			try {
				$this->filters 	 = $filters;
				$this->statement = null;
				$this->statement = $this->prepare($sql);
				$this->statement->execute($this->filters);
				$this->statement->setFetchMode($fetchMode);
				$this->queryCount++;
			} catch(PDOException $exception) {
				throw new Exception($exception->getCode().": ".$exception->getMessage(), (int) $exception->getCode());
			} finally {
				$this->lastQuery = $sql;
			}

			return $this->statement;
		}

		/**
		* Fetch a single row from the given criteria.
		* Rows are not ordered, make sure your criteria matches the desired row.
		* @param (string) $table Name of the table containing the row to be fetched
		* @param (array) $criteria Criteria used to filter the rows.
		* @return (mixed) Returns the first row in the result set, false upon failure.
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public function fetchRow($table, $criteria = null) {
			$sql = "SELECT * FROM `".$table."` WHERE ".$this->keysToSql($criteria, "AND")." LIMIT 1";
			return $this->query($sql, $criteria)->fetch();
		}

		/**
		* Fetch a cells value from the given criteria.
		* @param (string) $table Name of the table containing the row to be fetched
		* @param (string) $column Column name in $table where cell value will be returned
		* @param (array) $criteria Criteria used to filter the rows.
		* @return (mixed) Returns a single column from the next row of a result set or FALSE if there are no rows.
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public function fetchCell($table, $column, $criteria = null) {
			$sql = "SELECT `".$column."` FROM `".$table."` WHERE ".$this->keysToSql($criteria, "AND")." LIMIT 1";
			return $this->query($sql, $criteria)->fetchColumn(0);
		}

		/**
		* Alias of \Database\Connection::fetchCell implemented for the drupal developers sake.
		* @see \Database\Connection::fetchCell();
		*/
		public function fetchField($table, $column, $criteria = null) {
			return $this->fetchCell($table, $column, $criteria);
		}

		/**
		* Fetches a column of values from the given table.
		* @param (string) $table Name of the table containing the rows to be fetched
		* @param (string) $column Column name in $table where value should be returned from.
		* @param (array) $criteria Criteria used to filter the rows.
		* @return (mixed) Returns an array containing all the rows matching in the resultset,
		* 				  An empty array is returned if there are zero results to fetch, or FALSE on failure.
		* @author Allan Thue Rehhoff
		* @since 2.4
		*/
		public function fetchCol($table, $column, $criteria) {
			$sql = "SELECT `".$column."` FROM `".$table."` WHERE ".$this->keysToSql($criteria, "AND");
			return $this->query($sql, $criteria)->fetchCol();
		}
		/**
		* Select rows based on the given criteria
		* @param (string) $table Name of the table to query
		* @param (array) $criteria column => value pairs to filter the query results
		* @return (array)
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public function select($table, $criteria = null) {
			$sql = "SELECT * FROM ".$table." WHERE ".$this->keysToSql($criteria, "AND");
			return $this->query($sql, $criteria)->fetchAll();
		}

		/**
		* Inserts a row in the given table.
		* @param (string) $table Name of the table to insert the row in
		* @param (array) $variables Column => Value pairs to be inserted
		* @return (string) The last inserted ID
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public function insert($table, $variables = null) {
			$variables = ($variables != null) ? $variables : [];
			$this->createRow("INSERT", $table, $variables);

			return $this->lastInsertId();
		}

		/**
		* Inserts a new row in the given table.
		* Already existing rows with matching PRIMARY key or UNIQUE index are deleted prior to inserting.
		* @param (string) $table Name of the table to replace into
		* @param (array) $variables Column => Value pairs to be inserted
		* @return (string) The last inserted ID
		* @since 1.0
		*/
		public function replace($table, $variables) {
			return $this->createRow("REPLACE", $table, $variables)->rowCount();
		}

		/**
		* Update rows in the given table depending on the criteria.
		* @param (string) $table Name of the table to update rows in
		* @param (array) $variables Column => Value pairs containg the new values for the row
		* @param (array) $criteria Array of criterie for updating a row
		* @return (int) Number of affected rows
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public function update($table, $variables, $criteria = null) {
			$args = [];
			foreach ($variables as $key => $value) $args["new_".$key] = $value;
			foreach ($criteria as $key => $value) $args["old_".$key] = $value;
			
			$sql = "UPDATE `".$table."` SET ".$this->keysToSql($variables, ",", "new_")." WHERE ".$this->keysToSql($criteria, " AND ", "old_");
			return $this->query($sql, $args)->rowCount();
		}

		/**
		* Delete rows from the given table by criteria.
		* @param (string) $table Table to delete rows from
		* @param (array) $criteria Criteria for deletion
		* @author Allan Thue Rehhoff
		* @return (int) Number of rows affected.
		* @since 1.0
		* @todo Return row count
		*/
		public function delete($table, $criteria = null) {
			$sql = "DELETE FROM `".$table."` WHERE ".$this->keysToSql($criteria, " AND");
			return $this->query($sql, $criteria)->rowCount();
		}

		/**
		* Internal function to insert or replace a row.
		* @param (string) $type SQL operator to with the INTO can be either INSERT or REPLACE
		* @param (string) $table Table to insert/replace the row in.
		* @param (array) $variables Column => Value pairs to insert.
		* @return (string) The last inserted ID
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		private function createRow($type, $table, $variables) {
			$binds = [];
			foreach ($variables as $key => $value) $binds[] = ":$key";
			$sql = $type. " INTO "."`$table` (" . implode(", ", array_keys($variables)) . ") VALUES (" . implode(", ", $binds) . ")";
			return $this->query($sql, $variables);
		}

		/**
		* Internal function to convert column=>value pairs into SQL.
		* If a parameter value is an array, it will be treated as such, using the IN operator.
		* @param (array) $array Array of arguments to parse (You sure yet that it's an array?)
		* @param (string) $seperator String seperator to seperate the pairs with
		* @param (string) $variablePrefix string to use for prefixing values in the SQL
		* @return (string)
		* @author Mikkel Jensen
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		private function keysToSql(&$array, $seperator, $variablePrefix = "") {
			if ($array == null) return "1";

			$list = [];
			foreach ($array as $column => $value) {
				if(is_array($value)) {
					$operator = "IN";
				} else {
					$operator = '=';
				}

				$list[] = " `$column` $operator :".$variablePrefix.$column;
			}

			return implode(' '.$seperator, $list);
		}

		/**
		* Debugging prepared statements can be severely painful, use this as you would with \Database\Connection::query(); to output the resulting SQL
		* Replaces any parameter placeholders in a query with the corrosponding value that parameter.
		* Assumes anonymous parameters from $params are are in the same order as specified in $query
		* @param (string) $query A parameterized SQL query
		* @param (array) $filter Parameters for $query
		* @return (string) The interpolated query.
		* @author Allan Thue Rehhoff
		* @since 2.4
		*/
		public function debugQuery($query, $filters) {
			$this->filters 	 = $filters;
			$statement = $this->prepare($query);

			$tmpFilters = [];
			foreach ($this->filters as $column => $value) $tmpFilters[":".$column] = "'".$value."'";

			return strtr($statement->queryString, $tmpFilters);
		}

		/**
		* Wrapper function for debugging purposes
		* @see Database\Connection::debugQuery()
		* @param (string) $query A parameterized SQL query
		* @param (array) $params Parameters for $query
		* @return (string)
		* @author Allan Thue Rehhoff
		* @since 1.1
		*/
		public function interpolateQuery($query, $params) {
			return $this->debugQuery($query, $params);
			/*
			$keys = [];
			$values = $params;

			if(is_array($params)) {
				foreach ($params as $key => $value) {
					if (is_string($key)) {
						$keys[] = '/:'.$key.'/';
					} else {
						$keys[] = '/[?]/';
					}

					if (is_string($value)) {
						$values[$key] = "'" . $value . "'";
					}

			        if (is_array($value)) {
						$values[$key] = "('" . implode("','", $value) . "')";
					} else if (is_null($value)) {
						$values[$key] = "NULL";
					}
				}
			}

			return preg_replace($keys, $values, $query, 1, $count);
			*/
		}

		/**
		* Get the last inserted ID
		* @return (int)
		* @author Allan Thue Rehhoff
		* @since 1.0
		*/
		public function lastInsertId($seqname = null) {
			return parent::lastInsertId($seqname);
		}
	}
}
