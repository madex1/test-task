<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'tickets';
	$INFO['config'] = '0';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'tickets';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/tickets/admin.php';
	$COMPONENTS[] = './classes/components/tickets/class.php';
	$COMPONENTS[] = './classes/components/tickets/customAdmin.php';
	$COMPONENTS[] = './classes/components/tickets/customMacros.php';
	$COMPONENTS[] = './classes/components/tickets/events.php';
	$COMPONENTS[] = './classes/components/tickets/handlers.php';
	$COMPONENTS[] = './classes/components/tickets/i18n.en.php';
	$COMPONENTS[] = './classes/components/tickets/i18n.php';
	$COMPONENTS[] = './classes/components/tickets/includes.php';
	$COMPONENTS[] = './classes/components/tickets/install.php';
	$COMPONENTS[] = './classes/components/tickets/lang.en.php';
	$COMPONENTS[] = './classes/components/tickets/lang.php';
	$COMPONENTS[] = './classes/components/tickets/permissions.php';
