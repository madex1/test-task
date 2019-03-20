<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'social_networks',
		'config' => '0',
		'default_method' => 'vkontakte',
		'default_method_admin' => 'vkontakte',
		'per_page' => '10',
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/social_networks/admin.php',
		'./classes/components/social_networks/autoload.php',
		'./classes/components/social_networks/class.php',
		'./classes/components/social_networks/customAdmin.php',
		'./classes/components/social_networks/customMacros.php',
		'./classes/components/social_networks/handlers.php',
		'./classes/components/social_networks/i18n.en.php',
		'./classes/components/social_networks/i18n.php',
		'./classes/components/social_networks/includes.php',
		'./classes/components/social_networks/install.php',
		'./classes/components/social_networks/lang.en.php',
		'./classes/components/social_networks/lang.php',
		'./classes/components/social_networks/permissions.php',
		'./classes/components/social_networks/classes/network.php',
		'./classes/components/social_networks/classes/networks/vkontakte.php',
	];
