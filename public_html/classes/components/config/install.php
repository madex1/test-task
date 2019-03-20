<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'config';
	$INFO['config'] = '0';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'main';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/config/admin.php';
	$COMPONENTS[] = './classes/components/config/class.php';
	$COMPONENTS[] = './classes/components/config/customAdmin.php';
	$COMPONENTS[] = './classes/components/config/customMacros.php';
	$COMPONENTS[] = './classes/components/config/events.php';
	$COMPONENTS[] = './classes/components/config/handlers.php';
	$COMPONENTS[] = './classes/components/config/i18n.en.php';
	$COMPONENTS[] = './classes/components/config/i18n.php';
	$COMPONENTS[] = './classes/components/config/includes.php';
	$COMPONENTS[] = './classes/components/config/install.php';
	$COMPONENTS[] = './classes/components/config/lang.en.php';
	$COMPONENTS[] = './classes/components/config/lang.php';
	$COMPONENTS[] = './classes/components/config/permissions.php';
	$COMPONENTS[] = './classes/components/config/tests.php';
	$COMPONENTS[] = './classes/components/config/classes/Watermark/iAdminSettingsManager.php';
	$COMPONENTS[] = './classes/components/config/classes/Watermark/AdminSettingsManager.php';
	$COMPONENTS[] = './classes/components/config/classes/Captcha/iAdminSettingsManager.php';
	$COMPONENTS[] = './classes/components/config/classes/Captcha/AdminSettingsManager.php';
	$COMPONENTS[] = './classes/components/config/classes/Mail/iAdminSettingsManager.php';
	$COMPONENTS[] = './classes/components/config/classes/Mail/AdminSettingsManager.php';
