<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр файлов для скачивания */
		'list_files' => [
			'shared_file',
			'list_files'
		],
		/** Право на скачивание файлов */
		'download' => [
			'download'
		],
		/** Права на администрирование модуля */
		'directory_list' => [
			'shared_files',
			'shared_file_activity',
			'add_shared_file',
			'edit_shared_file',
			'shared_file.edit',
			'publish'
		],
		/** Права на удаление страниц со скачиваемым файлом */
		'delete' => [
			'del_shared_file',
		]
	];
