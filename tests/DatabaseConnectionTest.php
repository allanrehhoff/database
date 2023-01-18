<?php
	/**
	* Quickly test most frequent use cases for the DatabaseConnection() class
	* @todo Add the following queries to be tested against.
	*/
	class DatabaseConnectionTest extends PHPUnit\Framework\TestCase {
		private $db;
		private $idsToDeleteInOurMovies = [];
		private $initialAutoIncrementValue;

		/**
		* Sets up the required database connection
		* @author Allan Thue Rehhoff
		*/
		public function setUp() :void {
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
		public function tearDown() :void {
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
			$this->assertIsInt($insertId);

			$this->idsToDeleteInOurMovies[] = $insertId;
		}

		public function testInsertMultipleRows() {
			$date = date("Y-m-d h:i:s");

			$data = [
				[
					"movie_name" => "Movie 1",
					"added" => $date,
				],
				[
					"movie_name" => "Movie 2",
					"added" => $date,
				],
				[
					"movie_name" => "Movie 3",
					"added" => $date,
				]
			];

			$numInserted = $this->db->insertMultiple("movies", $data)->rowCount();

			$this->assertEquals(count($data), $numInserted);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testReplaceRowWithExistingPrimaryKey() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);

			$this->db->replace("movies", ["mid" => $insertId, "movie_name" => "test2", "added" => date("Y-m-d h:i:s")]);
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
			$this->db->update("movies", ["movie_name" => time()], ["mid" => 2]);

			$rowcount = $this->db->update("movies", ["movie_name" => "Star wars"], ["mid" => 2]);
			$this->assertEquals(1, $rowcount);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testInsertAndUpdateSingleRow() {
			$newMovieName = "Exciting new movie";

			$insertId = $this->db->insert("movies", ["movie_name" => "Old boring movie.", "added" => date("Y-m-d h:i:s")]);

			$this->assertIsInt($insertId);

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
			$insertId = $this->db->insert("movies", ["movie_name" => $newMovieName, "added" => date("Y-m-d h:i:s")]);

			$commit = $this->db->commit();
			$this->assertTrue($commit);

			$latestMovieNameAfterCommit = $this->db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;

			$this->assertNotEquals($latestMovieNameBeforeCommit, $latestMovieNameAfterCommit);

			$this->idsToDeleteInOurMovies[] = $insertId;
		}

		public function testOnDuplicateKeyUpdate() {
			$initVal = bin2hex(random_bytes(16));

			$this->db->upsert("test_table", [
				"test_id" => 110,
				"varchar_col" => $initVal,
				"text_col" => "lorem ipsum",
				"datetime_col" => date("Y-m-d h:i:s")
			]);

			$this->db->upsert("test_table", [
				"test_id" => 110,
				"varchar_col" => "newVal",
				"text_col" => "lorem ipsum",
				"datetime_col" => date("Y-m-d h:i:s")
			]);
			
			$newVal = $this->db->fetchField("test_table", "varchar_col", ["test_id" => 110]);

			$this->assertNotEquals($newVal, $initVal);
			$this->assertEquals($newVal, "newVal");
		}

		/**
		 * @author Allan Thue Rehhoff
		 */
		public function testSelectNullValue() {
			$this->db->delete("test_table", ["varchar_col" => null]);

			foreach(["test1", "test2", "test3", null, null] as $val) {
				$this->db->insert("test_table", [
					"varchar_col" => $val,
					"text_col" => "lorem ipsum",
					"datetime_col" => date("Y-m-d h:i:s")
				]);
			}

			$res = $this->db->count("test_table", "test_id", ["varchar_col" => null]);

			$this->assertEquals($res, 2);
		}

		/**
		 * @author Allan Thue Rehhoff
		 */
		public function testSelectIntegers() {
			$this->db->delete("test_table", ["varchar_col" => 2]);

			foreach([2, 2, 2, null, null, "string", "anotherstring"] as $val) {
				$this->db->insert("test_table", [
					"varchar_col" => $val,
					"text_col" => "lorem ipsum",
					"datetime_col" => date("Y-m-d h:i:s"),
				]);
			}

			$res = $this->db->count("test_table", "test_id", ["varchar_col" => 2]);

			$this->assertEquals($res, 3);
		}

		/**
		 * @author Allan Thue Rehhoff
		 */
		public function testSelectBooleans() {
			$this->db->delete("test_table", ["varchar_col" => [true, false]]);

			foreach([true, false] as $val) {
				$this->db->insert("test_table", [
					"varchar_col" => $val,
					"text_col" => "lorem ipsum",
					"datetime_col" => date("Y-m-d h:i:s"),
				]);
			}

			$true = $this->db->count("test_table", "test_id", ["varchar_col" => true]);
			$false = $this->db->count("test_table", "test_id", ["varchar_col" => false]);

			$this->assertEquals($true, 1);
			$this->assertEquals($false, 1);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSingletonCanDoQuery() {
			$sdb = Database\Connection::getInstance();
			$this->assertInstanceOf("Database\Connection", $sdb);
			$res = $sdb->query("SELECT mid FROM movies LIMIT 1")->fetchAll();
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectQueryWithoutParameter() {
			$res = $this->db->query("SELECT mid FROM movies")->fetchAll();
			$this->assertNotEmpty($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectFromParameterizedQuery() {
			$res = $this->db->query("SELECT mid FROM movies WHERE mid > :num", ["num" => 1])->fetchAll();
			$this->assertNotEmpty($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectQueryFromWrapperMethod() {
			$res = $this->db->select("movies");
			$this->assertNotEmpty($res);	
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectQueryMethodWithParameters() {
			$res = $this->db->select("movies", ["mid" => 1]);
			$this->assertNotEmpty($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testSelectSingleRowFromParameters() {
			$res = $this->db->fetchRow("movies", ["movie_name" => "Star wars"]);
			$this->assertIsObject($res);

			$res = $this->db->fetchRow("movies", ["mid" => time()]);
			$this->assertNull($res);
		}

		/**
		* @author Allan Thue Rehhoff
		*/
		public function testFetchSingleCellValueFromParameters() {
			$res = $this->db->fetchCell("movies", "movie_name", ["mid" => 1]);
			$this->assertEquals("test", $res);
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