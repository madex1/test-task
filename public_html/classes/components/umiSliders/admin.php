<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class UmiSlidersAdmin implements iUmiRegistryInjector, iModulePart {

		use baseModuleAdmin;
		use tUmiImageFileInjector;
		use tUmiRegistryInjector;
		use tModulePart;

		/** @const string FIELDS_LABEL_PREFIX префикс для языковой метки заголовка поля */
		const FIELDS_LABEL_PREFIX = 'label-sliders-field-';

		/** @const string FIELDS_LABEL_PREFIX префикс для языковой метки метода */
		const METHODS_LABEL_PREFIX = 'label-sliders-method-';

		/** @const string DEFAULT_PER_PAGE_NUMBER значение по умолчанию для кол-ва страниц к выводу в рамках пагинации */
		const DEFAULT_PER_PAGE_NUMBER = 10;

		/** @const string SLIDER_TYPE_KEY клюс типа слайдера */
		const SLIDER_TYPE_KEY = 'slider_type';

		/** @const string SLIDER_TYPE_KEY клюс типа слайда */
		const SLIDE_TYPE_KEY = 'slide_type';

		/** @const string SLIDES_ORDER_INTERVAL интервал значений порядка вывода слайдов */
		const SLIDES_ORDER_INTERVAL = 100;

		/** @const string CONFIG_GROUP_NAME название группы для настроек */
		const CONFIG_GROUP_NAME = 'config';

		/**
		 * @const string DEFAULT_SLIDING_SLIDES_COUNT_KEY название настройки
		 * "Количество отображаемых слайдов в слайдере по умолчанию"
		 */
		CONST DEFAULT_SLIDING_SLIDES_COUNT_KEY = 'default_sliding_slides_count';

		/**
		 * @const string DEFAULT_SLIDING_SPEED название настройки
		 * "Скорость переключения слайдов по умолчанию"
		 */
		CONST DEFAULT_SLIDING_SPEED = 'default_sliding_speed';

		/**
		 * @const string DEFAULT_SLIDING_DELAY название настройки
		 * "Время задержки перед переключением слайдов по умолчанию"
		 */
		CONST DEFAULT_SLIDING_DELAY = 'default_sliding_delay';

		/** Конструктор */
		public function __construct() {
			$this->setImageFileHandler(
				new umiImageFile(__FILE__)
			);

			$this->setRegistry(
				Service::Registry()
			);
		}

		/**
		 * Выводит список слайдеров в буффер, если передан id слайдера - выводит список слайдов
		 * @param null|int $sliderId идентификатор слайдера
		 * @param null|int $domainId идентификатор домена
		 * @param null|int $languageId идентификатор языка
		 * @param null|int $limit ограничение на количество результатов выборки
		 * @param null|int $offset смещение результатов выборки
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getSliders($sliderId = null, $domainId = null, $languageId = null, $limit = null, $offset = null) {
			/** @var umiSliders $module */
			$module = $this->getModule();
			if ($module->ifNotJsonMode()) {
				$this->setDataSetDirectCallMessage();
				return;
			}

			if ($sliderId === null) {
				$sliderId = $this->getRelationId();
			}

			if ($domainId === null) {
				$domainId = (int) $this->getDomainId();
			}

			if ($languageId === null) {
				$languageId = (int) $this->getLanguageId();
			}

			if ($limit === null) {
				$limit = (int) $this->getLimit();
			}

			if ($offset === null) {
				$offset = (int) $this->getOffset($limit);
			}

			if ($sliderId !== null || $this->isFilterExists()) {
				$this->getSlides($sliderId, $limit, $offset, $domainId, $languageId);
			}

			$slidersCollection = $module->getSlidersCollection();
			$collectionConstants = $slidersCollection->getMap();

			try {
				$queryParams = $this->getPageNavigationQueryParams(
					$collectionConstants, $limit, $offset
				);

				$queryParams += $this->getDomainAndLanguageQueryParams(
					$collectionConstants, $domainId, $languageId
				);

				$entities = $slidersCollection->exportForTableControl(
					$queryParams
				);

				$queryParams += [
					$collectionConstants->get('CALCULATE_ONLY_KEY') => true,
				];

				$total = $slidersCollection->count($queryParams);
			} catch (Exception $e) {
				$entities = $this->getSimpleErrorMessage(
					$e->getMessage()
				);
				$total = 0;
			}

			$this->module->printJson(
				$this->prepareTableControlEntities($entities, $total)
			);
		}

		/**
		 * Выводит список слайдов слайдера в буффер.
		 * Список выводится либо по идентификатору слайдера, либо по идентификатором домена и/или языка слайдера.
		 * @param int|null $sliderId идентификатор слайдера
		 * @param null|int $limit ограничение на количество результатов выборки
		 * @param null|int $offset смещение результатов выборки
		 * @param null|int $domainId идентификатор домена слайдеры
		 * @param null|int $languageId идентификатор языка слайдера
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getSlides($sliderId = null, $limit = null, $offset = null, $domainId = null, $languageId = null) {
			/** @var umiSliders $module */
			$module = $this->getModule();
			$slidesCollection = $module->getSlidesCollection();
			$collectionConstants = $slidesCollection->getMap();

			if ($limit === null) {
				$limit = (int) $this->getLimit();
			}

			if ($offset === null) {
				$offset = (int) $this->getOffset($limit);
			}

			if ($domainId === null) {
				$domainId = (int) $this->getDomainId();
			}

			if ($languageId === null) {
				$languageId = (int) $this->getLanguageId();
			}

			try {
				$queryParams = $this->getPageNavigationQueryParams(
					$collectionConstants, $limit, $offset
				);

				$sliderIdList = [];

				switch (true) {
					case $sliderId !== null : {
						$sliderIdList[] = $sliderId;
						break;
					}
					case $domainId !== null || $languageId !== null : {
						$sliderIdList = $module->getSlidersCollection()
							->getSliderIdListByDomainIdAndLanguageId($domainId, $languageId);

						if (empty($sliderIdList)) {
							$sliderIdList[] = 0; //not exists slider id
						}

						break;
					}
				}

				if (!empty($sliderIdList)) {
					$queryParams += [
						$collectionConstants->get('SLIDER_ID_FIELD_NAME') => $sliderIdList
					];
				}

				$queryParams += [
					$collectionConstants->get('ORDER_KEY') => [
						$collectionConstants->get('ORDER_FIELD_NAME') => $collectionConstants->get('ORDER_DIRECTION_ASC')
					]
				];

				$queryParams += $this->getEntitiesFilterQueryParams($slidesCollection);

				if (isset($queryParams['image'])) {
					if ($queryParams['image'] === '0') {
						$queryParams['compare_mode']['image'] = 'eq';
					} else {
						$queryParams['compare_mode']['image'] = 'ne';
					}

					$queryParams['image'] = null;
				}

				$entities = $slidesCollection->exportForTableControl($queryParams);

				$queryParams += [
					$collectionConstants->get('CALCULATE_ONLY_KEY') => true,
				];

				$total = $slidesCollection->count($queryParams);
			} catch (Exception $e) {
				$entities = $this->getSimpleErrorMessage(
					$e->getMessage()
				);
				$total = 0;
			}

			$this->module->printJson(
				$this->prepareTableControlEntities($entities, $total)
			);
		}

		/** @inheritdoc */
		public function getValidDragModes(
			iUmiCollectionItem $targetElement = null,
			iUmiCollectionItem $draggedElement = null
		) {
			return [
				'after',
				'before'
			];
		}

		/** @inheritdoc */
		public function getDatasetConfiguration($param = '') {
			/** @var umiSliders $module */
			$module = $this->getModule();
			$slidesCollection = $module->getSlidesCollection();

			$config = [
				'methods' => $this->getEntitiesMethodsConfig(),
				'fields' => $this->getEntityFieldsConfig(
					$slidesCollection->getCollectionItemClass(), $slidesCollection->getMap()
				)
			];

			$config = $this->setDefaultColumnsSizes($config);
			return $config;
		}

		/** Возвращает конфиг вкладки "Слайдеры" в формате JSON для табличного контрола */
		public function flushDatasetConfiguration() {
			$this->module->printJson($this->getDatasetConfiguration());
		}

		/**
		 * Возвращает настройки модуля, если передан ключевой параметр - сохраняет переданные настройки перед выводом
		 * @throws RequiredPropertyHasNoValueException
		 * @throws privateException
		 * @throws wrongParamException
		 */
		public function config() {
			$options = $this->getConfigOptions();

			if ($this->isSaveMode()) {
				foreach (array_keys($options) as $field) {
					$paramValue = (int) $this->getRequestParamValue($field);

					if ($paramValue <= 0) {
						$title = getLabel('option-' . $field, $this->getModuleName());
						$format = getLabel('error-option-value', $this->getModuleName());
						throw new publicAdminException(sprintf($format, $title));
					}

					$this->setRegistryValue(
						$field, $paramValue
					);
				}

				$this->chooseRedirect();
			}

			foreach ($options as $key => $value) {
				$newKey = $this->getConfigOptionName($value, $key);
				$options[$newKey] = (int) $this->getRegistryValue($key);
				unset($options[$key]);
			}

			$options = [
				self::CONFIG_GROUP_NAME => $options
			];

			$this->setConfigResult($options);
		}

		/** @inheritdoc */
		public function getConfigOptions() {
			return [
				self::DEFAULT_SLIDING_SLIDES_COUNT_KEY => 'int',
				self::DEFAULT_SLIDING_SPEED => 'int',
				self::DEFAULT_SLIDING_DELAY => 'int'
			];
		}

		/** @inheritdoc */
		public function callbackBeforeReturnEditFormData(
			array $formData,
			iUmiCollectionItem $entity,
			array $fieldsConfig
		) {
			$formData += $this->getEntitiesTemplateSettings();

			/** @var umiSliders $module */
			$module = $this->getModule();
			$sliderCollection = $module->getSlidersCollection();

			if ($sliderCollection->getCollectionItemClass() == get_class($entity)) {
				$formData = $this->appendDomainAndLanguageId($formData, $sliderCollection, $entity);
			}

			return $formData;
		}

		/** @inheritdoc */
		public function callbackBeforeReturnCreateFormData(
			array $formData,
			iUmiCollection $collection,
			array $fieldsConfig
		) {
			if ($collection->getCollectionItemClass() == 'UmiCms\Classes\Components\UmiSliders\Slider') {
				$formData = $this->appendDomainAndLanguageId($formData, $collection);
			}

			return $formData;
		}

		/** @inheritdoc */
		public function setDataSetDirectCallMessage() {
			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData(
				$this->getEntitiesTemplateSettings()
			);
			$this->doData();
		}

		/** @inheritdoc */
		public function callbackFieldValueBeforeSave(
			$fieldName,
			$fieldValue,
			iUmiCollectionItem $entity = null,
			iUmiCollection $collection = null
		) {
			if ($entity === null && $collection === null) {
				throw new wrongParamException('Collection and entity cannot be empty both');
			}
			$itemClass = ($entity !== null) ? get_class($entity) : $collection->getCollectionItemClass();
			/** @var iUmiCollectionItem|iUmiConstantMapInjector $entity */
			/** @var iUmiCollection|iUmiConstantMapInjector $collection */
			$itemMap = ($entity !== null) ? $entity->getMap() : $collection->getMap();

			$fieldConfig = $this->getTableControlFieldConfig(
				$itemClass, $itemMap, $fieldName
			);

			$fieldType = isset($fieldConfig['type']) ? $fieldConfig['type'] : null;

			if ($fieldType == 'image') {
				$imageFileHandlerClass = $this->getImageFileHandler();
				$fieldValue = new $imageFileHandlerClass($fieldValue);
			}

			if ($fieldType == 'bool') {
				$fieldValue = (bool) $fieldValue;
			}

			if ($fieldName == $itemMap->get('NAME_FIELD_NAME')) {
				/** @var UmiSliders|UmiSlidersMacros $module */
				$module = $this->getModule();
				switch ($itemClass) {
					case $module->getSlidersCollection()
						->getCollectionItemClass() : {
						/** @var \UmiCms\Classes\Components\UmiSliders\iSlider $entity */
						$this->validateSliderName($fieldValue, $entity);
						break;
					}
				}
			}

			return $fieldValue;
		}

		/** @inheritdoc */
		public function callbackAfterCreateEntityFromFormData(iUmiCollectionItem $entity, iUmiCollection $collection) {
			/** @var umiSliders $module */
			$module = $this->getModule();
			$slidesCollectionItemClass = $module->getSlidesCollection()
				->getCollectionItemClass();

			if ($collection->getCollectionItemClass() !== $slidesCollectionItemClass) {
				return $entity;
			}

			/** @var UmiCms\Classes\Components\UmiSliders\iSlidesCollection $collection */

			if (get_class($entity) !== $slidesCollectionItemClass) {
				return $entity;
			}

			/** @var UmiCms\Classes\Components\UmiSliders\iSlide $entity */
			$entity->setOrder(
				$collection->getMaximumSlidesOrder() + self::SLIDES_ORDER_INTERVAL
			);

			return $entity->commit();
		}

		/** @inheritdoc */
		public function getPageWithEntityEditFormLink(iUmiCollectionItem $entity) {
			$prefix = $this->getAdminRequestPrefix();
			$module = $this->getModuleName();
			$method = $this->getEntitiesEditFormMethod();
			$id = $entity->getId();
			$type = get_class($entity);

			return $prefix . '/' . $module . '/' . $method . '/' . $id . '/?type=' . $type;
		}

		/** @inheritdoc */
		public function getEntitiesEditFormMethod(iUmiCollectionItem $entity = null) {
			return 'getEditFormOfEntityWithDefinedType';
		}

		/** @inheritdoc */
		public function getEntitiesListMethod(iUmiCollectionItem $entity = null) {
			return 'getSliders';
		}

		/** @inheritdoc */
		public function getEntitiesDeleteMethod(iUmiCollectionItem $entity = null) {
			return 'deleteEntitiesWithDefinedTypes';
		}

		/** @inheritdoc */
		public function getEntitiesSaveMethod(iUmiCollectionItem $entity = null) {
			return 'saveFormDataToEntityWithDefinedType';
		}

		/** @inheritdoc */
		public function getEntitySaveFieldMethod(iUmiCollectionItem $entity = null) {
			return 'saveFieldOfEntityWithDefinedType';
		}

		/** @inheritdoc */
		public function getEntitiesCreateFormMethod(iUmiCollectionItem $entity = null) {
			return 'getCreateFormOfEntityWithDefinedType';
		}

		/** @inheritdoc */
		public function getEntitiesMoveFormMethod(iUmiCollectionItem $entity = null) {
			return 'moveEntitiesWithDefinedTypes';
		}

		/** @inheritdoc */
		public function getEntitiesCreateMethod(iUmiCollectionItem $entity = null) {
			return 'createEntityWithDefinedTypeFromFormData';
		}

		/** @inheritdoc */
		public function getModuleCollections() {
			/** @var umiSliders $module */
			$module = $this->getModule();
			return [
				$module->getSlidesCollection(),
				$module->getSlidersCollection()
			];
		}

		/**
		 * Возвращает настройки административных методов по работе с сущностями модуля
		 * @return array
		 */
		protected function getEntitiesMethodsConfig() {
			$config = [
				[
					'type' => 'load',
					'forload' => true,
					'name' => $this->getEntitiesListMethod()
				],
				[
					'type' => 'edit',
					'name' => $this->getEntitiesEditFormMethod()
				],
				[
					'type' => 'delete',
					'name' => $this->getEntitiesDeleteMethod()
				],
				[
					'type' => 'create',
					'name' => $this->getEntitiesCreateFormMethod()
				],
				[
					'type' => 'move',
					'name' => $this->getEntitiesMoveFormMethod()
				],
				[
					'type' => 'saveField',
					'name' => $this->getEntitySaveFieldMethod()
				]
			];

			$config = $this->setConfigItemsTitles($config, self::METHODS_LABEL_PREFIX, 'name');
			$config = $this->setMethodsModule($config);

			return $config;
		}

		/** @inheritdoc */
		public function getEntityFieldsConfig($entityClass, iUmiConstantMap $entityFieldsConstantMap) {
			/** @var umiSliders $module */
			$module = $this->getModule();

			switch ($entityClass) {
				case $module->getSlidesCollection()
					->getCollectionItemClass() : {
					$config = $this->getSlideFieldsConfig($entityFieldsConstantMap);
					break;
				}
				case $module->getSlidersCollection()
					->getCollectionItemClass() : {
					$config = $this->getSliderFieldsConfig($entityFieldsConstantMap);
					break;
				}
				default : {
					throw new wrongParamException('Unsupported entity class given');
				}
			}

			$config = $this->setConfigItemsTitles($config, self::FIELDS_LABEL_PREFIX, 'name');

			return $config;
		}

		/**
		 * Возвращает количество сущностей в табличном контроле по умолчанию
		 * @return int
		 */
		public function getDefaultPerPageNumber() {
			return self::DEFAULT_PER_PAGE_NUMBER;
		}

		/** @inheritdoc */
		public function callbackBeforeCreateEntityFromFormData(array $createFormData, iUmiCollection $collection) {
			foreach ($createFormData as $fieldName => &$fieldValue) {
				$fieldValue = $this->callbackFieldValueBeforeSave($fieldName, $fieldValue, null, $collection);
			}

			/** @var umiSliders $module */
			$module = $this->getModule();
			$slidesCollection = $module->getSlidesCollection();
			$slidesCollectionConstants = $slidesCollection->getMap();
			$slideItemClass = $slidesCollection->getCollectionItemClass();

			if ($collection->getCollectionItemClass() == $slideItemClass) {
				$createFormData += [
					$slidesCollectionConstants->get('SLIDER_ID_FIELD_NAME') => $this->getRelationId()
				];
			}

			$slidersCollection = $module->getSlidersCollection();
			$slidersCollectionConstants = $slidersCollection->getMap();
			$sliderItemClass = $slidersCollection->getCollectionItemClass();

			if ($collection->getCollectionItemClass() == $sliderItemClass) {
				$createFormData = $this->fillSliderDefaultValues($createFormData, $slidersCollectionConstants);
			}

			return $createFormData;
		}

		/** @inheritdoc */
		public function callbackBeforeSaveEditFormDataToEntity(array $editFormData, iUmiCollectionItem $entity) {

			foreach ($editFormData as $fieldName => &$fieldValue) {
				$fieldValue = $this->callbackFieldValueBeforeSave($fieldName, $fieldValue, $entity);
			}

			$editFormData = $this->fillBoolDefaultValues($entity, $editFormData, $entity->getMap());

			return $editFormData;
		}

		/**
		 * Валидирует имя для добавляемого/редактируемого слайдера
		 * @param string $sliderName имя слайдера
		 * @param \UmiCms\Classes\Components\UmiSliders\iSlider|null $slider редактируемый слайдер или ничего
		 * @throws publicAdminException
		 */
		public function validateSliderName($sliderName, \UmiCms\Classes\Components\UmiSliders\iSlider $slider = null) {
			/** @var umiSliders $module */
			$module = $this->getModule();
			$slidersCollection = $module->getSlidersCollection();
			$constants = $slidersCollection->getMap();

			$queryParams = [
				$constants->get('NAME_FIELD_NAME') => $sliderName,
				$constants->get('CALCULATE_ONLY_KEY') => true
			];

			if ($slider instanceof \UmiCms\Classes\Components\UmiSliders\iSlider) {
				$idFieldKey = $constants->get('ID_FIELD_NAME');
				$notEqualsCompareMode = '!=';
				$queryParams += [
					$idFieldKey => $slider->getId(),
					$constants->get('COMPARE_MODE_KEY') => [
						$idFieldKey => $notEqualsCompareMode
					]
				];
			}

			if ($slidersCollection->count($queryParams) > 0) {
				throw new publicAdminException(getLabel('label-error-slider-name-not-unique', $this->getModuleName()));
			}
		}

		/**
		 * Возвращает список слайдов заданного слайдера.
		 * Api для редактора слайдов.
		 * @param int|bool $sliderId идентификатор слайдера.
		 * @throws RequiredPropertyHasNoValueException
		 * @throws publicAdminException
		 */
		public function getSlidesList($sliderId = false) {
			if ($sliderId === false) {
				$sliderId = Service::Request()
					->Get()
					->get('slider_id');
			}

			if (!is_numeric($sliderId)) {
				throw new publicAdminException('Slider id expected');
			}

			/** @var umiSliders|UmiSlidersMacros $module */
			$module = $this->getModule();
			$slidersCollection = $module->getSlidersCollection();
			$slider = $slidersCollection->getById($sliderId);

			if (!$slider instanceof \UmiCms\Classes\Components\UmiSliders\iSlider) {
				throw new publicAdminException('Wrong slider id given');
			}

			$slidesCollection = $module->getSlidesCollection();
			$constants = $slidesCollection->getMap();
			$queryParams = $module->getQueryParamsForGettingSlides($slider, $constants);

			$result = [
				'nodes:list' => $slidesCollection->export($queryParams)
			];

			$this->setData($result);
			$this->doData();
		}

		/**
		 * Сохраняет список слайдов
		 * Api для редактора слайдов.
		 * @param array $slidesList список слайдов
		 *
		 * [
		 *        1 => [
		 *            'id' => 2
		 *            'slider_id' => 3
		 *            'image' => 'foo/bar/baz.png'
		 *            'name' => 'foo'
		 *            'title' => 'bar'
		 *            'text' => '<p>baz</p>'
		 *            'link' => '/foo/bar/baz
		 *        ]
		 * ]
		 *
		 * @throws RequiredPropertyHasNoValueException
		 * @throws publicAdminException
		 */
		public function saveSlidesList(array $slidesList = []) {
			if (empty($slidesList)) {
				$slidesList = (array) Service::Request()
					->Post()
					->get('slide_list');
			}

			if (empty($slidesList)) {
				throw new publicAdminException('Nothing to save');
			}

			/** @var umiSliders $module */
			$module = $this->getModule();
			$slidesCollection = $module->getSlidesCollection();

			foreach ($slidesList as &$slide) {
				if (isset($slide['image']) && is_string($slide['image']) && !empty($slide['image'])) {
					$slide['image'] = new umiImageFile('.' . $slide['image']);
				} else {
					unset($slide['image']);
				}

				$slide['is_active'] = true;
			}

			$result = $slidesCollection->import($slidesList);

			$this->setData($result);
			$this->doData();
		}

		/**
		 * Удаляет слайд.
		 * Api для редактора слайдов.
		 * @param int|bool $slideId идентификатор слайда
		 * @throws RequiredPropertyHasNoValueException
		 * @throws publicAdminException
		 */
		public function deleteSlide($slideId = false) {
			if ($slideId === false) {
				$slideId = Service::Request()
					->Post()
					->get('slide_id');
			}

			if (!is_numeric($slideId)) {
				throw new publicAdminException('Slide id expected');
			}

			/** @var umiSliders $module */
			$module = $this->getModule();
			$slidesCollection = $module->getSlidesCollection();
			$slide = $slidesCollection->getById($slideId);

			if (!$slide instanceof \UmiCms\Classes\Components\UmiSliders\iSlide) {
				throw new publicAdminException('Wrong slide id given');
			}

			$umiConfig = mainConfiguration::getInstance();

			if ($umiConfig->get('system', 'eip.fake-delete')) {
				$slide->setActiveStatus(false);
				$slide->commit();
			} else {
				$slidesCollection->deleteById($slideId);
			}

			$this->setData(['success']);
			$this->doData();
		}

		/**
		 * Заполняет и возвращает данные формы создания сущности значения булевых полей, если
		 * их значения не были переданы
		 * @param iUmiCollectionItem $entity сущность (слайд или слайдер)
		 * @param array $createFormData данные формы создания слайдер
		 * @param iUmiConstantMap $constants карта констант слайдера
		 * @return array
		 */
		protected function fillBoolDefaultValues(
			iUmiCollectionItem $entity,
			array $createFormData,
			iUmiConstantMap $constants
		) {

			if ($entity instanceof UmiCms\Classes\Components\UmiSliders\iSlider) {
				$createFormData = $this->fillDefaultValueIfNotDefined(
					$createFormData,
					$constants->get('SLIDING_LOOP_ENABLE_FIELD_NAME'),
					false
				);

				$createFormData = $this->fillDefaultValueIfNotDefined(
					$createFormData,
					$constants->get('SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME'),
					false
				);

				$createFormData = $this->fillDefaultValueIfNotDefined(
					$createFormData,
					$constants->get('SLIDES_RANDOM_ORDER_ENABLE_FIELD_NAME'),
					false
				);
			}

			if ($entity instanceof UmiCms\Classes\Components\UmiSliders\iSlide) {
				$createFormData = $this->fillDefaultValueIfNotDefined(
					$createFormData,
					$constants->get('OPEN_IN_NEW_TAB_FIELD_NAME'),
					false
				);

				$createFormData = $this->fillDefaultValueIfNotDefined(
					$createFormData,
					$constants->get('IS_ACTIVE_FIELD_NAME'),
					false
				);
			}

			return $createFormData;
		}

		/**
		 * Заполняет и возвращает данные формы создания слайдера значениями по умолчанию, если это необходимо
		 * @param array $createFormData данные формы создания слайдер
		 * @param iUmiConstantMap $constants карта констант слайдера
		 * @return array
		 */
		protected function fillSliderDefaultValues(array $createFormData, iUmiConstantMap $constants) {
			$createFormData = $this->fillDefaultValueIfNotDefinedOrNotNumeric(
				$createFormData,
				$constants->get('SLIDES_COUNT_FIELD_NAME'),
				$this->getRegistryValue(self::DEFAULT_SLIDING_SLIDES_COUNT_KEY)
			);

			$createFormData = $this->fillDefaultValueIfNotDefinedOrNotNumeric(
				$createFormData,
				$constants->get('SLIDING_SPEED_FIELD_NAME'),
				$this->getRegistryValue(self::DEFAULT_SLIDING_SPEED)
			);

			$createFormData = $this->fillDefaultValueIfNotDefinedOrNotNumeric(
				$createFormData,
				$constants->get('SLIDING_DELAY_FIELD_NAME'),
				$this->getRegistryValue(self::DEFAULT_SLIDING_DELAY)
			);

			return $createFormData;
		}

		/**
		 * Заполняет и возвращает данные формы создания сущности значением по умолчанию, если значение отсутствует
		 * или не числовое
		 * @param array $createFormData данные формы создания слайдер
		 * @param string $key ключ данных
		 * @param mixed $defaultValue значением по умолчанию
		 * @return array
		 */
		private function fillDefaultValueIfNotDefinedOrNotNumeric(array $createFormData, $key, $defaultValue) {
			if (!isset($createFormData[$key]) || !is_numeric($createFormData[$key])) {
				$createFormData[$key] = $defaultValue;
			}

			return $createFormData;
		}

		/**
		 * Заполняет и возвращает данные формы создания сущности значением по умолчанию, если значение отсутствует
		 * @param array $createFormData данные формы создания слайдер
		 * @param string $key ключ данных
		 * @param mixed $defaultValue значением по умолчанию
		 * @return array
		 */
		private function fillDefaultValueIfNotDefined(array $createFormData, $key, $defaultValue) {
			if (!isset($createFormData[$key])) {
				$createFormData[$key] = $defaultValue;
			}

			return $createFormData;
		}

		/**
		 * Возвращает настройки табличного контрола для вывода полей слайдов
		 * @param iUmiConstantMap $slideConstantMap карта констант слайда
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		private function getSlideFieldsConfig(iUmiConstantMap $slideConstantMap) {
			return [
				[
					'name' => $slideConstantMap->get('NAME_FIELD_NAME'),
					'type' => 'string',
					'required' => true,
					'default_size' => '250px',
					'sortable' => 'false'
				],
				[
					'name' => $slideConstantMap->get('IS_ACTIVE_FIELD_NAME'),
					'type' => 'bool',
					'default_size' => '250px',
					'sortable' => 'false'
				],
				[
					'name' => $slideConstantMap->get('IMAGE_FIELD_NAME'),
					'type' => 'image',
					'default_size' => '250px',
					'sortable' => 'false'
				],
				[
					'name' => $slideConstantMap->get('LINK_FIELD_NAME'),
					'type' => 'string',
					'default_size' => '250px',
					'sortable' => 'false'
				],
				[
					'name' => $slideConstantMap->get('TITLE_FIELD_NAME'),
					'type' => 'string',
					'sortable' => 'false'
				],
				[
					'name' => $slideConstantMap->get('TEXT_FIELD_NAME'),
					'type' => 'html',
					'editable' => 'false',
					'sortable' => 'false'
				],
				[
					'name' => $slideConstantMap->get('OPEN_IN_NEW_TAB_FIELD_NAME'),
					'type' => 'bool',
					'default_size' => '250px',
					'sortable' => 'false'
				]
			];
		}

		/**
		 * Возвращает настройки табличного контрола для вывода полей слайдеров
		 * @param iUmiConstantMap $sliderConstantMap карта констант слайдера
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		private function getSliderFieldsConfig(iUmiConstantMap $sliderConstantMap) {
			return [
				[
					'name' => $sliderConstantMap->get('NAME_FIELD_NAME'),
					'type' => 'string',
					'required' => true,
					'sortable' => false
				],
				[
					'name' => $sliderConstantMap->get('SLIDING_SPEED_FIELD_NAME'),
					'type' => 'integer',
					'sortable' => false
				],
				[
					'name' => $sliderConstantMap->get('SLIDING_LOOP_ENABLE_FIELD_NAME'),
					'type' => 'bool',
					'sortable' => false
				],
				[
					'name' => $sliderConstantMap->get('SLIDING_DELAY_FIELD_NAME'),
					'type' => 'integer',
					'sortable' => false
				],
				[
					'name' => $sliderConstantMap->get('SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME'),
					'type' => 'bool',
					'sortable' => false
				],
				[
					'name' => $sliderConstantMap->get('SLIDES_COUNT_FIELD_NAME'),
					'type' => 'integer',
					'sortable' => false,
					'hint' => getLabel('label-slides-count-field-hint', $this->getModuleName())
				],
				[
					'name' => $sliderConstantMap->get('SLIDES_RANDOM_ORDER_ENABLE_FIELD_NAME'),
					'type' => 'bool',
					'sortable' => false
				],
				[
					'name' => $sliderConstantMap->get('CUSTOM_ID_FIELD_NAME'),
					'type' => 'string',
					'required' => true,
					'sortable' => false,
				],
			];
		}

		/**
		 * Возвращает настройки сущностей для шаблона
		 * @return array
		 */
		private function getEntitiesTemplateSettings() {
			$methodsConfig = $this->getEntitiesMethodsConfig();
			/** @var umiSliders $module */
			$module = $this->getModule();
			return [
				'methods' => [
					'+method' => $methodsConfig
				],
				self::SLIDE_TYPE_KEY => $module->getSlidesCollection()
					->getCollectionItemClass(),
				self::SLIDER_TYPE_KEY => $module->getSlidersCollection()
					->getCollectionItemClass()
			];
		}

		/**
		 * Помещает поля идентификатора домена и языка в формы создания и редактирования слайдера
		 * @param array $formData данные формы создания слайдера
		 * @param iUmiCollection $collection коллекция слайдеров
		 * @param null|UmiCms\Classes\Components\UmiSliders\iSlider $entity редактируемый слайдер
		 * @return array
		 */
		private function appendDomainAndLanguageId(array $formData, iUmiCollection $collection, $entity = null) {
			/** @var null|UmiCms\Classes\Components\UmiSliders\iSlider $entity */
			/** @var iUmiCollection|iUmiConstantMapInjector $collection */
			$constants = $collection->getMap();

			$formData['fields']['+field'][] = [
				'@name' => $constants->get('DOMAIN_ID_FIELD_NAME'),
				'@type' => 'integer',
				'@required' => true,
				'@title' => getLabel('label-sliders-field-domain_id', $this->getModuleName()),
				'#value' => ($entity instanceof iUmiCollectionItem) ? $entity->getDomainId() : null
			];

			$formData['fields']['+field'][] = [
				'@name' => $constants->get('LANGUAGE_ID_FIELD_NAME'),
				'@type' => 'integer',
				'@required' => true,
				'@title' => getLabel('label-sliders-field-language_id', $this->getModuleName()),
				'#value' => ($entity instanceof iUmiCollectionItem) ? $entity->getLanguageId() : null
			];

			return $formData;
		}
	}
