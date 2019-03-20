<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\AutoUpdate\iRegistry;
	use UmiCms\Classes\Components\AutoUpdate\UpdateServer\iClient;

	/**
	 * Базовый класс модуля "Автообновление".
	 * Модуль отвечает за:
	 *
	 * 1) Получение информации о состоянии обновлений;
	 * 2) Работу с патчами;
	 * 3) Проверку прав на использование установленных модулей.
	 *
	 * Функционал самого обновления размещен в /smu/installer.php
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_avtoobnovleniya/
	 */
	class autoupdate extends def_module {

		/**
		 * Конструктор
		 * @throws coreException
		 */
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
				$commonTabs->add('versions');
				$commonTabs->add('manifests');
				$commonTabs->add('integrity');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('AutoupdateAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('AutoUpdateCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('AutoUpdateCustomMacros', true);

			$this->__loadLib('service.php');
			$this->__implement('AutoUpdateService');

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает оставшееся количество дней работы триальной лицензии
		 * @return array
		 */
		public function getDaysLeft() {
			if (!$this->isTrial() || Service::Request()->isLocal()) {
				return [];
			}

			return [
				'trial' => [
					'attribute:daysleft' => Service::Registry()
						->getDaysLeft()
				]
			];
		}

		/**
		 * Определяет работает ли система на триальной редакции
		 * @return bool
		 */
		public function isTrial() {
			return $this->isTrialEdition($this->getEdition());
		}

		/**
		 * Возвращает реестр модуля
		 * @return iRegistry
		 * @throws Exception
		 */
		public function getRegistry() {
			return Service::get('AutoUpdateRegistry');
		}

		/**
		 * Возвращает клиента сервера обновлений
		 * @return iClient
		 * @throws Exception
		 */
		public function getUpdateServerClient() {
			return Service::get('UpdateServerClient');
		}

		/**
		 * Возвращает количество дней, которое осталось до окончания поддержки лицензионного ключа
		 * @return array
		 * @throws publicException
		 * @throws coreException
		 * @throws Exception
		 */
		public function getSupportEndDate() {
			$cacheEngine = Service::CacheEngineFactory()
				->create();
			$key = 'support-end-time';
			$supportEndTime = $cacheEngine->loadRawData($key);
			$hoursInDay = 24;
			$secondsInHour = 3600;

			if (!is_numeric($supportEndTime)) {
				try {
					$supportEndTime = $this->getUpdateServerClient()
						->getSupportEndTime()
						->getDateTimeStamp();
				} catch (RuntimeException $exception) {
					throw new publicException($exception->getMessage());
				}

				$cacheEngine->saveRawData($key, $supportEndTime, 3 * $hoursInDay * $secondsInHour);
			}

			$status = '';
			$daysAverageInMonth = 30;
			$alertDuration = $daysAverageInMonth * $hoursInDay * $secondsInHour;
			$warningDuration = 3 * $alertDuration;
			$timeRemaining = $supportEndTime - time();

			switch (true) {
				case ($timeRemaining <= $alertDuration):
					$status = 'alert';
					break;
				case ($timeRemaining > $alertDuration && $timeRemaining <= $warningDuration):
					$status = 'warning';
					break;
			}

			return [
				'date' => [
					'@day' => date('j', $supportEndTime),
					'@month_rus' => getLabel('month-' . mb_strtolower(date('M', $supportEndTime))),
					'@year' => date('Y', $supportEndTime),
					'@timestamp' => $supportEndTime,
					'@status' => $status
				]
			];
		}

		/**
		 * Определяет установлена ли последняя версия системы
		 * @return bool
		 * @throws publicException
		 * @throws Exception
		 */
		public function isLastVersion() {
			try {
				$lastRevision = $this->getUpdateServerClient()
					->getLastRevision();
			} catch (RuntimeException $exception) {
				throw new publicException($exception->getMessage());
			}

			return (string) $lastRevision === $this->getRevision();
		}

		/**
		 * Возвращает версию системы
		 * @return string
		 */
		public function getVersion() {
			return $this->getRegistrySettings()
				->getVersion();
		}

		/**
		 * Возвращает ревизию системы
		 * @return string
		 */
		public function getRevision() {
			return $this->getRegistrySettings()
				->getRevision();
		}

		/**
		 * Возвращает редакцию системы
		 * @return string
		 */
		public function getEdition() {
			return $this->getRegistrySettings()
				->getEdition();
		}

		/**
		 * Возвращает timestamp последнего обнвовления
		 * @return int
		 * @throws publicException
		 */
		public function getUpdateTime() {
			try {
				return $this->getRegistrySettings()
					->getUpdateTime();
			} catch (RuntimeException $exception) {
				throw new publicException($exception->getMessage());
			}
		}

		/**
		 * Возвращает доменный лицензионный ключ
		 * @return string
		 */
		public function getLicense() {
			return $this->getRegistrySettings()
				->getLicense();
		}

		/**
		 * Возвращает список модулей, доступных для установки
		 * @return array
		 *
		 * [
		 *      'module' => 'Модуль',
		 *      ...
		 * ]
		 *
		 * @throws publicException
		 * @throws Exception
		 */
		public function getAvailableModuleList() {
			try {
				return $this->getUpdateServerClient()
					->getAvailableModuleList();
			} catch (RuntimeException $exception) {
				throw new publicException($exception->getMessage());
			}
		}

		/**
		 * Возвращает список расширений, доступных для установки
		 * @return array
		 *
		 * [
		 *      'extension' => 'Расширение',
		 *      ...
		 * ]
		 *
		 * @throws publicException
		 * @throws Exception
		 */
		public function getAvailableExtensionList() {
			try {
				return $this->getUpdateServerClient()
					->getAvailableExtensionList();
			} catch (RuntimeException $exception) {
				throw new publicException($exception->getMessage());
			}
		}

		/**
		 * Возвращает состояние целостности системы:
		 *
		 * 1) Список удаленных файлов;
		 * 2) Список изменнный файлов;
		 *
		 * @return array
		 *
		 * <data>
		 *      <deleted>
		 *          <item path="/styles/skins/modern/data/modules/geoip/settings.modify.xsl"/>
		 *      </deleted>
		 *      <changed>
		 *          <item path="/classes/components/events/lang.php"/>
		 *      </changed>
		 * </data>
		 *
		 * @throws publicException
		 * @throws Exception
		 */
		public function getIntegrityState() {
			$moduleList = Service::Registry()->getList('//modules');
			$moduleList = array_map(function (array $module) {
				return array_shift($module);
			}, $moduleList);
			$extensionList = Service::ExtensionRegistry()->getList();
			$deletedFileNodeList = [];
			$changedFileNodeList = [];

			foreach (array_merge($moduleList, $extensionList, ['core']) as $component) {
				try {
					$this->collectFileViolation($component, $deletedFileNodeList, $changedFileNodeList);
				} catch (publicException $exception) {
					$message = $exception->getMessage();

					if (contains($message, 'Модуль') && contains($message, 'не поддерживается вашей лицензией')) {
						continue;
					}

					throw $exception;
				}
			}

			return [
				'deleted' => [
					'nodes:item' => $deletedFileNodeList
				],
				'changed' => [
					'nodes:item' => $this->wasTrialPreviously() ? [] : $changedFileNodeList
				]
			];
		}

		/**
		 * Возвращает список установленных готовых решений
		 * @return array
		 * @see UmiCms\Classes\Components\AutoUpdate\UpdateServer::getInstalledSolutionList()
		 * @throws publicException
		 * @throws Exception
		 */
		public function getInstalledSolutionList() {
			$solutionNameList = Service::SolutionRegistry()
				->getList();
			try {
				return $this->getUpdateServerClient()
					->getSolutionList($solutionNameList);
			} catch (RuntimeException $exception) {
				throw new publicException($exception->getMessage());
			}
		}

		/**
		 * Возвращает полный список решений (демо, бесплатных и купленных) с их категориями и типами
		 * @return array
		 * @see UmiCms\Classes\Components\AutoUpdate\UpdateServer::getFullSolutionList()
		 * @throws publicException
		 * @throws Exception
		 */
		public function getFullSolutionList() {
			try {
				return $this->getUpdateServerClient()
					->getFullSolutionList();
			} catch (RuntimeException $exception) {
				throw new publicException($exception->getMessage());
			}
		}

		/**
		 * Собирает файлы компонента с нарушенением целостности
		 * @param string $component название компонента
		 * @param array $deletedList список удаленных файлов
		 * @param array $changedList список измененных файлов
		 * @throws publicException
		 */
		private function collectFileViolation($component, array &$deletedList, array &$changedList) {
			$fileFactory = Service::FileFactory();

			foreach ($this->getComponentFileList($component) as $hash => $path) {
				$file = $fileFactory->create($path);
				$fileNode = [
					'@path' => $file->getFilePath(true)
				];

				if (!$file->isExists()) {
					$deletedList[] = $fileNode;
					continue;
				}

				if ($file->getHash() !== $hash) {
					$changedList[] = $fileNode;
				}
			}
		}

		/**
		 * Возвращает список файлов компонента
		 * @param string $name имя компонента
		 * @return array
		 *
		 * [
		 *      '7aabc04173bb8edf45a000cc9e6f0bf8' => './classes/modules/faq/events.php'
		 * ]
		 *
		 * @throws publicException
		 */
		private function getComponentFileList($name) {
			try {
				return $this->getUpdateServerClient()
					->getComponentFileList($name);
			} catch (RuntimeException $exception) {
				throw new publicException($exception->getMessage());
			}
		}

		/**
		 * Возвращает предыдущую редакцию системы
		 * @return string
		 * @throws Exception
		 */
		private function getPreviousEdition() {
			return $this->getRegistry()->get('previous_edition');
		}

		/**
		 * Определяет была ли триальной предыдущая редакция
		 * @return bool
		 * @throws Exception
		 */
		private function wasTrialPreviously() {
			return $this->isTrialEdition($this->getPreviousEdition());
		}

		/**
		 * Возвращает класс реестра общих настроек системы
		 * @return mixed|\UmiCms\System\Registry\iSettings
		 */
		private function getRegistrySettings() {
			return Service::RegistrySettings();
		}

		/**
		 * Определяет является ли редакция триальной
		 * @param string $name название редакции
		 * @return bool
		 */
		private function isTrialEdition($name) {
			return contains($name, 'trial') || contains($name, 'commerce_enc');
		}

		/** @deprecated */
		public function requestSupportTime($cacheTime = true) {
		}

		/** @deprecated */
		public function getSupportTimeCacheFilePath() {
		}

		/** @deprecated */
		public function resetSupportTimeCache() {
		}
	}
