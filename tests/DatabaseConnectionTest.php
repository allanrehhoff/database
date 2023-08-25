<?php
	/**
	* Quickly test most frequent use cases for the DatabaseConnection() class
	*/
	class DatabaseConnectionTest extends PHPUnit\Framework\TestCase {
		private $db;

		/**
		* Sets up the required database connection
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
		*/
		public function tearDown() :void {
			$this->db->query("ALTER TABLE movies AUTO_INCREMENT = 0");
			$this->db->delete("movies");
			$this->db->delete("test_table");
		}

		public function testSingletonIsInstanceOfDatabaseConnection() {
			$this->assertInstanceOf("Database\Connection", Database\Connection::getInstance());
		}

		public function testInstanceOfDatabaseStatement() {
			$sql = "SELECT * FROM movies WHERE movie_name = :movie_name";
			$args = ["movie_name" => "test"];

			$statement = $this->db->prepare($sql, $args);

			$this->assertInstanceOf("Database\Statement", $statement);
		}

		public function testInsertSingleRow() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$this->assertIsInt($insertId);
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

		public function testReplaceRowWithExistingPrimaryKey() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);

			$this->db->replace("movies", ["mid" => $insertId, "movie_name" => "test2", "added" => date("Y-m-d h:i:s")]);
			$movieName = $this->db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals("test2", $movieName);
		}

		public function testInsertAndDeleteRow() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$rowsAffected = $this->db->delete("movies", ["mid" => $insertId]);
			$this->assertGreaterThan(0, $rowsAffected);
		}
		
		public function testUpdateStatement() {
			$insertId = $this->db->insert("movies", ["movie_name" => "test name to be updated", "added" => date("Y-m-d h:i:s")]);

			$this->db->update("movies", ["movie_name" => time()], ["mid" => $insertId]);

			$rowcount = $this->db->update("movies", ["movie_name" => "Star wars"], ["mid" => $insertId]);
			$this->assertEquals(1, $rowcount);
		}

		public function testInsertAndUpdateSingleRow() {
			$newMovieName = "Exciting new movie";

			$insertId = $this->db->insert("movies", ["movie_name" => "Old boring movie.", "added" => date("Y-m-d h:i:s")]);

			$this->assertIsInt($insertId);

			$rowsAffected = $this->db->update("movies", ["movie_name" => $newMovieName], ["mid" => $insertId]);
			$this->assertGreaterThan(0, $rowsAffected);

			$updatedRowMovieName = $this->db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals($newMovieName, $updatedRowMovieName);
		}

		public function testRollbackInsert() {
			// Rollbacks doesn't work with MyISAM engines..
			$this->db->query("ALTER TABLE movies ENGINE = InnoDB");

			$this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);

			$latestMovieIdBeforeTransaction = $this->db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;
			$start = $this->db->transaction();
			$this->assertTrue($start);

			$this->db->insert("movies", ["movie_name" => "New movie 2", "added" => date("Y-m-d h:i:s")]);

			$rollback = $this->db->rollback();
			$this->assertTrue($rollback);

			$latestMovieIdAfterTransaction = $this->db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;
			$this->assertEquals($latestMovieIdBeforeTransaction, $latestMovieIdAfterTransaction);
		}

		public function testCommitInsert() {
			$newMovieName = "boring thing";

			$this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);

			$start = $this->db->transaction();
			$this->assertTrue($start);

			$latestMovieNameBeforeCommit = $this->db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;
			$insertId = $this->db->insert("movies", ["movie_name" => $newMovieName, "added" => date("Y-m-d h:i:s")]);

			$commit = $this->db->commit();
			$this->assertTrue($commit);

			$latestMovieNameAfterCommit = $this->db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;

			$this->assertNotEquals($latestMovieNameBeforeCommit, $latestMovieNameAfterCommit);
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

		public function testSingletonCanDoQuery() {
			$sdb = Database\Connection::getInstance();
			$this->assertInstanceOf("Database\Connection", $sdb);
			$res = $sdb->query("SELECT mid FROM movies LIMIT 1")->fetchAll();
		}

		public function testSelectQueryWithoutParameter() {
			$this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$res = $this->db->query("SELECT mid FROM movies")->fetchAll();
			$this->assertNotEmpty($res);
		}

		public function testSelectFromParameterizedQuery() {
			$data = [
				[
					"movie_name" => "Movie 1",
					"added" => date("Y-m-d H:i:s"),
				],
				[
					"movie_name" => "Movie 2",
					"added" => date("Y-m-d H:i:s"),
				]
			];

			$numInserted = $this->db->insertMultiple("movies", $data)->rowCount();
			$res = $this->db->query("SELECT mid FROM movies WHERE mid > :num", ["num" => 1])->fetchAll();
			$this->assertNotEmpty($res);
		}

		public function testSelectQueryFromWrapperMethod() {
			$this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$res = $this->db->select("movies");
			$this->assertNotEmpty($res);	
		}

		public function testSelectQueryMethodWithParameters() {
			$this->db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$res = $this->db->select("movies", ["movie_name" => "test"]);
			$this->assertNotEmpty($res);
		}

		public function testSelectQueryMethodWithNegatedParameters() {
			$this->db->delete("movies");

			$date = date("Y-m-d h:i:s");

			$data = [
				[
					"movie_name" => "Avatar",
					"added" => $date,
				],
				[
					"movie_name" => "Avatar 2",
					"added" => $date,
				],
				[
					"movie_name" => "Sharknado",
					"added" => $date,
				]
			];

			$numInserted = $this->db->insertMultiple("movies", $data)->rowCount();

			$res = $this->db->search("movies", [
				"movie_name NOT IN :movies"
			], [
				"movies" => ["Avatar", "Avatar 2"]
			]);
			$this->assertCount(1, $res);
		}

		public function testSelectSingleRowFromParameters() {
			$this->db->insert("movies", ["movie_name" => "Star wars", "added" => date("Y-m-d h:i:s")]);

			$res = $this->db->fetchRow("movies", ["movie_name" => "Star wars"]);
			$this->assertIsObject($res);

			$res = $this->db->fetchRow("movies", ["mid" => time()]);
			$this->assertNull($res);
		}

		public function testFetchSingleCellValueFromParameters() {
			$movieName = "Star wars fallen order";
			$insertId = $this->db->insert("movies", ["movie_name" => $movieName, "added" => date("Y-m-d h:i:s")]);
			$res = $this->db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals($movieName, $res);
		}

		public function testFetchCellReturnsNullOnEmpty() {
			$res = $this->db->fetchCell("movies", "movie_name", ["mid" => "-1"]);
			$this->assertNull($res);
		}

		public function testFetchColumn() {
			$insertId = $this->db->insert("movies", ["movie_name" => "Star wars new war", "added" => date("Y-m-d h:i:s")]);

			$res1 = $this->db->query("SELECT movie_name FROM movies WHERE mid in :mid", ["mid" => [$insertId]])->fetchCol();
			$this->assertNotEmpty($res1);

			$res2 = $this->db->fetchCol("movies", "movie_name", ["mid" => $insertId]);
			$this->assertNotEmpty($res2);

			$this->assertEquals($res1, $res2);
		}

		public function testSimpleQueryDebugging() {
			$queryInterpolatedCorrect = "SELECT foo FROM bar WHERE baz = 'something'";
			$queryInterpolatedFromMethod = $this->db->debugQuery("SELECT foo FROM bar WHERE baz = :smth", ["smth" => "something"]);

			$this->assertEquals($queryInterpolatedCorrect, $queryInterpolatedFromMethod);
		}

		public function testDebugQueryUsingInKeyword() {
			$correctQuery = "SELECT foo FROM bar WHERE baz IN ('this', 'or', 'that')";
			$queryInterpolated = $this->db->debugQuery("SELECT foo FROM bar WHERE baz IN :arr", ["arr" => ["this", "or", "that"]]);
			$this->assertEquals($correctQuery, $queryInterpolated);
		}
	}
?>