<?php
	/**
	 * Заглушка модуля "Шаблоны уведомлений".
	 * Сам модуль работает только в новом режиме работы модулей.
	 */
	class umiNotifications extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();
			$cmsController = cmsController::getInstance();

			if ($cmsController->isCurrentModeAdmin()) {
				$this->initTabs();
			}
		}

		/**
		 * Заглушка метода административной панели по умолчанию
		 * @throws publicAdminException
		 */
		public function notifications() {
			throw new publicAdminException(getLabel('use-compatible-modules'));
		}

		/** Создает вкладки административной панели модуля */
		private function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('notifications');
			}
		}
	}
