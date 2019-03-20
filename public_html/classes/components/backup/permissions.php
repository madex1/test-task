<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на работу с резервными копиями страниц */
		'pages-backup' => [
			'snapshots',
			'backup_panel',
			'backup_panel_all',
			'rollback',
			'backup_save'
		],
		/** Права на работу с резервными копиями файлов */
		'files-backup' => [
			'backup_copies',
			'createsnapshot',
			'restoresnapshot',
			'downloadsnapshot'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление резервных копий файлов */
		'delete' => [
			'deletesnapshot'
		]
	];
