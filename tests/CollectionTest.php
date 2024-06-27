<?php
	/**
	* Tests Entity class against various common use cases
	*/
	class CollectionTest extends PHPUnit\Framework\TestCase {
		private static $db;

		/**
		 * Sets up the required database connection
		 */
		public static function setUpBeforeClass() :void {
			self::$db = db();
		}

		/**
		 * Cleans up the table after testing, since i dont want the table we are testing against to clutter up with random crap.
		 */
		public static function tearDownAfterClass() :void {
			self::$db->delete("movies");
			self::$db->delete("test_table");
			self::$db->query("ALTER TABLE movies AUTO_INCREMENT = 0");
		}

		public function testGetColumnIsNotEmpty() {
			$insertId = self::$db->insert("movies", ["movie_name" => "Star wars new war", "added" => date("Y-m-d h:i:s")]);

			$res = self::$db->select("movies", ["mid" => [$insertId]])->getColumn("movie_name");

			$this->assertNotEmpty($res);
		}

		/**
		 * Test Collection::getColumn
		 */
		public function testGetColumnReturnsCollection() {
			$insertId = self::$db->insert("movies", ["movie_name" => "Star wars new war", "added" => date("Y-m-d h:i:s")]);

			$res = self::$db->select("movies", ["mid" => [$insertId]])->getColumn("movie_name");

			$this->assertInstanceOf(\Database\Collection::class, $res);
		}

		/**
		 * Test Collection::getFirst
		 */
		public function testGetFirst() {
			$insertId = self::$db->insert("movies", ["movie_name" => "Star wars new war", "added" => date("Y-m-d h:i:s")]);

			$res = self::$db->select("movies", ["mid" => [$insertId]]);

			$this->assertEquals($res->getFirst()->movie_name, "Star wars new war");
		}

		/**
		 * Test Collection::getColumn and Collection::getFirst
		 */
		public function testGetColumnAndFirst() {
			$insertId = self::$db->insert("movies", ["movie_name" => "Star wars new war", "added" => date("Y-m-d h:i:s")]);

			$res = self::$db->select("movies", ["mid" => [$insertId]]);

			$this->assertEquals($res->getColumn("movie_name")->getFirst(), $res->getFirst()->movie_name);
		}

		/**
		 * Test the getIterator method
		 *
		 * @return void
		 */
		public function testIterator() {
			$res = self::$db->query("SELECT * FROM movies")->getIterator();

			$this->assertInstanceOf(Database\Collection::class, $res);
		}
	}