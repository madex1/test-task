<?php

	use UmiCms\Service;

	/** Класс функционала административной панели модуля */
	class BackupAdmin {

		use baseModuleAdmin;

		/** @var backup $module */
		public $module;

		/**
		 * Возвращает список резервных копий файлов системы
		 * @throws coreException
		 */
		public function backup_copies() {
			$backupDir = str_replace(CURRENT_WORKING_DIR, '', SYS_MANIFEST_PATH) . 'backup/';

			$params = [
				'snapshots' => [
					'status:backup-directory' => $backupDir
				]
			];

			$ent = getRequest('ent');

			if (!$ent) {
				$ent = time();
				$this->module->redirect($this->module->pre_lang . '/admin/backup/backup_copies/?ent=' . $ent);
			}

			$this->setConfigResult($params);
		}

		/** Возвращает данные вкладки "История изменений" */
		public function snapshots() {
			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData([]);
			$this->doData();
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение настроек.
		 * @throws coreException
		 */
		public function config() {
			$umiRegistry = Service::Registry();
			$params = [
				'backup' => [
					'boolean:enabled' => null,
					'int:max_timelimit' => null,
					'int:max_save_actions' => null
				]
			];

			if ($this->isSaveMode() && !isDemoMode()) {
				$params = $this->expectParams($params);
				$umiRegistry->set('//modules/backup/enabled', $params['backup']['boolean:enabled']);
				$umiRegistry->set('//modules/backup/max_timelimit', $params['backup']['int:max_timelimit']);
				$umiRegistry->set('//modules/backup/max_save_actions', $params['backup']['int:max_save_actions']);
				$this->chooseRedirect();
			}

			$params['backup']['boolean:enabled'] = $umiRegistry->get('//modules/backup/enabled');
			$params['backup']['int:max_timelimit'] = $umiRegistry->get('//modules/backup/max_timelimit');
			$params['backup']['int:max_save_actions'] = $umiRegistry->get('//modules/backup/max_save_actions');

			$this->setConfigResult($params);
		}
	}
