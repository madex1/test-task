<?php

	use UmiCms\Service;

	/**
	 * Класс работы с резервными копиями.
	 * Работает со следующими копиями:
	 *
	 * 1) Резервные копии контента страниц;
	 * 2) Резервные копии файлов системы;
	 *
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_rezervirovanie/
	 */
	class backup extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('snapshots');
				$commonTabs->add('backup_copies');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('BackupAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('BackupCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('BackupHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('BackupCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Алиас backupModel::getChanges(), возвращает
		 * список резервных копий заданной страницы.
		 * @param int|string $pageId идентификатор страницы
		 * @return array|string
		 */
		public function backup_panel($pageId = '') {
			if (!$pageId) {
				return '';
			}

			return backupModel::getInstance()->getChanges($pageId);
		}

		/**
		 * Алиас backupModel::getAllChanges(), возвращает
		 * список последних 100 резевных копий любых страниц.
		 * @return array
		 */
		public function backup_panel_all() {
			return backupModel::getInstance()->getAllChanges();
		}

		/**
		 * Алиас backupModel::rollback(), восстанавливает
		 * из резерной копии страницы.
		 * @throws requreMoreAdminPermissionsException
		 */
		public function rollback() {
			$revisionId = (int) getRequest('param0');
			backupModel::getInstance()->rollback($revisionId);
		}

		/**
		 * Алиас backupModel::save(), сохраняет
		 * резервную копию страницы
		 * @param int|string $pageId идентификатор страницы
		 * @param string $module модуль, средствами которого страницы была модифицирована
		 * @param string $method метод, средствами которого страница была модифицирована
		 * @return bool
		 */
		public function backup_save($pageId = '', $module = '', $method = '') {
			return backupModel::getInstance()->save($pageId, $module, $method);
		}

		/**
		 * Создает резервную копию файлов системы
		 * и воводит в буффер результат работы
		 * @throws coreException
		 */
		public function createSnapshot() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$buffer->clear();

			$location = $this->pre_lang . '/admin/backup/backup_copies/';

			if (isDemoMode()) {
				$err = getLabel('error-disabled-in-demo');
				$buffer->push("alert('{$err}');window.location = '{$location}';");
				$buffer->end();
			}

			Service::ManifestFactory()
				->create('MakeSystemBackup')
				->execute();

			$buffer->push("\nwindow.location = '{$location}';\n");
			$buffer->end();
		}

		/**
		 * Удаляет резервную копию файлов системы
		 * и перенаправляет на список резервных копий файлов системы
		 */
		public function deleteSnapshot() {
			/** @var backup|BackupAdmin $this */
			$fileName = getRequest('filename');

			if (!isDemoMode()) {
				$dir = new umiDirectory(SYS_MANIFEST_PATH . 'backup/');
				foreach ($dir as $item) {
					if ($item instanceof umiFile) {
						if ($item->getFileName() == $fileName) {
							$item->delete();
							break;
						}
					}
				}
			}

			$this->chooseRedirect($this->pre_lang . '/admin/backup/backup_copies/');
		}

		/**
		 * Восстанавливает файлы системы из резервной копии
		 * @throws coreException
		 */
		public function restoreSnapshot() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$buffer->clear();

			$location = $this->pre_lang . '/admin/backup/backup_copies/';

			if (isDemoMode()) {
				$err = getLabel('error-disabled-in-demo');
				$buffer->push("alert('{$err}');window.location = '{$location}';");
				$buffer->end();
			}

			$params = [
				'external-archive-filepath' => getRequest('filename')
			];

			Service::ManifestFactory()
				->create('RestoreSystemBackup', $params)
				->execute();

			$buffer->push("\nwindow.location = '{$location}';\n");
			$buffer->end();
		}

		/**
		 * Инициирует скачивание резервной копии файлов системы
		 * и перенаправляет на список резервных копий файлов системы
		 */
		public function downloadSnapshot() {
			/** @var backup|BackupAdmin $this */
			$fileName = getRequest('filename');

			$dir = new umiDirectory(SYS_MANIFEST_PATH . 'backup/');

			foreach ($dir as $item) {
				if ($item instanceof umiFile) {
					if ($item->getFileName() == $fileName) {
						$item->download();
						break;
					}
				}
			}

			$this->chooseRedirect($this->pre_lang . '/admin/backup/backup_copies/');
		}
	}
