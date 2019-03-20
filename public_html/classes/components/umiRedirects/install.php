<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'umiRedirects',
		'config' => '0',
		'default_method' => 'empty',
		'default_method_admin' => 'lists'
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/umiRedirects/admin.php',
		'./classes/components/umiRedirects/class.php',
		'./classes/components/umiRedirects/customAdmin.php',
		'./classes/components/umiRedirects/customMacros.php',
		'./classes/components/umiRedirects/handlers.php',
		'./classes/components/umiRedirects/i18n.en.php',
		'./classes/components/umiRedirects/i18n.php',
		'./classes/components/umiRedirects/includes.php',
		'./classes/components/umiRedirects/install.php',
		'./classes/components/umiRedirects/lang.en.php',
		'./classes/components/umiRedirects/lang.php',
		'./classes/components/umiRedirects/permissions.php'
	];
