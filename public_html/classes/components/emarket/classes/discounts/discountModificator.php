<?php

	/**
	 * Базовый класс модификатора цены скидки абстракного типа.
	 * Одновременно является родительским классом всех типов модификаторов
	 * и предоставляет интерфейс для создания и получения модификатора конкретного типа.
	 *
	 * По умолчанию в системе существуют следующие типы модификаторов цен скидки:
	 *
	 * 1) "Абсолютная скидка";
	 * 2) "Процентная скидка";
	 *
	 * Модификатор отвечает за правило расчета значения скидки.
	 */
	abstract class discountModificator extends umiObjectProxy {

		/** @var string $name название модификатора */
		protected $name;

		/** @var discount $discount скидка */
		protected $discount;

		/**
		 * Создает и возвращает модификатор цены скидки
		 * @param discount $discount скидка
		 * @param iUmiObject $modTypeObject тип модификатора скидки
		 * @return bool|discountModificator
		 * @throws coreException
		 */
		public static function create(discount $discount, iUmiObject $modTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$modifierTypeId = null;
			$modifierTypeGUID = (string) $modTypeObject->getValue('modificator_type_guid');

			if ($modifierTypeGUID !== '') {
				$modifierTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($modifierTypeGUID);
			} else {
				$modifierTypeId = $modTypeObject->getValue('modificator_type_id');
			}

			$objectId = $objects->addObject('', $modifierTypeId);
			$object = $objects->getObject($objectId);

			if (!$object instanceof iUmiObject) {
				return false;
			}

			$object->setValue('modificator_type_id', $modTypeObject->getId());
			$object->commit();
			return self::get($objectId, $discount);
		}

		/**
		 * Возвращает модификатор цены скидки по идентификатор его объекта-источника
		 * @param int $modObjectId идентификатор модификатора скидки
		 * @param discount $discount скидка
		 * @return bool|discountModificator
		 */
		public static function get($modObjectId, discount $discount) {
			$objects = umiObjectsCollection::getInstance();
			$modObject = $objects->getObject($modObjectId);

			if (!$modObject instanceof iUmiObject) {
				return false;
			}

			$modifierTypeId = $modObject->getValue('modificator_type_id');
			umiObjectProperty::loadPropsData([$modObject->getId(), $modifierTypeId]);
			$codeName = self::getCodeName($modifierTypeId);

			if (!$codeName) {
				return false;
			}

			$className = $codeName . 'DiscountModificator';

			if (!class_exists($className)) {
				self::includeModificator($codeName);
			}

			if (!class_exists($className)) {
				return false;
			}

			$modificator = new $className($modObject, $discount, $codeName);
			return ($modificator instanceof discountModificator) ? $modificator : false;
		}

		/**
		 * Возвращает список модификаторов цены скидки, применимых к заданному типу скидки
		 * @param bool|int $discountTypeId идентификатор типа скидки
		 * @param bool $orderById сортировка списка модификаторов по ID
		 * @return array|mixed
		 * @throws coreException
		 * @throws selectorException
		 */
		public static function getList($discountTypeId = false, $orderById = true) {
			$objectTypeId = self::getModificatorType()->getId();

			$sel = new selector('objects');
			$sel->types('object-type')->id($objectTypeId);

			if ($discountTypeId) {
				$sel->where('modificator_discount_types')->equals($discountTypeId);
			}

			if ($orderById) {
				$sel->order('id')->asc();
			}

			$sel->option('load-all-props')->value(true);
			$sel->option('no-length')->value(true);
			return $sel->result();
		}

		/**
		 * Применяет скидку с стоимости и возвращает стоимость за
		 * вычетом скидки
		 * @param float $price стоимость
		 * @return float
		 */
		abstract public function recalcPrice($price);

		/**
		 * Конструктор
		 * @param iUmiObject $object объект-источник данных для модификатора скидки
		 * @throws Exception
		 */
		protected function __construct(iUmiObject $object) {
			$args = func_get_args();

			$modifier = array_shift($args);

			if (!$modifier instanceof iUmiObject) {
				throw new Exception('Modifier expected for creating modifier');
			}

			$discount = array_shift($args);

			if (!$discount instanceof discount) {
				throw new Exception('Discount expected for creating modifier');
			}

			$discountName = array_shift($args);

			if (!is_string($discountName)) {
				throw new Exception('Discount name expected for creating modifier');
			}

			parent::__construct($modifier);

			$this->name = $discountName;
			$this->discount = $discount;
		}

		/**
		 * Возвращает объектный тип модификатора цены скидки
		 * @return iUmiObjectType
		 * @throws coreException
		 */
		private static function getModificatorType() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('emarket', 'discount_modificator_type');

			if (!$objectTypeId) {
				throw new coreException('Required data type (emarket::discount_modificator_type) not found');
			}

			return $objectTypes->getType($objectTypeId);
		}

		/**
		 * Подключает файл с реализацией типа модификатора цены скидки и возвращает результат операции
		 * @param string $modificatorName имя файла с реализацией скидки
		 * @return bool
		 */
		private static function includeModificator($modificatorName) {
			static $included = [];

			if (isset($included[$modificatorName])) {
				return $included[$modificatorName];
			}

			$filePath = dirname(__FILE__) . '/modificators/' . $modificatorName . '.php';

			if (is_file($filePath)) {
				/** @noinspection PhpIncludeInspection */
				require_once $filePath;
				return $included[$modificatorName] = true;
			}

			return $included[$modificatorName] = false;
		}

		/**
		 * Возвращает строковой идентификатор типа модификатора по его id
		 * @param int $modTypeObjectId идентификатор типа модификатора
		 * @return string|bool
		 */
		private static function getCodeName($modTypeObjectId) {
			static $cache = [];

			if (isset($cache[$modTypeObjectId])) {
				return $cache[$modTypeObjectId];
			}

			$objects = umiObjectsCollection::getInstance();
			$modTypeObject = $objects->getObject($modTypeObjectId);
			$cache[$modTypeObjectId] =
				($modTypeObject instanceof iUmiObject) ? trim($modTypeObject->getValue('modificator_codename')) : false;
			return $cache[$modTypeObjectId];
		}
	}
