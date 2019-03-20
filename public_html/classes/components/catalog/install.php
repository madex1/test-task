<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'catalog',
		'config' => '1',
		'default_method' => 'category',
		'default_method_admin' => 'tree',
		'per_page' => 10,
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/catalog/Classes/Trade/Offer/Price/Type/Admin.php',
		'./classes/components/catalog/manifest/actions/FilterIndexing.php',
		'./classes/components/catalog/Classes/Trade/Offer/Admin.php',
		'./classes/components/catalog/manifest/install.xml',
		'./classes/components/catalog/manifest/update.xml',
		'./classes/components/catalog/admin.php',
		'./classes/components/catalog/class.php',
		'./classes/components/catalog/customAdmin.php',
		'./classes/components/catalog/customMacros.php',
		'./classes/components/catalog/events.php',
		'./classes/components/catalog/handlers.php',
		'./classes/components/catalog/i18n.en.php',
		'./classes/components/catalog/i18n.php',
		'./classes/components/catalog/includes.php',
		'./classes/components/catalog/install.php',
		'./classes/components/catalog/lang.en.php',
		'./classes/components/catalog/lang.php',
		'./classes/components/catalog/macros.php',
		'./classes/components/catalog/permissions.php'
	];
