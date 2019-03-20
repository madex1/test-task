<?php

	use UmiCms\Service;

	class autoupdate extends def_module {

		/** @var string имя файла в котором хранится кэш времени окончания поддержки лицензии */
		public $supportTimeCacheFile = 'support-end.time';

		/** @desk Конструктор */
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$commonTabs = $this->getCommonTabs();

				if ($commonTabs) {
					$commonTabs->add('versions');
				}

				$this->__loadLib("__admin.php");
				$this->__implement("__autoupdate");

				$this->__loadLib("__json.php");
				$this->__implement("__json_autoupdate");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__autoupdate_custom_admin");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__autoupdate_custom");
		}

		/**
		* @desc Отображение сервисной информации
		* @return string
		*/
		public function service () {
			$event = strtoupper(getRequest('param0'));

			$autoupdates_disabled = (bool) regedit::getInstance()->getVal("//modules/autoupdate/autoupdates_disabled");
			if($autoupdates_disabled) {
				$this->flush('DISABLED', "text/plain");
			}

			$this->checkIsValidSender();
			$result = '';

			switch($event) {
				case "STATUS":
					$result = $this->returnStatus();
					break;

				case "VERSION":
					$result = $this->returnVersions();
					break;

				case "LAST_UPDATED":
					$result = $this->returnLastUpdated();
					break;

				case "MODULES": {
					$result = $this->getModules();
					break;
				}

				case "DOMAINS": {
					$result = $this->getDomains();
					break;
				}

				case 'SUPPORT':
					$supportEndDate = $this->getSupportEndDate();

					if (isset($supportEndDate['date']) && isset($supportEndDate['date']['@timestamp'])) {
						$result = date('d.m.Y H:i:s', $supportEndDate['date']['@timestamp']);
					}

					break;

				default:
					$result = "UNHANDLED_EVENT";
					break;
			}

			$this->flush($result, "text/plain");
		}

		/**
		* @desc Возвращает статус автообновления
		* @return string
		*/
		protected function returnStatus () {
			return (string) $this->getRegistrySettings()->getStatus();
		}

		/**
		* @desc Возвращает версию системы
		* @return string
		*/
		protected function returnVersions() {
			$registrySettings = $this->getRegistrySettings();
			return (string) $registrySettings->getVersion() . "\n" . $registrySettings->getRevision();
		}

		/**
		* @desc Возвращает дату последнего обновления
		* @return string
		*/
		public function returnLastUpdated() {
			return (string) $this->getLastUpdated();
		}

		/**
		 * Возвращает общие настройки реестра
		 * @return mixed|\UmiCms\System\Registry\iSettings
		 */
		public function getRegistrySettings() {
			return Service::RegistrySettings();
		}

		/** @desc */
		protected function checkIsValidSender () {
			//TODO
		}

		/**
		* @desc Получает список модулей системы
		* @return string
		*/
		protected function getModules() {
			$regedit = regedit::getInstance();
			$ml = $regedit->getList("//modules");

			$res = "";
			foreach($ml as $m) {
				$res .= $m[0] . "\n";
			}
			return $res;
		}

		/**
		* @desc Получает список доменов системы
		* @return string
		*/
		protected function getDomains() {
			$domainsCollection = domainsCollection::getInstance();
			$domains = $domainsCollection->getList();

			$res = "";
			foreach($domains as $domain) {
				$res .= $domain->getHost() . "\n";
			}
			return $res;
		}

		/**
		* @desc Получает текущую версию системы
		* @return string
		*/
		public function getCurrentVersion () {
			return (string) Service::RegistrySettings()->getVersion();
		}

		/**
		* @desc Устанавливает текущую версию
		* @param string $version Номер версии, который нужно установить для системы
		*/
		public function setCurrentVersion ($version) {
			Service::RegistrySettings()->set('system_version', (string) $version);
		}

		/**
		* @desc Получает дату последнего обновления системы
		* @return string
		*/
		public function getLastUpdated () {
			return (string) $this->getRegistrySettings()->getUpdateTime();
		}

		/**
		* @desc Устанавливает дату последнего обновления системы
		* @param int $time Время, которое нужно установить в качестве времени последнего обновления системы
		*/
		public function setLastUpdated ($time) {
			$this->getRegistrySettings()->set('last_updated', (int) $time);
		}

		/**
		* @desc
		* @return array
		*/
		public function getDaysLeft () {

			if(($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'subdomain.localhost') && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
				return;
			}

			$regedit = Service::Registry();
			$systemEdition = Service::RegistrySettings()->getEdition();

			if(strpos($systemEdition, 'trial') !== false|| (strpos($systemEdition, 'commerce_enc') !== false)) {
				$daysLeft = $regedit->getDaysLeft();
				return array('trial'=>array('attribute:daysleft'=>$daysLeft));
			}
		}

		/**
		 * Возвращает количество дней, которое осталось до окончания поддержки лицензионного ключа
		 * @return int количество дней
		 * @throws publicException
		 */
		public function getSupportEndDate() {
			$cacheValue = $cacheLastUpdateTime = null;
			$cacheFilePath = $this->getSupportTimeCacheFilePath();

			if (is_file($cacheFilePath)) {
				$cacheValue = file_get_contents($cacheFilePath);
				$cacheLastUpdateTime = filemtime($cacheFilePath);
			}

			$supportEndTime = intval($cacheValue);

			$daysToStorage = 3;
			$hoursInDay = 24;
			$secondsInHour = 3600;
			$storageTime = $daysToStorage * $hoursInDay * $secondsInHour;

			if ($supportEndTime <= 0 || $cacheLastUpdateTime <= 0 || !$this->checkSupportCacheRelevance($cacheLastUpdateTime, $storageTime)) {
				$supportEndTime = $this->requestSupportTime($cacheFilePath);
			}

			$dayNumber = date('j', $supportEndTime);
			$monthEng = date('M', $supportEndTime);
			$monthRus = getLabel('month-' . strtolower($monthEng));
			$year = date('Y', $supportEndTime);

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

				//no default
			}

			return array(
				'date' => array(
					'@day' => $dayNumber,
					'@month_rus' => $monthRus,
					'@year' => $year,
					'@timestamp' => $supportEndTime,
					'@status' => $status
				)
			);
		}

		/**
		 * Запрашивает время окончания поддержки текущей лицензии от сервера лицензий
		 * и возвращает его в формате Unix Timestamp
		 * @param bool $cacheTime сохранить время окончания поддержки в кэш
		 * @return int время окончания поддержки в формате Unix Timestamp
		 * @throws publicException
		 */
		public function requestSupportTime($cacheTime = true) {
			$licenseServerUrl = base64_decode('aHR0cDovL3Vkb2QudW1paG9zdC5ydS8=');
			$licenseInfoMacro = base64_decode('dWRhdGE6Ly9jdXN0b20vY2hlY2tMaWNlbnNlLz9rZXljb2RlPQ==');
			$userKeyCode = Service::RegistrySettings()->getLicense();

			if (!$userKeyCode) {
				throw new publicException('Доменный ключ не найден.');
			}

			$requestUrl = $licenseServerUrl . $licenseInfoMacro . $userKeyCode;
			$licenseInfo = simplexml_load_string(umiRemoteFileGetter::get($requestUrl));

			if (!$licenseInfo instanceof SimpleXMLElement) {
				throw new publicException('Данные лицензии не были загружены.');
			}

			$licenseTypeNodesList = $licenseInfo->xpath('/udata/license_type');
			$licenseTypeNode = array_shift($licenseTypeNodesList);

			if (!$licenseTypeNode instanceof SimpleXMLElement) {
				throw new publicException('Редакция лицензии не была получена');
			}

			$isTrialLicense = strpos(strtolower($licenseTypeNode->__toString()), 'trial') !== false;

			if ($isTrialLicense) {
				throw new publicException('У trial лицензий отсутствует срок окончания поддержки');
			}

			$supportTimeNodesList = $licenseInfo->xpath('/udata/support_time');
			$supportTimeNode = array_shift($supportTimeNodesList);

			if (!$supportTimeNode instanceof SimpleXMLElement) {
				throw new publicException('Время окончания поддержки не было получено');
			}

			$supportEndTime = intval($supportTimeNode->__toString());

			if ($supportEndTime <= 0) {
				throw new publicException('Время окончания поддержки не было загружено или некорректно');
			}

			if ($cacheTime) {
				file_put_contents($this->getSupportTimeCacheFilePath(), $supportEndTime . PHP_EOL);
			}

			return $supportEndTime;
		}

		/**
		 * Возвращает путь до файла, в котором хранится закэшировнное время окончания поддержки лицензии
		 * @return string
		 */
		public function getSupportTimeCacheFilePath() {
			return SYS_CACHE_RUNTIME . $this->supportTimeCacheFile;
		}

		/** Очищает кэш с временем окончания поддержки лицензии */
		public function resetSupportTimeCache() {
			if (is_file($this->getSupportTimeCacheFilePath())) {
				unlink($this->getSupportTimeCacheFilePath());
			}
		}

		/**
		 * Проверяет актуальность кэша, в котором хранится время окончания поддержки лицензии
		 * @param int $cacheLastUpdateTime время последнего обновления кэша
		 * @param int $storageInterval количество секунд хранения кэша
		 * @return bool
		 */
		protected function checkSupportCacheRelevance($cacheLastUpdateTime, $storageInterval) {
			return (time() - $cacheLastUpdateTime <= $storageInterval);
		}
	};
?>
