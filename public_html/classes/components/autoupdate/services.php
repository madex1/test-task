<?php

	/** @var array $rules правила инициализации сервисов */
	$rules = [
		'AutoUpdateRegistry' => [
			'class' => 'UmiCms\Classes\Components\AutoUpdate\Registry',
			'arguments' => [
				new ServiceReference('Registry'),
			],
			'calls' => [
				[
					'method' => 'setRegistrySettings',
					'arguments' => [
						new ServiceReference('RegistrySettings')
					]
				]
			]
		],
		'UpdateServerClient' => [
			'class' => 'UmiCms\Classes\Components\AutoUpdate\UpdateServer\Client',
			'arguments' => [
				new ServiceReference('RequestHttp'),
				new ServiceReference('AutoUpdateRegistry'),
				new ServiceReference('RegistrySettings'),
				new ServiceReference('DateFactory'),
				new ServiceReference('CacheEngineFactory'),
				new ServiceReference('DomainCollection')
			]
		]
	];
