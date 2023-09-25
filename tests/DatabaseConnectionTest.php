<?php
	/**
	* Quickly test most frequent use cases for the DatabaseConnection() class
	*/
	class DatabaseConnectionTest extends PHPUnit\Framework\TestCase {
		private static $db;

		/**
		* Sets up the required database connection
		*/
		public static function setUpBeforeClass() :void {
			self::$db = new \Database\Connection(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}

		/**
		* Cleans up the table after testing, since i dont want the table we are testing against to clutter up with random crap.
		*/
		public static function tearDownAfterClass() :void {
			self::$db->query("ALTER TABLE movies AUTO_INCREMENT = 0");
			self::$db->delete("movies");
			self::$db->delete("test_table");
		}

		public function testSingletonIsInstanceOfDatabaseConnection() {
			$this->assertInstanceOf("Database\Connection", Database\Connection::getInstance());
		}

		public function testInstanceOfDatabaseStatement() {
			$sql = "SELECT * FROM movies WHERE movie_name = :movie_name";
			$args = ["movie_name" => "test"];

			$statement = self::$db->prepare($sql, $args);

			$this->assertInstanceOf("Database\Statement", $statement);
		}

		public function testInsertSingleRow() {
			$insertId = self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
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

			$numInserted = self::$db->insertMultiple("movies", $data)->rowCount();

			$this->assertEquals(count($data), $numInserted);
		}

		public function testReplaceRowWithExistingPrimaryKey() {
			$insertId = self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);

			self::$db->replace("movies", ["mid" => $insertId, "movie_name" => "test2", "added" => date("Y-m-d h:i:s")]);
			$movieName = self::$db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals("test2", $movieName);
		}

		public function testInsertAndDeleteRow() {
			$insertId = self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$rowsAffected = self::$db->delete("movies", ["mid" => $insertId]);
			$this->assertGreaterThan(0, $rowsAffected);
		}
		
		public function testUpdateStatement() {
			$insertId = self::$db->insert("movies", ["movie_name" => "test name to be updated", "added" => date("Y-m-d h:i:s")]);

			self::$db->update("movies", ["movie_name" => time()], ["mid" => $insertId]);

			$rowcount = self::$db->update("movies", ["movie_name" => "Star wars"], ["mid" => $insertId]);
			$this->assertEquals(1, $rowcount);
		}

		public function testInsertAndUpdateSingleRow() {
			$newMovieName = "Exciting new movie";

			$insertId = self::$db->insert("movies", ["movie_name" => "Old boring movie.", "added" => date("Y-m-d h:i:s")]);

			$this->assertIsInt($insertId);

			$rowsAffected = self::$db->update("movies", ["movie_name" => $newMovieName], ["mid" => $insertId]);
			$this->assertGreaterThan(0, $rowsAffected);

			$updatedRowMovieName = self::$db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals($newMovieName, $updatedRowMovieName);
		}

		public function testRollbackInsert() {
			// Rollbacks doesn't work with MyISAM engines..
			self::$db->query("ALTER TABLE movies ENGINE = InnoDB");

			self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);

			$latestMovieIdBeforeTransaction = self::$db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;
			$start = self::$db->transaction();
			$this->assertTrue($start);

			self::$db->insert("movies", ["movie_name" => "New movie 2", "added" => date("Y-m-d h:i:s")]);

			$rollback = self::$db->rollback();
			$this->assertTrue($rollback);

			$latestMovieIdAfterTransaction = self::$db->query("SELECT mid FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->mid;
			$this->assertEquals($latestMovieIdBeforeTransaction, $latestMovieIdAfterTransaction);
		}

		public function testCommitInsert() {
			$newMovieName = "boring thing";

			self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);

			$start = self::$db->transaction();
			$this->assertTrue($start);

			$latestMovieNameBeforeCommit = self::$db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;
			$insertId = self::$db->insert("movies", ["movie_name" => $newMovieName, "added" => date("Y-m-d h:i:s")]);

			$commit = self::$db->commit();
			$this->assertTrue($commit);

			$latestMovieNameAfterCommit = self::$db->query("SELECT movie_name FROM movies ORDER BY mid DESC LIMIT 1")->fetch()->movie_name;

			$this->assertNotEquals($latestMovieNameBeforeCommit, $latestMovieNameAfterCommit);
		}

		public function testOnDuplicateKeyUpdate() {
			$initVal = bin2hex(random_bytes(16));

			self::$db->upsert("test_table", [
				"test_id" => 110,
				"varchar_col" => $initVal,
				"text_col" => "lorem ipsum",
				"datetime_col" => date("Y-m-d h:i:s")
			]);

			self::$db->upsert("test_table", [
				"test_id" => 110,
				"varchar_col" => "newVal",
				"text_col" => "lorem ipsum",
				"datetime_col" => date("Y-m-d h:i:s")
			]);
			
			$newVal = self::$db->fetchField("test_table", "varchar_col", ["test_id" => 110]);

			$this->assertNotEquals($newVal, $initVal);
			$this->assertEquals($newVal, "newVal");
		}

		public function testSelectNullValue() {
			self::$db->delete("test_table", ["varchar_col" => null]);

			foreach(["test1", "test2", "test3", null, null] as $val) {
				self::$db->insert("test_table", [
					"varchar_col" => $val,
					"text_col" => "lorem ipsum",
					"datetime_col" => date("Y-m-d h:i:s")
				]);
			}

			$res = self::$db->count("test_table", ["varchar_col" => null]);

			$this->assertEquals($res, 2);
		}

		public function testSelectIntegers() {
			self::$db->delete("test_table", ["varchar_col" => 2]);

			foreach([2, 2, 2, null, null, "string", "anotherstring"] as $val) {
				self::$db->insert("test_table", [
					"varchar_col" => $val,
					"text_col" => "lorem ipsum",
					"datetime_col" => date("Y-m-d h:i:s"),
				]);
			}

			$res = self::$db->count("test_table", ["varchar_col" => 2]);

			$this->assertEquals($res, 3);
		}

		public function testSelectBooleans() {
			self::$db->delete("test_table", ["varchar_col" => [true, false]]);

			foreach([true, false] as $val) {
				self::$db->insert("test_table", [
					"varchar_col" => $val,
					"text_col" => "lorem ipsum",
					"datetime_col" => date("Y-m-d h:i:s"),
				]);
			}

			$true = self::$db->count("test_table", ["varchar_col" => true]);
			$false = self::$db->count("test_table", ["varchar_col" => false]);

			$this->assertEquals($true, 1);
			$this->assertEquals($false, 1);
		}

		public function testSingletonCanDoQuery() {
			$sdb = Database\Connection::getInstance();
			$this->assertInstanceOf("Database\Connection", $sdb);
			$res = $sdb->query("SELECT mid FROM movies LIMIT 1")->fetchAll();

			$this->assertEquals(1, count($res));
		}

		public function testSelectQueryWithoutParameter() {
			self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$res = self::$db->query("SELECT mid FROM movies")->fetchAll();
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

			$numInserted = self::$db->insertMultiple("movies", $data)->rowCount();
			$res = self::$db->query("SELECT mid FROM movies WHERE mid > :num", ["num" => 1])->fetchAll();
			$this->assertNotEmpty($res);
		}

		public function testSelectQueryFromWrapperMethod() {
			self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$res = self::$db->select("movies");
			$this->assertNotEmpty($res);	
		}

		public function testSelectQueryMethodWithParameters() {
			self::$db->insert("movies", ["movie_name" => "test", "added" => date("Y-m-d h:i:s")]);
			$res = self::$db->select("movies", ["movie_name" => "test"]);
			$this->assertNotEmpty($res);
		}

		public function testSelectQueryMethodWithNegatedParameters() {
			self::$db->delete("movies");

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

			$numInserted = self::$db->insertMultiple("movies", $data)->rowCount();

			$res = self::$db->search("movies", [
				"movie_name NOT IN :movies"
			], [
				"movies" => ["Avatar", "Avatar 2"]
			]);
			$this->assertCount(1, $res);
		}

		public function testSelectSingleRowFromParameters() {
			self::$db->insert("movies", ["movie_name" => "Star wars", "added" => date("Y-m-d h:i:s")]);

			$res = self::$db->fetchRow("movies", ["movie_name" => "Star wars"]);
			$this->assertIsObject($res);

			$res = self::$db->fetchRow("movies", ["mid" => time()]);
			$this->assertNull($res);
		}

		public function testFetchSingleCellValueFromParameters() {
			$movieName = "Star wars fallen order";
			$insertId = self::$db->insert("movies", ["movie_name" => $movieName, "added" => date("Y-m-d h:i:s")]);
			$res = self::$db->fetchCell("movies", "movie_name", ["mid" => $insertId]);
			$this->assertEquals($movieName, $res);
		}

		public function testFetchCellReturnsNullOnEmpty() {
			$res = self::$db->fetchCell("movies", "movie_name", ["mid" => "-1"]);
			$this->assertNull($res);
		}

		public function testFetchColumn() {
			$insertId = self::$db->insert("movies", ["movie_name" => "Star wars new war", "added" => date("Y-m-d h:i:s")]);

			$res1 = self::$db->query("SELECT movie_name FROM movies WHERE mid in :mid", ["mid" => [$insertId]])->fetchCol();
			$this->assertNotEmpty($res1);

			$res2 = self::$db->fetchCol("movies", "movie_name", ["mid" => $insertId]);
			$this->assertNotEmpty($res2);

			$this->assertEquals($res1, $res2);
		}

		public function testSimpleQueryDebugging() {
			$queryInterpolatedCorrect = "SELECT foo FROM bar WHERE baz = 'something'";
			$queryInterpolatedFromMethod = self::$db->debugQuery("SELECT foo FROM bar WHERE baz = :smth", ["smth" => "something"]);

			$this->assertEquals($queryInterpolatedCorrect, $queryInterpolatedFromMethod);
		}

		public function testDebugQueryUsingInKeyword() {
			$correctQuery = "SELECT foo FROM bar WHERE baz IN ('this', 'or', 'that')";
			$queryInterpolated = self::$db->debugQuery("SELECT foo FROM bar WHERE baz IN :arr", ["arr" => ["this", "or", "that"]]);
			$this->assertEquals($correctQuery, $queryInterpolated);
		}
	}
?>