<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование модуля */
		'seo' => [
			'seo',
			'links',
			'webmaster',
			'flushsitelistconfig',
			'flushexternallinkslistconfig',
			'getexternallinklist',
			'getsiteinfo',
			'addsite',
			'verifysite',
			'addsitemap',
			'megaindex',
			'yandex',
			'getbrokenlinks',
			'getdatasetconfiguration',
			'flushbrokenlinksdatasetconfiguration',
			'indexlinks',
			'checklinks',
			'getlinksources',
			'emptymetatags'
		],
		/** Гостевые права */
		'guest' => [
			'getrelcanonical'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление сайта из Яндекс.Вебмастер */
		'delete' => [
			'deletesite'
		]
	];
