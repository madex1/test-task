<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'exchange';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'import';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/exchange/1CExchange.php';
	$COMPONENTS[] = './classes/components/exchange/admin.php';
	$COMPONENTS[] = './classes/components/exchange/class.php';
	$COMPONENTS[] = './classes/components/exchange/customAdmin.php';
	$COMPONENTS[] = './classes/components/exchange/customMacros.php';
	$COMPONENTS[] = './classes/components/exchange/handlers.php';
	$COMPONENTS[] = './classes/components/exchange/i18n.en.php';
	$COMPONENTS[] = './classes/components/exchange/i18n.php';
	$COMPONENTS[] = './classes/components/exchange/includes.php';
	$COMPONENTS[] = './classes/components/exchange/install.php';
	$COMPONENTS[] = './classes/components/exchange/permissions.php';
