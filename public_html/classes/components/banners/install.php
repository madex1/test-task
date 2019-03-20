<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'banners';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'insert';
	$INFO['default_method_admin'] = 'lists';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/banners/admin.php';
	$COMPONENTS[] = './classes/components/banners/class.php';
	$COMPONENTS[] = './classes/components/banners/customAdmin.php';
	$COMPONENTS[] = './classes/components/banners/customMacros.php';
	$COMPONENTS[] = './classes/components/banners/handlers.php';
	$COMPONENTS[] = './classes/components/banners/i18n.en.php';
	$COMPONENTS[] = './classes/components/banners/i18n.php';
	$COMPONENTS[] = './classes/components/banners/includes.php';
	$COMPONENTS[] = './classes/components/banners/install.php';
	$COMPONENTS[] = './classes/components/banners/lang.en.php';
	$COMPONENTS[] = './classes/components/banners/lang.php';
	$COMPONENTS[] = './classes/components/banners/macros.php';
	$COMPONENTS[] = './classes/components/banners/permissions.php';

