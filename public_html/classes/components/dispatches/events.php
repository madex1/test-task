<?php

	/**
	 * Валидация почтового ящика при создании, сохранении изменения и сохранения
	 * измений поля через eip у подписчика.
	 */
	new umiEventListener('systemCreateObject', 'dispatches', 'onCreateObject');
	new umiEventListener('systemModifyObject', 'dispatches', 'onModifyObject');
	$eipModifyEventListener = new umiEventListener('systemModifyPropertyValue', 'dispatches', 'onPropertyChanged');
	$eipModifyEventListener->setIsCritical(true);

	$forumModule = cmsController::getInstance()->getModule('forum');

	if ($forumModule instanceof def_module) {
		/** Обработчики изменения рассылки, подключают к рассылке выгрузку топиков из форума */
		new umiEventListener('systemModifyObject', 'dispatches', 'changeLoadForumOptionModify');
		new umiEventListener('systemModifyPropertyValue', 'dispatches', 'changeLoadForumOptionQuickEdit');
	}

	/** Отправка рассылок про системному крону */
	new umiEventListener('cron', 'dispatches', 'onAutosendDispathes');
