<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'comments';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'view_comments';
	$INFO['default_comments'] = '1';
	$INFO['per_page'] = '10';
	$INFO['moderated'] = '1';
	$INFO['guest_posting'] = '0';
	$INFO['allow_guest'] = '1';
	$INFO['vkontakte'] = '0';
	$INFO['vk_per_page'] = '0';
	$INFO['vk_width'] = '0';
	$INFO['vk_api'] = '0';
	$INFO['vk_extend'] = '0';
	$INFO['facebook'] = '0';
	$INFO['fb_per_page'] = '0';
	$INFO['fb_width'] = '0';
	$INFO['fb_colorscheme'] = '0';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/comments/admin.php';
	$COMPONENTS[] = './classes/components/comments/class.php';
	$COMPONENTS[] = './classes/components/comments/customAdmin.php';
	$COMPONENTS[] = './classes/components/comments/customMacros.php';
	$COMPONENTS[] = './classes/components/comments/events.php';
	$COMPONENTS[] = './classes/components/comments/handlers.php';
	$COMPONENTS[] = './classes/components/comments/i18n.en.php';
	$COMPONENTS[] = './classes/components/comments/i18n.php';
	$COMPONENTS[] = './classes/components/comments/includes.php';
	$COMPONENTS[] = './classes/components/comments/install.php';
	$COMPONENTS[] = './classes/components/comments/lang.en.php';
	$COMPONENTS[] = './classes/components/comments/lang.php';
	$COMPONENTS[] = './classes/components/comments/macros.php';
	$COMPONENTS[] = './classes/components/comments/permissions.php';

