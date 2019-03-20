<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'blogs20';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'blogsList';
	$INFO['default_method_admin'] = 'posts';
	$INFO['paging'] = 'Настройки количества выводимых страниц';
	$INFO['paging/blogs'] = '10';
	$INFO['paging/posts'] = '10';
	$INFO['paging/comments'] = '50';
	$INFO['autocreate_path'] = '/';
	$INFO['blogs_per_user'] = '5';
	$INFO['allow_guest_comments'] = '0';
	$INFO['moderate_comments'] = '1';
	$INFO['notifications'] = 'Настройки уведомлений';
	$INFO['notifications/on_comment_add'] = '1';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/blogs20/admin.php';
	$COMPONENTS[] = './classes/components/blogs20/class.php';
	$COMPONENTS[] = './classes/components/blogs20/customAdmin.php';
	$COMPONENTS[] = './classes/components/blogs20/customMacros.php';
	$COMPONENTS[] = './classes/components/blogs20/events.php';
	$COMPONENTS[] = './classes/components/blogs20/handlers.php';
	$COMPONENTS[] = './classes/components/blogs20/i18n.en.php';
	$COMPONENTS[] = './classes/components/blogs20/i18n.php';
	$COMPONENTS[] = './classes/components/blogs20/includes.php';
	$COMPONENTS[] = './classes/components/blogs20/install.php';
	$COMPONENTS[] = './classes/components/blogs20/lang.en.php';
	$COMPONENTS[] = './classes/components/blogs20/lang.php';
	$COMPONENTS[] = './classes/components/blogs20/macros.php';
	$COMPONENTS[] = './classes/components/blogs20/permissions.php';

