<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'users';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'auth';
	$INFO['default_method_admin'] = 'users_list';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/users/admin.php';
	$COMPONENTS[] = './classes/components/users/class.php';
	$COMPONENTS[] = './classes/components/users/customAdmin.php';
	$COMPONENTS[] = './classes/components/users/customMacros.php';
	$COMPONENTS[] = './classes/components/users/events.php';
	$COMPONENTS[] = './classes/components/users/handlers.php';
	$COMPONENTS[] = './classes/components/users/i18n.en.php';
	$COMPONENTS[] = './classes/components/users/i18n.php';
	$COMPONENTS[] = './classes/components/users/includes.php';
	$COMPONENTS[] = './classes/components/users/lang.en.php';
	$COMPONENTS[] = './classes/components/users/lang.php';
	$COMPONENTS[] = './classes/components/users/macros.php';
	$COMPONENTS[] = './classes/components/users/permissions.php';
