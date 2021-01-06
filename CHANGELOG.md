# Changelog #  
v3.0.1  
- Updated to use 'use' statements, instead of full path for global namespace classes.

v3.0  
- Backwards incompatible update
- Now includes function return types
- Now includes argument type hints
- Updated docblocks

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