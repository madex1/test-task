<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'umiSettings', // Имя модуля
		'default_method' => 'empty', // Метод по умолчанию в клиентской части
		'default_method_admin' => 'read', // Метод по умолчанию в административной части
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/umiSettings/manifest/actions/UpdateRelatedId.php',
		'./classes/components/umiSettings/manifest/install.xml',
		'./classes/components/umiSettings/admin.php',
		'./classes/components/umiSettings/class.php',
		'./classes/components/umiSettings/customAdmin.php',
		'./classes/components/umiSettings/customMacros.php',
		'./classes/components/umiSettings/i18n.en.php',
		'./classes/components/umiSettings/i18n.php',
		'./classes/components/umiSettings/install.php',
		'./classes/components/umiSettings/lang.en.php',
		'./classes/components/umiSettings/lang.php',
		'./classes/components/umiSettings/macros.php',
		'./classes/components/umiSettings/permissions.php',
	];
