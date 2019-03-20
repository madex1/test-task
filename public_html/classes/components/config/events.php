<?php

	/** Обработчик события запуска системного cron'а */
	new umiEventListener('cron', 'config', 'runGarbageCollector');
	new umiEventListener('cron', 'config', 'maintainDataBaseCache');

	$config = mainConfiguration::getInstance();

	if ($config->get('messages', 'catch-system-events')) {
		/** Обработчики событий сохранения изменений страниц и объектов */
		new umiEventListener('systemModifyObject', 'config', 'systemEventsNotify');
		new umiEventListener('systemModifyElement', 'config', 'systemEventsNotify');
	}
