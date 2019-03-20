<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр переходов на сайте */
		'json_get_referer_pages' => [
			'json_get_referer_pages'
		],
		/** Права на просмотр отчетов статистики */
		'total' => [
			'phrases',
			'phrase',
			'popular_pages',
			'engines',
			'engine',
			'sources',
			'sources_domain',
			'visitors',
			'visitors_by_date',
			'visitor',
			'sectionHits',
			'sectionHitsIncluded',
			'visits',
			'visits_sessions',
			'visits_visitors',
			'auditoryactivity',
			'auditoryloyality',
			'visitdeep',
			'visittime',
			'entrypoints',
			'paths',
			'exitpoints',
			'openstatcampaigns',
			'openstatservicesbycampaign',
			'openstatadsbyservice',
			'openstatservices',
			'openstatsources',
			'openstatservicesbysource',
			'openstatads',
			'visits_hits',
			'visits_visitors',
			'visiterscommonhours',
			'auditory',
			'sources_entry' .
			'yandex',
			'yandexmetric',
			'flushcounterlistconfig',
			'addcounter',
			'editName',
			'getcounterstat',
			'savecountercode',
			'downloadcountercode'
		],
		/** Права на просмотр облака тегов */
		'tagsCloud' => [
			'get_tags_cloud'
		],
		/** Права на работу с настройками */
		'config' => [
			'config',
			'yandex'
		],
		/** Права на удаление счетчика Яндекс.Метрики и внутренней статистики */
		'delete' => [
			'clear',
			'deletecounter'
		]
	];
