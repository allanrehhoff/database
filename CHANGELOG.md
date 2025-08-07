# Changelog #  
v6.1.0
- Added JsonSerializable interface to Database\Collection and Database\Entity for native JSON serialization support.
- Implemented jsonSerialize() method in Collection and Entity to enable JSON encoding of collections and entities.
- Improved Database\Connection to support multiple named singleton instances via an $alias parameter.
- Enhanced Entity class:
- Added support for passing arguments to Entity::new() for flexible instantiation.
- Improved Entity::find() to handle null results gracefully.
- Improved Entity::search() to auto-generate search expressions from criteria if none are provided.
- Added a guard in Entity::delete() to prevent deletion of non-existing entities.
- Improved typehints and docblocks for clarity and consistency.
- Minor code style and formatting improvements in UuidV4 and UuidV7 traits.
- Handle empty arrays being passed for IN queries.
- Support collections for IN queries.
- Update tests.

v6.0.0
**This release is backwards incompatible**
- Support for auto-generating UUIDs.  
- Restructured to allow decorator pattens to handle primary keys.  
- Removed unused parameters from functions.  
- Normalized typehints and docblocks.  
- Deprecated with(); method, use hydrate(); instead.
- Added hydrate(); to align naming with other well-known libraries.  
- $criteria parameter can no longer be null.  

v5.4.0
- Saving entities only updates set data, instead of loaded, to help mitigate race-conditions.  

v5.3.0
- New methods: Statement::getCollection() and Statement::getIterator()
- Added optional 'clause' parameter to search methods.

v5.2.1  
- Error from v5.2.0: Fix calling static methods in non-static context.  
- Updatred documentation.  

v5.2.0
- Primary key is now part of data array.  
- New method Database\EntityType::insert();  

v5.1.1
- Maintenance update.  
- PHP8.3 compatibility.  
- Minor performance optimizations.  

v5.0.0
- This update is backwards incompatible with previous methhods of Entity::from();  
- Coding standards update.  
- Return types update.  
- New instance cache.  

v4.1.0
- Added new Entity::from(string, mixed) method.  
- Updating inline docblocks.  
- Adding additional type hints to properties.  
- Throw proper exceptions.  
- Assign a default value of NULL to primary key.  

v4.0.0
- Added Entity::search() method.  
- Added magic method __isset to \Database\Entity
- Added Database\Collection object, loading entities will now return a collection.  
- Added more tests.  
- Removed myself as author from docblocks.  
- Some support for changing return types via PHP8 attributes.  
- Database\Entity::load(); now throws a TypeError when wrong parameter given.  
- Making sure Entity::id(); wraps properly around Entity::getKey();  
- Database\Entity::save(); no longer just re-throws errors, should be handled by the caller.  
- Static constants moved to abstract static functions.  
- Updated README documentation.  

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
