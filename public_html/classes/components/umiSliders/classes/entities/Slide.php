<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Class Slide класс слайда
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	class Slide implements
		iSlide,
		\iUmiDataBaseInjector,
		\iUmiConstantMapInjector,
		\iClassConfigManager,
		\iUmiImageFileInjector {

		use \tUmiDataBaseInjector;
		use \tCommonCollectionItem;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;
		use \tUmiImageFileInjector;

		/** @var string $name название слайда */
		private $name;

		/** @var int $sliderId идентификатор слайдера, к которому относится слайд */
		private $sliderId;

		/** @var string|null $title заголовок слайда */
		private $title;

		/** @var string|null $imagePath путь до изображения слайда */
		private $imagePath;

		/** @var string|null $text текст слайда */
		private $text;

		/** @var string|null $link ссылка слайда */
		private $link;

		/** @var bool $openLinkInNewTab необходимость открытия ссылки слайда в отдельном окне */
		private $openLinkInNewTab;

		/** @var bool $isActive акивность слайда */
		private $isActive;

		/** @var int $order порядок вывода слайда */
		private $order;

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
					'name' => 'SLIDER_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setSliderId',
					'getter' => 'getSliderId',
				],
				[
					'name' => 'TITLE_FIELD_NAME',
					'required' => false,
					'setter' => 'setTitle',
					'getter' => 'getTitle',
				],
				[
					'name' => 'IMAGE_FIELD_NAME',
					'required' => false,
					'setter' => 'setImagePath',
					'getter' => 'getImagePath',
				],
				[
					'name' => 'TEXT_FIELD_NAME',
					'required' => false,
					'setter' => 'setText',
					'getter' => 'getText',
				],
				[
					'name' => 'LINK_FIELD_NAME',
					'required' => false,
					'setter' => 'setLink',
					'getter' => 'getLink',
				],
				[
					'name' => 'OPEN_IN_NEW_TAB_FIELD_NAME',
					'required' => false,
					'setter' => 'setItIsNeedToOpenLinkInNewTab',
					'getter' => 'isNeedToOpenLinkInNewTab',
				],
				[
					'name' => 'IS_ACTIVE_FIELD_NAME',
					'required' => false,
					'setter' => 'setActiveStatus',
					'getter' => 'isActive',
				],
				[
					'name' => 'ORDER_FIELD_NAME',
					'required' => false,
					'setter' => 'setOrder',
					'getter' => 'getOrder',
				]
			],
			'constructor' => [
				'callback' => [
					'before' => 'constructorCallbackBefore'
				]
			]
		];

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
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setSliderId($sliderId) {
			if (!is_numeric($sliderId)) {
				throw new \wrongParamException('Wrong value for slider id given');
			}

			$sliderId = (int) $sliderId;

			if ($this->getSliderId() != $sliderId) {
				$this->setUpdatedStatus(true);
			}

			$this->sliderId = $sliderId;
			return $this;
		}

		/** @inheritdoc */
		public function getSliderId() {
			return $this->sliderId;
		}

		/** @inheritdoc */
		public function setTitle($title) {
			if (!is_string($title)) {
				throw new \wrongParamException('Wrong value for title given');
			}

			$title = trim($title);

			if ($this->getTitle() != $title) {
				$this->setUpdatedStatus(true);
			}

			$this->title = $title;
			return $this;
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->title;
		}

		/** @inheritdoc */
		public function setImagePath($imagePath = null) {
			$imagePath = ($imagePath instanceof \iUmiImageFile) ? $imagePath->getFilePath() : (string) $imagePath;
			/** @var \iUmiImageFile $image */
			$imageFileHandlerClass = $this->getImageFileHandler();
			$image = new $imageFileHandlerClass($imagePath);

			if ($image->getIsBroken() && !startsWith($imagePath, '.')) {
				$image = new $imageFileHandlerClass('.' . $imagePath);
			}

			$imagePath = $image->getIsBroken() ? null : $image->getFilePath(true);
			$imagePath = ltrim($imagePath, '.');

			if ($this->getImagePath() != $imagePath) {
				$this->setUpdatedStatus(true);
			}

			$this->imagePath = $imagePath;
			return $this;
		}

		/** @inheritdoc */
		public function getImagePath() {
			return $this->imagePath;
		}

		/** @inheritdoc */
		public function setText($text) {
			if (!is_string($text)) {
				throw new \wrongParamException('Wrong value for text given');
			}

			$text = trim($text);

			if ($this->getText() != $text) {
				$this->setUpdatedStatus(true);
			}

			$this->text = $text;
			return $this;
		}

		/** @inheritdoc */
		public function getText() {
			return $this->text;
		}

		/** @inheritdoc */
		public function setLink($link) {
			if (!is_string($link)) {
				throw new \wrongParamException('Wrong value for link given');
			}

			$link = trim($link);

			if ($this->getLink() != $link) {
				$this->setUpdatedStatus(true);
			}

			$this->link = $link;
			return $this;
		}

		/** @inheritdoc */
		public function getLink() {
			return $this->link;
		}

		/** @inheritdoc */
		public function setItIsNeedToOpenLinkInNewTab($needToOpen) {
			$needToOpen = (bool) $needToOpen;

			if ($this->isNeedToOpenLinkInNewTab() != $needToOpen) {
				$this->setUpdatedStatus(true);
			}

			$this->openLinkInNewTab = $needToOpen;
			return $this;
		}

		/** @inheritdoc */
		public function isNeedToOpenLinkInNewTab() {
			return $this->openLinkInNewTab;
		}

		/** @inheritdoc */
		public function setActiveStatus($status) {
			$status = (bool) $status;

			if ($this->isActive() != $status) {
				$this->setUpdatedStatus(true);
			}

			$this->isActive = $status;
			return $this;
		}

		/** @inheritdoc */
		public function isActive() {
			return $this->isActive;
		}

		/** @inheritdoc */
		public function setOrder($order) {
			if (!is_numeric($order)) {
				throw new \wrongParamException('Wrong value for order given');
			}

			$order = (int) $order;

			if ($this->getOrder() != $order) {
				$this->setUpdatedStatus(true);
			}

			$this->order = $order;
			return $this;
		}

		/** @inheritdoc */
		public function getOrder() {
			return $this->order;
		}

		/**
		 * Выполняет обработку параметров инициализации до нее и возвращает обработанные параметры
		 * @param array $params параметры инициализации
		 * @return array
		 */
		public function constructorCallbackBefore(array $params) {
			$this->setImageFileHandler(
				new \umiImageFile(__FILE__)
			);

			$imagesClass = get_class($this->getImageFileHandler());
			$map = $this->getMap();

			foreach ($params as $fieldName => &$imagePath) {
				if ($fieldName !== $map->get('IMAGE_FIELD_NAME')) {
					continue;
				}

				$imagePath = ($imagePath instanceof $imagesClass) ? $imagePath : new $imagesClass($imagePath);
			}

			return $params;
		}

		/** @inheritdoc */
		public function move(\iUmiCollectionItem $baseEntity, $mode) {
			if (!$baseEntity instanceof iSlide) {
				throw new ExpectSlideException('Incorrect base entity given, iSlide expected');
			}

			$constantsMap = $this->getMap();

			if ($mode == $constantsMap->get('MOVE_BEFORE_MODE_KEY')) {
				$newSlideOrder = $this->getPreviousOrder(
					$baseEntity->getOrder()
				);

				return $this->setOrder($newSlideOrder);
			}

			if ($mode == $constantsMap->get('MOVE_AFTER_MODE_KEY')) {
				$newSlideOrder = $this->getNextOrder(
					$baseEntity->getOrder()
				);

				return $this->setOrder($newSlideOrder);
			}

			throw new \wrongParamException('Unsupported move mode given');
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return $this;
			}

			$tableName = $this->getEscapedConstant('TABLE_NAME');
			$idField = $this->getEscapedConstant('ID_FIELD_NAME');
			$nameField = $this->getEscapedConstant('NAME_FIELD_NAME');
			$sliderIdField = $this->getEscapedConstant('SLIDER_ID_FIELD_NAME');
			$titleField = $this->getEscapedConstant('TITLE_FIELD_NAME');
			$imageField = $this->getEscapedConstant('IMAGE_FIELD_NAME');
			$textField = $this->getEscapedConstant('TEXT_FIELD_NAME');
			$linkField = $this->getEscapedConstant('LINK_FIELD_NAME');
			$openInNewTabField = $this->getEscapedConstant('OPEN_IN_NEW_TAB_FIELD_NAME');
			$isActiveField = $this->getEscapedConstant('IS_ACTIVE_FIELD_NAME');
			$orderField = $this->getEscapedConstant('ORDER_FIELD_NAME');

			$connection = $this->getConnection();
			$id = (int) $this->getId();
			$name = $connection->escape($this->getName());
			$sliderId = (int) $this->getSliderId();
			$title = $connection->escape($this->getTitle());
			$image = $connection->escape($this->getImagePath());
			$image = empty($image) ? 'NULL' : "'$image'";
			$text = \umiObjectProperty::filterInputString($this->getText());
			$link = $connection->escape($this->getLink());
			$openInNewTab = (int) $this->isNeedToOpenLinkInNewTab();
			$isActive = (int) $this->isActive();
			$order = (int) $this->getOrder();

			$sql = <<<SQL
UPDATE
	`$tableName`
SET
	`$nameField` = '$name', `$sliderIdField` = $sliderId, `$titleField` = '$title',
		`$imageField` = $image, `$textField` = '$text', `$linkField` = '$link',
			`$openInNewTabField` = $openInNewTab, `$isActiveField` = $isActive,  `$orderField` = $order
WHERE
	`$idField` = $id;
SQL;
			$connection->query($sql);
			return $this;
		}

		/**
		 * Вычисляет порядковый номер слайда, следующего за слайдом с заданным порядковым номером
		 * @param int $slideOrder порядковый номер слайда, относительно которого производятся вычисления
		 * @return int результат вычисления
		 */
		private function getNextOrder($slideOrder) {
			return (int) $slideOrder + 1;
		}

		/**
		 * Вычисляет порядковый номер слайда, следующего до слайда с заданным порядковым номером
		 * @param int $slideOrder порядковый номер слайда, относительно которого производятся вычисления
		 * @return int результат вычисления
		 */
		private function getPreviousOrder($slideOrder) {
			return (int) $slideOrder - 1;
		}
	}
