<?php
	/**
	* Quickly test most frequent use cases for the DatabaseConnection() class
	* @todo Add the following queries to be tested against.
	* ALTER TABLE  `movies` ADD  `test_col` INT NOT NULL AFTER  `completed`;
	* ALTER TABLE `movies` DROP `test_col`
	* @author Allan Thue Rehhoff
	*/
	class DatabaseConnectionTest extends PHPUnit_Framework_TestCase {
		private $db;
		private $idsToDeleteInOurMovies = [];
		private $initialAutoIncrementValue;

		/**
		* Sets up the required database connection
		* @author Allan Thue Rehhoff
		*/
		public function setUp() {
			try {
				$this->db = new DatabaseConnection(DB_HOST, DB_USER, DB_PASS, DatabaseConnectionTestTable);
			} catch(Exception $e) {
				$this->fail($e->getMessage());
			}

			$this->initialAutoIncrementValue = $this->db->fetchCell(
				"INFORMATION_SCHEMA.TABLES",
				"AUTO_INCREMENT",
				["TABLE_SCHEMA" => DatabaseConnectionTestTable, "TABLE_NAME" => "movies"]
			);
		}

		/**
		* Cleans up the table after testing, since i dont want the table we are testing against to clutter up with random crap.
		* @author Allan Thue Rehhoff
		*/
		public function tearDown() {
			foreach($this->idsToDeleteInOurMovies as $mid) {
				$this->db->delete("movies", ["mid" => $mid]);
			}
			
			$this->db->query("ALTER TABLE movies AUTO_INCREMENT = ".$this->initialAutoIncrementValue);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSingletonIsInstanceOfDatabaseConnection() {
			$this->assertInstanceOf("DatabaseConnection", DatabaseConnection::getInstance());
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSingletonCanDoQuery() {
			$sdb = DatabaseConnection::getInstance();
			$this->assertInstanceOf("DatabaseConnection", $sdb);
			$res = $sdb->query("SELECT mid FROM movies LIMIT 1")->fetchAll();
			$this->assertInternalType("array", $res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectQueryWithoutParameter() {
			$res = $this->db->query("SELECT mid FROM movies")->fetchAll();
			$this->assertInternalType("array", $res);
			$this->assertNotEmpty($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectFromParameterizedQuery() {
			$res = $this->db->query("SELECT mid FROM movies WHERE mid > :num", ["num" => 1])->fetchAll();
			$this->assertInternalType("array", $res);
			$this->assertNotEmpty($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectQueryFromWrapperMethod() {
			$res = $this->db->select("movies");
			$this->assertInternalType("array", $res);
			$this->assertNotEmpty($res);	
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectQueryMethodWithParameters() {
			$res = $this->db->select("movies", ["mid" => 4]);
			$this->assertInternalType("array", $res);
			$this->assertNotEmpty($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectSingleRowFromParameters() {
			$res = $this->db->fetchRow("movies", ["mid" => 4, "movie_name" => "Star wars"]);
			$this->assertInternalType("object", $res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testFetchSingleCellValueFromParameters() {
			$res = $this->db->fetchCell("movies", "movie_name", ["mid" => 4]);
			$this->assertEquals("Star wars", $res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testInsertSingleRow() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$this->assertInternalType("string", $insertId);

			$this->idsToDeleteInOurMovies[] = $insertId;
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testReplaceRowWithExistingPrimaryKey() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$this->db->replace("movies", ["mid" => $insertId, "movie_name" => "test2"]);

			$movieName = $this->db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals("test2", $movieName);

			$this->idsToDeleteInOurMovies[] = $insertId;
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testInsertAndDeleteRow() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$rowsAffected = $this->db->delete("movies", ["mid" => $insertId]);
			$this->assertGreaterThan(0, $rowsAffected);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testInsertAndUpdateSingleRow() {
			$newMovieName = "Exciting new movie";

			$insertId = $this->db->insert("movies", ["movie_name" => "Old boring movie."]);
			$this->assertInternalType("string", $insertId);

			$rowsAffected = $this->db->update("movies", ["movie_name" => $newMovieName], ["mid" => $insertId]);
			$this->assertGreaterThan(0, $rowsAffected);

			$updatedRowMovieName = $this->db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals($newMovieName, $updatedRowMovieName);

			$this->idsToDeleteInOurMovies[] = $insertId;
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testRollbackInsert() {
			$latestMovieIdBeforeTransaction = $this->db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;

			$start = $this->db->transaction();
			$this->assertTrue($start);

			$this->db->insert("movies", ["movie_name" => "New movie", "added" => date("Y-m-d h:i:s")]);

			$rollback = $this->db->rollback();
			$this->assertTrue($rollback);

			$latestMovieIdAfterTransaction = $this->db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;

			$this->assertEquals($latestMovieIdBeforeTransaction, $latestMovieIdAfterTransaction);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSimpleQueryInterpolation() {
			$queryInterpolatedCorrect = "SELECT foo FROM bar WHERE baz = 'something'";
			$queryInterpolatedFromMethod = $this->db->interpolateQuery("SELECT foo FROM bar WHERE baz = :smth", ["smth" => "something"]);

			$this->assertEquals($queryInterpolatedCorrect, $queryInterpolatedFromMethod);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testQueryInterpolateIN() {
			$correctQuery = "SELECT foo FROM bar WHERE baz IN ('this','or','that')";
			$queryInterpolated = $this->db->interpolateQuery("SELECT foo FROM bar WHERE baz IN :arr", ["arr" => ["this", "or", "that"]]);
			$this->assertEquals($correctQuery, $queryInterpolated);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testCommitInsert() {
			$newMovieName = "boring thing";

			$start = $this->db->transaction();
			$this->assertTrue($start);

			$latestMovieNameBeforeCommit = $this->db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;
			$insertId =$this->db->insert("movies", ["movie_name" => $newMovieName]);

			$commit = $this->db->commit();
			$this->assertTrue($commit);

			$latestMovieNameAfterCommit = $this->db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;

			$this->assertNotEquals($latestMovieNameBeforeCommit, $latestMovieNameAfterCommit);

			$this->idsToDeleteInOurMovies[] = $insertId;
		}

		/**
		* @author Allan Thue Rehhoff
		* @todo Create a more thorough test.
		* Note: ->fetchCell(); appears to have a problem with SQL functions in $column parameter.
		*/
		public function testAgainstSqlInjection() {
			try {
				$this->db->select("movies", ["mid" => "' --"]);
				$this->db->query("INSERT INTO `movies` (movie_name, added) VALUES (:movie_name, :date)", ["movie_name" => "'", "date" => date("Y-m-d h:i:s")]);
				$insertId = $this->db->lastInsertId();
				$this->db->query("UPDATE `movies` SET movie_name = :newname WHERE mid = :mid", ["newname" => "-- '", "mid" => $insertId]);
				$this->idsToDeleteInOurMovies[] = $insertId;
			} catch(Exception $e) {
				$this->fail("SQL injection prevention test failed (shame on me): ".$e->getMessage());
			}
		}
	}
?>