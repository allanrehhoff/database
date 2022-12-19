# Database\Connection class #
_By Allan Rehhoff_

A library for querying your database in an easy-to-maintain objected oriented manner.  
It features a few classes to speed up CMS / MVC / API and general CRUD development, and abstracts away your database queries.  

If you're not much for reading documentation, this snippet is for you.  
Or simply scroll down to the examples.  
 
## Installing ##

Install manually: ```<?php require "path/to/vendor/dir/database/autoload.php"; ?>```

Probably also work with most autoloaders, if you simply copy the **Database/** directory from a release.  

This tool is licensed under [ WTFPL ](http://www.wtfpl.net/)

## Using the Database class ##
This section assumes you have basic knowledge of PDO.  
(I haven't yet had time to properly test this documentation, as though it may appear outdated, use at own risk.)  
The \Database\Connection(); class wraps around PHP's PDO, so you are able to call all of the built-in PDO functions on the instantiated object as you normally would.  
With the exception of the \Database\Connection::query(); method, this has been overloaded to a more convenient way and usage, such that it supports all the below methods.  

1. **\Database\Connection::getInstance()->query()**  

If all you want to do, is a simple parameterized query, this line is the one you're looking for.  
This will return a custom statement class of \Database\Statement, which also extends the default PDOStatement class.  

```php
<?php \Database\Connection::getInstance()->query("UPDATE animals SET `extinct` = :value WHERE name = :name", ["value" => true, "name" => "Asian Rhino"]); ?>
```   

2. **\Database\Connection::getInstance()->select()**  

Simple queries with a return value will be fetched as objects, The second argument should be an array of key-value pairs.
Second argument for methods, insert(), update() and delete() is always the WHERE clause.  

The following queries:  

```php
<?php \Database\Connection::getInstance()->select("animals"); ?>

<?php \Database\Connection::getInstance()->select("animals", ["name" => "Asian Rhino"]]); ?>
```

Will both return an array of objects, if the given criterias matched any rows, otherwise the resultset is false.

This method also supports IN-like requests.

```php
<?php \Database\Connection::getInstance()->select("animals", ["name" => ["Asian Rhino", "Platypus"]]); ?>
```
  
```php
<?php \Database\Connection::getInstance()->update("animals", ["extinct" => true], ["name" => "Asian Rhino"]); ?>
```

## Database Entities ##
For easier data manipulation, data objects should extend the **\Database\Entity** class.  
Every class that extends **\Database\Entity** must implement the following methods.  

- getTableName(); // Table in which this data object should store data.  
- getKeyField(); // The primary key of the table in which this object stores data.  

Every data object take an optional parameter [(int) primary_key] upon instantiating,  
identifying whether a new data object should be instantiated or an already existing row should be loaded from the table.  

If you wish to change data use the **->set(['column' => 'value']);**  
This will allow you to call **->save();** on an object and thus saving the data to your database.  
The data object will be saved as a new row if the primary_key key parameter was not present upon instantiating. 

**File:** Animal.php  
```php
<?php
	class Animal extends Database\Entity {
		protected function getKeyField() { return "animal_id"; } // The column with your primary key index
		protected function getTableName() { return "animals"; } // Name of the table to work with

		/**
		* Develop whatever functions your might need below.
		*/
		public function myCustomFunction() {

		}
	}
?> 
```

You can now select a row presented as an object by it's primary key.
```php
<?php
if(isset($_GET["animalID"])) {
	$iAnimal = new Animal($_GET["animalID"]);
} else {
	$iAnimal = new Animal();
}
```

Objects can also be populated with new data, while still updating the row.  

```php
<?php
$iAnimal = new Animal([
	"animalID" => 42,
	"extinct" => false
]);
$iAnimal->save();

// ... or

$iAnimal = new Animal;
$iAnimal->set([
	"animalID" => 42,
	"extinct" => false
]);
$iAnimal->save();
```

This will update animalID #42 setting extinct to '0'

###A helping hand (wrapper and helper functions)###
There's a slew of available helper functions that you can use to fetch resultsets in various ways, instead of doping the query, and then call fetch.

For example:  
```php
<?php \Database\Connection::getInstance()->query("SELECT name FROM animals WHERE extinct = :extinct", ["extinct" => true)->fetchCol(); ?>
```

Could be rewritten to:
```php
<?php \Database\Connection::getInstance()->fetchCol("animals", "name", ["extinct" => true]); ?>
```

Other helper functions include
- \Database\Connection::fetchCell();  
- \Database\Connection::fetchField(); (wrapper for fetchCell();)  
- \Database\Connection::fetchRow();  

The helping hand, is not limited to selective queries only.
- \Database\Connection::delete();  
- \Database\Connection::update();  
- \Database\Connection::upsert();  
- \Database\Connection::replace();  
- \Database\Connection::transaction();  