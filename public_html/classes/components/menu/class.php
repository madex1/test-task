<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Меню".
	 * Модуль отвечает за:
	 * 1) Предоставление интерфейса для создания и редактирования меню из произвольных элементов;
	 * 2) Актуализацию содержимого меню;
	 * 3) Вывод данных для шаблонизации меню;
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_menu/
	 */
	class menu extends def_module {

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

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('MenuAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('MenuCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('MenuMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('MenuHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('MenuCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылку на страницу редактирования меню
		 * @param int $objectId идентификатор меню
		 * @param bool $type контрольный параметр
		 * @return string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return $this->getEditLink($objectId);
		}

		/**
		 * Возвращает суффикс для адресов системных страниц
		 * @return string
		 */
		public function getUrlSuffix() {
			$suffix = '';
			$config = mainConfiguration::getInstance();

			if ($config->get('seo', 'url-suffix.add')) {
				$suffix = (string) $config->get('seo', 'url-suffix');
			}

			return $suffix;
		}

		/**
		 * Возвращает список меню
		 * @return array|bool
		 * @throws selectorException
		 */
		public function getListMenu() {
			static $cache = null;

			if ($cache !== null) {
				return $cache;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('menu', 'item_element');

			if (!$sel->length()) {
				return $cache = false;
			}

			return $cache = $sel->result();
		}

		/**
		 * Актуализует меню
		 * @param bool|array|object $values меню в json
		 * @return array|bool
		 */
		public function editLinkMenu($values = false) {
			if (!is_array($values) && !is_object($values)) {
				return $values;
			}

			$values = is_array($values) ? $values : (array) $values;
			$hierarchy = umiHierarchy::getInstance();

			foreach ($values as $key => $value) {
				$rel = $value->rel;
				$children = !empty($value->children) ? $value->children : false;

				if ($rel != 'custom' && $rel != 'system') {
					$link = $hierarchy->getPathById($rel);
					$element = $hierarchy->getElement($rel);

					if (!$link || !($element instanceof iUmiHierarchyElement)) {
						unset($values[$key]);
						continue;
					}

					$value->link = $link;
					$element = $hierarchy->getElement($rel);
					$value->isactive = (int) $element->getIsActive();
					$value->isdeleted = (int) $element->getIsDeleted();
					$hierarchy->unloadElement($rel);
				}

				if ($children) {
					$this->editLinkMenu($children);
				}
			}

			return $values;
		}

		/**
		 * Сформировать ссылки для страниц из меню и загрузить эти страницы в кэш
		 * @param array $menuHierarchy массив объектов с информацией об элементах меню
		 * @return bool
		 */
		public function loadMenuElements($menuHierarchy) {
			$elementIds = [];

			foreach ($menuHierarchy as $element) {
				$childIds = $this->collectIds($element);

				if (is_array($childIds)) {
					foreach ($childIds as $id) {
						$elementIds[] = $id;
					}
				}
			}

			$elementIds = array_unique($elementIds);

			if (umiCount($elementIds) == 0) {
				return false;
			}

			umiLinksHelper::getInstance()->loadLinkPartForPages($elementIds);
			umiHierarchy::getInstance()->loadElements($elementIds);
			return true;
		}

		/**
		 * Возвращает идентификаторы
		 * страниц из ветви меню в json формате
		 * @param stdClass $element ветвь меню в json формате
		 * @return array|bool
		 */
		private function collectIds($element) {
			$elementIds = [];

			if (isset($element->rel) && is_numeric($element->rel)) {
				$elementIds[] = $element->rel;
			}

			if (isset($element->children)) {
				foreach ($element->children as $children) {
					$ids = $this->collectIds($children);

					if (is_array($ids)) {
						foreach ($ids as $id) {
							$elementIds[] = $id;
						}
					}
				}
			}

			if (umiCount($elementIds) > 0) {
				return $elementIds;
			}

			return false;
		}

		/**
		 * Возвращает ссылку на страницу с формой редактирования меню
		 * @param int $objectId идентификатор меню
		 * @return string
		 */
		public function getEditLink($objectId) {
			return $this->pre_lang . '/admin/menu/edit/' . $objectId . '/';
		}
	}
