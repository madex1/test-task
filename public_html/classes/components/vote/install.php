<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'vote';
	$INFO['config'] = '0';
	$INFO['default_method'] = 'poll';
	$INFO['default_method_admin'] = 'lists';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/vote/admin.php';
	$COMPONENTS[] = './classes/components/vote/class.php';
	$COMPONENTS[] = './classes/components/vote/customAdmin.php';
	$COMPONENTS[] = './classes/components/vote/customMacros.php';
	$COMPONENTS[] = './classes/components/vote/events.php';
	$COMPONENTS[] = './classes/components/vote/handlers.php';
	$COMPONENTS[] = './classes/components/vote/i18n.en.php';
	$COMPONENTS[] = './classes/components/vote/i18n.php';
	$COMPONENTS[] = './classes/components/vote/install.php';
	$COMPONENTS[] = './classes/components/vote/includes.php';
	$COMPONENTS[] = './classes/components/vote/lang.en.php';
	$COMPONENTS[] = './classes/components/vote/lang.php';
	$COMPONENTS[] = './classes/components/vote/macros.php';
	$COMPONENTS[] = './classes/components/vote/permissions.php';
