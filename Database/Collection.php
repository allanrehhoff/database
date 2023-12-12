<?php
namespace Database {

	/**
	 * Class Collection
	 *
	 * @package Database
	 */
	class Collection implements \ArrayAccess, \Iterator, \Countable {

		/**
		 * @var array Collection of objects that's iterable
		 */
		private array $items = [];

		/**
		 * Collection constructor.
		 *
		 * @param array $objects Array of objects to store as a collection.
		 */
		public function __construct(array $objects) {
			$this->items = $objects;
		}

		/**
		 * Get the first element of the collection.
		 * Returns null if the collection is empty.
		 *
		 * @return mixed|null
		 */
		#[\ReturnTypeWillChange]
		public function getFirst(): mixed {
			if ($this->isEmpty() === true) return null;

			$key = array_keys($this->items)[0];
			return $this->items[$key];
		}

		/**
		 * Get the last element of the collection.
		 * Returns null if the collection is empty.
		 *
		 * @return mixed|null
		 */
		#[\ReturnTypeWillChange]
		public function getLast(): mixed {
			if ($this->isEmpty() === true) return null;

			$keys = array_keys($this->items);
			$key = end($keys);
			return $this->items[$key];
		}

		/**
		 * Count the number of elements in this collection.
		 *
		 * @return int Number of elements
		 */
		public function count(): int {
			return count($this->items);
		}

		/**
		 * Get the values of a given key as a collection.
		 *
		 * @param mixed $key Array/object key to fetch values from.
		 * @return \Database\Collection
		 */
		public function getColumn($key): Collection {
			return new self(array_column($this->items, $key));
		}

		/**
		 * Check whether the collection is empty or not.
		 *
		 * @return bool
		 */
		public function isEmpty(): bool {
			return $this->count() === 0;
		}

		/**
		 * Rewind the collection array back to the start.
		 *
		 * @return void
		 */
		public function rewind(): void {
			reset($this->items);
		}

		/**
		 * Get the object at the current position.
		 *
		 * @return mixed
		 */
		#[\ReturnTypeWillChange]
		public function current(): mixed {
			return current($this->items);
		}

		/**
		 * Get the current position.
		 *
		 * @return mixed
		 */
		#[\ReturnTypeWillChange]
		public function key(): mixed {
			return key($this->items);
		}

		/**
		 * Advance the internal cursor of an array.
		 *
		 * @return void
		 */
		public function next(): void {
			next($this->items);
		}

		/**
		 * Check whether the collection contains more entries.
		 *
		 * @return bool
		 */
		public function valid(): bool {
			return key($this->items) !== null;
		}

		/**
		 * Determine if an item exists at an offset.
		 *
		 * @param mixed $key
		 * @return bool
		 */
		public function offsetExists($key): bool {
			return isset($this->items[$key]);
		}

		/**
		 * Get an item at a given offset.
		 *
		 * @param mixed $key
		 * @return mixed
		 */
		#[\ReturnTypeWillChange]
		public function offsetGet($key): mixed {
			return $this->items[$key];
		}

		/**
		 * Set the item at a given offset.
		 *
		 * @param mixed $key
		 * @param mixed $value
		 * @return void
		 */
		public function offsetSet($key, $value): void {
			if (is_null($key)) {
				$this->items[] = $value;
			} else {
				$this->items[$key] = $value;
			}
		}

		/**
		 * Unset the item at a given offset.
		 *
		 * @param mixed $key
		 * @return void
		 */
		public function offsetUnset($key): void {
			unset($this->items[$key]);
		}

		/**
		 * Get all of the items in the collection.
		 *
		 * @return array
		 */
		public function all(): array {
			return $this->items;
		}
	}
}
