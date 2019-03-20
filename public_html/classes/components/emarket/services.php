<?php

	/** @var array $parameters параметры инициализации сервисов */
	$parameters = [
		'ApiShipOrders' => 'ApiShipOrders'
	];

	/** @var array $rules правила инициализации сервисов */
	$rules = [
		'ApiShipOrders' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\Collection',
			'arguments' => [
				new ParameterReference('ApiShipOrders'),
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
						new InstantiableReference('UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\ConstantMap')
					]
				]
			]
		],

		'YandexKassaClient' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Kassa',
			'arguments' => [
				new ServiceReference('LoggerFactory'),
				new ServiceReference('Configuration'),
			]
		],

		'CurrencyCollection' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Collection',
		],

		'CurrencyFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Factory',
		],

		'CurrencyRepository' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Repository',
			'arguments' => [
				new ServiceReference('CurrencyFactory'),
				new ServiceReference('SelectorFactory'),
			]
		],

		'Currencies' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Facade',
			'arguments' => [
				new ServiceReference('CurrencyRepository'),
				new ServiceReference('CurrencyCollection'),
				new ServiceReference('Configuration'),
				new ServiceReference('CurrencyCalculator'),
				new ServiceReference('objects'),
				new ServiceReference('CookieJar'),
				new ServiceReference('Auth')
			]
		],

		'CurrencyCalculator' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Calculator',
		],

		'TaxRateVatFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Factory',
		],

		'TaxRateVatRepository' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Repository',
			'arguments' => [
				new ServiceReference('TaxRateVatFactory'),
				new ServiceReference('SelectorFactory'),
			]
		],

		'TaxRateVat' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Facade',
			'arguments' => [
				new ServiceReference('TaxRateVatRepository')
			],
			'calls' => [
				[
					'method' => 'setCalculator',
					'arguments' => [
						new ServiceReference('TaxRateCalculator')
					]
				],
				[
					'method' => 'setParser',
					'arguments' => [
						new ServiceReference('TaxRateParser')
					]
				]
			]
		],

		'TaxRateCalculator' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Calculator',
		],

		'TaxRateParser' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Parser\Factory',
		],

		'PaymentSubjectFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Subject\Factory',
		],

		'PaymentSubjectRepository' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Subject\Repository',
			'arguments' => [
				new ServiceReference('PaymentSubjectFactory'),
				new ServiceReference('SelectorFactory'),
			]
		],

		'PaymentSubject' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Subject\Facade',
			'arguments' => [
				new ServiceReference('PaymentSubjectRepository')
			]
		],

		'PaymentModeFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Mode\Factory',
		],

		'PaymentModeRepository' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Mode\Repository',
			'arguments' => [
				new ServiceReference('PaymentModeFactory'),
				new ServiceReference('SelectorFactory'),
			]
		],

		'PaymentMode' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Mode\Facade',
			'arguments' => [
				new ServiceReference('PaymentModeRepository')
			]
		],

		'ReceiptSerializerFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Factory',
			'arguments' => [
				new ServiceContainerReference()
			]
		],

		'ReceiptSerializerRoboKassa' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\RoboKassa',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
				new ServiceReference('PaymentSubject'),
				new ServiceReference('PaymentMode')
			]
		],

		'ReceiptSerializerPayAnyWay' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayAnyWay',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
				new ServiceReference('PaymentSubject'),
				new ServiceReference('PaymentMode')
			]
		],

		'ReceiptSerializerYandexKassa3' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\YandexKassa3',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
				new ServiceReference('PaymentSubject'),
				new ServiceReference('PaymentMode')
			]
		],

		'ReceiptSerializerYandexKassa4' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\YandexKassa4',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
				new ServiceReference('PaymentSubject'),
				new ServiceReference('PaymentMode')
			]
		],

		'ReceiptSerializerPayOnline' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayOnline',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
				new ServiceReference('PaymentSubject'),
				new ServiceReference('PaymentMode')
			]
		],

		'ReceiptSerializerSberbank' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Sberbank',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
				new ServiceReference('PaymentSubject'),
				new ServiceReference('PaymentMode')
			]
		],

		'PayOnlineFiscalClient' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client\Fiscal',
			'arguments' => [
				new ServiceReference('LoggerFactory'),
				new ServiceReference('Configuration'),
			]
		],

		'PackIdProvider' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Delivery\Russianpost\PackIdProvider',
		],

		'OrderItemListFilter' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Orders\Items\Filter'
		]
	];
