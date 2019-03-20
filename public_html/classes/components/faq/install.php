<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'faq';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'project';
	$INFO['default_method_admin'] = 'projects_list';
	$INFO['per_page'] = '10';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/faq/admin.php';
	$COMPONENTS[] = './classes/components/faq/class.php';
	$COMPONENTS[] = './classes/components/faq/customAdmin.php';
	$COMPONENTS[] = './classes/components/faq/customMacros.php';
	$COMPONENTS[] = './classes/components/faq/events.php';
	$COMPONENTS[] = './classes/components/faq/handlers.php';
	$COMPONENTS[] = './classes/components/faq/i18n.en.php';
	$COMPONENTS[] = './classes/components/faq/i18n.php';
	$COMPONENTS[] = './classes/components/faq/includes.php';
	$COMPONENTS[] = './classes/components/faq/install.php';
	$COMPONENTS[] = './classes/components/faq/lang.en.php';
	$COMPONENTS[] = './classes/components/faq/lang.php';
	$COMPONENTS[] = './classes/components/faq/macros.php';
	$COMPONENTS[] = './classes/components/faq/permissions.php';

