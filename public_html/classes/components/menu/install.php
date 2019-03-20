<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'menu',
		'config' => '0',
		'default_method' => 'empty',
		'default_method_admin' => 'lists',
		'per_page' => '10'
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/menu/manifest/install.xml',
		'./classes/components/menu/manifest/actions/UpdateLinks.php',
		'./classes/components/menu/admin.php',
		'./classes/components/menu/class.php',
		'./classes/components/menu/customAdmin.php',
		'./classes/components/menu/customMacros.php',
		'./classes/components/menu/events.php',
		'./classes/components/menu/handlers.php',
		'./classes/components/menu/i18n.en.php',
		'./classes/components/menu/i18n.php',
		'./classes/components/menu/includes.php',
		'./classes/components/menu/install.php',
		'./classes/components/menu/lang.en.php',
		'./classes/components/menu/lang.php',
		'./classes/components/menu/macros.php',
		'./classes/components/menu/permissions.php'
	];
