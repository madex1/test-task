<?php

	/** Обработчик события срабатывания системного cron'а */
	new umiEventListener('cron', 'backup', 'onCronCleanChangesHistory');

