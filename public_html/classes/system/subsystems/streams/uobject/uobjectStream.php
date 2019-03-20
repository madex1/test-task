<?php

	use UmiCms\Service;

	class uobjectStream extends umiBaseStream {

		protected $scheme = 'uobject', $prop_name;

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

			$object_id = $this->parsePath($path);
			$object = umiObjectsCollection::getInstance()->getObject($object_id);

			if ($object instanceof iUmiObject) {
				if ($this->prop_name === null) {
					$showEmptyFlag = translatorWrapper::$showEmptyFields;
					if (getArrayKey($this->params, 'show-empty') !== null) {
						translatorWrapper::$showEmptyFields = true;
					}

					$data = $this->translateToXml($object);

					translatorWrapper::$showEmptyFields = $showEmptyFlag;
				} else {
					$prop = $object->getPropByName($this->prop_name);
					if ($prop instanceof iUmiObjectProperty) {
						$data = $this->translateToXml($object, $prop);
					} else {
						return $this->setDataError('not-found');
					}
				}

				if ($this->expire) {
					$cacheFrontend->saveData($key, $data, $this->expire);
				}
				return $this->setData($data);
			}

			return $this->setDataError('not-found');
		}

		protected function parsePath($path) {
			$path = parent::parsePath($path);

			if (contains($path, '.')) {
				list($path, $prop_name) = explode('.', $path);
				$this->prop_name = $prop_name;
			} else {
				$this->prop_name = null;
			}

			return (int) $path;
		}

		protected function translateToXml() {
			$args = func_get_args();
			$object = $args[0];

			if (isset($args[1])) {
				$property = $args[1];
			} else {
				$property = null;
			}

			if ($property === null) {
				$request = ['full:object' => $object];
			} else {
				$request = ['property' => $property];
			}
			return parent::translateToXml($request);
		}
	}

