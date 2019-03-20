<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'search',
		'config' => '1',
		'default_method' => 'search_do',
		'default_method_admin' => 'index_control',
		'per_page' => '10',
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/search/admin.php',
		'./classes/components/search/class.php',
		'./classes/components/search/customAdmin.php',
		'./classes/components/search/customMacros.php',
		'./classes/components/search/handlers.php',
		'./classes/components/search/i18n.en.php',
		'./classes/components/search/i18n.php',
		'./classes/components/search/includes.php',
		'./classes/components/search/install.php',
		'./classes/components/search/lang.en.php',
		'./classes/components/search/lang.php',
		'./classes/components/search/macros.php',
		'./classes/components/search/permissions.php',
		'./classes/components/search/sphinx.php',
		'./classes/components/search/manifest/install.xml',
		'./classes/components/search/manifest/update.xml',
		'./classes/components/search/manifest/actions/DeleteIndex.php',
		'./classes/components/search/manifest/actions/SearchIndexing.php',
	];
