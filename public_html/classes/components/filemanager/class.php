<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Файловая система".
	 * Модуль отвечает за работу со страницами со скачиваемыми файлами
	 * и предоставляет бек для файлового менеджера.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_fajlovaya_sistema/
	 */
	class filemanager extends def_module {

		/** @var int количество выводимых элементов на страницу в рамках пагинации */
		public $per_page = 25;

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('FilemanagerAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('FileManagerCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('FileManagerMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('FileManagerCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылки на редактирование и создание сущности модуля
		 * @param int $element_id идентификатор сущности
		 * @param string $element_type тип сущности
		 * @return array|bool
		 */
		public function getEditLink($element_id, $element_type) {
			switch ($element_type) {
				case 'shared_file': {
					$link_add = $this->pre_lang . "/admin/filemanager/add_shared_file/{$element_id}/";
					$link_edit = $this->pre_lang . "/admin/filemanager/edit_shared_file/{$element_id}/";
					return [$link_add, $link_edit];
				}
				default: {
					return false;
				}
			}
		}
	}
