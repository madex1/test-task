<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'backup';
	$INFO['config'] = '0';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'config';
	$INFO['max_timelimit'] = '30';
	$INFO['max_save_actions'] = '10';
	$INFO['enabled'] = '1';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/backup/admin.php';
	$COMPONENTS[] = './classes/components/backup/class.php';
	$COMPONENTS[] = './classes/components/backup/customAdmin.php';
	$COMPONENTS[] = './classes/components/backup/customMacros.php';
	$COMPONENTS[] = './classes/components/backup/events.php';
	$COMPONENTS[] = './classes/components/backup/handlers.php';
	$COMPONENTS[] = './classes/components/backup/i18n.en.php';
	$COMPONENTS[] = './classes/components/backup/i18n.php';
	$COMPONENTS[] = './classes/components/backup/includes.php';
	$COMPONENTS[] = './classes/components/backup/install.php';
	$COMPONENTS[] = './classes/components/backup/lang.en.php';
	$COMPONENTS[] = './classes/components/backup/lang.php';
	$COMPONENTS[] = './classes/components/backup/permissions.php';

