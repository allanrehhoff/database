<?php
	class EntityType extends Entity {
		protected function getKeyField() { return "test_id"; }
		protected function getTableName() { return "test_table"; }
	}