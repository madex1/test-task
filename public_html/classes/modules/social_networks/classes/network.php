<?php
	abstract class social_network extends umiObjectProxy {
		private static $list = array();
		protected $network_name;
		protected $checkParents = false;

		/**
		 * Получает список доступных соц. сетей
		 *
		 * @return array Массив из umiObject типа соц. сети
		 */
		final public static function getList() {
				if (empty(self::$list)) {
					$sel = new selector('objects');
					$sel->types('hierarchy-type')->name('social_networks', 'network');
					$sel->option('no-length')->value(true);
					self::$list = $sel->result;
				}
			return self::$list;
		}

		final public static function get($objectId) {
			if (empty($objectId)) return false;

			if ($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$objects = umiObjectsCollection::getInstance();
				$object = $objects->getObject($objectId);

				if ($object instanceof iUmiObject == false) {
					throw new coreException("Couldn't load network #{$objectId}");
				}
			}

			$classPrefix = $object->social_id;

			objectProxyHelper::includeClass('social_networks/classes/networks/', $classPrefix);
			$className = $classPrefix . '_social_network';

			return new $className($object, $classPrefix);
		}

		/**
		 * Получает объект типа social_network(или дочернего) по заданным параметрам
		 *
		 * @param $code
		 * @param int $domainId Идентификатор домена
		 * @param string $code Идентификатор соц. сети
		 * @return social_network Объект соц.сети
		 */
		public static function getByCodeName($code, $domainId = NULL) {
			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('social_networks', 'network');
			$sel->types('hierarchy-type')->name('social_networks', 'vkontakte');
			$sel->where('social_id')->equals($code);
			if ($domainId) {
				$sel->where('domain_id')->equals($domainId);
			}
			$sel->option('no-length')->value(true);

			return self::get($sel->first);
		}

		/**
		 * Добавляет объект дочернего типа social_network с заданными параметрами
		 *
		 * @param $code Идентификатор соц. сети
		 * @param int $domainId Идентификатор домена
		 * @return umiObject Объект соц.сети
		 */
		public static function addByCodeName($code, $domainId) {
			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID("social_networks-network-".$code);

			$objects = umiObjectsCollection::getInstance();
			$objectId = $objects->addObject($code, $typeId);

			$object = $objects->getObject($objectId);
			$object->setValue("social_id", $code);
			$object->setValue("domain_id", $domainId);
			$object->commit();

			return self::get($object);
		}

		/**
		 * Создает объект social_network на основе объекта соц. сети
		 *
		 * @param iUmiObject|umiObject $object
		 * @param bool|null|string $network_name @deprecated -> $object->social_id
		 */
		public function __construct(umiObject $object) {
			$args = func_get_args();
			$object = array_shift($args);

			if (!$object instanceof umiObject) {
				throw new Exception('Object expected for creating network');
			}

			$network_name = array_shift($args);

			if (!is_string($network_name) && $network_name !== false) {
				throw new Exception('Incorrect network name given for creating network');
			}

			$network_name = $network_name ? $network_name : $object->social_id;
			parent::__construct($object);
			$this->network_name = $network_name;
			$mainConfigs = mainConfiguration::getInstance();
			$this->checkParents = (bool) $mainConfigs->get('kernel', 'social_network.check-parents');
		}

		/**
		 * Возвращает идентификтор соц. сети
		 *
		 * @return null|string
		 */
		public function getCodeName() {
			return $this->network_name;
		}

		/**
		 * Возвращает идентификтор соц. сети
		 * Используется для
		 *
		 * @return null|string
		 */
		public function __toString() {
			return $this->network_name;
		}
	};
?>