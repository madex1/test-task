<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Фотогалереи".
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Фотографии;
	 * 2) Фотоальбомы;
	 *
	 * Умеет создавать фотографии путем импорта файлов изображений,
	 * как по дному файлу, так и по несколько.
	 *
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_fotogalerei/
	 */
	class photoalbum extends def_module {

		/** @var int $per_page ограничение на количество выводимых страниц */
		public $per_page = 10;

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			$per_page = (int) Service::Registry()
				->get('//modules/photoalbum/per_page');

			if ($per_page) {
				$this->per_page = $per_page;
			}

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
			$this->__implement('PhotoalbumAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('PhotoAlbumCustomAdmin', true);

			$this->__loadLib('import.php');
			$this->__implement('ImportPhotoAlbum');

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('PhotoAlbumMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('PhotoAlbumCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Проверяет существование директории для изображения фотоальбомов.
		 * Если директорий нет - создает их.
		 * Возвращает путь до директории.
		 * @param iUmiHierarchyElement $parent фотоальбом
		 * @return string
		 */
		public function _checkFolder($parent) {
			$folder = USER_IMAGES_PATH . '/cms/data';

			if (!$parent) {
				return $folder . '/';
			}

			if (getRequest('param0') == 0 && getRequest('alt-name')) {
				@mkdir($folder . '/' . translit::convert(getRequest('alt-name')));
				return $folder;
			}

			/** @var iUmiHierarchyElement $parent */
			if (!$parent instanceof iUmiHierarchyElement) {
				return $folder . '/';
			}

			$hierarchy = umiHierarchy::getInstance();
			$parentsIds = $hierarchy->getAllParents($parent->getId(), true);

			if (umiCount($parentsIds) == 0) {
				return $folder . '/';
			}

			$parents = $hierarchy->loadElements($parentsIds);
			$altDirs = [];

			foreach ($parents as $parent) {
				$altDirs[] = $parent->getAltName();
			}

			foreach ($altDirs as $alt) {
				$folder .= '/' . $alt;
				if (!file_exists($folder)) {
					@mkdir($folder);
				}
			}

			return $folder . '/';
		}

		/**
		 * Возвращает ссылки на страницу редактирование сущности и
		 * страницу добавления дочерней сущности
		 * @param int $element_id идентификатор сущности
		 * @param string $element_type тип сущности
		 * @return array|bool
		 */
		public function getEditLink($element_id, $element_type) {
			$prefix = $this->pre_lang;
			switch ($element_type) {
				case 'album': {
					$link_add = $prefix . "/admin/photoalbum/add/{$element_id}/photo/";
					$link_edit = $prefix . "/admin/photoalbum/edit/{$element_id}/";
					return [$link_add, $link_edit];
				}
				case 'photo': {
					$link_add = false;
					$link_edit = $prefix . "/admin/photoalbum/edit/{$element_id}/";
					return [$link_add, $link_edit];
				}
				default: {
					return false;
				}
			}
		}
	}
