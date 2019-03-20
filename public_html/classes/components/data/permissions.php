<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Просмотр объектов и использование форм */
		'main' => [
			'geteditform',
			'getcreateform',
			'saveeditedobject',
			'getproperty',
			'getpropertygroup',
			'getpropertyofobject',
			'getpropertygroupofobject',
			'getallgroups',
			'getallgroupsofobject',
			'rss',
			'atom',
			'generatefeed',
			'getrssmeta',
			'getrssmetabypath',
			'getatommeta',
			'getatommetabypath',
			'checkiffeedable',
			'doselection',
			'getguideitems',
		],
		/** Управление справочниками */
		'guides' => [
			'guide_items',
			'guide_item_edit',
			'guide_add',
			'guide_items_all',
			'guide_item_add',
			'getguideitems',
			'getdomainlist',
			'addobjecttoguide'
		],
		/** Файловый менеджер */
		'files' => [
			'getfilelist',
			'uploadfile',
			'elfinder_connector',
			'get_filemanager_info'
		],
		/** Управление типами данных */
		'types' => [
			'type_add',
			'type_edit',
			'type_field_add',
			'isfieldexist',
			'type_field_edit',
			'type_group_add',
			'type_group_edit',
			'json_move_field_after',
			'json_move_group_after',
			'getsamefieldfromrelatedtypes',
			'attachField'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление объектных и базовых типов, справочников и их элементов, полей и их групп */
		'delete' => [
			'guide_item_del',
			'type_del',
			'json_delete_field',
			'json_delete_group',
		]
	];
