<?php

	/**
	 * @var array $rules правила инициализации сервисов
	 */
	$rules = [
		'YandexMetricSerializer' => [
			'class' => 'UmiCms\Classes\Components\Stat\Yandex\Metric\Serializer',
			'arguments' => [
				new ServiceReference('DomainCollection')
			]
		],

		'YandexMetricClient' => [
			'class' => 'UmiCms\Classes\Components\Stat\Yandex\Metric\Client',
			'arguments' => [
				new ServiceReference('StatRegistry'),
				new ServiceReference('DateFactory'),
				new ServiceReference('CacheEngineFactory')
			]
		],

		'StatRegistry' => [
			'class' => 'UmiCms\Classes\Components\Stat\Registry',
			'arguments' => [
				new ServiceReference('Registry')
			]
		]
	];
