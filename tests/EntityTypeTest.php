<?php
	/**
	* Tests Entity class against various common use cases
	*/
	class EntityTypeTest extends PHPUnit\Framework\TestCase {
		public function setUp() :void {
			try {
				$this->db = new Database\Connection(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			} catch(Exception $e) {
				$this->fail("Unable to setup tests, perhaps you forgot to configure database credentials.");
			}
		}

		public function tearDown() :void {
			$this->db->query("TRUNCATE test_table");
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

			$loadedEntity = Database\EntityType::from("varchar_col", "Yoda the great");

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

			$this->assertGreaterThan(0, $this->db->lastInsertId());
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

			$testId = $this->db->fetchField("test_table", "test_id", ["varchar_col" => "somevalue"]);

			$entity = Database\EntityType::load($testId);
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

			$entity = new Database\EntityType();
			$entity->set([
				"varchar_col" => $varcharColValue,
				"text_col" => $textColValue,
				"datetime_col" => date("Y-m-d h:i:s"),
			]);
			$insert_id = $entity->save();

			$this->assertEquals($insert_id, $this->db->lastInsertId());
			$loadedEntity = new Database\EntityType($insert_id);

			$this->assertNotEmpty($loadedEntity);

			$loadedIntegrity = md5($staticValue.$loadedEntity->varchar_col.$loadedEntity->text_col);
			$this->assertEquals($integrity, $loadedIntegrity);
		}

		/**
		* Test we're able to load more than one entity at a time
		*/
		public function testLoadMultipleEntityTypes() {
			$ids = [];

			// Insert a bunch of test data
			$numberItems =  mt_rand(1, 5);
			for ($i=0; $i < $numberItems; $i++) { 
				$entity = new Database\EntityType();
				$entity->set([
					"varchar_col" => "value".$i,
					"text_col" => "longvalue ".$i,
					"datetime_col" => date("Y-m-d h:i:s")
				]);
				$ids[] = $entity->save();
			}

			$entities = Database\EntityType::load($ids);
			$this->assertEquals(count($entities), $numberItems);
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

			$loaded = Database\EntityType::load($insert_id);
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