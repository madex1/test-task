<?php

	/** Класс вкладки настроек доставки */
	class EmarketDeliverySettingsAdmin {

		use baseModuleAdmin;

		/** @var emarket|EmarketAdmin $module */
		public $module;

		/** @var array поля товаров */
		protected $catalogFields = [];

		/** @var EmarketSettings настройки модуля */
		protected $settings;

		/** @const название вкладки */
		const TAB_NAME = 'deliverySettings';

		/** @const название группы полей с настройками соответствий характеристик с полями */
		const FIELDS_GROUP = 'fields-settings';

		/** @var array конфиг опций вкладки */
		protected $options = [
			'fields-settings' => [
				'select:weight-field' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'weightField',
					'initialValue' => 'getItemNumberFields',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				],
				'select:width-field' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'widthField',
					'initialValue' => 'getItemNumberFields',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				],
				'select:height-field' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'heightField',
					'initialValue' => 'getItemNumberFields',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				],
				'select:length-field' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'lengthField',
					'initialValue' => 'getItemNumberFields',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				],

			],

			'order-item-default-settings' => [
				'ufloat:item-default-weight' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'weight',
				],
				'ufloat:item-default-width' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'width',
				],
				'ufloat:item-default-height' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'height',
				],
				'ufloat:item-default-length' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'length',
				]
			],

			'order-settings' => [
				'ufloat:default-weight' => [
					'group' => 'ORDER_SECTION',
					'name' => 'defaultWeight',
				],
				'ufloat:default-width' => [
					'group' => 'ORDER_SECTION',
					'name' => 'defaultWidth',
				],
				'ufloat:default-height' => [
					'group' => 'ORDER_SECTION',
					'name' => 'defaultHeight',
				],
				'ufloat:default-length' => [
					'group' => 'ORDER_SECTION',
					'name' => 'defaultLength',
				],
			],

			'default-store-settings' => [
				'string:country-code' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'country-code',
				],
				'string:region' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'region',
				],
				'string:index' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'index',
				],
				'string:city' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'city',
				],
				'string:street' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'street',
				],
				'string:house-number' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'house-number',
				],
				'string:apartment' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'apartment',
				],
				'string:contact-full-name' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'contact-full-name',
				],
				'string:contact-phone' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'contact-phone',
				],
				'string:contact-email' => [
					'group' => 'DEFAULT_STORE_SECTION',
					'name' => 'contact-email',
				],
			]
		];

		/**
		 * Конструктор
		 * @param emarket $module
		 * @throws publicAdminException
		 */
		public function __construct($module) {
			$tabsManager = $module->getConfigTabs();
			$this->settings = $module->getImplementedInstance($module::SETTINGS_CLASS);

			if (!$tabsManager instanceof adminModuleTabs) {
				return false;
			}

			$tabsManager->add(self::TAB_NAME);
		}

		/**
		 * Метод вкладки настроек доставки
		 * @throws coreException
		 */
		public function deliverySettings() {
			$options = $this->initOptions();
			$settings = $this->settings;

			if ($this->isSaveMode()) {
				$options = $this->expectParams($options);

				$this->forEachOption(function ($group, $option, $settingGroup, $settingName) use ($settings, $options) {
					$settings->set($settingGroup, $settingName, $options[$group][$option]);
				});

				$this->chooseRedirect();
			}

			$this->forEachOption(function ($group, $option, $settingGroup, $settingName) use ($settings, &$options) {
				$options[$group][$option]['value'] = $settings->get($settingGroup, $settingName);
			});

			$this->module->setConfigResult($options);
		}

		/**
		 * Возвращает числовые поля для всех типов товаров
		 * @return array
		 */
		protected function getItemNumberFields() {
			if (umiCount($this->catalogFields) > 0) {
				return $this->catalogFields;
			}

			$objectTypes = umiObjectTypesCollection::getInstance();
			$itemTypeId = $objectTypes->getTypeIdByGUID('catalog-object');
			$allTypes = array_merge([$itemTypeId], $objectTypes->getChildTypeIds($itemTypeId));

			$fieldsList = [];

			foreach ($allTypes as $typeId) {
				$type = $objectTypes->getType($typeId);

				/** @var iUmiField $field */
				foreach ($type->getAllFields() as $field) {
					if ($field->getFieldType()->isNumber()) {
						$fieldsList[$field->getId()] = $field->getTitle();
					}
				}
			}

			$this->catalogFields = array_unique($fieldsList);
			return $this->catalogFields;
		}

		/**
		 * Возвращает массив текущих настроек для админзоны
		 * @return array
		 * [
		 * 		'<settings-group-name1>' => [
		 *			'select:<setting-name1>' => [
		 *				'<select-value1>' => '<value-label1>',
		 *				'<select-value2>' => '<value-label2>',
		 *				...
		 *				'value' => '<setting-value1>'
		 *			],
		 *			'ufloat:<setting-name2>' => [
		 *				'value' => '<setting-value2>'
		 *			],
		 *			...
		 *		],
		 * 		'<settings-group-name2>' => [
		 *			'string:<setting-name3>' => [
		 *				'value' => '<setting-value3>'
		 *			],
		 *			'ufloat:<setting-name4>' => [
		 *				'value' => '<setting-value4>'
		 *			],
		 *			...
		 *		],
		 *		...
		 * ]
		 */
		public function getDeliverySettings() {
			$module = $this->module;
			$options = $this->initOptions();
			$settings = $this->settings;

			$this->forEachOption(function ($group, $option, $settingGroup, $settingName) use ($settings, &$options) {
				$options[$group][$option]['value'] = $settings->get($settingGroup, $settingName);
			});

			return $options;
		}

		/**
		 * Сохраняет настройки
		 * @param array $data
		 * [
		 * 		'<setting-name1>' => '<value1>',
		 * 		'<setting-name2>' => '<value2>',
		 *		...
		 * ]
		 */
		public function setDeliverySettings($data) {
			$module = $this->module;
			$params = $this->initOptions();
			$settings = $this->settings;

			foreach ($params as $groupKey => $group) {
				foreach ($group as $paramKey => $param) {
					$paramName = def_module::getRealKey($paramKey);
					$params[$groupKey][$paramKey] = $data[$paramName];
				}
			}

			$this->forEachOption(function ($group, $option, $settingGroup, $settingName) use ($settings, $params) {
				$settings->set($settingGroup, $settingName, $params[$group][$option]);
			});
		}

	}
