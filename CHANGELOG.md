# Changelog #  
v3.2.1
- Rewritten Database::upsert(); method for future compatibility with multi-inserts.  
- Fixed PDO Param values being interpreted as \PDO::PARAM_STR.  

v3.2.0
- Added LIKE search method.  
- Added upsert(); methods, for updating on duplicate key.  
- Support setting key field in Entity::set();  

v3.1.2
- Fixed naming convention of a variables  
- Fixed missing curly bracket  

v3.1.1  
- Added two new methods for counting queries Connection::count(); and Connection::countQuery();
- Added support for selecting by null values

v3.1.0  
- Updated Connection::replace(); method.  
- Introduced new Entity::isNew(); method.  

v3.0.1  
- Added Entity::exists() method to determine if entity exists.  

v3.0  
- Updated to use 'use' statements, instead of full path for global namespace classes.
- Backwards incompatible update
- Now includes function return types
- Now includes argument type hints
- Updated docblocks
- Added a new ->connect(); method to the Database object
- Updated tests to match the new structure
- Moved from extending PDO to a single contained PDO property

v2.4  
- *This update might not be backwards compatible on all systems, depending on implementation.*  
- \Database\Connection now extends PHP's PDO class, all the same functions can now be used with a custom statement class.
- Added a more robust debugQuery(); method.  
- Updated tests to be compatible with the update.  
- Updated documentation.  

v2.3  
- Support for IN operator through query();, update(); and select();  
- Added a few tests to test the recent changes.  

v2.2  
- Entity::__get(); now throws an exception w´hen key field is incorrectly accessed.  
- Updated documentation blocks.  
- Datebase\Connection::fetchCell(); now returns null on no results instead of false.  
- Converted line endings to unix.  

v2.2  
- Fixed a bug in Connection::fetchCell(); that would make the function always return null.  
- Updated documentation for Connection::query(); method.  
- Enforce errors to be thrown as exceptions.  
- Moving away from .class.php extensions, just use .php, for easier drop-ins to other projects.  
- Only let the bundled autoloader care about it's own classes.  
- Added a few properties for debugging, more to come.
- Documented class properties.

v2.1  
- Wrapped the entire library into a namespacing structure.  
- Updated documentation.  
- Updated tests to match the new structure.  
- Added an autoloader file.  

v2.0 *Backwards incompatible*
- Added Entity and EntityType classes.  
- Moved project to seperate repository.  
- Minor bugfixes.  
- Updated documentation.  

v1.3  
- Added __destruct(); method.  
- Implemented a __call(); method to allow methods not being implemented by this library to be executed.  
- Added ability start transactions and rollback on error.  

v1.1  
- Added interpolateQuery for easier debugging SQL.  

v1.0  
- Initial release.  
