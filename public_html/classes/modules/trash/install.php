<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'trash', // Имя модуля
		'config' => '0', // У модуля есть настройки
		'default_method' => '', // Метод по умолчанию в клиентской части
		'default_method_admin' => 'trash', // Метод по умолчанию в административной части
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/trash/__admin.php',
		'./classes/components/trash/__custom_adm.php',
		'./classes/components/trash/class.php',
		'./classes/components/trash/i18n.en.php',
		'./classes/components/trash/i18n.php',
		'./classes/components/trash/install.php',
		'./classes/components/trash/lang.en.php',
		'./classes/components/trash/lang.php',
		'./classes/components/trash/permissions.php',
	];

