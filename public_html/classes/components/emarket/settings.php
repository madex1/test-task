<?php

	use UmiCms\Service;

	/**
	 * Класс настроек модуля
	 * Class EmarketSettings
	 */
	class EmarketSettings {

		/** @var emarket $module */
		public $module;

		/** @var regedit реестр */
		protected $registry;

		/** @var string начальный путь до настроек в реестре */
		protected $registryPath = '//modules/emarket';

		/** @const название секции настроек наименований заказа */
		const ORDER_ITEM_SECTION = 'order-item';

		/** @const название секции настроек заказа */
		const ORDER_SECTION = 'order';

		/** @const название секции настроек основного склада */
		const DEFAULT_STORE_SECTION = 'default-store';

		/** @var array список настроек */
		protected $list = [
			self::ORDER_ITEM_SECTION => [
				'weightField',
				'widthField',
				'heightField',
				'lengthField',
				'weight',
				'width',
				'height',
				'length',
				'taxRateId',
				'paymentSubjectId',
				'paymentModeId'
			],

			self::ORDER_SECTION => [
				'defaultWeight',
				'defaultWidth',
				'defaultHeight',
				'defaultLength'
			],

			self::DEFAULT_STORE_SECTION => [
				'country-code',
				'region',
				'index',
				'city',
				'street',
				'house-number',
				'apartment',
				'contact-full-name',
				'contact-phone',
				'contact-email'
			],
		];

		/**
		 * Конструктор
		 * @param null|iRegedit $registry реестр системы
		 */
		public function __construct($registry = null) {
			$this->registry = Service::Registry();

			if ($registry instanceof iRegedit) {
				$this->registry = $registry;
			}
		}

		/**
		 * Возвращает значение опции
		 * @param string $section секция, которой принадлежит опция
		 * @param string $name имя опции
		 * @return mixed|null
		 */
		public function get($section, $name) {
			$registryName = $this->getRegistryName($section, $name);

			if (!$registryName) {
				return null;
			}

			return $this->getRegistryValue($registryName);
		}

		/**
		 * Устанавливает значение опции
		 * @param string $section секция, которой принадлежит опция
		 * @param string $name имя опции
		 * @param mixed $value новое значепие опции
		 * @return bool
		 */
		public function set($section, $name, $value) {
			$registryName = $this->getRegistryName($section, $name);

			if (!$registryName) {
				return false;
			}

			try {
				$this->setRegistryValue($registryName, $value);
			} catch (Exception $e) {
				return false;
			}

			return true;
		}

		/**
		 * Устанавливет значение в реестр
		 * @param string $name имя ключа в реестре
		 * @param string $value новое значение ключа
		 */
		protected function setRegistryValue($name, $value) {
			$this->registry->set("{$this->registryPath}/{$name}", $value);
		}

		/**
		 * Возвращает значение из реестра
		 * @param string $name имя ключа в реестре
		 * @return mixed
		 */
		protected function getRegistryValue($name) {
			return $this->registry->get("{$this->registryPath}/{$name}");
		}

		/**
		 * Проверяет существует ли опция
		 * @param string $section секция, которой принадлежит опция
		 * @param string $name имя опции
		 * @return bool
		 */
		protected function isExists($section, $name) {
			if (!isset($this->list[$section])) {
				return false;
			}

			return in_array($name, $this->list[$section]);
		}

		/**
		 * Возвращает имя ключа реестра для опции
		 * @param string $section секция, которой принадлежит опция
		 * @param string $name имя опции
		 * @return null
		 */
		protected function getRegistryName($section, $name) {
			if (!$this->isExists($section, $name)) {
				return null;
			}

			return $this->getUniqueRegistry($section, $name);
		}

		/**
		 * Возвращает уникальное имя ключа реестра
		 * @param string $section секция директивы
		 * @param string $name имя директивы
		 * @return string
		 */
		protected function getUniqueRegistry($section, $name) {
			return ($section . '-' . $name);
		}
	}
