<?php
	/**
	* This file is solely used for demonstation purposes.
	* Whenever you create a new CRUD'able instance that extends Entity
	* it must have at bare minimum the following structure.
	* Don't forget to set return values accordingly.
	*/
	class EntityType extends Entity {
		protected function getKeyField() { return "test_id"; }
		protected function getTableName() { return "test_table"; }
	}