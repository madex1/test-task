<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Просмотр о создание комментариев */
		'insert' => [
			'insert',
			'post',
			'comment',
			'countcomments',
			'smilepanel',
			'insertvkontakte',
			'insertfacebook',
		],
		/** Администрирование модуля */
		'view_comments' => [
			'view_comments',
			'edit',
			'activity',
			'view_noactive_comments',
			'comment.edit',
			'publish'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление комментариев */
		'delete' => [
			'del'
		]
	];
