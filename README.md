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

##Using the Database class##
This section assumes you have basic knowledge of PDO.  
(I haven't yet had time to properly test this documentation, as though it may appear outdated, use at own risk.)

1. **\Database\Connection::getInstance()->query()**  

```
<?php \Database\Connection::getInstance()->query("UPDATE animals SET `extinct` = :value WHERE name = :name", ["value" => true, "name" => "Asian Rhino"]); ?>
```   

This could also be written as follows:  
```
<?php \Database\Connection::getInstance()->update("animals", ["extinct" => true], ["name" => "Asian Rhino"]); ?>
```

Queries with a return value will be fetched as objects, for instance:  
```
<?php \Database\Connection::getInstance()->select("animals"); ?>
```

Second argument for methods, insert(), update() and delete() is always the WHERE clause, use these do define what columns you want to be affected by the query.  
  
##Database Entities##
For easier data manipulation, data objects should extend the **\Database\Entity** class.  
Every class that extends **\Database\DBObject** must implement the following methods.  

- getTableName(); // Table in which this data object should store data.  
- getKeyField(); // The primary key of the table in which this object stores data.  

Every data object take an optional parameter [(int) primary_key] upon instantiating,  
identifying whether a new data object should be instantiated or an already existing row should be loaded from the table.  

If you wish to change data use the **->set(array('column' => 'value'));**  
This will allow you to call **->save();** on an object and thus saving the data to your database.  
The data object will be saved as a new row if the primary_key key parameter was not present upon instantiating. 