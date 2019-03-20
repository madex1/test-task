<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Просмотр контента */
		'content' => [
			'menu',
			'sitemap',
			'gen_sitemap',
			'get_page_url',
			'get_page_id',
			'redirect',
			'insert',
			'gen404',
			'getlist',
			'content',
			'pagesbydomaintags',
			'pagesbyaccounttags',
			'tagsaccountcloud',
			'tagsaccountusagecloud',
			'tagsaccountefficiencycloud',
			'tagsdomaincloud',
			'tagsdomainefficiencycloud',
			'tagsdomainusagecloud',
			'addrecentpage',
			'getrecentpages',
			'delrecentpage',
			'getserverprotocol',
			'getmobilemodeslist',
			'setmobilemode',
			'page',
			'includeFrontendResources',
		],
		/** Администрирование модуля */
		'sitetree' => [
			'getobjectsbytypelist',
			'getobjectsbybasetypelist',
			'getpagesbybasetypelist',
			'setbasetemplate',
			'domaintemplates',
			'sitetree',
			'tree',
			'edit',
			'add',
			'tpl_edit',
			'unlock_page',
			'content_control',
			'publish',
			/** Быстрое редактирование в контролах */
			'tree_move_element',
			'tree_set_activity',
			'tree_move_element',
			'tree_delete_element',
			'load_tree_node',
			'tree_copy_element',
			'copyElementToSite',
			'get_editable_region',
			'save_editable_region',
			'move',
			/** EIP */
			'eip_move_page',
			'eip_quick_add',
			'editValue',
			'eip_add_page',
			'frontendpanel',
			'gettypeadding',
			/** Редактор изображений */
			'ieditor',
			'ieditor_resize',
			'ieditor_crop',
			'ieditor_rotate',
			'ieditor_upload',
			'getimageurl',
			'getimagedata',
			'page.edit',
			'.edit'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление страниц */
		'delete' => [
			'del',
			'eip_del_page'
		]
	];

