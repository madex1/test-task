<?php

	use UmiCms\Service;

	error_reporting(0);
	ini_set('display_errors', 0);
	set_time_limit(0);

	if ((isset($_REQUEST['step']) && $_REQUEST['step'] == 'ping') &&
		(isset($_REQUEST['guiUpdate']) && $_REQUEST['guiUpdate'] == 'true')) {
		header('Content-Type: text/xml; charset=utf-8');
		echo '<result>ok</result>';
		die();
	}

	// check is cli mode
	if (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') {
		define('INSTALLER_CLI_MODE', false);
	} else {
		define('INSTALLER_CLI_MODE', true);
	}

	/** Режим дебага исталлера, когда инсталлер не обновляется */
	define('INSTALLER_DEBUG', false);
	define('CRON', true);
	define('DEBUG', true);
	define('UMICMS_CLI_MODE', INSTALLER_CLI_MODE);

	if (INSTALLER_CLI_MODE) {
		// error handlers
		function exception_error_handler($errno, $errstr, $errfile, $errline) {
			try {
				throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
			} catch (ErrorException $exception) {
				$msg = "Ошибка установки #{$errno}: \"" . $errstr . '" в строке ' . $errline . ' файла ' . $errfile . "\n";
				if ($errno != 0) {
					$msg .= "Подробнее об ошибке http://errors.umi-cms.ru/{$errno}/\n";
				}
				echo $msg;
			}
		}

		set_error_handler('exception_error_handler');

		/**
		 * Обработчик исключения или ошибки
		 * @param Exception|Throwable $exception исключение или ошибка
		 */
		function exception_handler($exception) {
			$errno = $exception->getCode();
			$msg = "Критическая ошибка установки #{$errno}: \"" . $exception->getMessage() . '" в строке ' .
				$exception->getLine() . ' файла ' . $exception->getFile() . "\n";

			if ($errno != 0) {
				$msg .= "Подробнее об ошибке http://errors.umi-cms.ru/{$errno}/\n";
			}

			// write into stderr
			$fp = fopen('php://stderr', 'w');
			if ($fp) {
				fwrite($fp, $msg);
			}

			die();
		}

		set_exception_handler('exception_handler');

		$args = parse_argv($_SERVER['argv']);
	} else {
		$args = $_REQUEST;
		header('Content-type: text/xml; charset=utf-8');
	}

	$installMode = true;

	if (in_array(mb_substr(dirname(__FILE__), -4, 4), ['/smu', '\smu'])) {
		define('INSTALLER_CURRENT_WORKING_DIR', realpath(dirname(__FILE__) . '/..'));

		$controlFilePath = INSTALLER_CURRENT_WORKING_DIR . '/installed';

		if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'install') {
			$installMode = true;
		} elseif (is_file($controlFilePath)) { // Update
			$installMode = false;
		} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'update') {
			$installMode = false;

			file_put_contents($controlFilePath, '');

			if (!file_exists($controlFilePath)) {
				throw new Exception(
					'Не удалось создать файл installed в корневой директории сайта. Создайте, пожалуйста, его самостоятельно.'
				);
			}
		}
	} else {
		define('INSTALLER_CURRENT_WORKING_DIR', realpath(dirname(__FILE__)));
	}

	define('INSTALL_MODE', $installMode);
	$currentStep = isset($args['step']) ? mb_strtolower(trim($args['step'])) : 'install-run';

	$temporaryDirectory = './sys-temp/updates/';
	if (!$installMode) {
		$configPath = INSTALLER_CURRENT_WORKING_DIR . '/install.ini';

		if (is_file($configPath)) {
			$conf = (array) parse_ini_file($configPath, true);
			if (isset($conf['includes']['sys-temp-path'])) {
				$temporaryDirectory = $conf['includes']['sys-temp-path'];
			}
		}
	}

	umask(0);
	$previousDirectory = getcwd();
	chdir(INSTALLER_CURRENT_WORKING_DIR);

	$installer = new umiInstallExecutor($temporaryDirectory, $installMode, $args);
	$installer->run($currentStep, INSTALLER_CLI_MODE, $args);

	chdir($previousDirectory);
	exit();

	/** Производит процесс установки/обновления */
	class umiInstallExecutor {

		const BUFFER_SIZE = 128;

		/** Файл, в котором хранится состояние обновления */
		const STATE_FILE_NAME = '.isf';

		private $currentStep = 'run';

		private $cliMode = true;

		private $settings;

		private $params = [];

		private $connection;

		private $installMode;

		static private $splitBlockSize;

		static private $state = false;

		static private $log = [];

		private $temporaryDirectory;

		public function __construct($tempDir, $installMode = true, $params = []) {
			$this->temporaryDirectory = $tempDir;
			$this->installMode = $installMode;

			if (!defined('PHP_FILES_ACCESS_MODE')) {
				$mode = $this->getConfigOption('SETUP', 'php_files_access_mode', false);

				if (!$mode) {
					if (INSTALLER_CLI_MODE || !$this->installMode) {
						$mode = mb_substr(decoct(fileperms(__FILE__)), -4, 4);
					} else {
						$mode = mb_substr(decoct(fileperms(INSTALLER_CURRENT_WORKING_DIR . '/install.php')), -4, 4);
					}
				}

				define('PHP_FILES_ACCESS_MODE', octdec($mode));
			}

			if (!self::$splitBlockSize) {
				self::$splitBlockSize = $this->getConfigOption('SETUP', 'split_block_size', 100);
			}

			if (self::$state === false) {
				$this->loadState();
			}
		}

		private function flushLog($msg) {
			if ($this->cliMode) {
				echo rtrim($msg, PHP_EOL), PHP_EOL;
			} else {
				self::$log[] = $msg;
			}
		}

		private function getConfigOption($section, $option, $default = null, $errorMessage = false, $errorNo = 0) {
			if ($this->settings === null) {
				$this->getInstallConfig(isset($errorMessage) && $errorMessage !== false);
			}

			if (isset($this->settings[$section][$option])) {
				return $this->settings[$section][$option];
			}

			if ($errorMessage) {
				throw new Exception($errorMessage, $errorNo);
			}
			return $default;
		}

		private function getInstallConfig($throw = true) {
			if ($this->settings !== null) {
				return $this->settings;
			}
			if ($this->installMode) {
				// В режиме установки ищем настройки рядом с инсталлятором
				$config_path = dirname(__FILE__) . '/install.ini';
			} else {
				// В режиме обновления - в корне сайта
				$config_path = INSTALLER_CURRENT_WORKING_DIR . '/install.ini';
			}

			if (!is_file($config_path)) {
				if ($throw) {
					throw new Exception('Не найден файл настроек для установки install.ini');
				}

				return false;
			}

			$this->settings = parse_ini_file($config_path, true);
		}

		private function checkDone($method) {
			return isset(self::$state[$method]) && self::$state[$method];
		}

		private function setDone($method, $done = true) {
			self::$state[$method] = $done;
			$this->saveState();
		}

		private function getComponentOffset($component) {
			return (isset(self::$state['@components']) && isset(self::$state['@components'][$component]))
				?
				(int) self::$state['@components'][$component]
				:
				0;
		}

		/** Загружает состояние установщика из файла */
		private function loadState() {
			$sf = $this->temporaryDirectory . umiInstallExecutor::STATE_FILE_NAME;

			if (file_exists($sf) && $c = file_get_contents($sf)) {
				self::$state = @unserialize($c);
			}

			if (!self::$state) {
				self::$state = [];
			}
		}

		/** Сохраняет состояние установщика в файл */
		private function saveState() {
			$sf = $this->temporaryDirectory . umiInstallExecutor::STATE_FILE_NAME;
			file_put_contents($sf, serialize(self::$state));
		}

		private function getParam($name) {
			return isset($this->params[$name]) ? $this->params[$name] : null;
		}

		private function setComponentOffset($component, $offset) {
			if (!isset(self::$state['@components']) || !is_array(self::$state['@components'])) {
				self::$state['@components'] = [];
			}

			self::$state['@components'][$component] = (int) $offset;
			$this->saveState();
		}

		/** Возвращает информацию о dummy-файле */
		private function getDummyInfo() {
			$ht = [];
			$ht['begin'] = '########## UMI.CMS - update begin ##########';
			$ht['end'] = '########### UMI.CMS - update end ###########';
			$ht['dummyname'] = 'dummy.php';
			$ht['allow_array'] = ['install.php', 'installer.php', 'smu/install.php', 'smu/installer.php', 'umi_smt.php'];
			return $ht;
		}

		/**
		 * Создает заглушку на время обновления
		 * @param string $dummyPath путь до файоа с заглушкой
		 */
		private function htCreateDummy($dummyPath) {
			$downloader = $this->getDownloader();
			$url = base64_decode('aHR0cDovL3d3dy5pbnN0YWxsLnVtaS1jbXMucnUvZmlsZXMvZHVtbXkuaHRtbA==');
			$downloader->saveRemoteFile($url, $dummyPath);
		}

		/** Добавляет запрещающие доступ инструкции в .htaccess на время обновления или установки. */
		private function setUpdateMode() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$ht = $this->getDummyInfo();
			$this->htCreateDummy(INSTALLER_CURRENT_WORKING_DIR . '/' . $ht['dummyname']);
			$dummy = [];

			if (is_array($ht['allow_array']) && 0 < count($ht['allow_array'])) {
				foreach ($ht['allow_array'] as $file) {
					$dummy[] = 'RewriteCond %{REQUEST_URI} !/' . $file . '$';
				}
			}

			$dummy[] = 'RewriteCond %{REQUEST_URI} !/' . $ht['dummyname'] . '$';
			$dummy[] = 'RewriteRule ^.*$ /' . $ht['dummyname'] . ' [L]';
			$htArray = [];

			if (file_exists(INSTALLER_CURRENT_WORKING_DIR . '/.htaccess')) {
				$htArray = $this->htGetCleanArray(INSTALLER_CURRENT_WORKING_DIR . '/.htaccess', $ht);
			}

			$result = [];
			$doInsert = true;

			if (count($htArray) > 0) {
				foreach ($htArray as $line) {
					$result[] = $line;

					if ($doInsert && preg_match('|^[ \t]*RewriteEngine|i', $line)) {
						$doInsert = false;
						$result[] = $ht['begin'];

						foreach ($dummy as $dLine) {
							$result[] = $dLine;
						}

						$result[] = $ht['end'];
					}
				}
			}

			$content = implode("\r\n", $result) . "\r\n";

			if ($doInsert) {
				$content .= $ht['begin'] . "\r\n";
				$content .= "RewriteEngine On\r\n";

				foreach ($dummy as $dLine) {
					$content .= $dLine . "\r\n";
				}

				$content .= $ht['end'] . "\r\n";
			}

			file_put_contents(INSTALLER_CURRENT_WORKING_DIR . '/.htaccess', $content);

			$this->setDone(__METHOD__);
			return true;
		}

		/** Отменяет блокирование для режима обновления */
		private function cleanUpdateMode() {
			$ht = $this->getDummyInfo();
			unlink(INSTALLER_CURRENT_WORKING_DIR . '/' . $ht['dummyname']);
			$htArray = $this->htGetCleanArray(INSTALLER_CURRENT_WORKING_DIR . '/.htaccess', $ht);
			file_put_contents(INSTALLER_CURRENT_WORKING_DIR . '/.htaccess', implode("\r\n", $htArray) . "\r\n");
			return true;
		}

		/**
		 * Удаляет из htaccess блок инструкций
		 * @param mixed $filename
		 * @param mixed $ht ('start_string', 'end_string')
		 * @return array
		 */
		private function htGetCleanArray($filename, $ht) {
			$content = file_get_contents($filename);
			$htArray = [];

			foreach (explode("\n", $content) as $htKey => $htLine) {
				$htArray[] = trim($htLine);
			}

			if (in_array($ht['begin'], $htArray) && in_array($ht['end'], $htArray)) {
				$clear = false;

				foreach ($htArray as $htKey => $htLine) {
					if ($htLine == $ht['begin']) {
						$clear = true;
						unset($htArray[$htKey]);
						continue;
					}

					if ($htLine == $ht['end']) {
						unset($htArray[$htKey]);
						break;
					}

					if ($clear) {
						unset($htArray[$htKey]);
						continue;
					}
				}
			}

			return $htArray;
		}

		public function run($currentStep = 'install-run', $cli = true, $params = []) {
			$this->currentStep = $currentStep;
			$this->cliMode = $cli;
			$this->params = $params;
			$result = false;
			$error = null;

			if (!$this->cliMode // Запрос выполнен из браузера
				&& $currentStep != 'check-user' // И это не проверка прав пользователя
				&& !$this->isSV() // не sv
			) {
				if (!$this->installMode) {
					$error = ['mess' => 'Недостаточно прав для выполнения обновлений!', 'no' => '15001'];
					$currentStep = 'error';
				} elseif (file_exists('./installed') && is_file('./installed')) {
					$error = ['mess' => 'Система уже установлена.', 'no' => '15002'];
					$currentStep = 'error';
				}
			}

			try {
				switch ($currentStep) {
					case 'error':
						throw new Exception($error['mess'], $error['no']);

					// Проверяет права пользователя на установку обновлений
					case 'check-user':
						$result = $this->checkUser();
						break;

					// Проверяет, есть ли доступные обновления для текущей ревизии
					case 'check-update':
						$result = $this->checkUpdate();
						break;

					case 'check-installed':
						$result = $this->checkInstalled();
						break;

					// сохраняет переданные данные инсталляции
					case 'save-settings':
						$result = $this->saveSettings();
						break;

					// точка входа, запускает шаги инсталляции
					case 'install-run':
						$result = $this->runInstaller();
						break;

					// получаем инструкции для обновления с сервера
					case 'get-update-instructions':
						$result = $this->downloadUpdateInstructions();
						break;

					// скачиваем компоненты
					case 'download-components':
						$result = $this->downloadComponents();
						break;

					case 'download-component':
						$result = $this->downloadComponent();
						break;

					// распаковываем компоненты
					case 'extract-components':
						$result = $this->extractComponents();
						break;

					case 'extract-component':
						$result = $this->extractComponent();
						break;

					// проверяем компоненты на целостность
					case 'check-components':
						$result = $this->checkComponents();
						break;

					case 'check-component':
						$result = $this->checkComponent();
						break;

					// обновляем инсталлятор
					case 'update-installer':
						$result = $this->updateInstaller();
						break;

					// сохраняет перезаписываемое состояние
					case 'save-overwritable-state':
						$result = $this->saveOverwritableState();
						break;

					case 'restore-supervisor':
						$result = $this->restoreSupervisor();
						break;

					// обновляем структуру базы данных
					case 'update-database':
						$result = $this->updateDatabaseStructure();
						break;

					// конфигурируем установленную систему
					case 'configure':
						$result = $this->configure();
						break;

					// установка дефолтного значения домена из полученного пакета
					case 'set-default-domain':
						$result = $this->setDefaultDomain();
						break;

					case 'download-service-package':
						$result = $this->downloadServicePackage();
						break;

					case 'extract-service-package':
						$result = $this->extractServicePackage();
						break;

					case 'write-initial-configuration':
						$result = $this->writeInitialConfiguration();
						break;

					// Запускаем тесты системы
					case 'run-tests':
						$result = $this->runTests();
						break;

					// устанавливаем компоненты
					case 'install-components':
						$result = $this->installComponents();
						break;

					case 'install-component':
						$result = $this->installComponent();
						break;

					// демосайт
					case 'download-demosite':
						$result = $this->downloadDemosite();
						break;

					case 'extract-demosite':
						$result = $this->extractDemosite();
						break;

					case 'install-demosite':
						$result = $this->installDemosite();
						break;

					case 'check-demosite':
						$result = $this->checkDemosite();
						break;

					case 'execute-component-manifests': {
						$result = $this->executeComponentManifests();
						break;
					}

					case 'execute-component-manifest': {
						$result = $this->executeComponentManifest();
						break;
					}

					case 'execute-migrate-manifests': {
						$result = $this->executeMigrateManifests();
						break;
					}

					case 'cleanup':
						$result = $this->cleanup();
						break;

					case 'clear-cache':
						$result = $this->clearCache();
						break;

					case 'get-solution-list':
						$result = $this->getDemositeList();
						break;

					case 'set-update-mode':
						$result = $this->setUpdateMode();
						break;

					default:
						throw new Exception('Неизвестный шаг установки "' . $currentStep . '" для установки');
				}
			} catch (Exception $e) {
				if ($this->isGuiInstallMode()) {
					self::returnErrorXML($e);
				}

				if (!$this->getParam('guiUpdate') && ($this->cliMode || $currentStep !== 'install-run')) {
					throw $e;
				}

				self::returnErrorXML($e);
			}

			if ($this->cliMode) {
				return 1;
			}

			$possibleSteps = [
				'install-run',
				'save-settings',
				'download-service-package',
				'extract-service-package',
				'write-initial-configuration',
				'run-tests',
				'get-update-instructions',
				'download-components',
				'extract-components',
				'check-components',
				'update-database',
				'install-components',
				'configure',
				'download-demosite',
				'extract-demosite',
				'check-demosite',
				'install-demosite',
				'set-default-domain',
				'clear-cache'
			];

			if (in_array($currentStep, $possibleSteps) || $this->getParam('guiUpdate')) {
				self::returnResultXML($result);
			} else {
				return $result;
			}
		}

		/** Скачивает с сервера инструкции по обновлению и проверяет, доступна ли новая ревизия */
		private function checkUpdate() {
			if (is_file($this->temporaryDirectory . '/update-instructions.xml')) {
				unlink($this->temporaryDirectory . '/update-instructions.xml');
			}

			// Сбрасываем загруженное из файла состояние обновления и удаляем файл
			self::$state = [];

			if (is_file($this->temporaryDirectory . umiInstallExecutor::STATE_FILE_NAME)) {
				unlink($this->temporaryDirectory . umiInstallExecutor::STATE_FILE_NAME);
			}

			$this->downloadUpdateInstructions();

			$xml = new DOMDocument();
			$xml->load($this->temporaryDirectory . '/update-instructions.xml');
			$xpath = new DOMXPath($xml);
			$package = $xpath->query('/package')->item(0);

			if (!$this->installMode) {
				$this->includeCore();
				$regedit = Service::Registry();
				$key = $regedit->get('//settings/keycode');
				$domainKey = $package->getAttribute('domain_key');

				if ($key != $domainKey) {
					$regedit->set('//settings/keycode', $domainKey);
				}
			}

			if ($package->getAttribute('last-revision') == $package->getAttribute('client-revision')) {
				// опечатка в слове available оставлена для обратной совместимости
				throw new Exception('Updates not avaiable.');
			}

			if (!$this->installMode && !INSTALLER_CLI_MODE && $package->getAttribute('client-revision') > 18080) {
				// опечатка в слове available оставлена для обратной совместимости
				throw new Exception('Updates avaiable.');
			}

			$this->flushLog('Updates available.');

			return true;
		}

		private function isSV() {
			@session_start();
			@header_remove('Set-Cookie');
			$isSv = isset($_SESSION['user_is_sv']) && $_SESSION['user_is_sv'];
			@session_commit();
			return $isSv;
		}

		private function checkUser() {
			if ($this->isSV()) {
				$this->flushLog('Права на выполнение обновления подтверждены.');
			} else {
				throw new Exception('Недостаточно прав для выполнения обновлений!');
			}

			return true;
		}

		/**
		 * Загружает указанный xml документ
		 * @param mixed $filename
		 * @return DOMDocument
		 * @throws Exception
		 */
		private function loadDomDocument($filename = '') {
			$dom = new DOMDocument();
			if (!$dom->load($filename)) {
				throw new Exception('Не удалось загрузить xml документ.');
			}
			return $dom;
		}

		private function getDemositeList() {
			header('Content-Type: text/xml; charset=utf-8');
			echo $this->getDownloader()->getDemositesList()->saveXML();
			return null;
		}

		/**
		 * Сохраняет переданные настройки в файл install.ini
		 * @param array $new новые значения настроек
		 * @return bool
		 * @throws Exception
		 */
		private function saveSettings(array $new = []) {
			if ($this->isGuiInstallMode()) {
				$this->checkSelf();
			}

			$current = [];
			if (file_exists(INSTALLER_CURRENT_WORKING_DIR . '/install.ini')) {
				$current = (array) parse_ini_file(INSTALLER_CURRENT_WORKING_DIR . '/install.ini', true);
			}

			$current = $this->extendSettings($current, $new);
			$serialized = $this->serializeSettings($current);

			if (!file_put_contents(INSTALLER_CURRENT_WORKING_DIR . '/install.ini', $serialized)) {
				throw new Exception('Не удается сохранить файл install.ini, проверьте права доступа.', 13049);
			}

			return true;
		}

		/**
		 * Расширяет текущие настройки новыми значениями в зависимости от этапа установки/обновления
		 * @param array $current текущие настройки
		 * @param array $new новые настройки
		 * @return array
		 * @throws Exception
		 */
		private function extendSettings(array $current, array $new) {
			$isUpdateMode = (!$this->installMode && count($new) > 0);

			if ($isUpdateMode) {
				foreach ($new as $name => $value) {

					if (in_array($name, ['isUsingSsl', 'favicon'])) {
						$current['OVERWRITABLE'][$name] = $value;
						continue;
					}

					$current['SUPERVISOR'][$name] = $value;
				}
			} elseif ($this->getParam('demosite')) {
				$current['DEMOSITE']['name'] = $this->getParam('demosite');
			} elseif ($this->getParam('sv_login')) {
				if (!$this->getParam('sv_password')) {
					throw new Exception('Не указан пароль супервайзера');
				}

				if ($this->getParam('sv_password') != $this->getParam('sv_password2')) {
					throw new Exception('Пароли не совпадают!');
				}

				if (mb_strlen($this->getParam('sv_login')) < 2) {
					throw new Exception('Имя пользователя должно быть не менее двух символов');
				}

				if ($this->getParam('sv_login') == $this->getParam('sv_password')) {
					throw new Exception('Пароль не должен совпадать с логином');
				}

				$current['SUPERVISOR']['login'] = $this->getParam('sv_login');
				$current['SUPERVISOR']['password'] = $this->getParam('sv_password');
				$current['SUPERVISOR']['email'] = $this->getParam('sv_email');

				$this->cleanUpdateMode();
			} else {
				$current['LICENSE']['domain'] = $_SERVER['HTTP_HOST'];
				$current['LICENSE']['ip'] =
					isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : str_replace("\\", '', $_SERVER['DOCUMENT_ROOT']);
				$current['LICENSE']['key'] = $this->getParam('license_key');

				/**
				 * Возможные сочетания:
				 * host
				 * host:port
				 * host:socket
				 * :socket
				 */
				$host = $this->getParam('db_host');

				if ($host === null) {
					$host = 'localhost';
					$port = '';
				} elseif (mb_strpos($host, ':') !== false) {
					list($host, $port) = explode(':', $host);
				} else {
					$port = '';
				}

				$current['DB']['host'] = trim($host);
				$current['DB']['port'] = trim($port);
				$current['DB']['user'] = $this->getParam('db_login');
				$current['DB']['password'] = $this->getParam('db_password');
				$current['DB']['dbname'] = $this->getParam('db_name');

				$this->cleanup();
			}

			return $current;
		}

		/**
		 * Сериализует настройки в строку для записи в файл
		 * @param array $settings настройки
		 * @return string
		 */
		private function serializeSettings(array $settings) {
			$result = '';

			/** @var array $fieldList */
			foreach ($settings as $groupName => $fieldList) {
				$result .= "[{$groupName}]\n";

				foreach ($fieldList as $name => $value) {
					$result .= "{$name} = \"" . addslashes($value) . "\"\n";
				}
			}

			return $result;
		}

		private function closeByHtaccess($dir) {
			return is_file($dir . '/.htaccess') || file_put_contents($dir . '/.htaccess', 'Deny from all');
		}

		public static function returnResultXML($done) {
			$document = new DOMDocument('1.0', 'utf-8');
			$root = $document->createElement('result');
			$document->appendChild($root);

			$install = $document->createElement('install');
			$state = $document->createAttribute('state');
			$state->value = $done ? 'done' : 'inprogress';
			$install->appendChild($state);
			$root->appendChild($install);
			$root->appendChild(self::getLogXML($document));

			self::printXml($document->saveXML());
		}

		public static function returnErrorXML(Exception $e) {
			$document = new DOMDocument('1.0', 'utf-8');
			$root = $document->createElement('result');
			$document->appendChild($root);

			$error = $document->createElement('error');
			$message = $document->createAttribute('message');
			$message->value = $e->getMessage();
			$error->appendChild($message);
			$error->appendChild(self::getBacktraceXML($document, $e->getTrace()));
			$root->appendChild($error);
			$root->appendChild(self::getLogXML($document));

			self::printXml($document->saveXML());
		}

		/**
		 * Выводит xml в буффер
		 * @param string $xml
		 * @throws coreException
		 */
		private static function printXml($xml) {
			$isCalledFromBrowser = defined('INSTALLER_CLI_MODE') && INSTALLER_CLI_MODE === false;
			$itIsUpdating = defined('INSTALL_MODE') && INSTALL_MODE === false;
			$coreLoaded = class_exists('outputBuffer');

			/**
			 * Используются deprecated методы для обратной совместимости в состояниях, когда ядро обновляемой версии
			 * еще старое, а установщик уже новый.
			 */
			if ($coreLoaded && $isCalledFromBrowser && $itIsUpdating) {
				/** @var outputBuffer $buffer */
				$buffer = outputBuffer::current('HTTPOutputBuffer');

				if (is_callable([$buffer, 'disableEvents'])) {
					$buffer->disableEvents();
				}

				$buffer->contentType('text/xml');
				$buffer->charset('utf-8');
				$buffer->push($xml);
				$buffer->end();
			}

			header('Content-Type: text/xml; charset=utf-8');
			echo $xml;
			die();
		}

		private static function getBacktraceXML(DOMDocument $document, $trace) {
			$backtrace = $document->createElement('backtrace');
			foreach ($trace as $callInfo) {
				$call = $document->createElement('call');
				$arguments = '';
				$all = [];
				foreach ($callInfo['args'] as $arg) {
					switch (gettype($arg)) {
						case 'string' :
							$all[] = "\"{$arg}\"";
							break;
						case 'boolean':
							$all[] = $arg ? 'true' : 'false';
							break;
						case 'array'  :
							$all[] = 'array';
							break;
						case 'object' :
							$all[] = get_class($arg);
							break;
						default :
							$all[] = (string) $arg;
					}
					$arguments = implode(', ', $all);
				}
				$callString = $callInfo['class'] .
					$callInfo['type'] .
					$callInfo['function'] .
					"({$arguments})";
				$cdata = $document->createCDATASection($callString);
				$call->appendChild($cdata);
				$backtrace->appendChild($call);
			}
			return $backtrace;
		}

		private static function getLogXML(DOMDocument $document) {
			$log = $document->createElement('log');
			foreach (self::$log as $messageText) {
				$message = $document->createElement('message', $messageText);
				$log->appendChild($message);
			}
			return $log;
		}

		/**
		 * Создает и инициализирует umiUpdateDownloader
		 * @return umiUpdateDownloader
		 */
		private function getDownloader() {
			if ($this->installMode) { // Режим установки
				$key = $this->getConfigOption('LICENSE', 'key', null, 'В install.ini не указан лицензионный ключ.');
				$host =
					$this->getConfigOption('LICENSE', 'domain', null, 'В install.ini не указано имя домена для установки.');
				$ip = $this->getConfigOption('LICENSE', 'ip', 'В install.ini не указан ip адрес сервера.');
				$current_revision = 'last';
			} else { // Режим обновления, в нем мы всегда в папке smu
				$this->includeCore();

				if (INSTALLER_CLI_MODE) {
					$ip = $this->getConfigOption('LICENSE', 'ip', 'В install.ini не указан ip адрес сервера.');
				} else {
					$ip = $_SERVER['SERVER_ADDR'];
				}

				$umiRegistry = Service::Registry();
				$umiRegistry->resetCache();
				$key = $umiRegistry->get('//settings/keycode');
				$current_revision = $umiRegistry->get('//settings/system_build') ?: 'last';
				$host = Service::DomainCollection()->getDefaultDomain()->getHost();
			}

			$downloader = new umiUpdateDownloader($this->temporaryDirectory, $key, $host, $ip, $current_revision);
			return $downloader;
		}

		/** Скачивает сервисный пакет */
		private function downloadServicePackage() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$this->flushLog('Загрузка сервисного компонента...');

			$downloader = $this->getDownloader();
			$downloader->downloadServiceComponent('installer');
			$this->flushLog('Сервисный компонент загружен.');
			$this->setDone(__METHOD__);
			return true;
		}

		/** Распаковывает сервисный пакет */
		private function extractServicePackage() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$result = $this->execSubProcess('extract-component', ['component' => 'installer.service']);
			$this->setDone(__METHOD__);
			return $result;
		}

		/** Записывает начальную конфигурацию */
		private function writeInitialConfiguration() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$core = $this->temporaryDirectory . '/installer.service/umi.phar.php';

			if (!file_exists($core)) {
				throw new Exception('В сервисном пакете отсутствует umi.phar.php!');
			}

			if (!is_dir($this->temporaryDirectory . '/core/smu') &&
				!mkdir($this->temporaryDirectory . '/core/smu', 0777, true)) {
				throw new Exception('Не удается создать временную директорию для ядра!');
			}

			if (!copy($core, $this->temporaryDirectory . '/core/smu/umi.phar.php')) {
				throw new Exception('Не удается скопировать umi.phar.php!');
			}

			chmod($this->temporaryDirectory . '/core/smu/umi.phar.php', PHP_FILES_ACCESS_MODE);

			$configSource = $this->temporaryDirectory . '/installer.service/config.ini.original';

			if (!file_exists($configSource)) {
				throw new Exception('В сервисном пакете отсутствует файл с примером конфигурации системы.');
			}

			if ($this->installMode || (!file_exists('config.ini') || !is_file('config.ini'))) {
				// В режиме установки просто записываем config.ini
				$host = $this->getConfigOption('DB', 'host', 'localhost');
				$port = $this->getConfigOption('DB', 'port', '');
				$host = trim($host);
				$port = trim($port);

				$login = $this->getConfigOption('DB', 'user', 'root');
				$password = $this->getConfigOption('DB', 'password', '');
				$dbname = $this->getConfigOption('DB', 'dbname', null, 'В install.ini не указано имя базы данных.');

				$config = file_get_contents($configSource);
				$config = str_replace('%db-core-host%', $host, $config);
				$config = str_replace('%db-core-port%', $port, $config);
				$config = str_replace('%db-core-login%', $login, $config);
				$config = str_replace('%db-core-password%', $password, $config);
				$config = str_replace('%db-core-name%', $dbname, $config);
			} else {
				// В режиме обновления - обновляем, соответственно
				$new_config = parse_ini_file($configSource, true);
				$old_config = parse_ini_file('config.ini', true);

				foreach ($new_config as $section => $s_values) {
					foreach ($s_values as $s_value => $p_value) {
						if (is_array($p_value)) {
							foreach ($p_value as $val) {
								if (!isset($old_config[$section][$s_value]) ||
									!in_array($val, $old_config[$section][$s_value])) {
									$old_config[$section][$s_value][] = "{$val}";
								}
							}
						} else {
							if (!isset($old_config[$section][$s_value])) {
								$old_config[$section][$s_value] = "{$new_config[$section][$s_value]}";
							}
						}
					}
				}

				// Формируем контент результата
				$config = '';
				ksort($old_config);
				foreach ($old_config as $section => $s_values) {
					ksort($old_config[$section]);
					$config .= "[{$section}]\n";
					foreach ($s_values as $s_value => $p_value) {
						if (is_array($p_value)) {
							sort($old_config[$section][$s_value]);
							foreach ($p_value as $val) {
								$config .= "{$s_value}[] = \"{$val}\"\n";
							}
						} else {
							$config .= "{$s_value} = \"{$p_value}\"\n";
						}
					}
					$config .= "\n";
				}
			}

			file_put_contents('config.ini', $config);
			$this->setDone(__METHOD__);
			return $this->cliMode;
		}

		// Записывает корневой htaccess в режиме установки
		private function writeHtaccess() {
			$htaccess = $this->temporaryDirectory . '/installer.service/.htaccess.original';
			$new_htaccess = './.htaccess';
			$begin = '####################### UMI_CMS_HTACCESS_BEGIN ###########################';
			$end = '######################## UMI_CMS_HTACCESS_END ############################';

			if (!file_exists($htaccess)) {
				throw new Exception('В сервисном пакете отсутствует корневой файл настроек .htaccess');
			}

			if (file_exists($new_htaccess)) {
				$old_ht = file_get_contents($new_htaccess);
				if (($b = stripos($old_ht, $begin)) !== false && ($e = stripos($old_ht, $end)) !== false) {
					$old = mb_substr($old_ht, 0, $b) . mb_substr($old_ht, $e + mb_strlen($end));
				} else {
					$old = $old_ht;
				}
				copy('./.htaccess', './.htaccess_old');
				if (!file_put_contents(
					'./.htaccess',
					$old . "\r\n" . $begin . "\r\n" . file_get_contents($htaccess) . "\r\n" . $end
				)) {
					throw new Exception('Не удается записать .htaccess, проверьте разрешения!');
				}
			} else {
				if (!file_put_contents($new_htaccess, $begin . "\r\n" . file_get_contents($htaccess) . "\r\n" . $end)) {
					throw new Exception('Не удается записать .htaccess, проверьте разрешения!');
				}
			}
		}

		/** Выполняет тесты совместимости */
		private function runTests() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->flushLog('Проверка системных требований...');
			$tests = $this->temporaryDirectory . '/installer.service/testhost.php';

			if (!is_file($tests)) {
				throw new Exception('Не удается найти запрошенный файл: ' . $tests);
			}

			include $tests;

			if ($this->getParam('guiUpdate') && is_file('config.ini')) {
				$ini = parse_ini_file('config.ini');
				$host = $ini['core.host'];
				$port = $ini['core.port'];
				$login = $ini['core.login'];
				$password = $ini['core.password'];
				$dbname = $ini['core.dbname'];
				$domain = $_SERVER['HTTP_HOST'];
			} else {
				$host = $this->getConfigOption('DB', 'host', 'localhost');
				$port = $this->getConfigOption('DB', 'port', '');
				$login = $this->getConfigOption('DB', 'user', 'root');
				$password = $this->getConfigOption('DB', 'password', '');
				$dbname = $this->getConfigOption('DB', 'dbname', null, 'В install.ini не указано имя базы данных.');
				$domain = $this->getConfigOption('LICENSE', 'domain');
			}

			$host = trim($host);
			$port = trim($port);
			$host .= ($port === '') ? '' : (':' . $port);

			$tests = new testHost([], $domain);
			$tests->setConnect($host, $login, $password, $dbname);
			$errors = $tests->getResults();

			if (count($errors) > 0) {
				foreach ($errors as $key => $value) {
					if ($value[1] == 1) {
						throw new Exception(
							' Cервер не соответствует системным требованиям для установки UMI.CMS. Подробное описание ошибки и способы её устранения доступны по ссылке <a href="http://errors.umi-cms.ru/' .
							$value[0] . '/" target="_blank">http://errors.umi-cms.ru/' . $value[0] . '/</a>'
						);
					}

					$this->flushLog(
						'Ошибка #' . $value[0] .
						' Сервер не соответствует системным требованиям для установки UMI.CMS. Подробная информация по ссылке http://errors.umi-cms.ru/' .
						$value[0] . '/'
					);
				}
			}

			$this->flushLog('завершено.');
			$this->setDone(__METHOD__);
			return true;
		}

		/** Скачивает changelog и инструкции для установки/обновления с сервера */
		private function downloadUpdateInstructions() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$this->flushLog('Загрузка инструкций по обновлению...');
			if (is_file($this->temporaryDirectory . '/update-instructions.xml')) {
				unlink($this->temporaryDirectory . '/update-instructions.xml');
			}

			$downloader = $this->getDownloader();
			$downloader->downloadUpdateInstructions();
			$this->flushLog('Инструкции загружены.');
			$this->setDone(__METHOD__);
			return $this->cliMode;
		}

		/** Скачивает все доступные компоненты */
		private function downloadComponents() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->setUpdateMode();

			$instructions = $this->temporaryDirectory . '/update-instructions.xml';
			$doc = new DOMDocument('1.0', 'utf-8');

			if (!$doc->load($instructions)) {
				throw new Exception('Не удается загрузить инструкции по обновлению');
			}

			$xpath = new DOMXPath($doc);

			$components = $xpath->query('//package/component[not(@downloaded)]');

			foreach ($components as $component) {
				$name = $component->getAttribute('name');

				$this->execSubProcess('download-component', ['component' => $name]);

				$component->setAttribute('downloaded', true);
				$doc->save($instructions);

				if (!$this->cliMode) {
					return false;
				}
			}

			$this->flushLog('Все компоненты загружены.');
			$this->setDone(__METHOD__);
			return true;
		}

		/** Скачивает компонент с сервера */
		private function downloadComponent() {
			$name = isset($this->params['component']) ? trim($this->params['component']) : '';

			if ($name === '') {
				throw new Exception('Отсутствует имя компонента (пример: installer.php  --component=core).');
			}

			$filePath = isset($this->params['fname']) ? trim($this->params['fname']) : $this->getComponentPath($name);

			if ($filePath === '') {
				throw new Exception('Отсутствует имя файла компонента (пример: installer.php  --fname=core.tar).');
			}

			$this->flushLog("Загрузка компонента {$name}...");

			$downloader = $this->getDownloader();

			$url = $downloader->buildUrl('get-component', ['component' => $name]);

			$downloader->saveRemoteFile($url, $filePath);

			$this->flushLog("Компонент {$name} был загружен.");

			return true;
		}

		/**
		 * Возвращает путь до файла компонента
		 * @param string $name название компонента
		 * @return string
		 */
		private function getComponentPath($name) {
			return $this->temporaryDirectory . $name . '.tar';
		}

		/** Распаковывает скачанные ранее компоненты */
		private function extractComponents() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$instructions = $this->temporaryDirectory . '/update-instructions.xml';
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception('Не удается загрузить инструкции по обновлению');
			}
			$xpath = new DOMXPath($doc);
			$components = $xpath->query('//package/component[not(@extracted)]');
			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				$result = $this->execSubProcess('extract-component', ['component' => $name]);
				if ($result) {
					$component->setAttribute('extracted', true);
					$doc->save($instructions);
				}
				if (!$this->cliMode) {
					return false;
				}
			}
			$this->flushLog('Все компоненты были распакованы.');
			$this->setDone(__METHOD__);
			return $this->cliMode;
		}

		/** Распаковывает указанный компонент */
		private function extractComponent() {
			$name = isset($this->params['component']) ? trim($this->params['component']) : '';
			if ($name === '') {
				throw new Exception('Отсутствует имя компонента (пример: installer.php  --component=core).');
			}

			$this->flushLog("Распаковка компонента {$name}...");

			$cwd = getcwd();
			$extract_dir = $this->temporaryDirectory . '/' . $name;
			if (!is_dir($extract_dir)) {
				mkdir($extract_dir);
			}

			chdir($extract_dir);
			$extracter = new umiTarExtracter('../' . $name . '.tar');
			$extracter->extractFiles();
			chdir($cwd);

			unlink($this->temporaryDirectory . $name . '.tar');

			$this->flushLog("Компонент {$name} был распакован.");
			return true;
		}

		/** Проверяет распакованные компоненты на целостность */
		private function checkComponents() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$instructions = $this->temporaryDirectory . '/update-instructions.xml';
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception('Не удается загрузить инструкции по обновлению');
			}
			$xpath = new DOMXPath($doc);
			$components = $xpath->query('//package/component[not(@checked)]');
			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				$result = $this->execSubProcess('check-component', ['component' => $name]);
				if ($result) {
					$component->setAttribute('checked', true);
					$doc->save($instructions);
				}
				if (!$this->cliMode) {
					return false;
				}
			}
			$this->flushLog('Все компоненты были проверены.');
			$this->setDone(__METHOD__);
			return $this->cliMode;
		}

		/** Скачивает демосайт с сервера */
		private function downloadDemosite() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$name = $this->getSolutionName();

			if ($name == '_blank') {
				return true;
			}

			$this->flushLog("Загрузка сайта \"{$name}\"...");

			$downloader = $this->getDownloader();
			$downloader->downloadDemosite($name);
			$this->flushLog("Сайт \"{$name}\" был загружен.");
			$this->setDone(__METHOD__);
			return $this->cliMode;
		}

		/**
		 * Возвращает название устанавливаемого решения
		 * @return string
		 */
		private function getSolutionName() {
			$name = isset($this->params['component']) ? trim($this->params['component']) : '';

			if (!$name) {
				$name = $this->getConfigOption('DEMOSITE', 'name', '_blank');
			}

			return $name;
		}

		/** Распаковывает демосайт */
		private function extractDemosite() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$name = $this->getSolutionName();

			if ($name == '_blank') {
				return true;
			}

			$result = $this->execSubProcess('extract-component', ['component' => $name]);
			$this->setDone(__METHOD__, $result);
			return $result && $this->cliMode;
		}

		/** Устанавливает демосайт */
		private function installDemosite() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$siteName = $this->getSolutionName();
			$installSiteStepName = 'INSTALL_SITE_' . $siteName;

			if ($siteName != '_blank' && !$this->checkDone($installSiteStepName)) {
				do {
					$offset = $this->getComponentOffset($siteName);

					if ($offset == 0) {
						$this->flushLog("Установка сайта {$siteName}...");
					}

					$params = [
						'component' => $siteName,
						'type' => 'demosite'
					];

					if (isset($this->params['domain_id'])) {
						$params['domain_id'] = $this->params['domain_id'];
					}

					$this->execSubProcess('install-component', $params);
					// Перезагружаем состояние
					$this->loadState();
					$new_offset = $this->getComponentOffset($siteName);

					if ($new_offset != $offset && !$this->cliMode) {
						return false;
					}
				} while ($offset != $new_offset);

				if (!$this->cliMode) {
					$this->setDone($installSiteStepName);
					return false;
				}
			}

			$this->flushLog("Сайт {$siteName} установлен.");
			$this->includeCore();
			$this->executeInstallScenario($siteName);

			do {
				$params = [];

				if (isset($this->params['manifest_config_name'])) {
					$params['manifest_config_name'] = $this->params['manifest_config_name'];
				}

				$executionComplete = $this->execSubProcess('execute-component-manifests', $params);

				if (!$executionComplete && !$this->cliMode) {
					return false;
				}
			} while (!$executionComplete);

			$this->flushLog("Сайт {$siteName} готов.");
			$this->setDone(__METHOD__);
			return true;
		}

		/** @deprecated */
		private function executeInstallScenario($siteName) {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->flushLog('Выполнение сценария установки (deprecated).');

			$installScenarioPath = $this->temporaryDirectory . "/{$siteName}/templates/{$siteName}/install.php";

			if (!file_exists($installScenarioPath)) {
				$this->setDone(__METHOD__);
				$this->flushLog('Не найден сценарий для сайта ' . $siteName);
				return false;
			}

			$this->flushLog('Выполнение сценария установки сайта ' . $siteName . '...');

			include_once $installScenarioPath;

			$scenarioClassName = $siteName . 'installScenario';

			if (!class_exists($scenarioClassName)) {
				throw new Exception('Сценарий для сайта ' . $siteName . ' некорректен');
			}

			$scenario = new $scenarioClassName($siteName);

			if (!$scenario instanceof iSiteInstallScenario) {
				throw new Exception('Сценарий для сайта ' . $siteName . ' некорректен');
			}

			if (get_parent_class($scenario) !== 'siteInstallScenario') {
				throw new Exception('Сценарий для сайта ' . $siteName . ' некорректен');
			}

			/** @var siteInstallScenario|iSiteInstallScenario $scenario */
			$scenario->runViaDomain($this->getDomainId());
			$messages = $scenario->getLogMessages();

			if (count($messages) > 0) {
				foreach ($messages as $message) {
					$this->flushLog($message);
				}
			}

			$this->flushLog('Выполнение сценария установки сайта ' . $siteName . ' завершено');
			$this->setDone(__METHOD__);
			return true;
		}

		/** Проверяет демосайт на целостность */
		private function checkDemosite() {
			if (!$this->installMode) {
				$this->setDone(__METHOD__);
				return true;
			}
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$name = $this->getConfigOption('DEMOSITE', 'name', '_blank');
			if ($name == '_blank') {
				return true;
			}

			$result = $this->execSubProcess('check-component', ['component' => $name]);
			$this->setDone(__METHOD__, $result);
			return $result && $this->cliMode;
		}

		/** Обновляет инсталлятор */
		private function updateInstaller() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->flushLog('Обновление инсталлятора...');

			if (defined('INSTALLER_DEBUG') && INSTALLER_DEBUG) {
				$this->flushLog('Not updated (debug mode).');
				$this->setDone(__METHOD__);
				return true;
			}

			$installer = $this->temporaryDirectory . '/core/smu/installer.php';

			if (!is_file($installer)) {
				throw new Exception('Инсталлятор не найден в пакете: ' . $installer);
			}

			if (!copy($installer, __FILE__)) {
				throw new Exception(
					'Не удалось обновить инсталлятор, возможно файл ' . __FILE__ . ' не доступен для записи.' . $installer
				);
			}

			chmod(__FILE__, PHP_FILES_ACCESS_MODE);

			$this->flushLog('Инсталлятор был обновлен.');
			$this->setDone(__METHOD__);
			return $this->cliMode;
		}

		/**
		 * Проверяет указанный компонент на целостность,
		 * проверяет возможность перезаписать файлы компонента у клиента
		 */
		private function checkComponent() {
			$name = isset($this->params['component']) ? trim($this->params['component']) : '';
			if ($name === '') {
				throw new Exception('Не передано имя компонента для проверки (пример: installer.php  --component=core).');
			}

			$this->flushLog("Проверка компонента \"$name\"...");
			$config = $this->temporaryDirectory . '/' . $name . "/{$name}.xml";

			if (!is_file($config)) {
				throw new Exception('Не удается загрузить конфигурацию компонента: ' . $config);
			}

			$r = new DomDocument();
			$r->load($config);

			$xpath = new DOMXPath($r);

			$notWritable = [];
			$dirs = $xpath->query('//directory');

			if ($dirs->length > 0) {
				foreach ($dirs as $dir) {
					$dir_path = $dir->getAttribute('path');
					if (is_dir($dir_path) && !is_writable($dir_path)) {
						$notWritable[] = $dir_path;
					}
				}
			}

			if ($this->installMode) {
				$files = $xpath->query('//file');
			} else {
				$files = $xpath->query('//file[not(@only_install)]');
			}

			if ($files->length > 0) {
				foreach ($files as $file) {
					$file_path = $file->getAttribute('path');
					if (is_file($file_path) && !is_writable($file_path)) {
						$notWritable[] = $file_path;
					}
					// packet
					$file_path = $this->temporaryDirectory . '/' . $name . '/' . $file->textContent;
					$file_hash = $file->getAttribute('hash');

					if (!is_file($file_path)) {
						throw new Exception("Файл \"{$file_path}\" не существует");
					}
					if ($file_hash != md5_file($file_path)) {
						throw new Exception("Файл \"{$file_path}\" загружен неверно (контрольная сумма: {$file_hash})");
					}
				}
			}

			if (count($notWritable)) {
				throw new Exception(
					"Невозможно обновить систему, пока следующие файлы и директории недоступны на запись:<br/>\n" .
					implode("<br/>\n", $notWritable)
				);
			}

			$this->flushLog("Компонент \"{$name}\" был проверен.");
			return true;
		}

		/** Подключает установленную систему */
		private function includeCore() {
			$core = INSTALLER_CURRENT_WORKING_DIR . '/standalone.php';

			if (!is_file($core)) {
				throw new Exception('Не найден standalone.php для обновления: ' . $core);
			}

			require_once $core;
			ini_set('display_errors', 0);
			error_reporting(0);

			$this->connection = ConnectionPool::getInstance()->getConnection();
		}

		/**
		 * Подключает phar архив с UMI.CMS последней версии
		 * @throws Exception
		 */
		private function includeUmiPhar() {
			if (!defined('CONFIG_INI_PATH')) {
				define('CONFIG_INI_PATH', INSTALLER_CURRENT_WORKING_DIR . '/config.ini');
			}

			$core = $this->temporaryDirectory . 'core/smu/umi.phar.php';

			if (!is_file($core)) {
				$core = $this->temporaryDirectory . 'installer.service/umi.phar.php';

				if (!is_file($core)) {
					throw new Exception('Не найден umi.phar.php для обновления: ' . $core);
				}
			}

			require_once $core;
			ini_set('display_errors', 0);
			error_reporting(0);

			$this->connection = ConnectionPool::getInstance()->getConnection();
		}

		/** Сохраняет перезаписываемое состояние, чтобы не потерять ее в процессе обновления */
		private function saveOverwritableState() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->flushLog('Сохранение перезаписываемого состояния...');
			$this->includeCore();

			/** @noinspection PhpDeprecationInspection (обратная совместимость со старой версией класса) */
			$domain = domainsCollection::getInstance()->getDefaultDomain();
			$isUsingSsl = method_exists($domain, 'isUsingSsl') ? $domain->isUsingSsl() : false;
			$favicon = method_exists($domain, 'getFavicon') ? $domain->getFavicon() : null;
			$state = [
				'login' => '',
				'md5pass' => '',
				'email' => '',
				'fname' => '',
				'lname' => '',
				'mname' => '',
				'isUsingSsl' => (int) $isUsingSsl,
				'favicon' => ($favicon instanceof iUmiImageFile) ? $favicon->getFilePath(true) : ''
			];

			$sv = $this->getSupervisor();

			if ($sv instanceof iUmiObject) {
				$state['login'] = $sv->getValue('login');
				$state['md5pass'] = $sv->getValue('password');
				$state['email'] = $sv->getValue('e-mail');
				$state['fname'] = $sv->getValue('fname');
				$state['lname'] = $sv->getValue('lname');
				$state['mname'] = $sv->getValue('father_name');
			}

			$this->saveSettings($state);

			$this->flushLog('завершено.');
			$this->setDone(__METHOD__);
			return false;
		}

		/**
		 * Возвращает объект супервайзера
		 * @return bool|iUmiObject
		 */
		private function getSupervisor() {
			$umiObjects = umiObjectsCollection::getInstance();
			$sv = $umiObjects->getObjectByGUID('system-supervisor');

			if (!$sv instanceof iUmiObject) {
				$sv = $umiObjects->getObject(14);
			}

			return $sv;
		}

		/** Восстанавливает сохраненные данные супервайзера */
		private function restoreSupervisor() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->flushLog('Восстановление данных супервайзера...');
			$this->includeCore();

			$login = $this->getConfigOption('SUPERVISOR', 'login');
			$md5pass = $this->getConfigOption('SUPERVISOR', 'md5pass');
			$email = $this->getConfigOption('SUPERVISOR', 'email');
			$firstName = $this->getConfigOption('SUPERVISOR', 'fname');
			$lastName = $this->getConfigOption('SUPERVISOR', 'lname');
			$fatherName = $this->getConfigOption('SUPERVISOR', 'mname');
			$sv = $this->getSupervisor();

			if ($sv instanceof iUmiObject) {
				$sv->setName($login);
				$sv->setValue('login', $login);
				$sv->setValue('password', $md5pass);
				$sv->setValue('e-mail', $email);
				$sv->setValue('fname', $firstName);
				$sv->setValue('lname', $lastName);
				$sv->setValue('father_name', $fatherName);
				$sv->commit();
			}

			$this->flushLog('завершено.');
			$this->setDone(__METHOD__);
			return false;
		}

		/**
		 * Удаляет таблицы, которые используются для UMI.CMS
		 * @param $path
		 * @return bool
		 */
		private function dropTables($path) {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$xml = new DOMDocument();
			$xml->load($path);

			$xpath = new DOMXPath($xml);
			$tables = $xpath->query('//table[@drop]');

			if ($tables->length == 0) {
				$this->flushLog('Удаление таблиц в базе данных...');
			}

			$tables = $xpath->query('//table[not(@drop)]');
			if ($tables->length == 0) {
				// Удалить атрибут, поставить статус завершено, вернуть false
				$tables = $xpath->query('//table');
				foreach ($tables as $table) {
					$table->removeAttribute('drop');
				}
				$xml->save($path);
				$this->flushLog('завершено');
				$this->setDone(__METHOD__);
				return false;
			}

			// Выключаем проверку внешних ключей
			$connection = ConnectionPool::getInstance()->getConnection();
			$query = 'SET foreign_key_checks = 0';
			$connection->query($query);

			foreach ($tables as $table) {
				$connection->query('DROP TABLE IF EXISTS `' . $table->getAttribute('name') . '`');
				$this->flushLog('Очистка таблицы ' . $table->getAttribute('name'));
				$table->setAttribute('drop', 1);
				$xml->save($path);
				return false;
			}
		}

		/**
		 * Сохраняет структуру базы данных в xml файл
		 * @param string $path - путь к файлу
		 * @return bool
		 */
		private function saveDatabaseStructure($path) {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$this->flushLog('Обновление структуры базы данных...');

			$converter = new dbSchemeConverter($this->connection);
			$converter->setDestinationFile($path);
			$converter->setMode('save');
			$statePath = INSTALLER_CURRENT_WORKING_DIR . '/sys-temp/';
			$converter->setStateDirectoryPath($statePath);
			$converter->run();

			$this->setDone(__METHOD__);
			return false;
		}

		private function updateDatabaseStructureFromFile($old_structure, $database_structure, $byParts = false) {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$converter = new dbSchemeConverter($this->connection);
			$converter->setDestinationFile($old_structure);
			$converter->setSourceFile($database_structure);
			$converter->setMode('restore', $byParts);
			$statePath = INSTALLER_CURRENT_WORKING_DIR . '/sys-temp/';
			$converter->setStateDirectoryPath($statePath);

			while (true) {
				$answer = $converter->run();
				$result = $converter->getConverterLog();
				foreach ($result as $message) {
					$this->flushLog($message);
				}
				if ($answer === true) {
					break;
				}
				return false;
			}

			$this->setDone(__METHOD__);
			return false;
		}

		/** Обновляет структуру базы данных */
		private function updateDatabaseStructure() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			if (!$this->installMode && !$this->isOverwritableStateSaved()) {
				$this->execSubProcess('save-overwritable-state');

				if (!INSTALLER_CLI_MODE) {
					return false;
				}
			}

			$this->includeUmiPhar();

			$database_structure = $this->temporaryDirectory . '/core/smu/database.xml';
			if (!is_file($database_structure)) {
				throw new Exception('Не удается найти структуру базы данных: ' . $database_structure);
			}

			// В режиме установки очищаем таблицы
			if ($this->installMode) {
				while (!$this->dropTables($database_structure)) {
					if (!INSTALLER_CLI_MODE) {
						return false;
					}
				}
			}

			$old_structure = str_replace('.xml', '_old.xml', $database_structure);

			// Сохраняем существующую структуру
			while (!$this->saveDatabaseStructure($old_structure)) {
				if (!INSTALLER_CLI_MODE) {
					return false;
				}
			}

			// Обновляем структуру базы данных
			if ($this->installMode) {
				// Режим установки
				$this->updateDatabaseStructureFromFile($old_structure, $database_structure);
			} else {
				$updateByParts = mainConfiguration::getInstance()->get('updates', 'update-database-by-parts');
				if ($updateByParts === null) {
					$updateByParts = true;
				}
				$updateByParts = (boolean) $updateByParts;

				if ($updateByParts) {
					while (!$this->updateDatabaseStructureFromFile($old_structure, $database_structure, $updateByParts)) {
						if (!INSTALLER_CLI_MODE) {
							return false;
						}
					}
				} else {
					$this->updateDatabaseStructureFromFile($old_structure, $database_structure);
				}
			}

			$this->flushLog('Структура базы данных обновлена.');
			$this->setDone(__METHOD__);
			return $this->cliMode;
		}

		/**
		 * Определяет было ли сохранено перезаписываемое состояние
		 * @return bool
		 */
		private function isOverwritableStateSaved() {
			if (!file_exists(INSTALLER_CURRENT_WORKING_DIR . '/install.ini')) {
				return false;
			}

			$settings = (array) parse_ini_file(INSTALLER_CURRENT_WORKING_DIR . '/install.ini', true);
			$login = isset($settings['SUPERVISOR']['login']) ? $settings['SUPERVISOR']['login'] : '';
			$password = isset($settings['SUPERVISOR']['password']) ? $settings['SUPERVISOR']['password'] : '';
			$md5pass = isset($settings['SUPERVISOR']['md5pass']) ? $settings['SUPERVISOR']['md5pass'] : '';
			$isUsingSsl = isset($settings['OVERWRITABLE']['isUsingSsl']) ? $settings['OVERWRITABLE']['isUsingSsl'] : null;
			$favicon = isset($settings['OVERWRITABLE']['favicon']) ? $settings['OVERWRITABLE']['favicon'] : null;
			return $login && ($password || $md5pass) && ($isUsingSsl !== null) && ($favicon !== null);
		}

		/** Запускает установку всех не установленных компонентов */
		private function installComponents() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$instructions = $this->temporaryDirectory . '/update-instructions.xml';
			$doc = new DOMDocument('1.0', 'utf-8');

			if (!$doc->load($instructions)) {
				throw new Exception('Не удается загрузить инструкции по обновлению');
			}

			$xpath = new DOMXPath($doc);
			/** @var DOMElement[] $componentList */
			$componentList = $xpath->query('//package/component[not(@installed)]');

			foreach ($componentList as $component) {
				$name = $component->getAttribute('name');

				if ($xpath->query('//package/component[@installed]')->length == 0 &&
					$this->getComponentOffset($name) == 0) {
					$this->flushLog('Установка компонентов...');
				}

				do {
					$old_offset = $this->getComponentOffset($name);
					if ($old_offset == 0) {
						$this->flushLog("Установка компонента {$name}...");
						// При первом запуске удаляем файлы, которые предназначены только для установки, и уже существуют
						if (!$this->installMode) {
							$component_config = $this->temporaryDirectory . "/{$name}/{$name}.xml";
							$source = new DomDocument();
							$source->load($component_config);
							$source_xpath = new DOMXPath($source);

							/** @var DOMElement[] $fileList */
							$fileList = $source_xpath->query('//file[@only_install]');

							foreach ($fileList as $file) {
								$path = $file->textContent;

								if (file_exists(INSTALLER_CURRENT_WORKING_DIR . $path)) {
									$file->parentNode->removeChild($file);
								} else {
									$file->removeAttribute('only_install');
								}
							}

							$source->save($component_config);
						}
					}

					$this->execSubProcess('install-component', [
						'component' => $name,
						'is_extension' => (bool) $component->getAttribute('is_extension')
					]);

					// Перезагружаем состояние
					$this->loadState();
					$new_offset = $this->getComponentOffset($name);

					if ($new_offset >= $old_offset + self::$splitBlockSize && !$this->cliMode) {
						return false;
					}
				} while ($new_offset >= $old_offset + self::$splitBlockSize);

				$component->setAttribute('installed', true);
				$doc->save($instructions);

				if (!$this->cliMode) {
					return false;
				}
			}

			if (!$this->installMode && !$this->execSubProcess('execute-migrate-manifests')) {
				return false;
			}

			$this->flushLog('Все компоненты были установлены.');
			$this->setDone(__METHOD__);
			return true;
		}

		/**
		 * Удаляет директорию полностью
		 *
		 * @param mixed $path - путь к директории
		 */
		private function delete($path) {
			if (is_dir($path)) {
				$objects = scandir($path);
				foreach ($objects as $object) {
					if ($object != '.' && $object != '..') {
						if (filetype($path . '/' . $object) == 'dir') {
							$this->delete($path . '/' . $object);
						} else {
							unlink($path . '/' . $object);
						}
					}
				}
				reset($objects);
				rmdir($path);
			}
		}

		/** Запускает установку текущего компонента */
		private function installComponent() {
			$componentName = isset($this->params['component']) ? trim($this->params['component']) : '';

			if ($componentName === '') {
				throw new Exception('Не передано имя компонента для установки (пример: installer.php  --component=core).');
			}

			$systemComponentList = $this->getSystemComponentList();

			if (in_array($componentName, $systemComponentList)) {
				$this->includeUmiPhar();
			} else {
				$this->includeCore();
			}

			$offset = $this->getComponentOffset($componentName);
			$componentConfig = $this->temporaryDirectory . "/{$componentName}/{$componentName}.xml";

			if (!is_file($componentConfig)) {
				throw new Exception("Не удается найти файл конфигурации компонента \"{$componentName}\": {$componentConfig}");
			}

			$importType = isset($this->params['type']) ? trim($this->params['type']) : 'system';
			$isDemoSite = ($importType === 'demosite');
			$source = $isDemoSite ? Service::UmiDumpSolutionPostfixBuilder()->run($componentName, $this->getDomainId()) : $importType;

			$importer = new xmlImporter($source);
			$importer->setRootDirPath(INSTALLER_CURRENT_WORKING_DIR);

			if ($this->installMode) {
				$importer->disableEvents();
			}

			if ($isDemoSite) {
				$updateIgnoreMode = false;
				$importer->setDemositeMode();
				$importer->setForcedDomainId($this->getDomainId());
				$importer->setIgnoreParentGroups(false);
			} else {
				$updateIgnoreMode = $this->installMode;
			}

			$importer->setUpdateIgnoreMode($updateIgnoreMode);
			$importer->setFilesSource($this->temporaryDirectory . "/{$componentName}/");

			$splitterType = ($importType == 'demosite' || !$this->installMode) ? 'transfer' : 'umiDump20';
			$splitterClass = $splitterType . 'Splitter';

			/** @var iUmiImportSplitter $splitter */
			$splitter = new $splitterClass($splitterType);
			$splitter->load($componentConfig, self::$splitBlockSize, $offset);

			$importer->loadXmlDocument($splitter->getDocument());
			$newOffset = $splitter->getOffset();
			$this->setComponentOffset($componentName, $newOffset);

			$importer->execute();
			file_put_contents(
				INSTALLER_CURRENT_WORKING_DIR . '/install.log',
				implode(PHP_EOL, $importer->getImportLog()),
				FILE_APPEND
			);

			$isReady = $newOffset < ($offset + self::$splitBlockSize);

			if ($isReady) {
				$isExtension = isset($this->params['is_extension']) ? $this->params['is_extension'] : false;

				if ($isExtension) {
					Service::ExtensionRegistry()
						->append($componentName);
				}

				if ($isDemoSite) {
					Service::SolutionRegistry()
						->append($componentName, $this->getDomainId());
				}

				$this->flushLog("Компонент {$componentName} был установлен.");
			} else {
				$this->flushLog("Установка компонента {$componentName}: шаг с {$offset} по " .
					($offset + self::$splitBlockSize));
			}

			return $isReady;
		}

		/**
		 * Возвращает идентификатор домена, в который проводится установка
		 * @return int
		 */
		private function getDomainId() {
			$defaultDomainId = domainsCollection::getInstance()
				->getDefaultDomain()
				->getId();
			return isset($this->params['domain_id']) ? $this->params['domain_id'] : $defaultDomainId;
		}

		/**
		 * Возвращает список названий системных компонентов
		 * @return array
		 */
		private function getSystemComponentList() {
			return [
				'core',
				'events',
				'menu',
				'news',
				'content',
				'blogs20',
				'forum',
				'comments',
				'vote',
				'webforms',
				'photoalbum',
				'faq',
				'dispatches',
				'catalog',
				'emarket',
				'banners',
				'users',
				'stat',
				'seo',
				'exchange',
				'social_networks',
				'tickets',
				'config',
				'data',
				'autoupdate',
				'backup',
				'search',
				'filemanager',
				'umiSettings',
				'umiSliders',
				'appointment',
				'umiRedirects',
				'umiNotifications'
			];
		}

		/** Устанавливает домен по умолчанию */
		private function setDefaultDomain() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->flushLog('Установка домена по умолчанию...');

			$doc = $this->loadDomDocument($this->temporaryDirectory . '/update-instructions.xml');
			$xpath = new DOMXPath($doc);

			$host = $xpath->evaluate('/package')->item(0)->getAttribute('host');

			$this->includeCore();

			$defaultDomain = Service::DomainCollection()->getDefaultDomain();
			$defaultDomain->setHost($host);
			$defaultDomain->commit();

			unset($doc, $xpath, $defaultDomain);
			$this->flushLog('завершено.');
			$this->setDone(__METHOD__);
			return true;
		}

		/** Конфигурирует установленную систему */
		private function configure() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->includeCore();

			$instructions = $this->temporaryDirectory . '/update-instructions.xml';
			$doc = new DOMDocument('1.0', 'utf-8');

			if (!$doc->load($instructions)) {
				throw new Exception('Не удается загрузить инструкции по обновлению');
			}

			if (!$this->installMode && !$this->execSubProcess('execute-component-manifests')) {
				return false;
			}

			/** @var DOMElement $package */
			$package = $doc->firstChild;
			$xpath = new DOMXPath($doc);
			/** @var DOMElement $version */
			$version = $xpath->evaluate("/package/component[@name='core']/version")->item(0);
			$login = $this->getConfigOption(
				'SUPERVISOR',
				'login',
				null,
				'В install.ini не указан логин супервайзера.'
			);

			$md5pass = $this->getConfigOption('SUPERVISOR', 'md5pass');
			$password = null;

			if ($md5pass === null) {
				$password = $this->getConfigOption(
					'SUPERVISOR',
					'password',
					null,
					'В install.ini не указан пароль супервайзера.'
				);
			}

			$firstName = (string) $this->getConfigOption('SUPERVISOR', 'fname', $package->getAttribute('owner_fname'));
			$lastName = (string) $this->getConfigOption('SUPERVISOR', 'lname', $package->getAttribute('owner_lname'));
			$fatherName = (string) $this->getConfigOption('SUPERVISOR', 'mname', $package->getAttribute('owner_mname'));
			$email = (string) $this->getConfigOption('SUPERVISOR', 'email', $package->getAttribute('owner_email'));
			$installTime = time();

			$umiRegistry = Service::Registry();
			$umiRegistry->set('//settings/keycode', $package->getAttribute('domain_key'));
			$umiRegistry->set('//settings/system_edition', $package->getAttribute('edition'));
			$umiRegistry->set('//settings/previous_edition', $package->getAttribute('edition'));
			$umiRegistry->set('//settings/system_version', $version ? $version->getAttribute('name') : '');
			$umiRegistry->set('//settings/system_build', $package->getAttribute('last-revision'));
			$umiRegistry->set('//settings/last_updated', time());

			/** Оставлено для обратной совместимости */
			$umiRegistry->set('//modules/autoupdate/system_build', $package->getAttribute('last-revision'));

			if ($this->installMode) {
				$umiRegistry->set('//settings/install', $installTime);
			}

			$umiObjects = umiObjectsCollection::getInstance();
			$umiRegistry->set('//modules/users/def_group', $umiObjects->getObjectIdByGUID('users-users-2374'));
			$umiRegistry->set('//modules/users/guest_id', $umiObjects->getObjectIdByGUID('system-guest'));
			if ($this->installMode) {
				$umiRegistry->set('//settings/create', $installTime);
			}

			$sv = $umiObjects->getObjectByGUID('system-supervisor');
			$sv->setName($login);
			$sv->login = $login;
			$sv->password = $md5pass === null ? md5($password) : $md5pass;

			if ($firstName !== '') {
				$sv->fname = $firstName;
			}

			if ($lastName !== '') {
				$sv->lname = $lastName;
			}

			if ($fatherName !== '') {
				$sv->father_name = $fatherName;
			}

			if ($email !== '') {
				$sv->setValue('e-mail', $email);
			}

			$sv->commit();

			$umiConfig = mainConfiguration::getInstance();
			$umiConfig->set('system', 'default-skin', 'modern');
			$umiConfig->save();

			if ($this->isGuiInstallMode()) {
				$this->cleanup();
				$this->setInstalled();
			}

			$isUsingSsl = $this->getConfigOption('OVERWRITABLE', 'isUsingSsl', false);
			$faviconPath = $this->getConfigOption('OVERWRITABLE', 'favicon', false);
			$favicon = Service::ImageFactory()
				->create($faviconPath);
			$favicon = ($favicon->getIsBroken()) ? null : $favicon;

			Service::DomainCollection()
				->getDefaultDomain()
				->setUsingSsl($isUsingSsl)
				->setFavicon($favicon)
				->commit();

			$this->deleteIllegalComponents();
			$this->writeHtaccess();

			if (!INSTALLER_CLI_MODE) {
				$this->deleteInstallIni();
			}

			$this->setDone(__METHOD__);
			return true;
		}

		/** Удаляет неподдерживаемые компоненты */
		private function deleteIllegalComponents() {
			/** @var autoupdate|AutoUpdateService $moduleAutoUpdates */
			$autoUpdate = cmsController::getInstance()
				->getModule('autoupdate');

			if (INSTALLER_CLI_MODE) {
				$ip = $this->getConfigOption('LICENSE', 'ip', 'В install.ini не указан ip адрес сервера.');
				Service::Request()->Server()->set('SERVER_ADDR', $ip);
			}

			if ($autoUpdate instanceof autoupdate) {
				$autoUpdate->resetSupportTimeCache();

				if ($autoUpdate->isMethodExists('deleteIllegalComponents')) {
					$autoUpdate->deleteIllegalComponents();
				} elseif ($autoUpdate->isMethodExists('deleteIllegalModules')) {
					$autoUpdate->deleteIllegalModules();
				} else {
					$oldServicePath = SYS_MODULES_PATH . 'autoupdate/ch_m.php';

					if (is_file($oldServicePath)) {
						include $oldServicePath;
						ch_remove_m_garbage();
					}
				}
			}
		}

		/**
		 * Выполняет миграции.
		 * Используется при обновлении системы, до импорта umiDump'а
		 * @return bool
		 */
		private function executeMigrateManifests() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->includeCore();
			$this->flushLog('Выполнение миграций.');

			$manifest = Service::ManifestFactory()
				->create('Migrate', [], iAtomicOperationCallbackFactory::COMMON)
				->execute();

			foreach ($manifest->getLog() as $message) {
				$this->flushLog($message);
			}

			$executionComplete = $manifest->isReady();

			if ($executionComplete) {
				$this->flushLog('Все миграции были выполнены.');
				$this->setDone(__METHOD__);
			}

			return $executionComplete;
		}

		/**
		 * Выполняет манифесты компонентов
		 * @return bool
		 */
		private function executeComponentManifests() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->includeCore();
			$this->fillInstructionWithSites();

			$instruction = $this->getUpdateInstructions();
			$xpath = new DOMXPath($instruction);
			/** @var DOMNodeList $components */
			$components = $xpath->evaluate("/package/component[@name != 'core']");
			$executionComplete = true;
			$this->flushLog('Выполнение манифестов...');

			for ($counter = 0; $counter < $components->length; $counter++) {
				$component = $components->item($counter);
				$module = $component->getAttribute('name');
				$isExecuted = $component->getAttribute('manifest-executed');

				if ($isExecuted) {
					continue;
				}

				if (is_string($isExecuted) && empty($isExecuted)) {
					$component->setAttribute('manifest-executed', false);
				}

				$params = [
					'component' => $module
				];

				if (isset($this->params['manifest_config_name'])) {
					$params['manifest_config_name'] = $this->params['manifest_config_name'];
				}

				if ($this->execSubProcess('execute-component-manifest', $params)) {
					$component->setAttribute('manifest-executed', true);
				}

				if (!$this->cliMode) {
					$executionComplete = false;
					break;
				}
			}

			$instruction->save($this->getUpdateInstructionsSource());

			if ($executionComplete) {
				$this->flushLog('Все манифесты были выполнены.');
				$this->setDone(__METHOD__);
			}

			return $executionComplete;
		}

		/**
		 * Выполняет манифест компонента
		 * @return bool
		 * @throws Exception
		 */
		private function executeComponentManifest() {
			$component = isset($this->params['component']) ? trim($this->params['component']) : '';

			if ($component === '') {
				throw new Exception('Не передано имя компонента для установки (пример: installer.php  --component=core).');
			}

			if (isset($this->params['manifest_config_name'])) {
				$configName = trim($this->params['manifest_config_name']);
			} else {
				$configName = $this->installMode ? 'install' : 'update';
			}

			$this->includeCore();
			$manifestFactory = Service::ManifestFactory();
			$this->flushLog("Поиск манифеста {$configName} компонента {$component}...");

			$blockedManifestList = (array) mainConfiguration::getInstance()
				->get('updates', 'disable-update-manifest');

			if (in_array($component, $blockedManifestList)) {
				$this->flushLog("Манифест {$configName} компонента {$component} заблокирован");
				return true;
			}

			try {
				$manifest = $manifestFactory
					->createByModule($configName, $component);
			} catch (Exception $e) {
				if (in_array($component, $this->getSystemComponentList())) {
					$this->flushLog("Манифест {$configName} компонента {$component} не найден");
					return true;
				}

				try {
					$manifest = $manifestFactory->createBySolution($configName, $component);
				} catch (Exception $e) {
					$this->flushLog("Манифест {$configName} решения {$component} не найден");
					return true;
				}
			}

			$this->flushLog("Выполнение манифеста {$configName} компонента {$component}...");

			if ($this->cliMode) {
				do {
					$manifest->execute();
				} while (!$manifest->isReady());
			} else {
				$manifest->execute();
			}

			foreach ($manifest->getLog() as $message) {
				$this->flushLog($message);
			}

			$this->flushLog("Манифест {$configName} компонента {$component} выполнен");

			return $manifest->isReady();
		}

		/** Добавляет в инструкцию по обновлению узлы с установленными шаблонами */
		private function fillInstructionWithSites() {
			$instruction = $this->getUpdateInstructions();
			$xpath = new DOMXPath($instruction);
			/** @var DOMNode $packageNode */
			$packageNode = $xpath->evaluate('/package')->item(0);
			$templateList = templatesCollection::getInstance()
				->getFullTemplatesList();

			foreach ($templateList as $template) {
				$templateName = $template->getName();

				if (!is_string($templateName)) {
					continue;
				}

				$templateName = trim($templateName);

				if (empty($templateName)) {
					continue;
				}

				/** @var DOMNodeList $templateNode */
				$templateNode = $xpath->evaluate("/package/component[@name = '${templateName}']");

				if ($templateNode->length > 0) {
					continue;
				}

				/** @var DOMElement $templateNode */
				$templateNode = $instruction->createElement('component');
				$templateNode->setAttribute('name', $templateName);

				$packageNode->appendChild($templateNode);
			}

			$instruction->save($this->getUpdateInstructionsSource());
		}

		/**
		 * Возвращает путь до файла с инструкциями по обновлению
		 * @return string
		 */
		private function getUpdateInstructionsSource() {
			return $this->temporaryDirectory . '/update-instructions.xml';
		}

		/**
		 * Возвращает инструкции по обновлениию
		 * @return DOMDocument
		 */
		private function getUpdateInstructions() {
			$instructionSource = $this->getUpdateInstructionsSource();
			$instruction = new DOMDocument('1.0', 'utf-8');
			$instruction->load($instructionSource);
			return $instruction;
		}

		/** Check this script for writable */
		private function checkSelf() {
			if (!is_writable(__FILE__)) {
				throw new Exception('Файл ' . __FILE__ . ' должен быть доступен на запись');
			}
			if (!is_dir($this->temporaryDirectory) && !mkdir($this->temporaryDirectory, 0777, true)) {
				throw new Exception("Не удается создать временную директорию \"{$this->temporaryDirectory}\".");
			}
			if (!is_writable($this->temporaryDirectory)) {
				throw new Exception(
					"Временная директория \"{$this->temporaryDirectory}\" не доступна для записи. Пожалуйста, проверьте разрешения."
				);
			}
			if (!$this->closeByHtaccess($this->temporaryDirectory . '/..')) {
				throw new Exception(
					"Не удается создать htaccess в директории \"{$this->temporaryDirectory}\". Пожалуйста, проверьте разрешения."
				);
			}

			return true;
		}

		/**
		 * Проверяет, установлена ли уже система
		 * @return bool
		 * @throws Exception
		 */
		private function checkInstalled() {
			if ($this->installMode && is_file('./installed')) {
				throw new Exception(
					'UMI.CMS уже установлена. Для принудительной установки удалите файл installed из корневой директории сервера.'
				);
			}

			return true;
		}

		/** System cleanup */
		private function cleanup() {
			if (file_exists($this->temporaryDirectory . '/runtime-cache/registry')) {
				unlink($this->temporaryDirectory . '/runtime-cache/registry');
			}

			// Удаляем файл состояния
			if (is_file($this->temporaryDirectory . umiInstallExecutor::STATE_FILE_NAME)) {
				unlink($this->temporaryDirectory . umiInstallExecutor::STATE_FILE_NAME);
			}

			self::$state = [];
			return true;
		}

		/**
		 * Очистка системного кеша.
		 *
		 */
		private function clearCache() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$this->flushLog('Очистка системного кеша...');
			$this->includeCore();

			$downloader = $this->getDownloader();
			$url = $downloader->buildUrl('get-modules-list');
			$modules = $downloader->getRemoteFile($url);

			$xml = new DOMDocument();
			if ($xml->loadXML($modules)) {
				$xpath = new DOMXPath($xml);
				$no_active = $xpath->query('//module[not(@active)]');

				if ($no_active->length > 0) {
					$regedit = Service::Registry();
					foreach ($no_active as $module) {
						$name = $module->getAttribute('name');
						if ($regedit->get("//modules/{$name}")) {
							$regedit->delete("//modules/{$name}");
						}
					}
				}
			}

			$cache = Service::CacheFrontend();
			$cache->flush();

			$this->flushLog('Завершено.');

			$this->cleanUpdateMode();

			$this->setDone(__METHOD__);
			return true;
		}

		private function deleteInstallIni() {
			if (file_exists(INSTALLER_CURRENT_WORKING_DIR . '/install.ini') &&
				is_file(INSTALLER_CURRENT_WORKING_DIR . '/install.ini')) {
				return unlink(INSTALLER_CURRENT_WORKING_DIR . '/install.ini');
			}
		}

		private function setInstalled() {

			if (!defined('INSTALLER_DEBUG') || !INSTALLER_DEBUG) {
				touch('./installed');
			}
			if ($this->installMode) {
				$this->flushLog('UMI.CMS установлена.');
			} else {
				$this->flushLog('UMI.CMS обновлена.');
			}

			if (INSTALLER_CLI_MODE) {
				if ($this->deleteInstallIni()) {
					$this->flushLog('Файл install.ini удален.');
				} else {
					$this->flushLog(
						'Не удалось удалить ' . INSTALLER_CURRENT_WORKING_DIR .
						'/install.ini. В целях обеспечения безопасности, пожалуйста, удалите его самостоятельно.'
					);
				}
			}

			return true;
		}

		/**
		 * Запускает отдельный шаг установки/обновления.
		 * Если установка/обновление выполняется из консоли,
		 * для шага будет создан отдельный php-процесс.
		 * @param string $step название шага
		 * @param array $params параметры запроса
		 * @param string $data stdin для дочернего процесса при консольной установке
		 * @return bool|int
		 */
		private function execSubProcess($step, array $params = [], $data = '') {
			if ($this->cliMode) {
				return $this->execSubProcessCLI($step, $params, $data);
			}

			return $this->execSubProcessNonCLI($step, $params);
		}

		/**
		 * Запускает шаг установки в отдельном процессе
		 * Как только в stderr попадают ошибки, установка/обновление прерывается
		 * @param string $step название шага
		 * @param array $params параметры запроса
		 * @param string $data stdin для дочернего процесса
		 * @return bool
		 * @throws Exception
		 */
		private function execSubProcessCLI($step, array $params = [], $data = '') {
			$php = $this->getConfigOption('SERVER', 'phppath', 'php');
			$sleep = (int) $this->getConfigOption('SETUP', 'sleep', 0);
			if ($sleep > 0) {
				$this->flushLog('Sleep ' . ($sleep / 1000) . ' sec');
				usleep($sleep * 1000);
			}
			$descriptorspec = [
				0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
				1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
				2 => ['pipe', 'w']   // stderr is a file to write to
			];

			$s_params = '';
			foreach ($params as $param_name => $param_val) {
				$s_params .= " --{$param_name}={$param_val}";
			}
			$cmd = $php . ' -f ' . __FILE__ . ' -- --step=' . $step . $s_params;
			$process = proc_open($cmd, $descriptorspec, $pipes);
			if (is_resource($process)) {
				// send data for child process
				if (mb_strlen($data)) {
					fwrite($pipes[0], $data);
					fclose($pipes[0]);
				}
				// run process
				$errors = '';
				while (($buffer = fgets($pipes[1], self::BUFFER_SIZE)) != null ||
					($errbuf = fgets($pipes[2], self::BUFFER_SIZE)) != null) {
					$this->flushLog($buffer);
					if (isset($errbuf)) {
						$errors .= $errbuf;
					}
				}

				if (mb_strlen($errors)) {
					echo $errors;
					die();
				}

				// close all pipes and process
				foreach ($pipes as $pipe) {
					fclose($pipe);
				}
				proc_close($process);
			} else {
				throw new Exception("Не могу запустить дочерний процесс: {$cmd}");
			}
			return true;
		}

		/**
		 * Запускает отдельный шаг установки/обновления в GUI-режиме.
		 * @param string $step название шага
		 * @param array $params параметры запроса
		 * @return bool|int
		 */
		private function execSubProcessNonCLI($step, array $params = []) {
			$subInstaller = new self($this->temporaryDirectory, $this->installMode, $params);
			return $subInstaller->run($step, $this->cliMode, $params);
		}

		/** Запускает процесс установки в CLI-режиме */
		private function runInstaller() {
			return

				// check already installed
				$this->checkInstalled() and

				// check for writable
				$this->checkSelf() and

				// clear system
				$this->cleanup() and

				// download and extract service package
				$this->execSubProcess('download-service-package') and
				$this->execSubProcess('extract-service-package') and
				// run tests
				$this->execSubProcess('run-tests') and

				// write configuration required for installation purposes
				$this->execSubProcess('write-initial-configuration') and

				// download instructions
				$this->execSubProcess('get-update-instructions') and
				$this->execSubProcess('download-components') and
				$this->execSubProcess('extract-components') and
				$this->execSubProcess('check-components') and

				// install the system
				$this->execSubProcess('update-installer') and
				$this->execSubProcess('update-database') and
				$this->execSubProcess('install-components') and

				// set default domains from package
				$this->execSubProcess('set-default-domain') and

				// install demosite
				$this->execSubProcess('download-demosite') and
				$this->execSubProcess('extract-demosite') and
				$this->execSubProcess('check-demosite') and
				$this->execSubProcess('install-demosite') and

				// configure installed system
				$this->execSubProcess('configure') and

				// Очистка системного кеша
				$this->execSubProcess('clear-cache') and

				// cleanup installed system
				$this->cleanup() and

				// set installed
				$this->setInstalled();
		}

		/**
		 * Определяет, запущен ли скрипт в режиме установки системы из браузера
		 * @return bool
		 */
		private function isGuiInstallMode() {
			return !$this->cliMode && $this->installMode;
		}
	}

	/** Класс для последовательного скачивания и распаковки пакетов с сервера обновлений */
	class umiUpdateDownloader {

		/** @var int максимальный размер XML-ответа сервера */
		const MAX_XML_RESPONSE_SIZE_IN_BYTES = 10000000;

		private $destination, $key, $host, $ip, $current_revision;

		/**
		 * Создает экземпляр класса umiUpdateDownloader
		 *
		 * @param string $destination - путь до директории, в которую будет происходить скачивание. Директория должна
		 *   существовать и быть доступна на запись.
		 * @param mixed $key - Лицензионный или доменный ключ
		 * @param mixed $host - Имя домена, на который выписана лицензия
		 * @param mixed $ip - Ip-адрес, на который выписана лицензия
		 * @param mixed $currentRevision - Номер текущей ревизии, 'last', если это установка, либо ревизия не актуальна
		 * @throws Exception
		 */
		public function __construct($destination, $key, $host, $ip, $currentRevision = 'last') {
			if (!is_dir($destination)) {
				throw new Exception('Директория назначения не найдена');
			}
			$this->destination = realpath($destination);

			$this->key = $key;
			$this->host = $host;
			$this->ip = $ip;
			$this->current_revision = $currentRevision;
		}

		/**
		 * Скачивает $component_name с сервера обновлений
		 *
		 * @param mixed $componentName - имя компонента
		 */
		public function downloadComponent($componentName) {
			$this->downloadFile('get-component', $componentName);
		}

		/**
		 * Скачивает указанный демосайт с сервера обновлений
		 *
		 * @param string $demositeName имя демосайта
		 */
		public function downloadDemosite($demositeName) {
			$this->downloadFile('get-demosite', $demositeName);
		}

		/**
		 * Скачивает указанный сервисный пакет с сервера обновлений
		 * @param string $serviceName имя сервисного пакета
		 */
		public function downloadServiceComponent($serviceName) {
			$this->downloadFile('get-service', $serviceName, 'service');
		}

		private function downloadFile($requestType, $filename, $fileSuffix = false) {
			$filePath = $this->destination . '/' . $filename . ($fileSuffix ? ".{$fileSuffix}" : '') . '.tar';
			$url = $this->buildUrl($requestType, ['component' => $filename]);
			$this->saveRemoteFile($url, $filePath);
		}

		public function getDemositesList() {
			$url = $this->buildUrl('get-solution-list');
			$result = $this->getRemoteFile($url);
			$doc = new DOMDocument('1.0', 'utf-8');

			if ($doc->loadXML($result)) {
				$this->checkResponseErrors($doc);
				return $doc;
			}

			throw new Exception('Не удается загрузить список сайтов.');
		}

		/** Скачивает changelog и инструкции для установки/обновления с сервера */
		public function downloadUpdateInstructions() {
			$url = $this->buildUrl('get-update-instructions');
			$filePath = $this->destination . '/update-instructions.xml';
			$this->saveRemoteFile($url, $filePath);

			$doc = new DOMDocument('1.0', 'utf-8');

			if ($doc->load($filePath)) {
				$this->checkResponseErrors($doc);
				return $doc;
			}

			throw new Exception('Не удается загрузить инструкции по обновлению');
		}

		private function checkResponseErrors(DOMDocument $doc) {
			if ($doc->documentElement->getAttribute('type') == 'exception') {
				$xpath = new DOMXPath($doc);
				$errors = $xpath->query('//error');

				foreach ($errors as $error) {
					throw new Exception($error->nodeValue, $error->getAttribute('code'));
				}
			}
		}

		/**
		 * Возвращает содержимое удаленного файла
		 * @param string $url адрес удаленного файла
		 * @return mixed|string
		 * @throws Exception
		 */
		public function getRemoteFile($url) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$remoteFileContent = curl_exec($curl);
			$headers = curl_getinfo($curl);

			curl_close($curl);

			if (isset($headers['content_type']) && stripos($headers['content_type'], 'text/xml') !== false) {
				$this->checkXmlErrors($remoteFileContent);
			}

			return $remoteFileContent;
		}

		/**
		 * Сохраняет содержимое удаленного файла в заданный файл
		 * @param string $url адрес удаленного файла
		 * @param string $filePath адрес файла
		 * @return bool
		 * @throws Exception
		 */
		public function saveRemoteFile($url, $filePath) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$resource = fopen($filePath, 'w+');
			curl_setopt($curl, CURLOPT_FILE, $resource);
			curl_exec($curl);
			curl_close($curl);
			fclose($resource);

			$size = (int) filesize($filePath);

			if ($size === 0) {
				throw new Exception('Не удалось загрузить файл: ', $url);
			}

			if ($size < self::MAX_XML_RESPONSE_SIZE_IN_BYTES) {
				$this->checkXmlErrors(file_get_contents($filePath));
			}

			return true;
		}

		/**
		 * Формирует адрес для запроса и возвращает его
		 * @param $type
		 * @param array $params
		 * @return string
		 */
		public function buildUrl($type, $params = []) {
			$params['type'] = $type;
			$params['host'] = $this->host;
			$params['ip'] = $this->ip;
			$params['key'] = $this->key;
			$params['revision'] = $this->current_revision;
			return base64_decode('aHR0cDovL3VwZGF0ZXMudW1pLWNtcy5ydS91cGRhdGVzZXJ2ZXIv') . '?' .
				http_build_query($params, '', '&');
		}

		private function checkXmlErrors($xml) {
			if (!class_exists('DomDocument')) {
				throw new Exception(
					'Отсутствует класс DomDocument. Подробное описание ошибки и способы её устранения ' .
					'доступны по ссылке http://errors.umi-cms.ru/13051/'
				);
			}

			$doc = new DOMDocument('1.0', 'utf-8');

			if (is_string($xml) && strpos($xml, '<') === 0 && $doc->loadXML($xml)) {
				$this->checkResponseErrors($doc);
			}

			unset($doc);
		}
	}

	/**
	 * Class for extracting files from uncompressed tarball (ustar) archives
	 * @link http://www.freebsd.org/cgi/man.cgi?query=tar&sektion=5&manpath=FreeBSD+8-current
	 */
	class umiTarExtracter {

		const TAR_CHUNK_SIZE = 512;

		/** Tar entry type flags */
		const TAR_ENTRY_REGULARFILE = '0';

		const TAR_ENTRY_HARDLINK = '1';

		const TAR_ENTRY_SYMLINK = '2';

		const TAR_ENTRY_CHARDEVICE = '3';

		const TAR_ENTRY_BLOCKDEVICE = '4';

		const TAR_ENTRY_DIRECTORY = '5';

		const TAR_ENTRY_FIFO = '6';

		const TAR_ENTRY_RESERVED = '7';

		private $unpackString = 'Z100name/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/Z8checksum/Ztypeflag/Z100linkname/Z6magic/Z2version/Z32uname/Z32gname/Z8devmajor/Z8devminor/Z155prefix/x12pad';

		/**
		 * Path to the tarball archive file
		 *
		 * @var string
		 */
		private $archiveFilename;

		/**
		 * Archive file handle
		 *
		 * @var resource
		 */
		private $handle;

		/**
		 * @param string $filename path to tarball archive file
		 * @throws Exception
		 */
		public function __construct($filename) {
			$this->archiveFilename = $filename;
			if (!is_file($this->archiveFilename)) {
				throw new Exception("umiTarExtracter: {$this->archiveFilename} не существует.");
			}
		}

		public function __destruct() {
			$this->close();
		}

		/**
		 * Extract $limit file records starting from $offset position
		 * @param bool|false|int $offset
		 * @param bool|false|int $limit
		 * @param bool $ignorePhar
		 * @return int|void
		 * @throws Exception
		 */
		public function extractFiles($offset = false, $limit = false, $ignorePhar = false) {
			if (extension_loaded('phar') && !$ignorePhar) {
				$pathList = mb_substr($this->archiveFilename, 0, mb_strlen($this->archiveFilename) - 4);
				try {
					$pharData = new PharData($this->archiveFilename);
					$pharData->extractTo($pathList, null, true);
				} catch (Exception $e) {
					return $this->extractFiles($offset, $limit, true);
				}
				return;
			}

			$currentOffset = 0;

			$this->open();

			fseek($this->handle, 0);

			while ($currentOffset < $offset) {
				$data = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);

				if ($this->eof($data)) {
					return $currentOffset;
				}

				$header = $this->parseEntryHeader($data);
				if ($header['typeflag'] == umiTarExtracter::TAR_ENTRY_REGULARFILE) {
					$fileChunkCount = floor($header['size'] / umiTarExtracter::TAR_CHUNK_SIZE) + 1;
					fseek($this->handle, $fileChunkCount * umiTarExtracter::TAR_CHUNK_SIZE, SEEK_CUR);
				}
				$currentOffset++;
			}

			while ($limit === false || ($currentOffset < $offset + $limit)) {
				$data = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);

				if ($this->eof($data)) {
					break;
				}

				$header = $this->parseEntryHeader($data);
				$name = (mb_strlen($header['prefix']) ? ($header['prefix'] . '/') : '') . $header['name'];

				$pathList = explode('/', $name);
				unset($pathList[count($pathList) - 1]);

				$pathDir = implode('/', $pathList);
				if (!file_exists($pathDir)) {
					mkdir($pathDir, 0777, true);
				}

				switch ($header['typeflag']) {
					case umiTarExtracter::TAR_ENTRY_REGULARFILE : {
						$dstHandle = fopen($name, 'wb');
						if (!$dstHandle) {
							throw new Exception('umiTarExtracter: не удается записать файл: ' . $name);
						}
						$bytesLeft = $header['size'];
						if ($bytesLeft) {
							do {
								$bytesToWrite = $bytesLeft < umiTarExtracter::TAR_CHUNK_SIZE
									? $bytesLeft
									: umiTarExtracter::TAR_CHUNK_SIZE;
								$bytes = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
								fwrite($dstHandle, $bytes, $bytesToWrite);
								$bytesLeft -= umiTarExtracter::TAR_CHUNK_SIZE;
							} while ($bytesLeft > 0);
						}
						fclose($dstHandle);
						if (mb_strtolower(mb_substr($name, -4, 4)) === '.php') {
							chmod($name, PHP_FILES_ACCESS_MODE);
						}
						break;
					}
					case umiTarExtracter::TAR_ENTRY_DIRECTORY : {
						if (!is_dir($name)) {
							if (!mkdir($name, 0777, true)) {
								throw new Exception('umiTarExtracter: не удается создать директорию: ' . $name);
							}
						}
						break;
					}
				}
				$currentOffset++;
			}

			return $currentOffset;
		}

		private function open() {
			if ($this->handle == null) {
				$this->handle = fopen($this->archiveFilename, 'rb');
				if ($this->handle === false) {
					throw new Exception("umiTarExtracter: Не удается открыть {$this->archiveFilename}");
				}
			}
			return $this->handle;
		}

		private function close() {
			if ($this->handle != null) {
				fclose($this->handle);
			}
		}

		private function parseEntryHeader($rawHeaderData) {
			if ($rawHeaderData === '') {
				throw new Exception('umiTarExtracter: не удается распаковать файл: ' . $this->archiveFilename);
			}

			$header = unpack($this->unpackString, $rawHeaderData);
			$header['uid'] = octdec($header['uid']);
			$header['gid'] = octdec($header['gid']);
			$header['size'] = octdec($header['size']);
			$header['mtime'] = octdec($header['mtime']);
			$header['checksum'] = octdec(mb_substr($header['checksum'], 0, 6));
			return $header;
		}

		private function eof(&$data) {
			$eofPattern = null;
			if ($eofPattern == null) {
				$eofPattern = str_repeat(chr(0), 512);
			}
			if (strcmp($data, $eofPattern) == 0) {
				$ahead = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
				if (strcmp($ahead, $eofPattern) == 0) {
					return true;
				}
				fseek($this->handle, -umiTarExtracter::TAR_CHUNK_SIZE, SEEK_CUR);
			}
			return false;
		}

	}

	/** @deprecated */
	abstract class siteInstallScenario {

		/* @var string $siteName имя устанавливаемого сайта */
		private $siteName;

		/* @var array $logMessages сообщения журнала установки */
		private $logMessages = [];

		/**
		 * Конструктор
		 * @param string $siteName имя устанавливаемого сайта
		 */
		final public function __construct($siteName) {
			$this->siteName = (string) $siteName;
		}

		/** @inheritdoc */
		public function run() {}

		/**
		 * Запускает выполнение сценария
		 * @param int $id идентификатор домена, на который устанавливается решение
		 * @return mixed
		 */
		public function runViaDomain($id) {
			$this->run();
		}

		/**
		 * Возвращает сообщения журнала установки
		 * @return array
		 */
		final public function getLogMessages() {
			return $this->logMessages;
		}

		/**
		 * Добавляет сообщение журнала установки
		 * @param $message
		 */
		final protected function addLogMessage($message) {
			$this->logMessages[] = (string) $message;
		}

		/**
		 * Возвращает имя устанавливаемого сайта
		 * @return string
		 */
		final protected function getSiteName() {
			return $this->siteName;
		}
	}

	/** @deprecated */
	interface iSiteInstallScenario {

		/**
		 * Запускает выполнение сценария
		 * @return mixed
		 */
		public function run();
	}

	function parse_argv($arr) {
		$args = [];
		foreach ($arr as $v) {
			$va = explode('=', $v);
			if (count($va) != 2) {
				continue;
			}
			list($k, $p) = $va;
			$args[trim(mb_substr($k, 2))] = trim($p);
		}
		return $args;
	}
