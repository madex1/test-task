<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр фотогелерей */
		'albums' => [
			'album',
			'albums',
			'photo'
		],
		/** Права на администрирование модуля */
		'albums_list' => [
			'lists',
			'add',
			'edit',
			'activity',
			'uploadimages',
			'upload_arhive',
			'album.edit',
			'photo.edit',
			'publish'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление фотографий и альбомов */
		'delete' => [
			'del'
		]
	];
