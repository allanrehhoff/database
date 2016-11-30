<?php
	/**
	* Tests Entity class against various common use cases
	*/
	class EntityTypeTest extends PHPUnit_Framework_TestCase {
		public function setUp() {
			try {
				$this->db = new DatabaseConnection(DB_HOST, DB_USER, DB_PASS, EntityTypeTestTable);
			} catch(Exception $e) {
				$this->fail("Unable to setup tests, perhaps you forgot to configure database credentials.");
			}
		}

		public function tearDown() {
			db()->query("TRUNCATE test_table");
		}

		/**
		* Test instance works as expected
		* @author Allan Thue Rehhoff
		*/
		public function testInstanceIsEntity() {
			$entity = new EntityType();
			$this->assertInstanceOf("EntityType", $entity);
		}

		/**
		* Test we're able to set and insert data, and able to read insert_id independent of EntityType
		* @author Allan Thue Rehhoff
		*/
		public function testGetLastInsertIdAfterInsertEntityType() {
			$entity = new EntityType();
			$entity->set( [
				"datetime_col" => date("Y-m-d H:i:s"),
				"varchar_col" => "Lorem ipsum dolor sit amet",
				"text_col" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum."
			] );
			$entity->save();

			$this->assertGreaterThan(0, db()->lastInsertId());
		}

		/**
		* Test loading a single entity works
		* @depends testGetLastInsertIdAfterInsertEntityType
		* @author Allan Thue Rehhoff
		*/
		public function testStaticLoadSingleEntity() {
			$entity = new EntityType();
			$entity->set(["varchar_col" => "somevalue", "text_col" => "Lorem ipsum dolor sit amet"]);
			$entity->save();
			
			$testId = db()->fetchField("test_table", "test_id", ["varchar_col" => "somevalue"]);

			$entity = EntityType::load($testId);
			$this->assertInstanceOf("EntityType", $entity);
		}

		/**
		* Inserts a new entity and loads it, validates the data integrity
		* @author Allan Thue Rehhoff
		*/
		public function testInsertAndLoadEntity() {
			$staticValue = "not_changed";

			$varcharColValue = "Danes are --' wierd æøå!#<";
			$textColValue = "At this point I should really start considering going to bed.. it's way past 22:00";

			$integrity = md5($staticValue.$varcharColValue.$textColValue);

			$entity = new EntityType();
			$entity->set(["varchar_col" => $varcharColValue, "text_col" => $textColValue]);
			$insert_id = $entity->save();

			$this->assertEquals($insert_id, db()->lastInsertId());
			$loadedEntity = new EntityType($insert_id);

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
				$entity = new EntityType();
				$entity->set(["varchar_col" => "value".$i, "text_col" => "longvalue ".$i]);
				$ids[] = $entity->save();
			}

			$entities = EntityType::load($ids);
			$this->assertEquals(count($entities), $numberItems);
		}

		/**
		* Inserts an entity and make sure it can be deleted.
		* Hopefully this should only delete a single row
		* @author Allan Thue Rehhoff
		*/
		public function testInsertAndDelete() {
			$insert = new EntityType();
			$insert->set(["varchar_col" => "Lorem ipsum dolor sit amet"])->save();
			$insert_id = $insert->id();

			$loaded = EntityType::load($insert_id);
			$this->assertNotEmpty($loaded);

			$this->assertEquals(1, $loaded->delete());
		}
	}