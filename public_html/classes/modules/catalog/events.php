<?php
	/* Привязка обработчиков событий модуля каталог к системным событиям */

	/* Начало обработчиков событий для обновления индекса фильтров каталога */
	new umiEventListener('releaseFilterIndex', 'catalog', 'setSourceIndex');

	$mainConfiguration = mainConfiguration::getInstance();
	if ((bool) $mainConfiguration->get('modules', 'catalog.allow-auto-update-filter-index')) {
		new umiEventListener('systemModifyElement', 'catalog', 'updateIndexOnModify');
		new umiEventListener('systemSwitchElementActivity', 'catalog', 'updateIndexOnSwitchActivity');
		new umiEventListener('systemCreateElement', 'catalog', 'updateIndexOnCreate');
		new umiEventListener('systemMoveElement', 'catalog', 'updateIndexOnMove');
		new umiEventListener('systemDeleteElement', 'catalog', 'updateIndexOnDelete');
		new umiEventListener('systemVirtualCopyElement', 'catalog', 'updateIndexOnVirtualCopy');
		new umiEventListener('systemModifyPropertyValue', 'catalog', 'updateIndexOnModifyProperty');
		new umiEventListener('systemKillElement', 'catalog', 'updateIndexOnKill');
		new umiEventListener('systemRestoreElement', 'catalog', 'updateIndexOnRestore');
	}

	if ((bool) $mainConfiguration->get('modules', 'catalog.reindex-on-cron-event-enable')) {
		new umiEventListener('cron', 'catalog', 'reIndexOnCron');
	}

	/* Конец обработчиков событий для обновления индекса фильтров каталога */
?>
