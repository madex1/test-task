<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр форума и добавление топиков и сообщений */
		'view' => [
			'conf',
			'confs_list',
			'conf_last_message',
			'getmessagelink',
			'message',
			'message_post',
			'message_post_do',
			'topic',
			'topic_last_message',
			'topic_post',
			'topic_post_do',
		],
		/** Права на администрирование форума */
		'last_messages' => [
			'lists',
			'last_messages',
			'edit',
			'add',
			'activity',
			'getedilLink',
			'confs_list',
			'conf.edit',
			'message.edit',
			'topic.edit',
			'publish'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление конференций, топиков и сообщений */
		'delete' => [
			'del'
		]
	];
