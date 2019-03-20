<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на запуск крона по http */
		'cron_http_execute' => [
			'cron_http_execute'
		],
		/** Права на работу с глобальными настройками */
		'main' => [
			'main'
		],
		/** Права на работу с решениями */
		'solutions' => [
			'solutions',
			'getfullsolutionlist'
		],
		/** Права на работу с модулями */
		'modules' => [
			'modules',
			'add_module_do'
		],
		/** Права на работу с расширениями */
		'extensions' => [
			'extensions'
		],
		/** Права на работу с языками */
		'langs' => [
			'langs'
		],
		/** Права на работу с доменами */
		'domains' => [
			'domains',
			'domain_mirrows',
			'update_sitemap'
		],
		/** Права на работу с настройками почты */
		'mails' => [
			'mails'
		],
		/** Права на работу с настройками производительности */
		'cache' => [
			'cache',
			'speedtest'
		],
		/** Права на выполнение тестов безопасности */
		'security' => [
			'security',
			'securityruntest'
		],
		/** Права на чтение phpInfo */
		'phpInfo' => [
			'phpInfo'
		],
		/** Права на работу с настройками водяного знака */
		'watermark' => [
			'watermark'
		],
		/** Права на работу с настройками captcha */
		'captcha' => [
			'captcha'
		],
		/** Права на удаление доменов и зеркал, языков, модулей, решений и расширений */
		'delete' => [
			'deletesolution',
			'deleteextension',
			'del_module',
			'lang_del',
			'domain_mirrow_del',
			'domain_del'
		]
	];
