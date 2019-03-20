<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class CommentsAdmin {

		use baseModuleAdmin;

		/** @var comments $module */
		public $module;

		/**
		 * Возвращает активные комментарии
		 * @return bool
		 * @throws coreException
		 * @throws expectElementException
		 * @throws selectorException
		 */
		public function view_comments() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$this->expectElementId('param0');
			$limit = getRequest('per_page_limit');
			$currentPage = (int) getRequest('p');
			$offset = $limit * $currentPage;

			if (($rel = getRequest('rel')) !== null) {
				$rel = array_extract_values($rel);
				if (empty($rel)) {
					unset($_REQUEST['rel']);
				}
			}

			$sel = new selector('pages');
			$sel->types('object-type')->name('comments', 'comment');
			$sel->order('id')->desc();
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает неактивные комментарии сайта
		 * @return bool
		 * @throws coreException
		 * @throws expectElementException
		 * @throws selectorException
		 */
		public function view_noactive_comments() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$this->expectElementId('param0');
			$limit = getRequest('per_page_limit');
			$currentPage = (int) getRequest('p');
			$offset = $limit * $currentPage;

			$sel = new selector('pages');
			$sel->types('object-type')->name('comments', 'comment');
			$sel->where('is_active')->equals(false);
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Удаляет комментарии
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
					'allowed-element-types' => ['comment']
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
		 * Изменяет активность комментариев
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['comment']);
		}

		/**
		 * Возвращает данные для создания формы редактирования комментария,
		 * если передан $_REQUEST['param1'] = do пытается сохранить изменения
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);
			$inputData = [
				'element' => $element,
				'allowed-element-types' => ['comment']
			];

			if ($this->isSaveMode('param1')) {
				$this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($inputData, 'page');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет переданные настройки.
		 * @throws coreException
		 */
		public function config() {
			$regedit = Service::Registry();
			$params = [
				'config' => [
					'boolean:default_comments' => null,
					'int:per_page' => null,
					'boolean:moderated' => null,
					'boolean:allow_guest' => null,
				],
				'vkontakte' => [
					'boolean:vkontakte' => null,
					'boolean:vk_extend' => null,
					'int:vk_per_page' => null,
					'int:vk_width' => null,
					'string:vk_api' => null,
				],
				'facebook' => [
					'boolean:facebook' => null,
					'int:fb_per_page' => null,
					'int:fb_width' => null,
					'select:fb_colorscheme' => [
						'light' => getLabel('option-colorscheme-light'),
						'dark' => getLabel('option-colorscheme-dark')
					]
				]
			];

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);

				$regedit->set('//modules/comments/default_comments', $params['config']['boolean:default_comments']);
				$regedit->set('//modules/comments/per_page', $params['config']['int:per_page']);
				$regedit->set('//modules/comments/moderated', $params['config']['boolean:moderated']);
				$regedit->set('//modules/comments/allow_guest', $params['config']['boolean:allow_guest']);

				$regedit->set('//modules/comments/vkontakte', $params['vkontakte']['boolean:vkontakte']);
				$regedit->set('//modules/comments/vk_per_page', $params['vkontakte']['int:vk_per_page']);
				$regedit->set('//modules/comments/vk_width', $params['vkontakte']['int:vk_width']);
				$regedit->set('//modules/comments/vk_api', $params['vkontakte']['string:vk_api']);
				$regedit->set('//modules/comments/vk_extend', $params['vkontakte']['boolean:vk_extend']);

				$regedit->set('//modules/comments/facebook', $params['facebook']['boolean:facebook']);
				$regedit->set('//modules/comments/fb_per_page', $params['facebook']['int:fb_per_page']);
				$regedit->set('//modules/comments/fb_width', $params['facebook']['int:fb_width']);
				$regedit->set('//modules/comments/fb_colorscheme', $params['facebook']['select:fb_colorscheme']);

				$this->chooseRedirect();
			}

			$params['config']['boolean:default_comments'] = (bool) $regedit->get('//modules/comments/default_comments');
			$params['config']['int:per_page'] = (int) $regedit->get('//modules/comments/per_page');
			$params['config']['boolean:moderated'] = (bool) $regedit->get('//modules/comments/moderated');
			$params['config']['boolean:allow_guest'] = (bool) $regedit->get('//modules/comments/allow_guest');

			$params['vkontakte']['boolean:vkontakte'] = (bool) $regedit->get('//modules/comments/vkontakte');
			$params['vkontakte']['int:vk_per_page'] = (int) $regedit->get('//modules/comments/vk_per_page');
			$params['vkontakte']['int:vk_width'] = (int) $regedit->get('//modules/comments/vk_width');
			$params['vkontakte']['string:vk_api'] = (string) $regedit->get('//modules/comments/vk_api');
			$params['vkontakte']['boolean:vk_extend'] = (bool) $regedit->get('//modules/comments/vk_extend');

			$params['facebook']['boolean:facebook'] = (bool) $regedit->get('//modules/comments/facebook');
			$params['facebook']['int:fb_per_page'] = (int) $regedit->get('//modules/comments/fb_per_page');
			$params['facebook']['int:fb_width'] = (int) $regedit->get('//modules/comments/fb_width');
			$params['facebook']['select:fb_colorscheme']['value'] = (string) $regedit->get('//modules/comments/fb_colorscheme');

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'comments',
						'#__name' => ($param == 'noactive') ? 'view_noactive_comments' : 'view_comments'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'comments',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'comments',
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
						'id' => 'comment'
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
					'anons',
					'content',
					'rate_voters',
					'rate_sum'
				],
				'default' => 'name[400px]|publish_time[250px]'
			];
		}
	}


