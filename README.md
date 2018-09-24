#DatabaseConnection class#
_By Allan Rehhoff_

This repository contains a class for querying your database in an efficient way and object oriented way.  
It also features a few classes to speed up CMS / CRUD development, and abstracts away your database queries.  

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
 
##Installing##

Install with composer: ```composer require rehhoff/database```

Install manually: ```<?php require "path/to/vendor/dir/database/autoload.php"; ?>```

This tool is licensed under [ WTFPL ](http://www.wtfpl.net/)

##Using the Database class##
This section assumes you have basic knowledge of PDO.  
(I haven't yet had time to properly test this documentation, as though it may appear outdated, use at own risk.)  
The \Database\Connection(); class wraps around PHP's PDO, so you are able to call all of the built-in PDO functions on the instantiated object as you normally would.  
With the exception of the \Database\Connection::query(); method, this has been overloaded to a more convenient way and usage, such that it supports all the below methods.  

1. **\Database\Connection::query()**  

If all you want to do, is a simple parameterized query, this line is the one you're looking for.  
This will return a custom statement class of \Database\Statement, which also extends the default PDOStatement class.  

```
<?php \Database\Connection::getInstance()->query("UPDATE animals SET `extinct` = :value WHERE name = :name", ["value" => true, "name" => "Asian Rhino"]); ?>
```   

2. **\Database\Connection::select()**  

Simple queries with a return value will be fetched as objects, The second argument should be an array of key-value pairs.
Second argument for methods, insert(), update() and delete() is always the WHERE clause.  

The following queries:  

```
<?php \Database\Connection::getInstance()->select("animals"); ?>

<?php \Database\Connection::getInstance()->select("animals", ["name" => "Asian Rhino"]]); ?>
```

Will both return an array of objects, if the given criterias matched any rows, otherwise the resultset is false.

This method also supports IN-like requests.

```
<?php \Database\Connection::getInstance()->select("animals", ["name" => ["Asian Rhino", "Platypus"]]); ?>
```
  
```
<?php \Database\Connection::getInstance()->update("animals", ["extinct" => true], ["name" => "Asian Rhino"]); ?>
```

###A helping hand (wrapper and helper functions)###
There's a slew of available helper functions that you can use to fetch resultsets in various ways, instead of doping the query, and then call fetch.

For example:  
```
<?php \Database\Connection::getInstance()->query("SELECT name FROM animals WHERE extinct = :extinct", ["extinct" => true)->fetchCol(); ?>
```

Could be rewritten to:
```
<?php \Database\Connection::getInstance()->fetchCol("animals", "name", ["extinct" => true]); ?>
```

Other helper functions include
- \Database\Connection::fetchCell();  
- \Database\Connection::fetchField(); (wrapper for fetchCell();)  
- \Database\Connection::fetchRow();  

The helping hand, is not limited to selective queries only.
- \Database\Connection::delete();  
- \Database\Connection::update();  
- \Database\Connection::replace();  
- \Database\Connection::transaction();  

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