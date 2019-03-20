<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'webforms',
		'config' => '0',
		'ico' => 'ico_webforms',
		'default_method' => 'insert',
		'default_method_admin' => 'addresses'
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/webforms/manifest/actions/UpdateRelatedId.php',
		'./classes/components/webforms/manifest/install.xml',
		'./classes/components/webforms/admin.php',
		'./classes/components/webforms/class.php',
		'./classes/components/webforms/customAdmin.php',
		'./classes/components/webforms/customMacros.php',
		'./classes/components/webforms/handlers.php',
		'./classes/components/webforms/i18n.en.php',
		'./classes/components/webforms/i18n.php',
		'./classes/components/webforms/includes.php',
		'./classes/components/webforms/install.php',
		'./classes/components/webforms/lang.en.php',
		'./classes/components/webforms/lang.php',
		'./classes/components/webforms/macros.php',
		'./classes/components/webforms/permissions.php',
	];
