<?php

	/**
	 * Класс для генерации индекса фильтруемых полей сущностей (объектов и страниц) системы
	 * Варианты использования:
	 *
	 * 1) Создать индекс для сущностей определенного иерархического типа:
	 *
	 *   $indexGenerator = new FilterIndexGenerator(56, 'objects');
	 *   $indexGenerator->run();
	 *
	 * 2) Создать индекс для страниц с ограничением по иерархии:
	 *
	 *   $indexGenerator = new FilterIndexGenerator(56, 'pages');
	 *   $indexGenerator->setHierarchyCondition(4, 100);
	 *   $indexGenerator->run();
	 *
	 * 3) Создать индекс в несколько итераций:
	 *
	 *   $indexGenerator = new FilterIndexGenerator(56, 'pages');
	 *   $indexGenerator->setLimit(50);
	 *   for ($i = 0; !$indexGenerator->isDone(); $i++) {
	 *      $indexGenerator->run();
	 *   }
	 */
	class FilterIndexGenerator {

		/** @var int $hierarchyTypeId идентификатор иерархического типа, к которому принадлежат индексируемые сущности */
		private $hierarchyTypeId;

		/** @var string $tableName имя таблицы в бд, которая будет хранить индекс фильтров */
		private $tableName;

		/** @var string $temporaryTableName имя таблицы в бд, в которую будет помещаться индекс во время его генерации */
		private $temporaryTableName;

		/** @var null|int $parentId идентификатор страницы, дочерние страницы которых нужно проиндексировать */
		private $parentId;

		/** @var int $level уровень вложенности индексируемых страниц, относительно $parentId */
		private $level = 1;

		/** @var bool|int ограничение на количество индексируемых сущностей за одну итерацию индексации */
		private $limit = false;

		/** @var array|null объекты фильтруемых полей, данные которых составляют индекс */
		private $filteredFields;

		/** @var string $entitiesType тип индексируемых сущностей (pages|objects) */
		private $entitiesType;

		/** @var bool|int смещение процесса индексации, то есть сколько сущностей уже было проиндексировано */
		private $offset = false;

		/** @var umiObjectsCollection $umiObjects экземпляр класса коллекции объектов системы */
		private $umiObjects;

		/** @var umiHierarchy $umiHierarchy экземпляр класса коллекции страниц системы */
		private $umiHierarchy;

		/** @var int $indexedEntitiesCounter сколько сущностей было проиндексировано за текущую итерацию */
		private $indexedEntitiesCounter = 0;

		/** @var bool завершен ли процесс индексации */
		private $isDone = false;

		/** @var string $stateDirectoryPath путь до директории, в которой хранится состояние */
		private $stateDirectoryPath;

		/* @const префикс имени таблицы для хранения индекса фильтров */
		const TABLE_NAME_PREFIX = 'cms3_filter_index_';

		/* @const разделитель значений полей с несколькими значениями */
		const MULTIPLE_VALUE_SEPARATOR = ';';

		/* @const максимальное количество создаваемых индексов */
		const MAX_KEYS_COUNT = 64;

		/* @const максимальное количество колонок таблицы */
		const MAX_COLUMNS_COUNT = 1000;

		/* @const драйвер MySQL */
		const MYSQL_TABLE_ENGINE = 'InnoDB';

		/**
		 * Конструктор
		 * @param int $hierarchyTypeId идентификатор иерархического типа, к которому принадлежат индексируемые сущности
		 * @param string $entitiesType тип индексируемых сущностей (pages|objects)
		 * @throws publicAdminException
		 */
		public function __construct($hierarchyTypeId, $entitiesType) {
			$this->setHierarchyTypeId($hierarchyTypeId);
			$this->setEntitiesType($entitiesType);
			$this->setTableName();
			$this->umiObjects = umiObjectsCollection::getInstance();
			$this->umiHierarchy = umiHierarchy::getInstance();
		}

		/**
		 * Устанавливает ограничени на количество индексируемых объектов
		 * @param int $limit ограничени на количество индексируемых объектов
		 * @throws publicAdminException если $limit не является числом
		 */
		public function setLimit($limit) {
			if (!is_numeric($limit)) {
				throw new publicAdminException(__METHOD__ . ': correct limit expected,' . $limit . ' given');
			}
			$this->limit = (int) $limit;
		}

		/**
		 * Запускает процесс индексации и возвращает количество проиндексированных сущностей
		 * @return int
		 * @throws publicAdminException
		 * @throws publicException
		 * @throws Exception
		 */
		public function run() {
			$isSetLimit = is_numeric($this->getLimit()) ? true : false;
			if (!$isSetLimit) {
				$this->deleteStoredOffset();
			}
			$offset = $this->getOffset();
			if (!$offset) {
				$this->deleteSavedFilteredFields();
				$this->dropTemporaryTable();
				$this->createTable();
			}
			$this->generateIndex();
			if ($isSetLimit) {
				$this->storeOffset();
			}
			if ($this->isDone() || !$isSetLimit) {
				$this->releaseIndex();
			}
			return $this->getIndexedEntitiesCounter();
		}

		/**
		 * Возвращает идентификатор иерархического типа, к которому принадлежат индексируемые сущности
		 * @return int
		 */
		public function getHierarchyTypeId() {
			return $this->hierarchyTypeId;
		}

		/**
		 * Возвращает тип индексируемых сущностей (pages|objects)
		 * @return string
		 */
		public function getEntitiesType() {
			return $this->entitiesType;
		}

		/**
		 * Очищает таблицу с индексом, возвращает результат операции
		 * @return bool
		 * @throws Exception
		 */
		public function flushIndex() {
			return $this->truncateTable();
		}

		/**
		 * Возвращает имя таблицы в бд, которая будет хранить индекс фильтров
		 * @return string
		 */
		public function getTableName() {
			return $this->tableName;
		}

		/**
		 * Возвращает объекты фильтруемых полей, данные которых составляют индекс
		 * @return array|null
		 * @throws publicAdminException
		 */
		public function getFilteredFields() {
			if ($this->filteredFields === null) {
				$this->loadFilteredFields();
			}
			return $this->filteredFields;
		}

		/**
		 * Возвращает имена системных полей индекса
		 * @return array
		 * @throws publicAdminException
		 */
		public function getSystemFields() {
			$commonFieldsData = $this->getCommonFieldsData();
			return array_keys($commonFieldsData);
		}

		/**
		 * Завершен ли процесс индексации
		 * @return bool
		 */
		public function isDone() {
			return $this->isDone;
		}

		/**
		 * Устанавливает ограничение на индексируемые сущности по иерархии
		 * @param int $parentId идентификатор родительской страницы для индексируемых страниц
		 * @param int $level уровень вложенности индексируемых страницы, относительно $parentId
		 * @throws publicAdminException если текущие индексируемые сущности не являются страницами
		 * @throws publicAdminException если на чтение страницы с ид $parentId нет прав или она не существует
		 * @throws publicAdminException если $level не является числом
		 */
		public function setHierarchyCondition($parentId, $level = 1) {
			if ($this->getEntitiesType() !== 'pages') {
				throw new publicAdminException(__METHOD__ . ': hierarchy condition is allowed only for pages');
			}

			if (!$this->umiHierarchy->isAllowed($parentId)) {
				throw new publicAdminException(__METHOD__ . ': page with id: ' . $parentId . ' is not allowed');
			}

			if (!is_numeric($level)) {
				throw new publicAdminException(__METHOD__ . ': wrong level given: ' . $level);
			}

			$this->parentId = $parentId;
			$this->level = (int) $level;
			$this->setTableName('_' . $parentId);
		}

		/**
		 * Создает или обновляет индекс для сущности
		 * @param iUmiHierarchyElement|iUmiObject $entity индексируемая сущности
		 * @return bool
		 * @throws publicAdminException если сущность $entity имеет некорректный тип
		 */
		public function updateEntityIndex($entity) {
			switch (true) {
				case $entity instanceof iUmiHierarchyElement: {
					return $this->updatePageIndex($entity);
				}
				case $entity instanceof iUmiObject: {
					return $this->updateObjectIndex($entity);
				}
				default: {
					throw new publicAdminException(__METHOD__ . ': wrong entity given');
				}
			}
		}

		/**
		 * Удаляет индекс сущности
		 * @param int $entityId идентификатор сущности
		 * @return bool
		 * @throws publicAdminException если запрос к бд завершился ошибкой
		 * @throws Exception
		 */
		public function dropEntityIndex($entityId) {
			if (!is_numeric($entityId)) {
				throw new publicAdminException(__METHOD__ . ': wrong entity id given');
			}
			$entityId = (int) $entityId;
			$tableName = $this->getTableName();
			$sql = "DELETE FROM `$tableName` WHERE `id` = $entityId;";
			$connection = ConnectionPool::getInstance()->getConnection();
			try {
				$connection->query($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			return true;
		}

		/**
		 * Удаляет "боевую" таблицу с индексом
		 * @throws Exception
		 */
		public function dropProductionTable() {
			$this->dropTable();
		}

		/**
		 * Удаляет временную таблицу с индексом
		 * @throws Exception
		 */
		public function dropTemporaryTable() {
			$this->dropTable(false);
		}

		/**
		 * Удаляет таблицу с индексом.
		 *
		 * Функцию для внутреннего использования, в клиентском коде
		 * нужно использовать функции:
		 * @see FilterIndexGenerator::dropTemporaryTable()
		 * @see FilterIndexGenerator::dropProductionTable()
		 *
		 * @param bool $isProduction "боевая" или временная таблица
		 * @throws Exception
		 */
		public function dropTable($isProduction = true) {
			$tableName = $isProduction ? $this->getTableName() : $this->getTemporaryTableName();
			$sql = "DROP TABLE IF EXISTS `$tableName`;";
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->queryResult($sql);
		}

		/**
		 * Удаляет кеш со смещением выборки индексируемых сущностей
		 * @return bool
		 * @throws publicAdminException если не удалось удалить файл
		 */
		public function deleteStoredOffset() {
			$filePath = $this->getStateDirectoryPath() . md5($this->getTemporaryTableName() . 'offset');
			if (!file_exists($filePath)) {
				return false;
			}
			$isDeleted = unlink($filePath);
			if (!$isDeleted) {
				throw new publicAdminException(__METHOD__ . ': cant delete stored offset');
			}
			return true;
		}

		/**
		 * Устанавливает путь до директории, в которую класс сохраняется свое состояние и кеш
		 * @param string $path путь до директории
		 * @throws publicAdminException
		 */
		public function setStateDirectoryPath($path) {
			if (!is_string($path) || !is_dir($path)) {
				throw new publicAdminException('Incorrect state directory path given');
			}

			$this->stateDirectoryPath = $path;
		}

		/**
		 * Удаляет файл с кешем фильтруемых полей
		 * @return bool
		 * @throws publicAdminException если не удалось удалить файл
		 */
		public function deleteSavedFilteredFields() {
			$filePath = $this->getStateDirectoryPath() . md5($this->getTemporaryTableName() . 'fields');
			if (!file_exists($filePath)) {
				return false;
			}
			$isDeleted = unlink($filePath);
			if (!$isDeleted) {
				throw new publicAdminException(__METHOD__ . ': cant delete stored fields');
			}

			return true;
		}

		/**
		 * Инициирует обновление индекса страницы
		 * @param iUmiHierarchyElement $page страница
		 * @return bool
		 * @throws publicAdminException если запрос к бд завершился ошибкой
		 * @throws Exception
		 */
		private function updatePageIndex(iUmiHierarchyElement $page) {
			$indexHierarchyTypeId = $this->getHierarchyTypeId();
			$pageHierarchyTypeId = $page->getTypeId();

			if ($indexHierarchyTypeId !== $pageHierarchyTypeId) {
				return false;
			}

			$pageId = $page->getId();
			$parentId = $this->getParentId();

			if (is_numeric($parentId)) {
				$level = $this->getLevel();
				$sql =
					"SELECT `rel_id` FROM `cms3_hierarchy_relations` WHERE `level` <= $level AND `child_id` = $pageId AND `rel_id` = $parentId;";
				$connection = ConnectionPool::getInstance()->getConnection();
				try {
					$result = $connection->queryResult($sql);
				} catch (databaseException $e) {
					throw new publicAdminException(
						__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
					);
				}
				if ($result->length() == 0) {
					return false;
				}
			}

			$commonFieldsData = $this->getCommonFieldsData();
			$filteredFields = $this->getFilteredFields();
			/* @var iUmiHierarchyElement $page */
			$pageIndex = $this->getEntityData($page, $commonFieldsData, $filteredFields);
			$this->saveEntityIndex($pageId, $pageIndex, true);
			return true;
		}

		/**
		 * Инициирует обновление индекса объекта
		 * @param iUmiObject $object объект
		 * @return bool
		 * @throws publicAdminException
		 */
		private function updateObjectIndex(iUmiObject $object) {
			/* @var iUmiObject $object */
			$indexHierarchyTypeId = $this->getHierarchyTypeId();
			$objectTypeId = $object->getTypeId();
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$objectHierarchyTypeId = $umiObjectTypes->getHierarchyTypeIdByObjectTypeId($objectTypeId);

			if ($indexHierarchyTypeId !== $objectHierarchyTypeId) {
				return false;
			}

			$commonFieldsData = $this->getCommonFieldsData();
			$filteredFields = $this->getFilteredFields();
			$objectIndex = $this->getEntityData($object, $commonFieldsData, $filteredFields);
			$this->saveEntityIndex($object->getId(), $objectIndex, true);
			return true;
		}

		/**
		 * Обновляет или создает индекс сущности
		 * @param int $entityId идентификатор сущности
		 * @param array $index данные индекса, которые требуется сохранить
		 * @param bool $isProd сохранять индекс во временную или в "боевую" таблицу
		 * @return bool
		 * @throws publicAdminException если запрос к бд завершился ошибкой
		 * @throws Exception
		 */
		private function saveEntityIndex($entityId, array $index, $isProd = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$tableName = $isProd ? $this->getTableName() : $this->getTemporaryTableName();
			$index = array_map([__CLASS__, 'prepareValue'], $index);
			$columnNames = array_keys($index);
			$columns = '`id`, `' . implode('`, `', $columnNames) . '`';
			$values = "$entityId, " . implode(', ', $index);
			$sql = "REPLACE INTO `$tableName` ($columns) VALUES ($values);";
			try {
				$connection->query($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			return true;
		}

		/**
		 * Подготавливает данные для записи в бд
		 * @param mixed $value данные
		 * @return string
		 * @throws Exception
		 */
		private function prepareValue($value) {
			if (is_string($value)) {
				$connection = ConnectionPool::getInstance()->getConnection();
				return "'" . $connection->escape($value) . "'";
			}

			return $value;
		}

		/**
		 * Запускает генерацию и сохранение индекса фильтров
		 * @return bool
		 * @throws publicAdminException
		 * @throws selectorException
		 */
		private function generateIndex() {
			$entities = $this->getEntities();
			$counter = 0;

			if (empty($entities)) {
				return $this->setIndexedEntitiesCounter($counter);
			}

			$commonFieldsData = $this->getCommonFieldsData();
			$filteredFields = $this->getFilteredFields();
			/* @var iUmiObject|iUmiHierarchyElement $entity */
			foreach ($entities as $entity) {
				$entityId = $entity->getId();
				$entityIndex = $this->getEntityData($entity, $commonFieldsData, $filteredFields);
				$this->saveEntityIndex($entityId, $entityIndex);
				$this->unloadEntity($entityId);
				$counter++;
			}

			return $this->setIndexedEntitiesCounter($counter);
		}

		/**
		 * Устанавливает количество проиндексированных сущностей
		 * @param int $number количество
		 * @return bool
		 * @throws publicAdminException если $number не является числом
		 */
		private function setIndexedEntitiesCounter($number) {
			if (!is_numeric($number)) {
				throw new publicAdminException(__METHOD__ . ': correct number expected,' . $number . ' given');
			}
			$this->indexedEntitiesCounter = (int) $number;
			return true;
		}

		/**
		 * Возвращает количество проиндексированных сущностей
		 * @return int
		 */
		private function getIndexedEntitiesCounter() {
			return $this->indexedEntitiesCounter;
		}

		/**
		 * Выгружает объект сущности из памяти
		 * @param int $entityId ид сущности
		 * @param bool|string $entityType тип выгружаемой сущности (pages|objects)
		 * @throws publicAdminException если тип выгружаемой сущности не был определен
		 */
		private function unloadEntity($entityId, $entityType = false) {
			if (!is_string($entityType)) {
				$entityType = $this->getEntitiesType();
			}
			switch ($entityType) {
				case 'pages': {
					$this->umiHierarchy->unloadElement($entityId);
					break;
				}
				case 'objects': {
					$this->umiObjects->unloadObject($entityId);
					break;
				}
				default: {
					throw new publicAdminException(
						__METHOD__ . ': correct entity type expected, ' . $entityType . ' given'
					);
				}
			}
		}

		/**
		 * Возвращает данные индекса сущности
		 * @param iUmiEntinty|iUmiObject|iUmiHierarchyElement $entity объект сущности
		 * @param array $commonFieldsData данные системных полей сущности
		 * @param array $filteredFields данные фильтруемых полей сущности
		 * @return array
		 * @throws publicAdminException
		 */
		private function getEntityData(iUmiEntinty $entity, array $commonFieldsData, array $filteredFields) {
			$indexData = [];
			foreach ($commonFieldsData as $key => $value) {
				$indexData[$key] = $entity->$value();
			}
			/* @var iUmiField $value */
			foreach ($filteredFields as $key => $value) {
				$propertyValue = $this->getPropertyValue($value, $entity->getValue($key));
				if ($propertyValue !== null) {
					$indexData[$key] = $propertyValue;
				}
			}
			return $indexData;
		}

		/**
		 * Возвращает значение поля, подготовленное к записи в индекс
		 * @param iUmiField $field объект поля
		 * @param mixed $value значение поля
		 * @return null|string
		 * @throws publicAdminException если передано поле с неподдерживаемым типом
		 */
		private function getPropertyValue(iUmiField $field, $value) {

			switch ($field->getDataType()) {
				case 'wysiwyg':
				case 'text':
				case 'color':
				case 'string': {
					return $this->getStringValue($value);
				}
				case 'int':
				case 'price':
				case 'password':
				case 'float':
				case 'counter': {
					return $this->getNumericValue($value);
				}
				case 'date': {
					return $this->getDateValue($value);
				}
				case 'boolean': {
					return (int) $value;
				}
				case 'video_file':
				case 'swf_file':
				case 'img_file':
				case 'file': {
					return $this->getFileValue($value);
				}
				case 'multiple_image': {
					return (umiCount($value) == 0) ? 0 : 1;
				}
				case 'relation': {
					return $this->getRelationValue($field, $value);
				}
				case 'symlink': {
					return $this->getSymlinkValue($value);
				}
				case 'tags': {
					return $this->getTagsValue($value);
				}
				case 'optioned': {
					return $this->getOptionedValue($value);
				}
				default: {
					throw new publicAdminException(__METHOD__ . ': unsupported field type: ' . $field->getDataType());
				}
			}
		}

		/**
		 * Возвращает значение файлового поля, подготовленное к записи в индекс
		 * @param mixed $value значение поля
		 * @return int|null
		 */
		private function getFileValue($value) {
			if ($value instanceof iUmiFile || $value instanceof iUmiImageFile) {
				return (int) $value->getIsBroken();
			}
			return null;
		}

		/**
		 * Возвращает значение числового поля, подготовленное к записи в индекс
		 * @param mixed $value значение поля
		 * @return int|null
		 */
		private function getNumericValue($value) {
			if (is_numeric($value)) {
				return $value;
			}
			return null;
		}

		/**
		 * Возвращает значение составного поля, подготовленное к записи в индекс
		 * @param mixed $value значение поля
		 * @return string|null
		 * @throws publicAdminException
		 */
		private function getOptionedValue($value) {

			if (!is_array($value) || umiCount($value) == 0) {
				return null;
			}

			$valueList = [];

			foreach ($value as $option) {

				if (!isset($option['rel']) || !is_numeric($option['rel'])) {
					continue;
				}

				$valueList[] = $this->getObjectName($option['rel']);
			}

			return $this->getStringMultipleValue($valueList);
		}

		/**
		 * Возвращает значение строкового поля, подготовленное к записи в индекс
		 * @param mixed $value значение поля
		 * @return string|null
		 */
		private function getStringValue($value) {

			if (!$this->isCorrectString($value)) {
				return null;
			}

			return $this->prepareStringValue($value);
		}

		/**
		 * Определяет корректность строки для записи в бд
		 * @param mixed $value проверяемая строка
		 * @return bool
		 */
		private function isCorrectString($value) {
			return (is_string($value) && $value !== '');
		}

		/**
		 * Подготавливает строковое значение к записи
		 * @param string $value строковое значение
		 * @return string
		 */
		private function prepareStringValue($value) {
			return trim($value);
		}

		/**
		 * Подготавливает строковое множественное значение к записи
		 * @param string[] $valueList множественное строковое значение
		 * @return string
		 */
		private function getStringMultipleValue(array $valueList) {

			foreach ($valueList as $index => $value) {
				$value = $this->getStringValue($value);

				if ($value === null) {
					unset($valueList[$index]);
					continue;
				}

				$valueList[$index] = $value;
			}

			if (count($valueList) === 0) {
				return null;
			}

			$separator = self::MULTIPLE_VALUE_SEPARATOR;
			$value = implode($separator, $valueList);
			return sprintf('%s%s%s', $separator, $value, $separator);
		}

		/**
		 * Возвращает значение поля даты, подготовленное к записи в индекс
		 * @param mixed $value значение поля
		 * @return int|null
		 */
		private function getDateValue($value) {
			if ($value instanceof umiDate) {
				return $value->timestamp;
			}
			return null;
		}

		/**
		 * Возвращает значение поля типа "выпадающий список", подготовленное к записи в индекс
		 * @param iUmiField $field Поле
		 * @param mixed $value значение поля
		 * @return string|null
		 * @throws publicAdminException
		 */
		private function getRelationValue(iUmiField $field, $value) {
			$fieldType = $field->getFieldType();
			$isMultiple = $fieldType->getIsMultiple();

			switch (true) {
				case $isMultiple && is_array($value) && umiCount($value) > 0: {

					$valueList = [];

					foreach ($value as $objectId) {
						$valueList[] = $this->getObjectName($objectId);
					}

					return $this->getStringMultipleValue($valueList);
				}
				case !$isMultiple && is_numeric($value): {
					$name = $this->getObjectName($value);
					return $this->getStringValue($name);
				}
				default: {
					return null;
				}
			}
		}

		/**
		 * Возвращает значение поля типа "ссылка на дерево", подготовленное к записи в индекс
		 * @param mixed $value значение поля
		 * @return string|null
		 * @throws publicAdminException
		 */
		private function getSymlinkValue($value) {

			if (!is_array($value) || umiCount($value) == 0) {
				return null;
			}

			$valueList = [];
			/* @var iUmiHierarchyElement $element */
			foreach ($value as $element) {
				$valueList[] = $element->getName();
				$this->unloadEntity($element->getId(), 'pages');
			}

			return $this->getStringMultipleValue($valueList);
		}

		/**
		 * Возвращает значение поля типа "теги", подготовленное к записи в индекс
		 * @param mixed $value значение поля
		 * @return string|null
		 */
		private function getTagsValue($value) {

			if (!is_array($value) || umiCount($value) == 0) {
				return null;
			}

			$valueList = [];

			foreach ($value as $tag) {
				$valueList[] = $tag;
			}

			return $this->getStringMultipleValue($valueList);
		}

		/**
		 * Возвращает имя объекта или null, если объект не удалось получить
		 * @param int $objectId идентификатор объекта
		 * @return string|null
		 * @throws publicAdminException
		 */
		private function getObjectName($objectId) {

			$object = $this->umiObjects->getObject($objectId);

			if (!$object instanceof iUmiObject) {
				return null;
			}

			$objectName = $object->getName();
			$this->unloadEntity($objectId, 'objects');
			return $objectName;
		}

		/**
		 * Возвращает команды на создание индексов для колонок таблицы.
		 * @return array
		 * @throws publicAdminException
		 */
		private function getColumnsIndexes() {
			$tableColumns = $this->getTableColumns();
			$tableColumns = array_flip($tableColumns);
			$commonFieldsData = $this->getCommonFieldsData();

			foreach ($tableColumns as $key => $value) {

				$definition = null;
				if (isset($commonFieldsData[$key])) {
					$definition = $this->getCommonColumnIndex($key);
				} else {
					$definition = $this->getCustomColumnIndex($key);
				}

				if ($definition === null) {
					unset($tableColumns[$key]);
					continue;
				}

				$tableColumns[$key] = $definition;
			}
			return $tableColumns;
		}

		/**
		 * Возвращает команду на создание индекса для системного поля
		 * @param string $columnName имя системного поля
		 * @return string|null
		 * @throws publicAdminException если $columnName не строка
		 * @throws publicAdminException если передано имя неподдерживаемого поля
		 */
		private function getCommonColumnIndex($columnName) {
			if (!is_string($columnName)) {
				throw new publicAdminException(__METHOD__ . ': correct column name expected, ' . $columnName . ' given');
			}

			switch ($columnName) {
				case 'obj_id':
				case 'type_id':
				case 'parent_id':
				case 'lang_id':
				case 'domain_id': {
					return "KEY `$columnName` (`$columnName`)";
				}
				default: {
					throw new publicAdminException(__METHOD__ . ': unsupported common column: ' . $columnName);
				}
			}
		}

		/**
		 * Возвращает команду на создание индекса для обычного поля объекта
		 * @param string $columnName имя обычного поля объекта
		 * @return string|null
		 * @throws publicAdminException если $columnName не строка
		 * @throws publicAdminException если передано имя неподдерживаемого поля
		 * @throws publicAdminException поле с именем $columnName неподдерживаемого типа данных
		 */
		private function getCustomColumnIndex($columnName) {
			if (!is_string($columnName)) {
				throw new publicAdminException(__METHOD__ . ': correct column name expected, ' . $columnName . ' given');
			}

			$filteredFields = $this->getFilteredFields();

			if (!isset($filteredFields[$columnName]) || !$filteredFields[$columnName] instanceof iUmiField) {
				throw new publicAdminException(__METHOD__ . ': unsupported custom column: ' . $columnName);
			}

			/* @var iUmiField $filteredField */
			$filteredField = $filteredFields[$columnName];

			switch ($filteredField->getDataType()) {
				case 'date':
				case 'int':
				case 'password':
				case 'string':
				case 'price':
				case 'float':
				case 'color':
				case 'counter': {
					return "KEY `$columnName` (`$columnName`)";
				}
				case 'boolean':
				case 'file':
				case 'img_file':
				case 'swf_file':
				case 'video_file':
				case 'multiple_image':
				case 'optioned':
				case 'tags':
				case 'symlink':
				case 'text':
				case 'wysiwyg': {
					return null;
				}
				case 'relation': {
					/* @var iUmiFieldType $fieldType */
					$fieldType = $filteredField->getFieldType();
					if ($fieldType->getIsMultiple()) {
						return null;
					}

					return "KEY `$columnName` (`$columnName`)";
				}
				default: {
					throw new publicAdminException(
						__METHOD__ . ': unsupported field type: ' . $filteredField->getDataType()
					);
				}
			}
		}

		/**
		 * Возвращает команды на создание строк таблицы индекса
		 * @return array
		 * @throws publicAdminException если не удалось получить команду для какого-либо поля
		 */
		private function getColumnsDefinitions() {
			$tableColumns = $this->getTableColumns();
			$tableColumns = array_flip($tableColumns);
			$commonFieldsData = $this->getCommonFieldsData();

			foreach ($tableColumns as $key => $value) {

				$definition = null;
				if (isset($commonFieldsData[$key])) {
					$definition = $this->getCommonColumnDefinition($key);
				} else {
					$definition = $this->getCustomColumnDefinition($key);
				}

				if ($definition === null) {
					throw new publicAdminException(__METHOD__ . ': cant get definition for column with name: ' . $key);
				}

				$tableColumns[$key] = $definition;
			}

			return $tableColumns;
		}

		/**
		 * Возвращает команду на создание колонки таблицы для системного поля
		 * @param string $columnName имя системного поля
		 * @return string|null
		 * @throws publicAdminException если $columnName не строка
		 * @throws publicAdminException если передано имя неподдерживаемого поля
		 */
		private function getCommonColumnDefinition($columnName) {
			if (!is_string($columnName)) {
				throw new publicAdminException(__METHOD__ . ': correct column name expected, ' . $columnName . ' given');
			}

			switch ($columnName) {
				case 'obj_id':
				case 'type_id':
				case 'parent_id':
				case 'lang_id':
				case 'domain_id': {
					return "`$columnName` int(10) unsigned NOT NULL, ";
				}
				default: {
					throw new publicAdminException(__METHOD__ . ': unsupported common column: ' . $columnName);
				}
			}
		}

		/**
		 * Возвращает команду на создание колонки для обычного поля объекта
		 * @param string $columnName имя обычного поля объекта
		 * @return string|null
		 * @throws publicAdminException если $columnName не строка
		 * @throws publicAdminException если передано имя неподдерживаемого поля
		 * @throws publicAdminException поле с именем $columnName неподдерживаемого типа данных
		 */
		private function getCustomColumnDefinition($columnName) {
			if (!is_string($columnName)) {
				throw new publicAdminException(__METHOD__ . ': correct column name expected, ' . $columnName . ' given');
			}

			$filteredFields = $this->getFilteredFields();

			if (!isset($filteredFields[$columnName]) || !$filteredFields[$columnName] instanceof iUmiField) {
				throw new publicAdminException(__METHOD__ . ': unsupported custom column: ' . $columnName);
			}

			/* @var iUmiField $filteredField */
			$filteredField = $filteredFields[$columnName];

			switch ($filteredField->getDataType()) {
				case 'date':
				case 'int': {
					return "`$columnName` bigint(20) DEFAULT NULL, ";
				}
				case 'color':
				case 'password':
				case 'string': {
					return "`$columnName` varchar(255) DEFAULT NULL, ";
				}
				case 'price': {
					return "`$columnName` double DEFAULT 0, ";
				}
				case 'float': {
					return "`$columnName` double DEFAULT NULL, ";
				}
				case 'counter': {
					return "`$columnName` int(10) DEFAULT 0, ";
				}
				case 'boolean':
				case 'file':
				case 'img_file':
				case 'swf_file':
				case 'multiple_image':
				case 'video_file': {
					return "`$columnName` tinyint(1) DEFAULT 0, ";
				}
				case 'optioned':
				case 'tags':
				case 'symlink':
				case 'text':
				case 'wysiwyg': {
					return "`$columnName` mediumtext DEFAULT NULL, ";
				}
				case 'relation': {
					/* @var iUmiFieldType $fieldType */
					$fieldType = $filteredField->getFieldType();
					if ($fieldType->getIsMultiple()) {
						return "`$columnName` mediumtext DEFAULT NULL, ";
					}

					return "`$columnName` varchar(255) DEFAULT NULL, ";
				}
				default: {
					throw new publicAdminException(
						__METHOD__ . ': unsupported field type: ' . $filteredField->getDataType()
					);
				}
			}
		}

		/**
		 * Возвращает столбцы таблицы индекса
		 * @return array
		 * @throws publicAdminException
		 */
		private function getTableColumns() {
			$commonFieldsData = $this->getCommonFieldsData();
			$filteredFields = $this->getFilteredFields();
			$columns = array_keys($filteredFields);
			$commonFieldsData = array_keys($commonFieldsData);
			$columns = array_merge($commonFieldsData, $columns);
			if (umiCount($columns) > self::MAX_COLUMNS_COUNT) {
				$columns = array_slice($columns, 0, self::MAX_COLUMNS_COUNT - 1);
			}
			return $columns;
		}

		/**
		 * Очищает таблицу с индексом
		 * @param bool $isProd "боевая" или временная таблица
		 * @return bool
		 * @throws Exception
		 */
		private function truncateTable($isProd = true) {
			$tableName = $isProd ? $this->getTableName() : $this->getTemporaryTableName();
			$sql = "TRUNCATE TABLE IF EXISTS `$tableName`;";
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->queryResult($sql);
			return true;
		}

		/**
		 * Создает таблицу для индекса
		 * @return bool
		 * @throws publicException если требуется создать слишком много индексов
		 * @throws Exception
		 */
		private function createTable() {
			$columnsDefinitions = $this->getColumnsDefinitions();
			$columnsDefinitionsSql = '';
			foreach ($columnsDefinitions as $columnDefinition) {
				$columnsDefinitionsSql .= $columnDefinition;
			}

			$columnsIndexes = $this->getColumnsIndexes();
			$columnsIndexesSql = '';
			$indexesCount = umiCount($columnsIndexes);
			if ($indexesCount > self::MAX_KEYS_COUNT) {
				throw new maxKeysCountExceedingException(
					__METHOD__ . ': too many indexed columns taken, please specify another index options'
				);
			}
			$counter = 1;
			foreach ($columnsIndexes as $columnIndex) {
				$columnsIndexesSql .= ($counter != $indexesCount) ? $columnIndex . ',' : $columnIndex;
				$counter++;
			}

			$tableName = $this->getTemporaryTableName();
			$engine = self::MYSQL_TABLE_ENGINE;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
CREATE TABLE `$tableName` (
	`id` int(10) unsigned NOT NULL,
	$columnsDefinitionsSql
	PRIMARY KEY (`id`),
	$columnsIndexesSql
)ENGINE=$engine DEFAULT CHARSET=utf8;
SQL;
			try {
				$connection->queryResult($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			return true;
		}

		/**
		 * Возвращает имя временной таблицы
		 * @return string
		 */
		private function getTemporaryTableName() {
			return $this->temporaryTableName;
		}

		/**
		 * Удаляет "боевую" таблицу и переименовывает временную в ее имя.
		 * Вызывает событие releaseFilterIndex.
		 * @return bool
		 * @throws publicAdminException если запрос к БД завершился с ошибкой
		 * @throws Exception
		 */
		private function releaseIndex() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$this->dropProductionTable();
			$tempTable = $this->getTemporaryTableName();
			$prodTable = $this->getTableName();
			$sql = "RENAME TABLE `$tempTable` TO `$prodTable`";

			try {
				$connection->queryResult($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			$eventPoint = new umiEventPoint('releaseFilterIndex');
			$eventPoint->setMode('after');
			$eventPoint->setParam('entity_type', $this->getEntitiesType());
			$eventPoint->setParam('hierarchy_type_id', $this->getHierarchyTypeId());
			$eventPoint->setParam('table_name', $prodTable);
			$eventPoint->setParam('parent_id', $this->getParentId());
			$eventPoint->setParam('level', $this->getLevel());
			def_module::setEventPoint($eventPoint);

			return true;
		}

		/**
		 * Возвращает данные системных полей
		 * @return array
		 * @throws publicAdminException если был получен некорректный тип сущности
		 */
		private function getCommonFieldsData() {
			$entitiesType = $this->getEntitiesType();
			switch ($entitiesType) {
				case 'pages': {
					return [
						'obj_id' => 'getObjectId',
						'parent_id' => 'getRel',
						'type_id' => 'getObjectTypeId',
						'lang_id' => 'getLangId',
						'domain_id' => 'getDomainId'
					];
				}
				case 'objects': {
					return [
						'type_id' => 'getTypeId'
					];
				}
			}
			throw new publicAdminException(__METHOD__ . ': incorrect entity type given: ' . $entitiesType);
		}

		/**
		 * Возващает смещение выборки индексируемых сущностей
		 * @return bool|int
		 * @throws publicAdminException
		 */
		private function getOffset() {
			if (is_numeric($this->offset)) {
				return $this->offset;
			}
			$storedOffset = $this->getStoredOffset();

			if (is_numeric($storedOffset)) {
				return $this->setOffset($storedOffset);
			}

			return $this->offset;
		}

		/**
		 * Устанавливает и возвращает смещение выборки индексируемых сущностей
		 * @param int $offset смещение выборки индексируемых сущностей
		 * @return int
		 */
		private function setOffset($offset) {
			return $this->offset = (int) $offset;
		}

		/**
		 * Возвращает  смещение выборки индексируемых сущностей из кеша
		 * @return bool|int
		 * @throws publicAdminException если файл с кешем нельзя прочитать
		 * @throws publicAdminException если не удалось получить данные из кеша
		 */
		private function getStoredOffset() {
			$filePath = $this->getStateDirectoryPath() . md5($this->getTemporaryTableName() . 'offset');

			if (!file_exists($filePath)) {
				return false;
			}

			if (!is_readable($filePath)) {
				throw new publicAdminException(__METHOD__ . ': offset cash is not readable');
			}

			$storedOffset = file_get_contents($filePath);

			if (!$storedOffset) {
				throw new publicAdminException(__METHOD__ . ': cant get stored offset');
			}

			return (int) $storedOffset;
		}

		/**
		 * Сохраняет смещение выборки индексируемых сущностей в кеш
		 * @throws publicAdminException если не удалось сохранить данные
		 */
		private function storeOffset() {
			$filePath = $this->getStateDirectoryPath() . md5($this->getTemporaryTableName() . 'offset');
			$this->deleteStoredOffset();
			if (!$this->isDone() && is_numeric($this->getOffset())) {
				$isStored = file_put_contents($filePath, $this->getOffset());
				if (!$isStored) {
					throw new publicAdminException(__METHOD__ . ': cant save stored offset: ' . $this->getOffset());
				}
			}
		}

		/**
		 * Возвращает ограничение на количество индексируемых сущностей
		 * @return bool|int
		 */
		private function getLimit() {
			return $this->limit;
		}

		/**
		 * Возвращает объекты индексируемых сущностей
		 * @return array
		 * @throws selectorException
		 * @throws publicAdminException
		 */
		private function getEntities() {
			$entitiesType = $this->getEntitiesType();
			$hierarchyTypeId = $this->getHierarchyTypeId();

			$entities = new selector($entitiesType);
			$entities->types('hierarchy-type')->id($hierarchyTypeId);
			$entities->option('load-all-props')->value(true);

			if ($entitiesType == 'pages') {
				$entities->where('domain')->isnotnull();
				$entities->where('lang')->isnotnull();
				$entities->where('is_active')->equals(1);
			}

			$parentId = $this->getParentId();
			$level = $this->getLevel();

			if (is_numeric($parentId)) {
				$entities->where('hierarchy')->page($parentId)->level($level);
			}

			$limit = $this->getLimit();
			$offset = (int) $this->getOffset();

			if (is_numeric($limit)) {
				$offset = $offset * $limit;
				$entities->limit($offset, $limit);
				$this->setOffset($this->getOffset() + 1);
			}

			$entities->order('id');

			$result = $entities->result();

			if (umiCount($result) == 0) {
				$this->setIsDone();
			}

			return $result;
		}

		/**
		 * Ставит флаг успешного завершения индексации
		 */
		private function setIsDone() {
			$this->isDone = true;
		}

		/**
		 * Устанавливает тип индексируемых сущностей
		 * @param string $entitiesType тип индексируемых сущностей
		 * @throws publicAdminException если тип некорректен
		 */
		private function setEntitiesType($entitiesType) {
			if (!is_string($entitiesType) || !in_array($entitiesType, $this->getCorrectEntitiesTypes())) {
				throw new publicAdminException(__METHOD__ . ': correct entity type expected, ' . $entitiesType . ' given');
			}
			$this->entitiesType = $entitiesType;
		}

		/**
		 * Возвращает корректные типы индексируемых сущностей
		 * @return array
		 */
		private function getCorrectEntitiesTypes() {
			return ['pages', 'objects'];
		}

		/**
		 * Загружает объект фильтруемых полей, являющихся источник индекса
		 * @throws publicAdminException если не удалось найти фильтруемые поля
		 * среди объектных типов текущего иерархического типа
		 * @throws publicAdminException если не удалось найти корректные фильтруемые поля
		 * среди объектных типов текущего иерархического типа
		 */
		private function loadFilteredFields() {
			$result = $this->getQueryResult();
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				throw new publicAdminException(
					__METHOD__ . ': filtered fields data not found in hierarchy type with id ' . $this->getHierarchyTypeId()
				);
			}

			$umiFields = umiFieldsCollection::getInstance();
			$filteredFields = [];
			$filteredFieldsIds = [];

			foreach ($result as $row) {
				$field = $umiFields->getField($row[0], $row);
				/* @var iUmiField $field */
				if ($field instanceof iUmiField) {
					$filteredFields[$field->getName()] = $field;
					$filteredFieldsIds[] = $field->getId();
				}
			}

			if (umiCount($filteredFieldsIds) == 0) {
				throw new publicAdminException(__METHOD__ . ': filtered fields not found');
			}

			$this->filteredFields = $filteredFields;
			$this->saveFilteredFieldsIds($filteredFieldsIds);
		}

		/**
		 * Сохраняет идентификаторы фильтруемых полей в кеш
		 * @param array $fieldsIds идентификаторы фильтруемых полей
		 * @throws publicAdminException если не удалось удалить файл
		 */
		private function saveFilteredFieldsIds(array $fieldsIds) {
			$filePath = $this->getStateDirectoryPath() . md5($this->getTemporaryTableName() . 'fields');
			$fieldsIds = serialize($fieldsIds);
			$isSaved = file_put_contents($filePath, $fieldsIds);
			if (!$isSaved) {
				throw new publicAdminException(__METHOD__ . ': cant save fields ids');
			}
		}

		/**
		 * Возвращает идентификаторы фильтруемых полей из кеша
		 * @return bool|mixed
		 * @throws publicAdminException если файл с кешем нельзя прочитать
		 * @throws publicAdminException если не удалось получить данные из кеша
		 */
		private function getSavedFilteredFieldsIds() {
			$filePath = $this->getStateDirectoryPath() . md5($this->getTemporaryTableName() . 'fields');

			if (!file_exists($filePath)) {
				return false;
			}

			if (!is_readable($filePath)) {
				throw new publicAdminException(__METHOD__ . ': fields cash is not readable');
			}

			$savedIds = file_get_contents($filePath);

			if (!$savedIds) {
				throw new publicAdminException(__METHOD__ . ': cant get saved ids');
			}

			return unserialize($savedIds);
		}

		/**
		 * Возвращает ресурс запроса на получения объектов фильтруемых полей
		 * @return IQueryResult
		 * @throws publicAdminException если запрос к бд закончился ошибкой
		 * @throws Exception
		 */
		private function getQueryResult() {
			$savedFilteredFields = $this->getSavedFilteredFieldsIds();

			switch (true) {
				case is_array($savedFilteredFields): {
					$sql = $this->getQueryForFieldsByIds($savedFilteredFields);
					break;
				}
				case is_numeric($this->getParentId()): {
					$objectsTypesIds = $this->getObjectTypeIdsByHierarchy();
					$sql = $this->getQueryForAllFilteredFieldsByObjectTypeIds($objectsTypesIds);
					break;
				}
				default: {
					$hierarchyTypeId = $this->getHierarchyTypeId();
					$sql = $this->getQueryForAllFilteredFieldsByHierarchyTypeId($hierarchyTypeId);
				}
			}

			$connection = ConnectionPool::getInstance()->getConnection();

			try {
				$result = $connection->queryResult($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			return $result;
		}

		/**
		 * Возвращает идентификаторы объектных типов данных страниц,
		 * в рамках указанной иерархии
		 * @return array
		 * @throws publicAdminException если страницы не были найдены
		 * @throws selectorException
		 */
		private function getObjectTypeIdsByHierarchy() {
			$parentId = $this->getParentId();
			$level = $this->getLevel();
			$hierarchyTypeId = $this->getHierarchyTypeId();

			$pages = new selector('pages');
			$pages->types('hierarchy-type')->id($hierarchyTypeId);
			$pages->where('hierarchy')->page($parentId)->childs($level);
			$pages->group('obj_type_id');
			$pages = $pages->result();

			if (umiCount($pages) == 0) {
				throw new noObjectsFoundForIndexingException(
					__METHOD__ . ' pages not found by parent: ' . $parentId . ' and level: ' . $level
				);
			}

			$objectTypeIds = [];
			/* @var iUmiHierarchyElement $page */
			foreach ($pages as $page) {
				$objectTypeIds[] = $page->getObjectTypeId();
				$this->unloadEntity($page->getId(), 'pages');
			}

			return $objectTypeIds;
		}

		/**
		 * Возвращает sql запрос на получения всех фильтруемых полей по идентификатору иерархического типа
		 * @param int $hierarchyTypeId идентификатор иерархического типа
		 * @return string
		 */
		private function getQueryForAllFilteredFieldsByHierarchyTypeId($hierarchyTypeId) {
			$hierarchyTypeId = (int) $hierarchyTypeId;
			return $sql = <<<SQL
SELECT
  cms3_object_fields.id,
  cms3_object_fields.name,
  cms3_object_fields.title,
  cms3_object_fields.is_locked,
  cms3_object_fields.is_inheritable,
  cms3_object_fields.is_visible,
  cms3_object_fields.field_type_id,
  cms3_object_fields.guide_id,
  cms3_object_fields.in_search,
  cms3_object_fields.in_filter,
  cms3_object_fields.tip,
  cms3_object_fields.is_required,
  cms3_object_fields.sortable,
  cms3_object_fields.is_system,
  cms3_object_fields.restriction_id,
  cms3_object_fields.is_important
FROM
  cms3_object_fields
  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
  LEFT JOIN cms3_hierarchy_types ON cms3_object_types.hierarchy_type_id = cms3_hierarchy_types.id
WHERE
 cms3_hierarchy_types.id = $hierarchyTypeId AND
 cms3_object_field_groups.is_active = 1 AND
 cms3_object_field_groups.is_visible = 1 AND
 cms3_object_fields.in_filter = 1 AND
 cms3_object_fields.is_visible = 1
GROUP BY cms3_object_fields.name;
SQL;
		}

		/**
		 * Возвращает sql запрос на получения всех фильтруемых полей по идентификаторам объектных типов
		 * @param array $objectTypeIds идентификаторы объектных типов
		 * @return string
		 */
		private function getQueryForAllFilteredFieldsByObjectTypeIds(array $objectTypeIds) {
			$objectTypeIds = array_map('intval', $objectTypeIds);
			$objectTypeIds = implode(', ', $objectTypeIds);
			return $sql = <<<SQL
SELECT
  cms3_object_fields.id,
  cms3_object_fields.name,
  cms3_object_fields.title,
  cms3_object_fields.is_locked,
  cms3_object_fields.is_inheritable,
  cms3_object_fields.is_visible,
  cms3_object_fields.field_type_id,
  cms3_object_fields.guide_id,
  cms3_object_fields.in_search,
  cms3_object_fields.in_filter,
  cms3_object_fields.tip,
  cms3_object_fields.is_required,
  cms3_object_fields.sortable,
  cms3_object_fields.is_system,
  cms3_object_fields.restriction_id,
  cms3_object_fields.is_important
FROM
  cms3_object_fields
  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
WHERE
 cms3_object_types.id IN ($objectTypeIds) AND
 cms3_object_field_groups.is_active = 1 AND
 cms3_object_field_groups.is_visible = 1 AND
 cms3_object_fields.in_filter = 1 AND
 cms3_object_fields.is_visible = 1
GROUP BY cms3_object_fields.name;
SQL;
		}

		/**
		 * Возвращает sql запрос на получения всех фильтруемых полей по их идентификаторам
		 * @param array $fieldsIds идентификаторы полей
		 * @return string
		 */
		private function getQueryForFieldsByIds(array $fieldsIds) {
			$fieldsIds = array_map('intval', $fieldsIds);
			$fieldsIds = implode(', ', $fieldsIds);
			return $sql = <<<SQL
SELECT
  cms3_object_fields.id,
  cms3_object_fields.name,
  cms3_object_fields.title,
  cms3_object_fields.is_locked,
  cms3_object_fields.is_inheritable,
  cms3_object_fields.is_visible,
  cms3_object_fields.field_type_id,
  cms3_object_fields.guide_id,
  cms3_object_fields.in_search,
  cms3_object_fields.in_filter,
  cms3_object_fields.tip,
  cms3_object_fields.is_required,
  cms3_object_fields.sortable,
  cms3_object_fields.is_system,
  cms3_object_fields.restriction_id,
  cms3_object_fields.is_important
FROM
  cms3_object_fields
WHERE
 cms3_object_fields.id IN ($fieldsIds)
SQL;
		}

		/**
		 * Устанавливает идентификатор иерархического типа данных, к которому принадлежат индексируемые сущности
		 * @param int $hierarchyTypeId идентификатор иерархического типа данных
		 * @throws publicAdminException если $hierarchyTypeId не является числом
		 * @throws publicAdminException если не удалось получить объект по $hierarchyTypeId
		 */
		private function setHierarchyTypeId($hierarchyTypeId) {
			if (!is_numeric($hierarchyTypeId)) {
				throw new publicAdminException(
					__METHOD__ . ': correct hierarchy type id expected, ' . $hierarchyTypeId . ' given'
				);
			}

			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$hierarchyType = $umiHierarchyTypes->getType($hierarchyTypeId);

			if (!$hierarchyType instanceof iUmiHierarchyType) {
				throw new publicAdminException(
					__METHOD__ . ': cant get hierarchy type by id, ' . $hierarchyTypeId . ' given'
				);
			}

			$this->hierarchyTypeId = $hierarchyTypeId;
		}

		/**
		 * Устанавливает имена таблиц
		 * @param string $postfix постфикс имени таблицы
		 * @throws publicAdminException если $postfix не является строкой
		 */
		private function setTableName($postfix = '') {
			if (!is_string($postfix)) {
				throw new publicAdminException(__METHOD__ . ': table name postfix should be a string');
			}

			$this->tableName =
				self::TABLE_NAME_PREFIX . $this->getHierarchyTypeId() . '_' . $this->getEntitiesType() . $postfix;
			$this->temporaryTableName = $this->tableName . '_temp';
		}

		/**
		 * Возвращает идентификатор страницы, дочерние страницы которых нужно проиндексировать
		 * @return int|null
		 */
		private function getParentId() {
			return $this->parentId;
		}

		/**
		 * Возвращает уровень вложенности индексируемых страниц, относительно $parentId
		 * @return int
		 */
		private function getLevel() {
			return $this->level;
		}

		/**
		 * Возвращает путь до директории, в которой хранится состояние и кеш
		 * Если путь не задан - полагает, что он содержится в глобальной константе SYS_CACHE_RUNTIME
		 * @return string
		 */
		private function getStateDirectoryPath() {
			if ($this->stateDirectoryPath !== null) {
				return $this->stateDirectoryPath;
			}

			return $this->stateDirectoryPath = SYS_CACHE_RUNTIME;
		}
	}
