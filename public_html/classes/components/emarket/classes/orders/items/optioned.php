<?php

	/**
	 * Класс товарного наименования заказа типа "Опционный".
	 * Добавляет возможность применять к товарному наименованию опции.
	 *
	 * Опция - это значения поля типа "Составное" товара (объекта каталога).
	 * Опция представляет собой ссылку на объект из некоторого справочника
	 * с возможностью добавить наценку на данный объект.
	 *
	 * Например:
	 *
	 * Футболка стоит 100 рублей, а футболка с черным цветом - 120 рублей, а с белым - 130 рублей.
	 *
	 * Черный - 20 рублей и Белый - 30 рублей - это опции, а цвет - это поле типа "Составное", где
	 * черный и белый - объекты справочника, привязанного к полю.
	 *
	 * Данный тип опции применяется по умолчанию.
	 *
	 * Чтобы применить определенный тип товарного наименования,
	 * нужно произвести соответствующие настройки в config.ini,
	 * см. метод orderItem::getItemTypeId().
	 */
	class optionedOrderItem extends orderItem {

		/**
		 * @var array $options список опций товарного наименования.
		 *
		 * Схема значения:
		 *
		 * [
		 *        Название поля => [
		 *            'option-id'    => id связанного объекта справочника,
		 *            'price'        => стоимость опции,
		 *            'field-name'=> название поля
		 *        ]
		 * ]
		 *
		 * Пример:
		 *
		 * [
		 *        color => [
		 *            'option-id'    => 123,
		 *            'price'        => 100,
		 *            'field-name'=> color
		 *        ]
		 * ]
		 */
		protected $options = [];

		/**
		 * Конструктор.
		 * Вызывает загрузку списка опций товарного наименования.
		 * @param iUmiObject $object объект-источник данных для товарного наименования
		 */
		public function __construct(iUmiObject $object) {
			parent::__construct($object);
			$this->reloadOptions();
		}

		/**
		 * Возвращает список опций товарного наименования
		 * @return array
		 */
		public function getOptions() {
			return $this->options;
		}

		/**
		 * Определяет применены ли опции к товарному наименованию
		 * @param array $optionList проверяемые опции
		 * @return bool
		 */
		public function hasOptions(array $optionList) {
			$itemOptionList = $this->getOptions();

			if (count($itemOptionList) != count($optionList)) {
				return false;
			}

			foreach ($optionList as $optionName => $optionId) {
				$itemOption = getArrayKey($itemOptionList, $optionName);

				if (getArrayKey($itemOption, 'option-id') != $optionId) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Применяет список опций к товарному наименованию
		 * @param array $optionList список опций
		 * @return $this
		 */
		public function appendOptionList(array $optionList) {

			foreach ($optionList as $optionName => $optionId) {
				if ($optionId) {
					$this->appendOption($optionName, $optionId);
				} else {
					$this->removeOption($optionName);
				}
			}

			return $this;
		}

		/**
		 * Добавляет опцию к товарному наименованию.
		 * @param string $propertyName guid поля
		 * @param int $optionId идентификатор связанного объекта
		 * @param bool|float $price цена опции
		 */
		public function appendOption($propertyName, $optionId, $price = false) {
			$options = $this->object->options;

			if (!$price) {
				$price = $this->getOptionPrice($propertyName, $optionId);
			}

			$integer = $this->getIntegerValue($propertyName, $optionId);

			$options[$propertyName] = [
				'varchar' => $propertyName,
				'rel' => (string) $optionId,
				'float' => $price,
				'int' => $integer
			];

			$this->object->options = $options;
			$this->reloadOptions();
		}

		/**
		 * Удаляет опцию из товарного наименования.
		 * @param string $propertyName guid поля
		 */
		public function removeOption($propertyName) {
			if (!isset($this->options[$propertyName])) {
				return;
			}

			$options = $this->object->options;

			foreach ($options as $i => $optionInfo) {
				if ($optionInfo['varchar'] == $propertyName) {
					unset($options[$i]);
				}
			}

			$this->object->options = $options;
			$this->reloadOptions();
		}

		/**
		 * @internal
		 * Возвращает оригинальную стоимость одного товарного наименования суммированную со стоимостью опций
		 * @todo: Подумать как можно обойтись без вычисления в данной методе - он по идее не должен этого делать
		 * @return float
		 */
		public function getOriginalPrice() {
			return parent::getOriginalPrice() + $this->getOptionsPrice();
		}

		/**
		 * Возвращает стоимость примененных опций
		 * @return float
		 */
		public function getOptionsPrice() {
			$options = $this->getOptions();
			$price = 0;

			foreach ($options as $optionInfo) {
				$optionPrice = getArrayKey($optionInfo, 'price');

				if ($optionPrice) {
					$price += (float) $optionPrice;
				}
			}

			return $price;
		}

		/**
		 * Устанавливает цену опции
		 * @param string $propertyName guid поля
		 * @param float $price цена
		 * @return bool
		 */
		public function setOptionPrice($propertyName, $price) {
			if (!isset($this->options[$propertyName])) {
				return false;
			}

			$optionId = $this->options[$propertyName]['option-id'];
			$this->removeOption($propertyName);
			$this->appendOption($propertyName, $optionId, $price);

			return true;
		}

		/**
		 * Получает список всех опций и меняет название товарного наименования
		 * и вызывает родительский метод
		 * @param bool $recalculateDiscount нужно ли заново пересчитывать скидку
		 * @param bool $useAppliedDiscount нужно ли использовать уже примененную скидку
		 * или произвести поиск наиболее подходящей
		 * @return bool
		 */
		public function refresh($recalculateDiscount = true, $useAppliedDiscount = false) {
			$element = $this->getRelatedProduct();

			if ($element instanceof iUmiHierarchyElement) {
				$name = $element->getName();
				$options = [];
				$objects = umiObjectsCollection::getInstance();

				foreach ($this->getOptions() as $optionInfo) {
					$optionId = $optionInfo['option-id'];
					$option = $objects->getObject($optionId);

					if ($option instanceof iUmiObject) {
						$options[] = $option->getName();
					}
				}

				if (umiCount($options)) {
					$name .= ' (' . implode(', ', $options) . ')';
				}

				$this->object->setName($name);
			}

			return parent::refresh($recalculateDiscount, $useAppliedDiscount);
		}

		/** @inheritdoc */
		public function containsAppliedModifier() {
			return (bool) $this->getOptions() || parent::containsAppliedModifier();
		}

		/** Загружает опции */
		protected function reloadOptions() {
			$options = [];
			$objectOptions = [];

			foreach ($this->object->options as $optionInfo) {
				$objectOptions[$optionInfo['varchar']] = $optionInfo;

				$options[$optionInfo['varchar']] = [
					'option-id' => getArrayKey($optionInfo, 'rel'),
					'price' => getArrayKey($optionInfo, 'float'),
					'field-name' => getArrayKey($optionInfo, 'varchar')
				];
			}

			$this->object->options = $objectOptions;
			$this->options = $options;
		}

		/**
		 * Возвращает стоимость опции
		 * @param string $propertyName guid поля
		 * @param int $optionId идентификатор связанного объекта
		 * @return bool|float
		 */
		protected function getOptionPrice($propertyName, $optionId) {
			$itemLinks = $this->object->item_link;

			if (is_array($itemLinks) && umiCount($itemLinks)) {
				/** @var iUmiHierarchyElement $element */
				list($element) = $itemLinks;

				$params = [
					'filter' => [
						'rel' => $optionId
					]
				];

				$value = $element->getValue($propertyName, $params);

				if (is_array($value) && umiCount($value)) {
					return $price = getArrayKey($value[0], 'float');
				}
			}

			return false;
		}

		/**
		 * Возвращает числовое значение опции
		 * @param string $propertyName guid поля
		 * @param int $optionId идентификатор связанного объекта
		 * @return bool|int
		 */
		protected function getIntegerValue($propertyName, $optionId) {
			$itemLinks = $this->object->item_link;

			if (is_array($itemLinks) && umiCount($itemLinks)) {
				/** @var iUmiHierarchyElement $element */
				list($element) = $itemLinks;

				$params = [
					'filter' => [
						'rel' => $optionId
					]
				];

				$value = $element->getValue($propertyName, $params);

				if (is_array($value) && umiCount($value)) {
					return $integer = getArrayKey($value[0], 'int');
				}
			}

			return false;
		}

		/** @deprecated */
		public function getItemPrice() {
			return $this->getOriginalPrice();
		}
	}
