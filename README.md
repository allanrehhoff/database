# Database\Connection class #

**Database\Connection V4**
A library for querying your database in an easy-to-maintain objected oriented manner.  
It features a few classes to speed up CMS / MVC / API and general CRUD development, and abstracts away your database queries.  
 
## Installing ##

Install manually: ```<?php require "autoload.php"; ?>```

Likely will work with standard autoloaders, if you simply copy the **Database/** directory from any recent release.  

## Database queries ##
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

Will both return a `Database\Collection` of objects, if the given criterias matched any rows, otherwise the resultset is empty.

This method also supports IN-like requests.

```php
<?php \Database\Connection::getInstance()->select("animals", ["name" => ["Asian Rhino", "Platypus"]]); ?>
```
  
```php
<?php \Database\Connection::getInstance()->update("animals", ["extinct" => true], ["name" => "Asian Rhino"]); ?>
```

3. **\Database\Connection::getInstance()->update()**  
```php
<?php \Database\Connection::getInstance()->update("animals", ["extinct" => false], ["name" => "Asian Rhino"]]); ?>
```

4. **\Database\Connection::getInstance()->delete()**  
```php
<?php \Database\Connection::getInstance()->delete("animals", ["extinct" => true]); ?>
```

5. **\Database\Connection::getInstance()->insert()**  
```php
<?php \Database\Connection::getInstance()->insert("animals", ["name" => "Asian Rhino", "extinct" => false]]); ?>
```

6. **\Database\Connection::getInstance()->insertMultiple()**  
```php
<?php
	\Database\Connection::getInstance()->update("animals",
		["name" => "Asian Rhino", "extinct" => true],
		["name" => "Platypus", "extinct" => false]
	]);
?>
```

## Database entities ##
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
		protected function getKeyField() : string { return "animal_id"; } // The column with your primary key index
		protected function getTableName() : string { return "animals"; }  // Name of the table to work with

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

Objects can **not** be loaded with the primary key passed as data.  
In the following example `$iAnimal` would be treated as a new object upon saving.  

```php
<?php
$iAnimal = new Animal;
$iAnimal->set([
	"animalID" => 42,
	"extinct" => false
]);
$iAnimal->save();
```

This will likely trigger a duplicate key error.

## Collections / Result sets ##
The **Database\Collection** class is inspired by Laravel collections.  

```php
<?php \Database\Connection::getInstance()->select("animals")->getColumn("name"); ?>
```

Get row (assuming your criteria matches only one row) 
```php
<?php \Database\Connection::getInstance()->select("animals", ["name" => "Asian Rhino"])->getFirst(); ?>
```