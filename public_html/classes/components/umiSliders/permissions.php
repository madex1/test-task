<?php

	/** @var array $permissions группы прав на функционал модуля */
	$permissions = [
		/** Права на управление слайдерами */
		'manage' => [
			'getsliders',
			'getdatasetconfiguration',
			'flushDatasetConfiguration',
			'geteditformofentitywithdefinedtype',
			'saveformdatatoentitywithdefinedtype',
			'getcreateformofentitywithdefinedtype',
			'createentitywithdefinedtypefromformdata',
			'moveentitieswithdefinedtypes',
			'getslideslist',
			'saveslideslist'
		],
		/** Права на просмотр слайдеров */
		'view' => [
			'getslidesbyslidername',
			'getslidelistbyslidercustomid',
			'getslidelistbyslidername',
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление слайдеров и слайдов */
		'delete' => [
			'deleteentitieswithdefinedtypes',
			'deleteslide'
		]
	];
