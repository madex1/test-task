<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Блоги".
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Блоги;
	 * 2) Посты;
	 * 3) Комментарии;
	 *
	 * Модуль позволяет создавать комментарии и посты с клиентской части.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_blogi/
	 */
	class blogs20 extends def_module {

		/** @var int $blogs_per_page количество блогов на странице */
		public $blogs_per_page = 0;

		/** @var int $posts_per_page количество постов на странице */
		public $posts_per_page = 0;

		/** @var int $comments_per_page количество комментариев на странице */
		public $comments_per_page = 0;

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

			$umiRegistry = Service::Registry();
			$blogXpath = '//modules/blogs20/paging/blogs';
			$postsXpath = '//modules/blogs20/paging/posts';
			$commentsXpath = '//modules/blogs20/paging/comments';

			$this->blogs_per_page = (int) $umiRegistry->get($blogXpath) > 0
				? (int) $umiRegistry->get($blogXpath)
				: $this->blogs_per_page;
			$this->posts_per_page = (int) $umiRegistry->get($postsXpath) > 0
				? (int) $umiRegistry->get($postsXpath)
				: $this->posts_per_page;
			$this->comments_per_page = (int) $umiRegistry->get($commentsXpath) > 0
				? (int) $umiRegistry->get($commentsXpath)
				: $this->comments_per_page;
			$this->moderate = (bool) $umiRegistry->get('//modules/blogs20/moderate_comments');

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
				$commonTabs->add('posts');
				$commonTabs->add('blogs');
				$commonTabs->add('comments');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('Blogs20Admin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('BlogsCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('BlogsMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('BlogsHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('BlogsCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылки на страницу редактирование сущности и
		 * страницу добавления дочерней сущности
		 * @param int $element_id идентификатор сущности
		 * @param string $element_type тип сущности
		 * @return array|bool
		 */
		public function getEditLink($element_id, $element_type) {
			switch ($element_type) {
				case 'blog': {
					$link_add = $this->pre_lang . "/admin/blogs20/add/{$element_id}/post/";
					$link_edit = $this->pre_lang . "/admin/blogs20/edit/{$element_id}/";
					return [$link_add, $link_edit];
				}
				case 'comment':
				case 'post': {
					$link_edit = $this->pre_lang . "/admin/blogs20/edit/{$element_id}/";
					return [false, $link_edit];
				}
				default: {
					return false;
				}
			}
		}

		/** @inheritdoc */
		public function getMailObjectTypesGuidList() {
			return ['users-user', 'users-author'];
		}

		/** @deprecated  */
		public function getVariableNamesForMailTemplates() {
			return [];
		}
	}
