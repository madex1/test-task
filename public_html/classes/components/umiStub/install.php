<?php

	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'umiStub', // Имя модуля
		'config' => '0', // У модуля есть настройки
		'default_method' => '', // Метод по умолчанию в клиентской части
		'default_method_admin' => 'stub', // Метод по умолчанию в административной части
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/umiStub/admin.php',
		'./classes/components/umiStub/autoload.php',
		'./classes/components/umiStub/class.php',
		'./classes/components/umiStub/customAdmin.php',
		'./classes/components/umiStub/customMacros.php',
		'./classes/components/umiStub/i18n.en.php',
		'./classes/components/umiStub/i18n.php',
		'./classes/components/umiStub/install.php',
		'./classes/components/umiStub/lang.en.php',
		'./classes/components/umiStub/lang.php',
		'./classes/components/umiStub/permissions.php',
		'./classes/components/umiStub/services.php',
		'./classes/components/umiStub/classes/Stub/AdminSettingsManager.php',
		'./classes/components/umiStub/classes/Stub/iAdminSettingsManager.php'
	];