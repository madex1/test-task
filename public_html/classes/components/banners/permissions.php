<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование модуля */
		'banners_list' => [
			'add',
			'edit',
			'activity',
			'lists',
			'places'
		],
		/** Права на просмотр баннеров */
		'insert' => [
			'insert',
			'go_to',
			'fastinsert',
			'multiplefastinsert',
			'getstaticbannercall',
			'renderBanner'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление баннеров и мест */
		'delete' => [
			'del'
		]
	];
