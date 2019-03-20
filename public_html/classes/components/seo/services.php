<?php

	$parameters = [
		'linksGrabber' => 'linksGrabber',
		'linksCollection' => 'linksCollection',
		'linksChecker' => 'linksChecker',
		'linksSourcesCollection' => 'linksSourcesCollection'
	];

	$rules = [
		'linksCollection' => [
			'class' => 'UmiCms\Classes\System\Utils\Links\Collection',
			'arguments' => [
				new ParameterReference('linksCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('UmiCms\Classes\System\Utils\Links\ConstantMap')
					]
				]
			]
		],

		'linksSourcesCollection' => [
			'class' => 'UmiCms\Classes\System\Utils\Links\SourcesCollection',
			'arguments' => [
				new ParameterReference('linksSourcesCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('UmiCms\Classes\System\Utils\Links\SourceConstantMap')
					]
				]
			]
		],

		'linksGrabber' => [
			'class' => 'UmiCms\Classes\System\Utils\Links\Grabber\Grabber',
			'arguments' => [
				new ParameterReference('linksGrabber'),
			],
			'calls' => [
				[
					'method' => 'setConfiguration',
					'arguments' => [
						new ServiceReference('Configuration')
					]
				],
				[
					'method' => 'setLinksCollection',
					'arguments' => [
						new ServiceReference('linksCollection')
					]
				],
				[
					'method' => 'setLinksSourcesCollection',
					'arguments' => [
						new ServiceReference('linksSourcesCollection')
					]
				],
				[
					'method' => 'setTemplatesCollection',
					'arguments' => [
						new ServiceReference('templates')
					]
				],
				[
					'method' => 'setDirectoriesHandler',
					'arguments' => [
						new ParameterReference('directoriesHandler')
					]
				],
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setPagesCollection',
					'arguments' => [
						new ServiceReference('pages')
					]
				]
			]
		],

		'linksChecker' => [
			'class' => 'UmiCms\Classes\System\Utils\Links\Checker\Checker',
			'arguments' => [
				new ParameterReference('linksChecker'),
			],
			'calls' => [
				[
					'method' => 'setRegistry',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],
				[
					'method' => 'setLinksCollection',
					'arguments' => [
						new ServiceReference('linksCollection')
					]
				],
			]
		],

		'SeoRegistry' => [
			'class' => 'UmiCms\Classes\Components\Seo\Registry',
			'arguments' => [
				new ServiceReference('Registry'),
			]
		],

		'YandexWebmasterClient' => [
			'class' => 'UmiCms\Classes\Components\Seo\Yandex\WebMaster\Client',
			'arguments' => [
				new ServiceReference('SeoRegistry'),
			]
		],

		'SeoAdminSettingsManager' => [
			'class' => 'UmiCms\Classes\Components\Seo\AdminSettingsManager',
		],
	];
