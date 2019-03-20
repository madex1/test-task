<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'news',
		'config' => '1',
		'default_method' => 'rubric',
		'default_method_admin' => 'lists',
		'per_page' => '10',
		'rss_per_page' => '10',
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/news/admin.php',
		'./classes/components/news/calendar.php',
		'./classes/components/news/class.php',
		'./classes/components/news/customAdmin.php',
		'./classes/components/news/customMacros.php',
		'./classes/components/news/events.php',
		'./classes/components/news/handlers.php',
		'./classes/components/news/i18n.en.php',
		'./classes/components/news/i18n.php',
		'./classes/components/news/includes.php',
		'./classes/components/news/install.php',
		'./classes/components/news/lang.en.php',
		'./classes/components/news/lang.php',
		'./classes/components/news/macros.php',
		'./classes/components/news/permissions.php',
		'./classes/components/news/feeds.php',
	];
