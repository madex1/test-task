<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'photoalbum';
	$INFO['config'] = '1';
	$INFO['ico'] = 'ico_photoalbum';
	$INFO['default_method'] = 'albums';
	$INFO['default_method_admin'] = 'lists';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/photoalbum/admin.php';
	$COMPONENTS[] = './classes/components/photoalbum/class.php';
	$COMPONENTS[] = './classes/components/photoalbum/customAdmin.php';
	$COMPONENTS[] = './classes/components/photoalbum/customMacros.php';
	$COMPONENTS[] = './classes/components/photoalbum/handlers.php';
	$COMPONENTS[] = './classes/components/photoalbum/i18n.en.php';
	$COMPONENTS[] = './classes/components/photoalbum/i18n.php';
	$COMPONENTS[] = './classes/components/photoalbum/import.php';
	$COMPONENTS[] = './classes/components/photoalbum/lang.en.php';
	$COMPONENTS[] = './classes/components/photoalbum/lang.php';
	$COMPONENTS[] = './classes/components/photoalbum/macros.php';
	$COMPONENTS[] = './classes/components/photoalbum/permissions.php';
