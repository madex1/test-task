<?php

	/** Коллекция приложений социальные сетей */
	abstract class social_network extends umiObjectProxy {

		/** @var array $list список приложений */
		private static $list = [];

		/** @var int|Mixed|string название приложения */
		protected $network_name;

		/** @var bool проверять родительские страницы на предмет доступности для приложения */
		protected $checkParents = false;

		/**
		 * Конструктор
		 * @param iUmiObject $object объект приложения
		 * @throws Exception
		 */
		public function __construct(iUmiObject $object) {
			$args = func_get_args();
			$object = array_shift($args);

			if (!$object instanceof iUmiObject) {
				throw new Exception('Object expected for creating network');
			}

			$network_name = array_shift($args);

			if (!is_string($network_name) && $network_name !== false) {
				throw new Exception('Incorrect network name given for creating network');
			}

			$network_name = $network_name ?: $object->social_id;
			parent::__construct($object);
			$this->network_name = $network_name;
			$mainConfigs = mainConfiguration::getInstance();
			$this->checkParents = (bool) $mainConfigs->get('kernel', 'social_network.check-parents');
		}

		/**
		 * Возвращает список приложений
		 * @return iUmiObject[]
		 * @throws selectorException
		 */
		final public static function getList() {
			if (umiCount(self::$list) > 0) {
				return self::$list;
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('social_networks', 'network');
			$sel->option('no-length')->value(true);
			return self::$list = $sel->result();
		}

		/**
		 * Возвращает приложение по идентификатору его объекта
		 * @param int $objectId идентификатор объекта приложения
		 * @return bool|social_network
		 * @throws coreException
		 */
		final public static function get($objectId) {
			if ($objectId === null) {
				return false;
			}

			if ($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$objects = umiObjectsCollection::getInstance();
				$object = $objects->getObject($objectId);

				if (!$object instanceof iUmiObject) {
					throw new coreException("Couldn't load network #{$objectId}");
				}
			}

			$classPrefix = $object->getValue('social_id');
			objectProxyHelper::includeClass('social_networks/classes/networks/', $classPrefix);
			$className = $classPrefix . '_social_network';
			return new $className($object, $classPrefix);
		}

		/**
		 * Возвращает приложение по его типу и идентификатору домена,
		 * которому оно принадлежит
		 * @param string $code тип приложения
		 * @param null|int $domainId идентификатор домена
		 * @return bool|social_network
		 * @throws coreException
		 * @throws selectorException
		 */
		public static function getByCodeName($code, $domainId = null) {
			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('social_networks', 'network');
			$sel->types('hierarchy-type')->name('social_networks', 'vkontakte');
			$sel->where('social_id')->equals($code);

			if ($domainId) {
				$sel->where('domain_id')->equals($domainId);
			}

			$sel->option('no-length')->value(true);

			return self::get($sel->first());
		}

		/**
		 * Создает и возвращает приложение с заданным типом и доменом,
		 * которому оно принадлежит.
		 * @param string $code идентификатор типа приложения
		 * @param int $domainId идентификатор домена
		 * @return bool|social_network
		 * @throws coreException
		 */
		public static function addByCodeName($code, $domainId) {
			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('social_networks-network-' . $code);

			$objects = umiObjectsCollection::getInstance();
			$objectId = $objects->addObject($code, $typeId);

			$object = $objects->getObject($objectId);
			$object->setValue('social_id', $code);
			$object->setValue('domain_id', $domainId);
			$object->commit();

			return self::get($object);
		}

		/**
		 * Возвращает идентификатор типа приложения
		 * @return int|Mixed|string
		 */
		public function getCodeName() {
			return $this->network_name;
		}

		/**
		 * Возвращает идентификатор домена
		 * @return int|string|null
		 */
		public function getDomainId() {
			return $this->getObject()
				->getValue('domain_id');
		}

		/**
		 * Возвращает идентификатор шаблона
		 * @return int|string|null
		 */
		public function getTemplateId() {
			return $this->getObject()
				->getValue('template_id');
		}

		/**
		 * Возвращает идентификатор типа приложения
		 * @return string
		 */
		public function __toString() {
			return (string) $this->network_name;
		}

		/**
		 * Включено ли отображение приложения
		 * @return bool
		 */
		abstract public function isIframeEnabled();

		/**
		 * Показывать ли страницу в приложении
		 * @param int $elementId идентификатор страницы
		 * @return bool
		 */
		abstract public function isHierarchyAllowed($elementId);
	}

