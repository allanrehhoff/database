#DatabaseConnection class#
_By Allan Rehhoff_

This repository contains a class for querying your database in an efficient way and object oriented way.  
It also features a few classes to speed up CMS / CRUD development.  

Simply copy the EntityType class to a new file and rename it accordingly.

**File:** Animal.php  
```
<?php
	class Animal extends Entity {
		protected function getKeyField() { return "animal_id"; } // The column with your primary key index
		protected function getTableName() { return "animals"; } // Name of the table to work with

		/**
		* Develop whatever functions your might need below.
		*/
		public function myFunction() {

		}
	}
?> 
```

This tool is licensed under [ WTFPL ](http://www.wtfpl.net/)