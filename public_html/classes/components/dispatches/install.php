<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'dispatches';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'subscribe';
	$INFO['default_method_admin'] = 'lists';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/dispatches/admin.php';
	$COMPONENTS[] = './classes/components/dispatches/class.php';
	$COMPONENTS[] = './classes/components/dispatches/customAdmin.php';
	$COMPONENTS[] = './classes/components/dispatches/customMacros.php';
	$COMPONENTS[] = './classes/components/dispatches/events.php';
	$COMPONENTS[] = './classes/components/dispatches/handlers.php';
	$COMPONENTS[] = './classes/components/dispatches/i18n.en.php';
	$COMPONENTS[] = './classes/components/dispatches/i18n.php';
	$COMPONENTS[] = './classes/components/dispatches/includes.php';
	$COMPONENTS[] = './classes/components/dispatches/install.php';
	$COMPONENTS[] = './classes/components/dispatches/lang.en.php';
	$COMPONENTS[] = './classes/components/dispatches/lang.php';
	$COMPONENTS[] = './classes/components/dispatches/macros.php';
	$COMPONENTS[] = './classes/components/dispatches/permissions.php';

