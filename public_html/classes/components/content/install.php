<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'content';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'content';
	$INFO['default_method_admin'] = 'sitetree';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/content/admin.php';
	$COMPONENTS[] = './classes/components/content/class.php';
	$COMPONENTS[] = './classes/components/content/customAdmin.php';
	$COMPONENTS[] = './classes/components/content/customMacros.php';
	$COMPONENTS[] = './classes/components/content/eip.php';
	$COMPONENTS[] = './classes/components/content/events.php';
	$COMPONENTS[] = './classes/components/content/handlers.php';
	$COMPONENTS[] = './classes/components/content/i18n.en.php';
	$COMPONENTS[] = './classes/components/content/i18n.php';
	$COMPONENTS[] = './classes/components/content/includes.php';
	$COMPONENTS[] = './classes/components/content/install.php';
	$COMPONENTS[] = './classes/components/content/lang.en.php';
	$COMPONENTS[] = './classes/components/content/lang.php';
	$COMPONENTS[] = './classes/components/content/macros.php';
	$COMPONENTS[] = './classes/components/content/menu.php';
	$COMPONENTS[] = './classes/components/content/permissions.php';
	$COMPONENTS[] = './classes/components/content/tags.php';

