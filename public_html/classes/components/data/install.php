<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'data';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'types';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/data/admin.php',
		'./classes/components/data/autoload.php',
		'./classes/components/data/class.php',
		'./classes/components/data/customAdmin.php',
		'./classes/components/data/customMacros.php',
		'./classes/components/data/feeds.php',
		'./classes/components/data/fileManager.php',
		'./classes/components/data/forms.php',
		'./classes/components/data/handlers.php',
		'./classes/components/data/i18n.en.php',
		'./classes/components/data/i18n.php',
		'./classes/components/data/includes.php',
		'./classes/components/data/install.php',
		'./classes/components/data/lang.en.php',
		'./classes/components/data/lang.php',
		'./classes/components/data/macros.php',
		'./classes/components/data/permissions.php',
		'./classes/components/data/Classes/iFormSaver.php',
		'./classes/components/data/Classes/FormSaver.php',
	];



