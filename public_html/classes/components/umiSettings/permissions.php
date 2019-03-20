<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на добавление настроек */
		'create' => [
			'create', 'permissions'
		],
		/** Права на чтение настроек */
		'read' => [
			'read', 'permissions', 'getid', 'getidbycustomid', 'getidbyname'
		],
		/** Права на изменение настроек */
		'update' => [
			'update', 'permissions'
		],
		/** Права на удаление настроек */
		'delete' => [
			'delete', 'permissions'
		]
	];
