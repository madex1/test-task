<?php

	/** Обработчик окончания индексации, устанавливает источник индекса для задействованых разделов */
	new umiEventListener('releaseFilterIndex', 'catalog', 'setSourceIndex');

	$mainConfiguration = mainConfiguration::getInstance();
	if ((bool) $mainConfiguration->get('modules', 'catalog.allow-auto-update-filter-index')) {
		/** Обработчики событий изменения каталога для обновления индекса фильтров */
		new umiEventListener('systemModifyElement', 'catalog', 'updateIndexOnModify');
		new umiEventListener('systemSwitchElementActivity', 'catalog', 'updateIndexOnSwitchActivity');
		new umiEventListener('systemCreateElement', 'catalog', 'updateIndexOnCreate');
		new umiEventListener('systemMoveElement', 'catalog', 'updateIndexOnMove');
		new umiEventListener('systemDeleteElement', 'catalog', 'updateIndexOnDelete');
		new umiEventListener('systemVirtualCopyElement', 'catalog', 'updateIndexOnVirtualCopy');
		new umiEventListener('systemModifyPropertyValue', 'catalog', 'updateIndexOnModifyPropertyByFastEdit');
		new umiEventListener('eipSave', 'catalog', 'updateIndexOnModifyPropertyByEIP');
		new umiEventListener('systemKillElement', 'catalog', 'updateIndexOnKill');
		new umiEventListener('systemRestoreElement', 'catalog', 'updateIndexOnRestore');
	}

	/** Обработчик запуска системного крона, запускает переиндексацию всех разделов */
	if ((bool) $mainConfiguration->get('modules', 'catalog.reindex-on-cron-event-enable')) {
		new umiEventListener('cron', 'catalog', 'reIndexOnCron');
	}

