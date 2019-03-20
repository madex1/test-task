<?php

	/** @var array $parameters параметры инициализации сервисов */
	$parameters = [
		'SlidesCollection' => 'SlidesCollection',
		'SlidersCollection' => 'SlidersCollection'
	];
	/** @var array $rules правила инициализации сервисов */
	$rules = [
		'SlidesCollection' => [
			'class' => 'UmiCms\Classes\Components\UmiSliders\SlidesCollection',
			'arguments' => [
				new \ParameterReference('SlidesCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new \ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new \InstantiableReference('UmiCms\Classes\Components\UmiSliders\SlidesConstantMap')
					]
				],
				[
					'method' => 'setImageFileHandler',
					'arguments' => [
						new \ParameterReference('imageFileHandler')
					]
				],
				[
					'method' => 'setSourceIdBinderFactory',
					'arguments' => [
						new ServiceReference('ImportEntitySourceIdBinderFactory')
					]
				]
			]
		],
		'SlidersCollection' => [
			'class' => 'UmiCms\Classes\Components\UmiSliders\SlidersCollection',
			'arguments' => [
				new \ParameterReference('SlidersCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new \ParameterReference('connection')
					]
				],
				[
					'method' => 'setDomainCollection',
					'arguments' => [
						new \ServiceReference('DomainCollection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new \InstantiableReference('UmiCms\Classes\Components\UmiSliders\SlidersConstantMap')
					]
				],
				[
					'method' => 'setSlidesCollection',
					'arguments' => [
						new \ServiceReference('SlidesCollection')
					]
				]
			]
		],
	];
