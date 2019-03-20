<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'dummy', // Имя модуля
		'config' => '1', // У модуля есть настройки
		'default_method' => 'page', // Метод по умолчанию в клиентской части
		'default_method_admin' => 'pages', // Метод по умолчанию в административной части
		'paging/' => 'Настройки постраничного вывода', // Группа настроек
		'paging/pages' => 25, // Настройка количества выводимых страниц
		'paging/objects' => 25, // Настройка количества выводимых объектов
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/dummy/admin.php',
		'./classes/components/dummy/class.php',
		'./classes/components/dummy/customAdmin.php',
		'./classes/components/dummy/customMacros.php',
		'./classes/components/dummy/i18n.php',
		'./classes/components/dummy/i18n.en.php',
		'./classes/components/dummy/install.php',
		'./classes/components/dummy/lang.php',
		'./classes/components/dummy/macros.php',
		'./classes/components/dummy/permissions.php',
	];

