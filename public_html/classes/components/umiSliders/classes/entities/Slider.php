<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	use UmiCms\Service;

	/**
	 * Class Slider класс слайдера
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	class Slider implements
		iSlider,
		\iUmiDataBaseInjector,
		\iUmiConstantMapInjector,
		\iClassConfigManager,
		\iUmiDomainsInjector,
		\iUmiLanguagesInjector {

		use \tUmiDataBaseInjector;
		use \tCommonCollectionItem;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;
		use \tUmiDomainsInjector;
		use \tUmiLanguagesInjector;

		/** @var string $name название слайдера */
		private $name;

		/** @var int $domainId идентификатор домена */
		private $domainId;

		/** @var int $languageId идентификатор языка */
		private $languageId;

		/** @var int $slidingSpeed скорость пролистывания слайдов в микросекундах */
		private $slidingSpeed;

		/** @var int $slidingDelay длительность задержки перед пролистыванием слайда в микросекундах */
		private $slidingDelay;

		/** @var bool $slidingLoopEnable статус включения цикличного пролистывания слайдов */
		private $slidingLoopEnable;

		/** @var bool $slidingAutoPlayEnable статус включения автоматического пролистывания слайдов */
		private $slidingAutoPlayEnable;

		/** @var bool $slidesRandomOrderEnable статус включения случайного порядка слайдов в слайдере */
		private $slidesRandomOrderEnable;

		/** @var int $slidesCount количество отображаемых слайдов в слайдере */
		private $slidesCount;

		/** @var string|null $customId кастомный идентификатор слайдера */
		private $customId;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId',
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName',
				],
				[
					'name' => 'DOMAIN_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setDomainId',
					'getter' => 'getDomainId',
				],
				[
					'name' => 'LANGUAGE_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setLanguageId',
					'getter' => 'getLanguageId',
				],
				[
					'name' => 'SLIDING_SPEED_FIELD_NAME',
					'required' => true,
					'setter' => 'setSlidingSpeed',
					'getter' => 'getSlidingSpeed',
				],
				[
					'name' => 'SLIDING_DELAY_FIELD_NAME',
					'required' => true,
					'setter' => 'setSlidingDelay',
					'getter' => 'getSlidingDelay',
				],
				[
					'name' => 'SLIDING_LOOP_ENABLE_FIELD_NAME',
					'setter' => 'setSlidingLoopEnableStatus',
					'getter' => 'isSlidingLoopEnable',
				],
				[
					'name' => 'SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME',
					'setter' => 'setSlidingAutoPlayEnableStatus',
					'getter' => 'isSlidingAutoPlayEnable',
				],
				[
					'name' => 'SLIDES_RANDOM_ORDER_ENABLE_FIELD_NAME',
					'setter' => 'setSlidesRandomOrderEnableStatus',
					'getter' => 'isSlidesRandomOrderEnable',
				],
				[
					'name' => 'SLIDES_COUNT_FIELD_NAME',
					'setter' => 'setSlidesCount',
					'getter' => 'getSlidesCount',
				],
				[
					'name' => 'CUSTOM_ID_FIELD_NAME',
					'setter' => 'setCustomId',
					'getter' => 'getCustomId',
				]
			],
			'constructor' => [
				'callback' => [
					'before' => 'constructorCallbackBefore'
				]
			]
		];

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			if (!is_string($name)) {
				throw new \wrongParamException('Wrong value for name given');
			}

			$name = trim($name);

			if ($name === '') {
				throw new \wrongParamException('Empty value for name given');
			}

			if ($this->getName() != $name) {
				$this->setUpdatedStatus(true);
			}

			$this->name = $name;
			return $this;
		}

		/** @inheritdoc */
		public function getDomainId() {
			return $this->domainId;
		}

		/** @inheritdoc */
		public function setDomainId($domainId) {
			if (!is_numeric($domainId)) {
				throw new \wrongParamException('Wrong value for domain id given');
			}

			$domainCollection = $this->getDomainCollection();

			if (!$domainCollection->getDomain($domainId) instanceof \iDomain) {
				throw new \wrongParamException('Domain with that id not exists');
			}

			if ($this->getDomainId() != $domainId) {
				$this->setUpdatedStatus(true);
			}

			$this->domainId = $domainId;
			return $this;
		}

		/** @inheritdoc */
		public function getLanguageId() {
			return $this->languageId;
		}

		/** @inheritdoc */
		public function setLanguageId($languageId) {
			if (!is_numeric($languageId)) {
				throw new \wrongParamException('Wrong value for language id given');
			}

			$languageCollection = $this->getLanguageCollection();

			if (!$languageCollection->getLang($languageId) instanceof \iLang) {
				throw new \wrongParamException('Language with that id not exists');
			}

			if ($this->getLanguageId() != $languageId) {
				$this->setUpdatedStatus(true);
			}

			$this->languageId = $languageId;
			return $this;
		}

		/** @inheritdoc */
		public function getSlidingSpeed() {
			return $this->slidingSpeed;
		}

		/** @inheritdoc */
		public function setSlidingSpeed($speed) {
			if (!is_numeric($speed)) {
				throw new \wrongParamException('Wrong value for sliding speed given');
			}

			$speed = (int) $speed;

			if ($this->getSlidingSpeed() != $speed) {
				$this->setUpdatedStatus(true);
			}

			$this->slidingSpeed = $speed;
			return $this;
		}

		/** @inheritdoc */
		public function getSlidingDelay() {
			return $this->slidingDelay;
		}

		/** @inheritdoc */
		public function setSlidingDelay($delay) {
			if (!is_numeric($delay)) {
				throw new \wrongParamException('Wrong value for sliding delay given');
			}

			$delay = (int) $delay;

			if ($this->getSlidingDelay() != $delay) {
				$this->setUpdatedStatus(true);
			}

			$this->slidingDelay = $delay;
			return $this;
		}

		/** @inheritdoc */
		public function setSlidingLoopEnableStatus($status) {
			$status = (bool) $status;

			if ($this->isSlidingLoopEnable() != $status) {
				$this->setUpdatedStatus(true);
			}

			$this->slidingLoopEnable = $status;
			return $this;
		}

		/** @inheritdoc */
		public function isSlidingLoopEnable() {
			return $this->slidingLoopEnable;
		}

		/** @inheritdoc */
		public function setSlidingAutoPlayEnableStatus($status) {
			$status = (bool) $status;

			if ($this->isSlidingAutoPlayEnable() != $status) {
				$this->setUpdatedStatus(true);
			}

			$this->slidingAutoPlayEnable = $status;
			return $this;
		}

		/** @inheritdoc */
		public function isSlidingAutoPlayEnable() {
			return $this->slidingAutoPlayEnable;
		}

		/** @inheritdoc */
		public function setSlidesRandomOrderEnableStatus($status) {
			$status = (bool) $status;

			if ($this->isSlidesRandomOrderEnable() != $status) {
				$this->setUpdatedStatus(true);
			}

			$this->slidesRandomOrderEnable = $status;
			return $this;
		}

		/** @inheritdoc */
		public function isSlidesRandomOrderEnable() {
			return $this->slidesRandomOrderEnable;
		}

		/** @inheritdoc */
		public function setSlidesCount($count) {
			$count = (int) $count;

			if ($this->getSlidesCount() != $count) {
				$this->setUpdatedStatus(true);
			}

			$this->slidesCount = $count;
			return $this;
		}

		/** @inheritdoc */
		public function getSlidesCount() {
			return $this->slidesCount;
		}

		/** @inheritdoc */
		public function getCustomId() {
			return $this->customId;
		}

		/** @inheritdoc */
		public function setCustomId($customId) {
			$customId = is_string($customId) ? trim($customId) : null;

			if ($this->getCustomId() != $customId) {
				$this->setUpdatedStatus(true);
			}

			$this->customId = $customId;
			return $this;
		}

		/**
		 * Выполняет обработку параметров инициализации до нее и возвращает обработанные параметры
		 * @param array $params параметры инициализации
		 * @return array
		 */
		public function constructorCallbackBefore(array $params) {
			$this->setDomainCollection(
				Service::DomainCollection()
			);
			$this->setLanguageCollection(
				Service::LanguageCollection()
			);
			return $params;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return $this;
			}

			$tableName = $this->getEscapedConstant('TABLE_NAME');
			$idField = $this->getEscapedConstant('ID_FIELD_NAME');
			$nameField = $this->getEscapedConstant('NAME_FIELD_NAME');
			$domainIdField = $this->getEscapedConstant('DOMAIN_ID_FIELD_NAME');
			$languageIdField = $this->getEscapedConstant('LANGUAGE_ID_FIELD_NAME');
			$slidingSpeedField = $this->getEscapedConstant('SLIDING_SPEED_FIELD_NAME');
			$slidingDelayField = $this->getEscapedConstant('SLIDING_DELAY_FIELD_NAME');
			$slidingLoopEnableField = $this->getEscapedConstant('SLIDING_LOOP_ENABLE_FIELD_NAME');
			$slidingAutoPlayEnableField = $this->getEscapedConstant('SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME');
			$slidesRandomOrderEnableField = $this->getEscapedConstant('SLIDES_RANDOM_ORDER_ENABLE_FIELD_NAME');
			$slidesCountField = $this->getEscapedConstant('SLIDES_COUNT_FIELD_NAME');
			$customIdField = $this->getEscapedConstant('CUSTOM_ID_FIELD_NAME');

			$connection = $this->getConnection();
			$id = (int) $this->getId();
			$name = $connection->escape($this->getName());
			$domainId = (int) $this->getDomainId();
			$languageId = (int) $this->getLanguageId();
			$slidingSpeed = (int) $this->getSlidingSpeed();
			$slidingDelay = (int) $this->getSlidingDelay();
			$slidingLoopEnableStatus = (int) $this->isSlidingLoopEnable();
			$slidingAutoPlayEnableStatus = (int) $this->isSlidingAutoPlayEnable();
			$slidesRandomOrderEnableStatus = (int) $this->isSlidesRandomOrderEnable();
			$slidesCount = (int) $this->getSlidesCount();
			$customId = $connection->escape($this->getCustomId());

			$sql = <<<SQL
UPDATE
	`$tableName`
SET
	`$nameField` = '$name', `$domainIdField` = $domainId, `$languageIdField` = $languageId,
		`$slidingSpeedField` = $slidingSpeed, `$slidingAutoPlayEnableField` = $slidingAutoPlayEnableStatus,
			`$slidingLoopEnableField` = $slidingLoopEnableStatus,`$slidingDelayField` = $slidingDelay,
				`$slidesCountField` = $slidesCount, `$slidesRandomOrderEnableField` = $slidesRandomOrderEnableStatus,
					`$customIdField` = '$customId'
WHERE
	`$idField` = $id;
SQL;
			$connection->query($sql);
			return $this;
		}
	}
