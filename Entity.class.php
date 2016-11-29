<?php
	abstract class Entity {
		private $key;
		protected $data;
		abstract protected function getKeyField();
		abstract protected function getTableName();

		public function __construct($data = null) {
			if ($data !== null && gettype($data) != "object") {
				$data = DatabaseConnection::getInstance()->fetchRow($this->getTableName(), [$this->getKeyField() => $data]);
			}

			if ($data !== null) {
				$key = $this->getKeyField();
				$this->key = $data->{$key};
				unset( $data->{$key} );
			} else {
				$data = (object) [];
			}

			$this->data = $data;
		}

		function __toString() {
			$result = get_class($this)."(".$this->key."):\n";
			foreach ($this->data as $key => $value) {
				$result .= " [".$key."] ".$value."\n";
			}
			return $result;
		}

		public function __set($name, $value) {
			$this->data->{$name} = $value;
		}

		public function __get($name) {
			return $this->data->{$name};
		}

		public function save() {
			try {
				if ($this->key == null) {
					$this->key = DatabaseConnection::getInstance()->insert($this->getTableName(), $this->data);
					return $this->key;
				} else {
					DatabaseConnection::getInstance()->update($this->getTableName(), $this->data, $this->getKeyFilter());
					return $this->data;
				}
			} catch(Exception $e) {
				throw $e;
			}
		}
		
		public function delete() {
			DatabaseConnection::getInstance()->delete($this->getTableName(), $this->getKeyFilter());		
		}
		
		public function safe($key) {
			return htmlspecialchars($this->data[$key], ENT_QUOTES, "UTF-8");
		}

		public static function load($ids) {
			$class = get_called_class();
			$obj = new $class();
			$key = $obj->getKeyField();

			if(is_array($ids)) {
				$objects = [];

				foreach($ids as $id) $objects[] = new $obj($id);
				return $objects;
			} else if(is_numeric($ids)) {
				return new $obj((int) $ids);
			}

			throw new Exception($obj."::load(); expects either an array or integer. '".gettype($ids)."' was provided.");
		}

		public function set($values, $allowed_fields = null) {
			if ($allowed_fields != null) {
				$values = array_intersect_key($values, array_flip($allowed_fields));
			}
			$this->data = array_merge($this->data, $values);
		}

		public function getData() {
			return $this->data;
		}
		
		public function get($key) {
			return $this->data[$key];
		}

		public function getKey() {
			return $this->key;
		}

		public function getKeyFilter() {
			return [$this->getKeyField() => $this->key];
		}

		public function id() {
			return $this->key;
		}
	}