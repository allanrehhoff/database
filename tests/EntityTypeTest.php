<?php
	/**
	* Tests Entity class against various common use cases
	*/
	class EntityTypeTest extends PHPUnit\Framework\TestCase {
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

		/**
		* Test instance works as expected
		*/
		public function testInstanceIsEntity() {
			$this->assertInstanceOf(Database\EntityType::class, new Database\EntityType());
		}

		public function testNewInstance() {
			$this->assertInstanceOf(Database\EntityType::class, Database\EntityType::new());
		}

		/**
		 * Test we can load an EntityType using the ::from(); method
		 */
		public function testLoadingWithFromMethod() {
			$entity = new Database\EntityType();
			$entity->set([
				"datetime_col" => date("Y-m-d H:i:s"),
				"varchar_col" => "Yoda the great",
				"text_col" => "Fear is the path to the dark side."
			]);
			$entity->save();

			$loadedEntity = Database\EntityType::from($entity->id());

			$this->assertInstanceOf(Database\EntityType::class, $loadedEntity);
			$this->assertTrue($loadedEntity->exists());
			$this->assertEquals("Fear is the path to the dark side.", $loadedEntity->get("text_col"));
		}

		/**
		* Test we're able to set and insert data, and able to read insert_id independent of EntityType
		*/
		public function testGetLastInsertIdAfterInsertEntityType() {
			$entity = new Database\EntityType();
			$entity->set( [
				"datetime_col" => date("Y-m-d H:i:s"),
				"varchar_col" => "Lorem ipsum dolor sit amet",
				"text_col" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum."
			] );
			$entity->save();

			$this->assertGreaterThan(0, self::$db->lastInsertId());
		}

		/**
		* Test loading a single entity works
		* @depends testGetLastInsertIdAfterInsertEntityType
		*/
		public function testStaticLoadSingleEntity() {
			$entity = new Database\EntityType();
			$entity->set([
				"varchar_col" => "somevalue",
				"datetime_col" => date("Y-m-d h:i:s"),
				"text_col" => "Lorem ipsum dolor sit amet"
			]);
			$entity->save();

			$testData = self::$db->fetchRow("test_table", ["varchar_col" => "somevalue"]);

			$entity = Database\EntityType::load($testData);
			$this->assertInstanceOf(Database\EntityType::class, $entity);
		}

		/**
		* Inserts a new entity and loads it, validates the data integrity
		*/
		public function testInsertAndLoadEntity() {
			$staticValue = "not_changed";

			$varcharColValue = "Danes are --' wierd æøå!#<";
			$textColValue = "At this point I should really start considering going to bed.. it's way past 22:00";

			$integrity = md5($staticValue.$varcharColValue.$textColValue);

			$entity = Database\EntityType::new();
			$entity->set([
				"varchar_col" => $varcharColValue,
				"text_col" => $textColValue,
				"datetime_col" => date("Y-m-d h:i:s"),
			]);
			$insert_id = $entity->save();

			$this->assertEquals($insert_id, self::$db->lastInsertId());
			$loadedEntity = Database\EntityType::from($insert_id);

			$this->assertNotEmpty($loadedEntity);

			$loadedIntegrity = md5($staticValue.$loadedEntity->varchar_col.$loadedEntity->text_col);
			$this->assertEquals($integrity, $loadedIntegrity);
		}

		/**
		* Inserts an entity and make sure it can be deleted.
		* Hopefully this should only delete a single row
		*/
		public function testInsertAndDelete() {
			$insert = new Database\EntityType();
			$insert->set([
				"varchar_col" => "Lorem ipsum dolor sit amet",
				"text_col" => "some other valua",
				"datetime_col" => date("Y-m-d h:i:s"),
			])->save();
			$insert_id = $insert->id();

			$loaded = Database\EntityType::from($insert_id);
			$this->assertNotEmpty($loaded);

			$this->assertEquals(1, $loaded->delete());
		}

		public function testEntityTypeSearch() {
			for ($i = 0; $i < 5; $i++) { 
				$randomValue = "phpunittest_".bin2hex(random_bytes(8));
				
				$insert = new Database\EntityType();
				$insert->set([
					"varchar_col" => $randomValue,
					"text_col" => "some other value",
					"datetime_col" => date("Y-m-d h:i:s"),
				])->save();
			}

			$result = Database\EntityType::search([
				"varchar_col LIKE :randomValue"
			], [
				"randomValue" => "phpunittest_%"
			]);

			$this->assertEquals(count($result), 5);

			$this->assertInstanceOf(Database\Collection::class, $result);

			$this->assertInstanceOf(Database\EntityType::class, $result->getFirst());
		}
	}