<?php
	class backup extends def_module {
		public function __construct() {
			parent::__construct();

			$cmsController = cmsController::getInstance();
			$config = mainConfiguration::getInstance();

			$this->loadCommonExtension();

			if($cmsController->getCurrentMode() == 'admin') {
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('snapshots');
					$commonTabs->add('backup_copies');
				}

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__backup_custom_admin");
			}

			$this->__loadLib("__admin.php");
			$this->__implement("__backup");

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__backup_custom");
		}

		public function config() {
			return __backup::config();
		}

		/**
		 * Обработчик события, который может быть вызван по CRON. 
		 * Очищает историю изменений модуля "Резервирование" в соответствии с
		 * настройками времени хранения событий.
		 * @param umiEventPoint $event Точка вызова
		 * @return null
		 * @throws coreException В случае ошибки MySQL
		 */
		public function onCronCleanChangesHistory(umiEventPoint $event) {
			
			if ($event->getMode() !== 'process') {
				return;
			}
			
			$regedit = regedit::getInstance();
			$maxDaysLimit = (int) $regedit->getVal("//modules/backup/max_timelimit");
			
			if ($maxDaysLimit === 0) {
				return;
			}
			
			$backupModel = backupModel::getInstance();
			$overdueChanges = $backupModel->getOverdueChanges($maxDaysLimit);
			$backupModel->deleteChanges($overdueChanges);
		}
	};
?>