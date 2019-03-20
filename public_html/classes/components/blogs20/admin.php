<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class Blogs20Admin {

		use baseModuleAdmin;

		/** @var blogs20 $module */
		public $module;

		/**
		 * Выводит дерево блогов, постов и комментариев
		 * @return mixed
		 */
		public function blogs() {
			$this->listItems('blog');
		}

		/**
		 * Выводит список постов
		 * @return mixed
		 */
		public function posts() {
			$this->listItems('post');
		}

		/**
		 * Выводит список комментариев
		 * @return mixed
		 */
		public function comments() {
			$this->listItems('comment');
		}

		/**
		 * Возвращает список сущностей определенного типа(ов)
		 * @param string $itemType тип сущностей
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function listItems($itemType) {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$data['nodes:blogs'] = [['nodes:blog' => $this->getAllBlogs()]];
				$this->setData($data, 0);
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$curr_page = getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('pages');
			$sel->limit($offset, $limit);

			switch ($itemType) {
				case 'comment': {
					$sel->types('object-type')->name('blogs20', 'comment');
					break;
				}
				case 'post': {
					if (getRequest('rel') !== null) {
						$sel->types('object-type')->name('blogs20', 'comment');
					}
					$sel->types('object-type')->name('blogs20', 'post');
					break;
				}
				default: {

					if (!selectorHelper::isFilterRequested()) {
						$sel->types('object-type')->name('blogs20', 'blog');
						$sel->types('object-type')->name('blogs20', 'comment');
					}

					$sel->types('object-type')->name('blogs20', 'post');
				}
			}

			selectorHelper::detectFilters($sel);
			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает список блогов
		 * @return array
		 * @throws selectorException
		 */
		public function getAllBlogs() {
			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('blogs20', 'blog');

			$result = [];
			/** @var iUmiHierarchyElement $blog */
			foreach ($sel as $blog) {
				$result[] = [
					'attribute:id' => $blog->getId(),
					'node:name' => $blog->getName()
				];
			}

			return $result;
		}

		/**
		 * Возвращает данные для построения формы добавления
		 * сущности модуля. Если передан ключевой параметр $_REQUEST['param2'] = do,
		 * то добавляет сущность.
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$parent = $this->expectElement('param0');
			$type = (string) getRequest('param1');
			$this->setHeaderLabel('header-blogs20-add-' . $type);

			$inputData = [
				'type' => $type,
				'parent' => $parent,
				'allowed-element-types' => [
					'post',
					'blog'
				]
			];

			if ($this->isSaveMode('param2')) {
				$this->saveAddedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'page');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для построения формы редактирования
		 * сущности модуля. Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения сущности
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);
			$this->setHeaderLabel('header-blogs20-edit-' . $this->getObjectTypeMethod($element->getObject()));
			$inputData = [
				'element' => $element,
				'allowed-element-types' => [
					'post',
					'blog',
					'comment'
				]
			];

			if ($this->isSaveMode('param1')) {
				$this->saveEditedElementData($inputData);
				if ($element->getTypeId() ==
					umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'blog')->getId()) {
					permissionsCollection::getInstance()->setElementPermissions($element->getObject()->getOwnerId(), $element->getId(), 31);
				}
				$this->chooseRedirect();
			}
			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($inputData, 'page');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет сущность модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$elements = getRequest('element');

			if (!is_array($elements)) {
				$elements = [$elements];
			}

			foreach ($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);

				$params = [
					'element' => $element,
					'allowed-element-types' => ['post', 'blog', 'comment']
				];
				$this->deleteElement($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($elements, 'pages');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Изменяет активность сущностей модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['post', 'blog', 'comment']);
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @throws coreException
		 */
		public function config() {
			$regedit = Service::Registry();
			$umiNotificationInstalled = cmsController::getInstance()
				->isModule('umiNotifications');

			$params = [
				'paging' => [
					'int:blogs_per_page' => null,
					'int:posts_per_page' => null,
					'int:comments_per_page' => null
				],
				'user' => [
					'string:autocreate_path' => null,
					'int:blogs_per_user' => null,
					'boolean:allow_guest_comments' => null,
					'boolean:moderate_comments' => null
				],
				'notifications' => [
					'boolean:on_comment_add' => null
				]
			];

			if ($umiNotificationInstalled) {
				$params['notifications']['boolean:use-umiNotifications'] = null;
			}

			if ($this->isSaveMode()) {
				try {
					$params = $this->expectParams($params);
					$regedit->set('//modules/blogs20/paging/blogs', $params['paging']['int:blogs_per_page']);
					$regedit->set('//modules/blogs20/paging/posts', $params['paging']['int:posts_per_page']);
					$regedit->set('//modules/blogs20/paging/comments', $params['paging']['int:comments_per_page']);
					$regedit->set('//modules/blogs20/autocreate_path', $params['user']['string:autocreate_path']);
					$regedit->set('//modules/blogs20/blogs_per_user', $params['user']['int:blogs_per_user']);
					$regedit->set('//modules/blogs20/allow_guest_comments', $params['user']['boolean:allow_guest_comments'] ? 1 : 0);
					$regedit->set('//modules/blogs20/moderate_comments', $params['user']['boolean:moderate_comments'] ? 1 : 0);
					$regedit->set('//modules/blogs20/notifications/on_comment_add', $params['notifications']['boolean:on_comment_add'] ? 1 : 0);

					if ($umiNotificationInstalled) {
						$regedit->set('//modules/blogs20/use-umiNotifications', $params['notifications']['boolean:use-umiNotifications'] ? 1 : 0);
					}
				} catch (Exception $e) {
					// nothing
				}
				$this->chooseRedirect();
			}

			$params['paging']['int:blogs_per_page'] = $regedit->get('//modules/blogs20/paging/blogs');
			$params['paging']['int:posts_per_page'] = $regedit->get('//modules/blogs20/paging/posts');
			$params['paging']['int:comments_per_page'] = $regedit->get('//modules/blogs20/paging/comments');
			$params['user']['string:autocreate_path'] = $regedit->get('//modules/blogs20/autocreate_path');
			$params['user']['int:blogs_per_user'] = $regedit->get('//modules/blogs20/blogs_per_user');
			$params['user']['boolean:allow_guest_comments'] =
				(bool) $regedit->get('//modules/blogs20/allow_guest_comments');
			$params['user']['boolean:moderate_comments'] = (bool) $regedit->get('//modules/blogs20/moderate_comments');
			$params['notifications']['boolean:on_comment_add'] =
				(bool) $regedit->get('//modules/blogs20/notifications/on_comment_add');

			if ($umiNotificationInstalled) {
				$params['notifications']['boolean:use-umiNotifications'] =
					(bool) $regedit->get('//modules/blogs20/use-umiNotifications');
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			switch ($param) {
				case 'comments' : {
					$loadMethod = 'comments';
					break;
				}
				case 'posts' : {
					$loadMethod = 'posts';
					break;
				}
				default: {
					$loadMethod = 'blogs';
				}
			}
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'blogs20',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'blogs20',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'blogs20',
						'#__name' => 'activity',
						'aliases' => 'tree_set_activity,activity'
					],
					[
						'title' => getLabel('smc-copy'),
						'module' => 'content',
						'#__name' => 'tree_copy_element'
					],
					[
						'title' => getLabel('smc-move'),
						'module' => 'content',
						'#__name' => 'move'
					],
					[
						'title' => getLabel('smc-change-template'),
						'module' => 'content',
						'#__name' => 'change_template'
					],
					[
						'title' => getLabel('smc-change-lang'),
						'module' => 'content',
						'#__name' => 'copyElementToSite'
					],
				],
				'types' => [
					[
						'common' => 'true',
						'id' => umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('blogs20', 'post')
					]
				],
				'stoplist' => [
					'title',
					'h1',
					'meta_keywords',
					'meta_descriptions',
					'menu_pic_ua',
					'menu_pic_a',
					'header_pic',
					'more_params',
					'robots_deny',
					'is_unindexed',
					'store_amounts',
					'locktime',
					'lockuser',
					'content',
					'rate_voters',
					'rate_sum'
				],
				'default' => 'name[400px]|publish_time[250px]'
			];
		}

	}
