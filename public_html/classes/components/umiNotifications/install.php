<?php

	/** Установщик модуля */

	/** @var array реестр модуля */
	$INFO = [
		'name' => 'umiNotifications', // Имя модуля
		'config' => '0', // У модуля есть настройки
		'default_method' => '', // Метод по умолчанию в клиентской части
		'default_method_admin' => 'notifications', // Метод по умолчанию в административной части
		'func_perms' => 'Группы прав на функционал модуля', // Группы прав
		'func_perms/admin' => 'Административные права', // Административная группа прав
	];

	/** @var array файлы модуля */
	$COMPONENTS = [
		'./classes/components/umiNotifications/admin.php',
		'./classes/components/umiNotifications/class.php',
		'./classes/components/umiNotifications/customAdmin.php',
		'./classes/components/umiNotifications/customMacros.php',
		'./classes/components/umiNotifications/handlers.php',
		'./classes/components/umiNotifications/i18n.php',
		'./classes/components/umiNotifications/i18n.en.php',
		'./classes/components/umiNotifications/includes.php',
		'./classes/components/umiNotifications/install.php',
		'./classes/components/umiNotifications/lang.php',
		'./classes/components/umiNotifications/lang.en.php',
		'./classes/components/umiNotifications/macros.php',
		'./classes/components/umiNotifications/permissions.php',
	];
