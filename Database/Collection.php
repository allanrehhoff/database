<?php
	namespace Database {
		class Collection implements \Iterator, \Countable {
			/**
			 * @var array Collection of objects that's iterable
			 */
			private $collection = [];

			/**
			 * Constructor
			 * @param array $objects Array of objects to store as collection
			 */
			public function __construct(array $objects) {
				$this->collection = $objects;
			}

			/**
			 * Get first element of collection
			 * Returns null if collection is empty
			 */
			#[ReturnTypeWillChange]
			public function getFirst() {
				if($this->isEmpty() === true) return null;

				$key = array_keys($this->collection)[0];
				return $this->collection[$key];
			}

			/**
			 * Coutn the number of elements in this collection
			 * @return int Number of elements
			 */
			public function count() : int {
				return count($this->collection);
			}

			/**
			 * Get first element of collection
			 * Returns null if collection is empty
			 */
			#[ReturnTypeWillChange]
			public function getLast() {
				if($this->isEmpty() === true) return null;

				$key = end(array_keys($this->collection));
				return $this->collection[$key];
			}

			/**
			 * Get a collection of column values by key
			 * @param mixed $key array/object key to fetch values from
			 * @return \Database\Collection
			 */
			public function getColumn($key) : Collection {
				return new self(array_column($this->collection, $key));
			}

			/**
			 * Tell whether the collection is empty or not
			 * @return bool
			 */
			public function isEmpty() : bool {
				return count($this->collection) === 0;
			}

			/**
			 * Rewind the collection array back to the start
			 * @return void
			 */
			public function rewind() : void {
				reset($this->collection);
			}

			/**
			 * Get object object at current position
			 */
			#[ReturnTypeWillChange]
			public function current() {
				return current($this->collection);
			}

			/**
			 * Get current position
			 */
			public function key() {
				return key($this->collection);
			}

			/**
			 * Advance the internal cursor of an array
			 */
			public function next() {
				return next($this->collection);
			}

			/**
			 * Check whether the collection contains more entries
			 * @return bool
			 */
			public function valid() : bool {
				return key($this->collection) !== null;
			}
		} 
	}