<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование каталога */
		'tree' => [
			'tree',
			'filters',
			'indexposition',
			'setvalueforindexfield',
			'cleangroupallfields',
			'getindexgroup',
			'getsettings',
			'add',
			'edit',
			'activity',
			'category.edit',
			'object.edit',
			'publish',
			'getproductofferlist',
			'savetradeofferfield',
			'changetradeofferorder',
			'addtradeoffer',
			'copytradeoffer',
			'changetradeofferlistactivity',
			'flushtradeofferlistconfig'
		],
		/** Права на просмотр каталога */
		'view' => [
			'category',
			'object',
			'viewobject',
			'getcategorylist',
			'getsmartfilters',
			'makeemptyfilterresponse',
			'getsmartcatalog',
			'makeemptycatalogresponse',
		],
		/** Права на работу с настройками */
		'config' => [
			'config',
			'tradeofferpricetypes',
			'addtradeofferpricetype',
			'savetradeofferpricetypefield',
			'flushtradeofferpricetypelistconfig'
		],
		/** Права на удаление объектов и разделов, индексов фильтра, торговых предложений и их типов цен */
		'delete' => [
			'del',
			'deleteindex',
			'deletetradeofferlist',
			'deletetradeofferpricetypelist',
		]
	];
