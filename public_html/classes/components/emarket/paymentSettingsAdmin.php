<?php

	/** Класс вкладки настроек оплаты */
	class EmarketPaymentSettingsAdmin implements iModulePart {

		use baseModuleAdmin;
		use tModulePart;

		/** @var EmarketSettings настройки модуля */
		protected $settings;

		/** @const название вкладки */
		const TAB_NAME = 'paymentSettings';

		/** @var array конфиг опций вкладки */
		protected $options = [
			'order-item-default-settings' => [
				'select:item-default-tax-id' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'taxRateId',
					'initialValue' => 'getOrderItemTaxList',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				],
				'select:item-default-payment-subject' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'paymentSubjectId',
					'initialValue' => 'getOrderItemPaymentSubjectList',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				],
				'select:item-default-payment-mode' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'paymentModeId',
					'initialValue' => 'getOrderItemPaymentModeList',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				]
			],
		];

		/**
		 * Конструктор
		 * @param emarket $module
		 * @throws coreException
		 */
		public function __construct($module) {
			$tabsManager = $module->getConfigTabs();

			if (!$tabsManager instanceof adminModuleTabs) {
				return false;
			}

			$tabsManager->add(self::TAB_NAME);
			$this->settings = $module->getImplementedInstance($module::SETTINGS_CLASS);
		}

		/**
		 * Метод вкладки настроек оплаты
		 * @throws coreException
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 * @throws RequiredPropertyHasNoValueException
		 * @throws publicAdminException
		 * @throws Exception
		 */
		public function paymentSettings() {
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

			/** @var baseModuleAdmin|emarket $module */
			$module = $this->getModule();
			$module->setConfigResult($options);
		}

		/**
		 * Возвращает список ставок НДС
		 * @return array
		 *
		 * [
		 *      umiObject::getId() => umiObject::getName()
		 * ]
		 * @throws coreException
		 */
		protected function getOrderItemTaxList() {
			return $this->getOrderItemPropertyListByGuid('tax-rate-guide');
		}

		/**
		 * Возвращает список предметов расчета
		 * @return array
		 *
		 * [
		 *      umiObject::getId() => umiObject::getName()
		 * ]
		 * @throws coreException
		 */
		protected function getOrderItemPaymentSubjectList() {
			return $this->getOrderItemPropertyListByGuid('payment_subject');
		}

		/**
		 * Возвращает список способов расчета
		 * @return array
		 *
		 * [
		 *      umiObject::getId() => umiObject::getName()
		 * ]
		 * @throws coreException
		 */
		protected function getOrderItemPaymentModeList() {
			return $this->getOrderItemPropertyListByGuid('payment_mode');
		}

		/**
		 * Возвращает список значений по гуиду типа данных
		 * @param string $guid
		 * @return array
		 *
		 * [
		 *      umiObject::getId() => umiObject::getName()
		 * ]
		 * @throws coreException
		 */
		protected function getOrderItemPropertyListByGuid($guid) {
			$taxGuideId = umiObjectTypesCollection::getInstance()
				->getTypeIdByGUID($guid);

			return umiObjectsCollection::getInstance()
				->getGuidedItems($taxGuideId);
		}
	}
