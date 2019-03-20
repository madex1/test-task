<?php

	use UmiCms\Service;

	class utypeStream extends umiBaseStream {

		protected $scheme = 'utype', $group_name, $field_name;

		/** @inheritdoc */
		public function stream_open($path, $mode, $options, $openedPath) {
			$path = $this->removeHash($path);
			$queryHash = Service::Request()
				->queryHash();
			$key = $path . $queryHash;
			$cacheFrontend = Service::CacheFrontend();
			$data = $cacheFrontend->loadData($key);

			if ($data) {
				return $this->setData($data);
			}

			$type_id = $this->parsePath($path);
			$collection = umiObjectTypesCollection::getInstance();
			if (mb_strpos($type_id, '~')) {
				list($module, $method) = explode('~', $type_id);
				$type_id = $collection->getTypeIdByHierarchyTypeName($module, $method);
			}

			if (is_array($type_id)) {
				$types = [];
				foreach ($type_id as $id) {
					$types[] = $collection->getType($id);
				}
			} else {
				$types = $collection->getType($type_id);
			}

			if ($types instanceof iUmiObjectType || is_array($types)) {
				$data = $this->translateToXml($types);

				if ($this->expire) {
					$cacheFrontend->saveData($key, $data, $this->expire);
				}

				return $this->setData($data);
			}

			return $this->setDataError('not-found');
		}

		/** @inheritdoc */
		protected function parsePath($path) {
			$path = parent::parsePath($path);
			$arr = explode('/', $path);

			if (umiCount($arr) >= 2 && $arr[1]) {
				$typeId = $this->getTypeId($arr[1]);

				switch ($arr[0]) {
					case 'dominant' : {
						$hierarchy = umiHierarchy::getInstance();
						return $hierarchy->getDominantTypeId($typeId);
					}
					case 'child' : {
						$domainId = isset($arr[2]) ? $arr[2] : null;
						$collection = umiObjectTypesCollection::getInstance();

						if ($domainId === null) {
							return $collection->getChildTypeIds($typeId);
						}

						return $collection->getChildIdListByDomain($typeId, $domainId);
					}
				}
			}

			$arr = explode('.', $path);

			if (is_array($arr)) {
				$path = trim($arr[0], '/');

				if (count($arr) > 1) {
					$this->group_name = $arr[1];
				}

				if (count($arr) > 2) {
					$this->field_name = $arr[2];
				}
			}

			return $path;
		}

		/** @inheritdoc */
		protected function translateToXml() {
			$args = func_get_args();
			$type = $args[0];

			switch (false) {
				case $this->field_name === null: {
					$field_id = $type->getFieldId($this->field_name);
					$field = umiFieldsCollection::getInstance()->getField($field_id);
					$request = ['full:field' => $field];
					break;
				}

				case $this->group_name === null: {
					$group = $type->getFieldsGroupByName($this->group_name);
					$request = ['full:group' => $group];
					break;
				}

				case !is_array($type): {
					$request = ['nodes:type' => $type];
					break;
				}

				default: {
					$request = ['full:type' => $type];
					break;
				}
			}

			return parent::translateToXml($request);
		}

		/**
		 * Возвращает идентификатор корневого объектного типа, связанного с иерархическим типом
		 * @param string $typeString строка, обозначающая иерархический тип, @example "catalog::object"
		 * @return int
		 */
		private function getTypeId($typeString) {
			if (is_numeric($typeString)) {
				return (int) $typeString;
			}

			list($module, $method) = explode('::', $typeString);
			return umiObjectTypesCollection::getInstance()
				->getTypeIdByHierarchyTypeName($module, $method);
		}
	}
