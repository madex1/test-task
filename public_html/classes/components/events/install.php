<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'events';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'last';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/events/admin.php';
	$COMPONENTS[] = './classes/components/events/class.php';
	$COMPONENTS[] = './classes/components/events/customAdmin.php';
	$COMPONENTS[] = './classes/components/events/customMacros.php';
	$COMPONENTS[] = './classes/components/events/events.php';
	$COMPONENTS[] = './classes/components/events/handlers.php';
	$COMPONENTS[] = './classes/components/events/i18n.en.php';
	$COMPONENTS[] = './classes/components/events/i18n.php';
	$COMPONENTS[] = './classes/components/events/includes.php';
	$COMPONENTS[] = './classes/components/events/install.php';
	$COMPONENTS[] = './classes/components/events/lang.en.php';
	$COMPONENTS[] = './classes/components/events/lang.php';
	$COMPONENTS[] = './classes/components/events/permissions.php';

