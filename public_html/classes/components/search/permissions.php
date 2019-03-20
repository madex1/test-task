<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование модуля */
		'index' => [
			'index_control',
			'sphinx_control',
			'reindex',
			'partialreindex',
			'search_replace'
		],
		/** Права на поиск по сайту */
		'search' => [
			'search_do',
			'insert_form',
			'suggestions',
			'sphinxSearch'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление поискового индекса */
		'delete' => [
			'truncate'
		]
	];
