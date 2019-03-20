<?php

	use UmiCms\Service;

	/** Базовый класс модуля "Настройки сайта" */
	class umiSettings extends def_module {

		/** @const string ROOT_TYPE_GUID гуид корневого типа данных настроек */
		const ROOT_TYPE_GUID = 'root-settings-type';

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('read');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('UmiSettingsAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('UmiSettingsCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('UmiSettingsMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('UmiSettingsCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает права пользователя на функционал модуля
		 * @param int|bool $userId идентификатор пользователя
		 * @return array
		 *
		 * [
		 *        'data' => [
		 *            'create' => 1|0,
		 *            'read' => 1|0,
		 *            'update' => 1|0,
		 *            'delete' => 1|0
		 *        ]
		 * ]
		 */
		public function permissions($userId = false) {
			$permissionsCollection = permissionsCollection::getInstance();
			$modulePermissionsGroupList = $permissionsCollection->getStaticPermissions(__CLASS__);

			$result = [];
			if (!is_numeric($userId)) {
				$userId = Service::Auth()
					->getUserId();
			}

			foreach ($modulePermissionsGroupList as $groupName => $methodList) {
				$result[$groupName] = (int) $permissionsCollection->isAllowedMethod($userId, __CLASS__, $groupName);
			}

			return [
				'data' => $result
			];
		}

		/** @inheritdoc */
		public function getObjectEditLink($objectId, $type = false) {
			$permissions = $this->permissions();
			$permissions = array_shift($permissions);

			if (isset($permissions['update']) && $permissions['update'] == 1) {
				return $this->pre_lang . '/admin/umiSettings/update/' . $objectId . '/';
			}

			return '';
		}
	}
