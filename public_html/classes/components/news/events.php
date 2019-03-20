<?php

	/** Импортирует все фиды по срабатыванию системного крона */
	new umiEventListener('cron', 'news', 'feedsImportListener');

	/** Активирует новости с подходящей датой публикации по срабатыванию системного крона */
	new umiEventListener('cron', 'news', 'cronActivateNews');

	/** Ставит у созданной новости в поле "Дата публикации" текущую дату, если поле было пустым */
	new umiEventListener('systemCreateElement', 'news', 'setNewsItemPublishTime');

	/** Ставит у созданной через EIP новости в поле "Дата публикации" текущую дату, если поле было пустым */
	new umiEventListener('eipQuickAdd', 'news', 'eipSetNewsItemPublishTime');
