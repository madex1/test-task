<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр новостей */
		'view' => [
			'lastlist',
			'listlents',
			'rubric',
			'related_links',
			'rss',
			'item',
			'lastlents'
		],
		/** Права на администрирование модуля */
		'lists' => [
			'lists',
			'subjects',
			'add',
			'edit',
			'activity',
			'rss_list',
			'item.edit',
			'rubric.edit',
			'publish'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление новостей, лент, сюжетов и RSS фидов */
		'delete' => [
			'del'
		]
	];
