<?php
	/**
	* Quickly test most frequent use cases for the DatabaseConnection() class
	* @todo Add the following queries to be tested against.
	* @author Allan Thue Rehhoff
	*/
	class DatabaseConnectionTest extends PHPUnit\Framework\TestCase {
		private $db;
		private $idsToDeleteInOurMovies = [];
		private $initialAutoIncrementValue;

		/**
		* Sets up the required database connection
		* @author Allan Thue Rehhoff
		*/
		public function setUp() {
			try {
				$this->db = new \Database\Connection(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			} catch(Exception $e) {
				$this->fail($e->getMessage());
			}
		}

		/**
		* Cleans up the table after testing, since i dont want the table we are testing against to clutter up with random crap.
		* @author Allan Thue Rehhoff
		*/
		public function tearDown() {
			
			
			$this->db->query("ALTER TABLE movies AUTO_INCREMENT = 0");
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSingletonIsInstanceOfDatabaseConnection() {
			$this->assertInstanceOf("Database\Connection", Database\Connection::getInstance());
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
		public function testUpdateStatement() {
			$this->db->update("movies", ["movie_name" => time()], ["mid" => 1]);

			$rowcount = $this->db->update("movies", ["movie_name" => "Star wars"], ["mid" => 1]);
			$this->assertEquals($rowcount, 1);
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
			// Rollbacks doesn't work with MyISAM engines..
			$this->db->query("ALTER TABLE movies ENGINE = InnoDB");
			//$checkMyIsam = $this->db->fetchField("information_schema.tables", "ENGINE", ["TABLE_NAME" => "movies"]);

			//if(strtolower($checkMyIsam) == "myisam") {
			//}

			$latestMovieIdBeforeTransaction = $this->db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;
			$start = $this->db->transaction();
			$this->assertTrue($start);

			$this->db->insert("movies", ["movie_name" => "New movie 2", "added" => date("Y-m-d h:i:s")]);

			$rollback = $this->db->rollback();
			$this->assertTrue($rollback);

			$latestMovieIdAfterTransaction = $this->db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;
			$this->assertEquals($latestMovieIdBeforeTransaction, $latestMovieIdAfterTransaction);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testCommitInsert() {
			$newMovieName = "boring thing";

			$start = $this->db->transaction();
			$this->assertTrue($start);

			$latestMovieNameBeforeCommit = $this->db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;
			$insertId = $this->db->insert("movies", ["movie_name" => $newMovieName]);

			$commit = $this->db->commit();
			$this->assertTrue($commit);

			$latestMovieNameAfterCommit = $this->db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;

			$this->assertNotEquals($latestMovieNameBeforeCommit, $latestMovieNameAfterCommit);

			$this->idsToDeleteInOurMovies[] = $insertId;
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSingletonCanDoQuery() {
			$sdb = Database\Connection::getInstance();
			$this->assertInstanceOf("Database\Connection", $sdb);
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
			$res = $this->db->select("movies", ["mid" => 1]);
			$this->assertInternalType("array", $res);
			$this->assertNotEmpty($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectSingleRowFromParameters() {
			$res = $this->db->fetchRow("movies", ["mid" => 1, "movie_name" => "Star wars"]);
			$this->assertInternalType("object", $res);

			$res = $this->db->fetchRow("movies", ["mid" => time()]);
			$this->assertFalse($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testFetchSingleCellValueFromParameters() {
			$res = $this->db->fetchCell("movies", "movie_name", ["mid" => 1]);
			$this->assertEquals("Star wars", $res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testFetchColumn() {
			$res1 = $this->db->query("SELECT movie_name FROM movies WHERE mid in :mid", ["mid" => [1]])->fetchCol();
			$this->assertNotEmpty($res1);

			$res2 = $this->db->fetchCol("movies", "movie_name", ["mid" => 1]);
			$this->assertNotEmpty($res2);

			$this->assertEquals($res1, $res2);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSimpleQueryDebugging() {
			$queryInterpolatedCorrect = "SELECT foo FROM bar WHERE baz = 'something'";
			$queryInterpolatedFromMethod = $this->db->debugQuery("SELECT foo FROM bar WHERE baz = :smth", ["smth" => "something"]);

			$this->assertEquals($queryInterpolatedCorrect, $queryInterpolatedFromMethod);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testDebugQueryUsingInKeyword() {
			$correctQuery = "SELECT foo FROM bar WHERE baz IN ('this', 'or', 'that')";
			$queryInterpolated = $this->db->debugQuery("SELECT foo FROM bar WHERE baz IN :arr", ["arr" => ["this", "or", "that"]]);
			$this->assertEquals($correctQuery, $queryInterpolated);
		}
	}
?>