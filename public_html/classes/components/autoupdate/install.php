<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'autoupdate',
		'config' => '0',
		'default_method' => 'empty',
		'default_method_admin' => 'versions',
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/autoupdate/Classes/UpdateServer/Client.php',
		'./classes/components/autoupdate/Classes/UpdateServer/iClient.php',
		'./classes/components/autoupdate/Classes/Registry.php',
		'./classes/components/autoupdate/Classes/iRegistry.php',
		'./classes/components/autoupdate/admin.php',
		'./classes/components/autoupdate/autoload.php',
		'./classes/components/autoupdate/class.php',
		'./classes/components/autoupdate/customAdmin.php',
		'./classes/components/autoupdate/customMacros.php',
		'./classes/components/autoupdate/handlers.php',
		'./classes/components/autoupdate/i18n.en.php',
		'./classes/components/autoupdate/i18n.php',
		'./classes/components/autoupdate/includes.php',
		'./classes/components/autoupdate/install.php',
		'./classes/components/autoupdate/lang.en.php',
		'./classes/components/autoupdate/lang.php',
		'./classes/components/autoupdate/permissions.php',
		'./classes/components/autoupdate/service.php',
		'./classes/components/autoupdate/services.php'
	];
