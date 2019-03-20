<?php

	use UmiCms\Service;

	class upageStream extends umiBaseStream {

		protected $scheme = 'upage', $prop_name;

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

			$element_id = $this->parsePath($path);
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if ($element instanceof iUmiHierarchyElement) {
				if ($this->prop_name === null) {
					$showEmptyFlag = translatorWrapper::$showEmptyFields;
					if (getArrayKey($this->params, 'show-empty') !== null) {
						translatorWrapper::$showEmptyFields = true;
					}

					$data = $this->translateToXml($element);

					translatorWrapper::$showEmptyFields = $showEmptyFlag;
				} else {
					$prop = $element->getObject()->getPropByName($this->prop_name);
					if ($prop instanceof iUmiObjectProperty) {
						$data = $this->translateToXml($element, $prop);
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
			$path = trim($path, '( )');

			if (($pos = mb_strrpos($path, '.')) !== false && mb_strpos($path, '/', $pos) === false) {
				$prop_name = mb_substr($path, $pos + 1);
				$path = mb_substr($path, 0, $pos);

				$this->prop_name = $prop_name;
			} else {
				$this->prop_name = null;
			}

			if (is_numeric($path)) {
				if ((string) (int) $path == $path) {
					return (int) $path;
				}
			}

			return umiHierarchy::getInstance()->getIdByPath($path);
		}

		protected function translateToXml() {
			$args = func_get_args();
			$element = $args[0];
			if (isset($args[1])) {
				$property = $args[1];
			} else {
				$property = null;
			}

			$request = $property === null ? ['full:page' => $element] : ['property' => $property];
			return parent::translateToXml($request);
		}
	}

