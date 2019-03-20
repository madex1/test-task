<?php

	use UmiCms\Service;

	/** Класс обработчиков событий */
	class BackupHandlers {

		/** @var backup $module */
		public $module;

		/**
		 * Обработчик события вызова системного cron'а.
		 * Очищает историю изменений модуля "Резервирование" в соответствии с
		 * настройками времени хранения событий.
		 * @param umiEventPoint $event событие вызова системного cron'а
		 * @return null
		 * @throws coreException
		 */
		public function onCronCleanChangesHistory(umiEventPoint $event) {
			if ($event->getMode() !== 'process') {
				return;
			}

			$umiRegistry = Service::Registry();
			$maxDaysLimit = (int) $umiRegistry->get('//modules/backup/max_timelimit');

			if ($maxDaysLimit === 0) {
				return;
			}

			$backupModel = backupModel::getInstance();
			$overdueChanges = $backupModel->getOverdueChanges($maxDaysLimit);
			$backupModel->deleteChanges($overdueChanges);
		}
	}
