<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'seo',
		'config' => '1',
		'default_method' => 'show',
		'default_method_admin' => 'webmaster',
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/seo/classes/Yandex/WebMaster/iClient.php',
		'./classes/components/seo/classes/Yandex/WebMaster/Client.php',
		'./classes/components/seo/classes/Yandex/ModuleApi/Admin.php',
		'./classes/components/seo/classes/Registry.php',
		'./classes/components/seo/classes/iRegistry.php',
		'./classes/components/seo/classes/AdminSettingsManager.php',
		'./classes/components/seo/classes/iAdminSettingsManager.php',
		'./classes/components/seo/admin.php',
		'./classes/components/seo/autoload.php',
		'./classes/components/seo/class.php',
		'./classes/components/seo/customAdmin.php',
		'./classes/components/seo/customMacros.php',
		'./classes/components/seo/handlers.php',
		'./classes/components/seo/i18n.en.php',
		'./classes/components/seo/i18n.php',
		'./classes/components/seo/includes.php',
		'./classes/components/seo/install.php',
		'./classes/components/seo/lang.en.php',
		'./classes/components/seo/lang.php',
		'./classes/components/seo/macros.php',
		'./classes/components/seo/megaIndex.php',
		'./classes/components/seo/permissions.php',
		'./classes/components/seo/services.php',
		'./classes/components/seo/manifest/install.xml',
		'./classes/components/seo/manifest/update.xml',
		'./classes/components/seo/manifest/actions/DeleteIndex.php',
		'./classes/components/seo/manifest/actions/SiteMapIndexing.php'
	];
