<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = "umiRedirects";
	$INFO['config'] = "0";
	$INFO['default_method'] = "empty";
	$INFO['default_method_admin'] = "lists";
	$INFO['func_perms'] = "Группы прав на функционал модуля";
	$INFO['func_perms/manage'] = "Права на управление редиректами";

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = "./classes/modules/umiRedirects/__admin.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/__custom.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/__custom_adm.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/class.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/i18n.en.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/i18n.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/install.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/lang.en.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/lang.php";
	$COMPONENTS[] = "./classes/modules/umiRedirects/permissions.php";
