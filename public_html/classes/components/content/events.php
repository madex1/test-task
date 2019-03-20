<?php

	use UmiCms\Service;

	/** Обработчики событий редактирования страниц, которые отвечают за блокирование редактирования */

	if (Service::Registry()->get('//settings/lock_pages')) {
		$oContentBeginEdit = new umiEventListener('sysytemBeginPageEdit', 'content', 'systemLockPage');
		$oContentSave = new umiEventListener('systemModifyElement', 'content', 'systemUnlockPage');
	}

	/** Обработчики событий редактирования страниц, которые отвечают работу с актуальностью публикации страниц */
	if (Service::Registry()->get('//settings/expiration_control')) {
		$oSendNotification = new umiEventListener('cron', 'content', 'cronSendNotification');
		$oUnpublishPage = new umiEventListener('cron', 'content', 'cronUnpublishPage');
		$oAddUnpublishSave = new umiEventListener('systemCreateElementAfter', 'content', 'pageCheckExpirationAdd');
		$oEditUnpublisSave = new umiEventListener('systemModifyElement', 'content', 'pageCheckExpiration');
	}

	/** Обработчики событий редактирования страниц, которые отвечают за фильтрацию спама */
	if ((int) mainConfiguration::getInstance()->get('anti-spam', 'service.enabled')) {
		new umiEventListener('systemModifyPropertyValue', 'content', 'onModifyPropertyAntiSpam');
		new umiEventListener('systemModifyElement', 'content', 'onModifyElementAntiSpam');
	}

	/** Обработчики событий модуля "Форум" */
	new umiEventListener('systemCreateElementAfter', 'forum', 'onElementAppend');
	new umiEventListener('systemDeleteElement', 'forum', 'onElementRemove');
	new umiEventListener('systemSwitchElementActivity', 'forum', 'onElementActivity');

	/** Обработчики событий модуля "FAQ" */
	new umiEventListener('systemSwitchElementActivity', 'faq', 'onChangeActivity');

	/** Обработчик тестового события */
	new umiEventListener('users_login_successfull', 'content', 'testMessages');
