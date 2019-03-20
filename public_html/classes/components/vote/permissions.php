<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на добавление опросов */
		'add_poll' => [
			'lists',
			'add'
		],
		/** Права на редактирование опросов */
		'edit_poll' => [
			'polls',
			'lists',
			'edit',
			'answers_list',
			'activity',
			'poll.edit',
			'publish'
		],
		/** Права на удаление опросов */
		'del_poll' => [
			'del'
		],
		/** Права на просмотр опросов и их результатов */
		'poll' => [
			'insertvote',
			'results',
			'insertlast',
			'getelementrating'
		],
		/** Права на на участии в опросах */
		'post' => [
			'post',
			'json_rate',
			'setelementrating'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		]
	];
