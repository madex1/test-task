<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Гостевые права */
		'guest' => [
			'page',
			'pageslist',
			'objectslist'
		],
		/** Административный права */
		'admin' => [
			'pages',
			'addpage',
			'editpage',
			'activity',
			'objects',
			'addobject',
			'editobject'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление объектов и страниц */
		'delete' => [
			'deletepages',
			'deleteobjects'
		]
	];
