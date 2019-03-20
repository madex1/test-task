<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'forum';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'show';
	$INFO['default_method_admin'] = 'lists';
	$INFO['per_page'] = '20';
	$INFO['need_moder'] = '0';
	$INFO['allow_guest'] = '0';
	$INFO['sort_by_last_message'] = '1';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/forum/admin.php';
	$COMPONENTS[] = './classes/components/forum/class.php';
	$COMPONENTS[] = './classes/components/forum/customAdmin.php';
	$COMPONENTS[] = './classes/components/forum/customMacros.php';
	$COMPONENTS[] = './classes/components/forum/events.php';
	$COMPONENTS[] = './classes/components/forum/handlers.php';
	$COMPONENTS[] = './classes/components/forum/i18n.en.php';
	$COMPONENTS[] = './classes/components/forum/i18n.php';
	$COMPONENTS[] = './classes/components/forum/includes.php';
	$COMPONENTS[] = './classes/components/forum/install.php';
	$COMPONENTS[] = './classes/components/forum/lang.en.php';
	$COMPONENTS[] = './classes/components/forum/lang.php';
	$COMPONENTS[] = './classes/components/forum/macros.php';
	$COMPONENTS[] = './classes/components/forum/permissions.php';
