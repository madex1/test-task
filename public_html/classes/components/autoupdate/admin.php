<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class AutoupdateAdmin {

		use baseModuleAdmin;

		/** @var autoupdate $module */
		public $module;

		/**
		 * Возвращает информацию о состоянии обновлений системы
		 * @throws coreException
		 * @throws publicException
		 */
		public function versions() {
			$module = $this->module;
			$systemEditionStatus = '%autoupdate_edition_' . $module->getEdition() . '%';

			if ($module->isTrial() && !Service::Request()->isLocal()) {
				$daysLeft = Service::Registry()->getDaysLeft();
				$systemEditionStatus .= " ({$daysLeft} " . getLabel('label-days-left') . ')';
			}

			$systemEditionStatus = autoupdate::parseTPLMacroses($systemEditionStatus);

			$params = [
				'autoupdate' => [
					'status:system-edition' => $systemEditionStatus,
					'status:last-updated' => date('Y-m-d H:i:s', $module->getUpdateTime()),
					'status:system-version' => $module->getVersion(),
					'status:system-build' => $module->getRevision(),
					'status:db-driver' => iConfiguration::MYSQL_DB_DRIVER,
					'boolean:disabled' => false
				]
			];

			$version = Service::SystemInfo()->getInfo(iSystemInfo::PHP)['php']['version'];

			if (version_compare($version, '7.0.0', '<')) {
				$params['autoupdate']['alert:alert'] = getLabel('label-php-5-alert', false, $version);
			}

			if (defined('CURRENT_VERSION_LINE')) {
				$isStartEdition = in_array(CURRENT_VERSION_LINE, ['start']);

				if (isDemoMode() || $isStartEdition) {
					$params['autoupdate']['boolean:disabled'] = true;
				}
			}

			$domainCollection = Service::DomainCollection();
			$host = Service::Request()->host();

			if (!$domainCollection->isDefaultDomain($host)) {
				$params['autoupdate']['check:disabled-by-host'] = $domainCollection->getDefaultDomain()
					->getHost();
			}

			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает настройки обновления манифестов
		 * @throws coreException
		 * @throws wrongParamException
		 * @throws publicAdminException
		 * @throws requireAdminParamException
		 */
		public function manifests() {
			if ($this->isNeedToDoAction()) {
				$this->saveManifestsForm();
			}

			$this->setConfigResult($this->getManifestsFormData());
		}

		/**
		 * Сохраняет форму настроек обновления манифестов и делает перенаправление
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		private function saveManifestsForm() {
			$settings = [
				'modules' => [
					'select-multi:disabled-update-manifests' => null
				]
			];

			$settings = $this->expectParams($settings);
			$moduleList = trim($settings['modules']['select-multi:disabled-update-manifests']);
			$moduleList = explode(',', $moduleList);

			$config = mainConfiguration::getInstance();
			$config->set('updates', 'disable-update-manifest', $moduleList);
			$config->save();

			$this->chooseRedirect();
		}

		/**
		 * Возвращает данные для создания формы настроек обновления манифестов
		 * @return array
		 */
		private function getManifestsFormData() {
			$blockedModuleList = (array) mainConfiguration::getInstance()
				->get('updates', 'disable-update-manifest');
			$moduleList = [];

			foreach (cmsController::getInstance()->getModulesList() as $module) {

				if (!$this->isUpdateManifestExits($module)) {
					continue;
				}

				$moduleNode = [
					'@id' => $module,
					'node:value' => getLabel(sprintf('module-%s', $module))
				];

				if (in_array($module, $blockedModuleList)) {
					$moduleNode['@selected'] = 1;
				}

				$moduleList[] = $moduleNode;
			}

			return [
				'modules' => [
					'select-multi:disabled-update-manifests' => [
						'nodes:item' => $moduleList
					]
				]
			];
		}

		/**
		 * Определяет есть ли у модуля манифест обновления
		 * @param string $module имя модуля
		 * @return bool
		 */
		private function isUpdateManifestExits($module) {
			try {
				Service::ManifestFactory()
					->createByModule('update', $module);
			} catch (Exception $exception) {
				return false;
			}

			return true;
		}

		/**
		 * Возвращает данные для вкладки "Целостность"
		 * @throws publicException
		 */
		public function integrity() {
			if (isDemoMode()) {
				throw new publicAdminException(getLabel('label-stop-in-demo'));
			} elseif ($this->module->isTrial()) {
				throw new publicAdminException(getLabel('error-trial-version-not-available-functional'));
			}

			$this->setDataType('settings');
			$this->setActionType('view');
			$data = $this->module->getIntegrityState();
			$this->setData($data);
			$this->doData();
		}
	}
