<?php

	use UmiCms\Service;

	/**
	 * Модуль заглушка
	 * @link http://api.docs.umi-cms.ru/razrabotka_nestandartnogo_funkcionala/razrabotka_sobstvennyh_makrosov_i_modulej/sozdanie_modulya/
	 */
	class dummy extends def_module {

		/** @var string $pagesLimitXpath путь до опции реестра, отвечающего за ограничение количества выводимых страниц */
		public $pagesLimitXpath = '//modules/dummy/paging/pages';

		/** @var string $objectsLimitXpath путь до опции реестра, отвечающего за ограничение количества выводимых объектов */
		public $objectsLimitXpath = '//modules/dummy/paging/objects';

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
			$configTabs = $this->getConfigTabs();

			if ($configTabs instanceof iAdminModuleTabs) {
				$configTabs->add('config');
			}

			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('pages');
				$commonTabs->add('objects');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('DummyAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('DummyCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('DummyMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('DummyCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылки на форму редактирования страницы модуля и
		 * на форму добавления дочернего элемента к странице.
		 * @param int $element_id идентификатор страницы модуля
		 * @param string|bool $element_type тип страницы модуля
		 * @return array
		 */
		public function getEditLink($element_id, $element_type = false) {
			return [
				false,
				$this->pre_lang . "/admin/dummy/editPage/{$element_id}/"
			];
		}

		/**
		 * Возвращает ссылку на редактирование объектов в административной панели
		 * @param int $objectId ID редактируемого объекта
		 * @param string|bool $type метод типа объекта
		 * @return string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . '/admin/dummy/editObject/' . $objectId . '/';
		}
	}

