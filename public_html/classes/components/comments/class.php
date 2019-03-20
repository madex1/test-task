<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Комментарии".
	 *
	 * Модуль управляет сущностью "Комментарий" и
	 * содержит настройки для вставки виджетов комментариев
	 * Facebook и Вконтакте.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_kommentarii/
	 */
	class comments extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();
			$umiRegistry = Service::Registry();

			if ($umiRegistry->get('//modules/comments/default_comments') == null) {
				$umiRegistry->set('//modules/comments/default_comments', 1);
			}

			$this->per_page = (int) $umiRegistry->get('//modules/comments/per_page');
			$this->moderated = (int) $umiRegistry->get('//modules/comments/moderated');

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
				$commonTabs->add('view_comments');
				$commonTabs->add('view_noactive_comments');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('CommentsAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('CommentsCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('CommentsMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('CommentsHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('CommentsCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылки на форму редактирования страницы модуля и
		 * на форму добавления дочернего элемента к странице.
		 * @param int $element_id идентификатор страницы модуля
		 * @param string $element_type тип страницы модуля
		 * @return array|bool
		 */
		public function getEditLink($element_id, $element_type) {
			switch ($element_type) {
				case 'comment': {
					$link_edit = $this->pre_lang . "/admin/comments/edit/{$element_id}/";
					return [false, $link_edit];
				}
				default: {
					return false;
				}
			}
		}
	}
