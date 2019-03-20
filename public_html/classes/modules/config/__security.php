<?php
	abstract class __security_config extends baseModuleAdmin {

		protected static $allowedTests = array(
			'UFS', 'UObject', 'DBLogin', 'DBPassword', 'ConfigIniAccess', 'FoldersAccess', 'PhpDisabledForFiles', 'PhpDelConnector'
		);

		public function security() {
			$params = array(
				"security-audit"=>array()
			);
			foreach (self::$allowedTests as $test) {
				$params["security-audit"][$test.":security-".$test] = NULL;
			}

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Запускает тестирование
		 *
		 * @throws coreException
		 */
		public function securityRunTest() {
			$testName = getRequest('param0');

			if ( array_search($testName, self::$allowedTests) !== FALSE) {
				$testName = 'test' . $testName;
			} else {
				throw new coreException("Selected test not allowed");
			}
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();

			$buffer-> push(
				json_encode(array(
					'result' => $this->$testName()
					)
				)
			);

			$buffer-> end();
		}

		/**
		 * Тестирует возможность доступа к стриму ufs по http
		 *
		 * @return boolean
		 */
		public function testUFS() {
			$config = mainConfiguration::getInstance();
			$enabledStreams = $config->get("streams", "enable");
			$ufsEnabled = in_array('ufs', $enabledStreams);
			$ufsHttpStatus = (bool) $config->get("streams", "ufs.http.allow");
			if (!($ufsEnabled && $ufsHttpStatus)) {
				return true;
			}

			return false;
		}

		/**
		 * Тестирует возможность доступа к стриму uobject по http
		 *
		 * @return boolean
		 */
		public function testUObject() {
			$config = mainConfiguration::getInstance();
			$enabledStreams = $config->get("streams", "enable");
			$uobjectEnabled = in_array('uobject', $enabledStreams);
			$uobjectHttpStatus = $config->get("streams", "uobject.http.allow");
			if (!($uobjectEnabled && $uobjectHttpStatus)) {
				return true;
			}
			return false;
		}

		/**
		 * Проверяет что доступ к БД осуществляется не из под рута
		 *
		 * @return boolean
		 */
		public function testDBLogin() {
			$config = mainConfiguration::getInstance();
			$dbLogin = $config->get("connections", "core.login");
			if (strtolower($dbLogin) != 'root') {
				return true;
			}
			return false;
		}

		/**
		 * Проверяет что доступ к БД осуществляется с непустым паролем
		 *
		 * @return boolean
		 */
		public function testDBPassword() {
			$config = mainConfiguration::getInstance();
			$dbPassword = $config->get("connections", "core.password");
			if (!empty($dbPassword)) {
				return true;
			}
			return false;
		}

		/**
		 * Проверяет что config.ini недоступен для чтения извне
		 *
		 * @return boolean
		 */
		public function testConfigIniAccess() {
			$headers = get_headers(getServerProtocol() . "://" . getServer("HTTP_HOST") . "/config.ini", 1);
			return ($headers && strpos($headers[0], "200") === false);
		}

		/**
		 * Проверяет что папка /classes недоступна извне
		 * @return boolean
		 */
		public function testFoldersAccess() {
			$headers = get_headers(getServerProtocol() . "://" . getServer("HTTP_HOST") . "/classes", 1);
			return ($headers && strpos($headers[0], "403") !== false);
		}

		/**
		 * Проверка доступности исполнение php файлов в директории files
		 *
		 * @return bool
		 */
		public function testPhpDisabledForFiles() {
			file_put_contents(USER_FILES_PATH . '/exploit.php', '<?php echo "exploit" ?>');
			chmod(USER_FILES_PATH . '/exploit.php', 0777);
			$result = file_get_contents(getServerProtocol() . "://" . getServer("HTTP_HOST") . '/files/exploit.php');
			unlink(USER_FILES_PATH . '/exploit.php');

			return ($result != 'exploit');
		}

		/**
		 * Проверка наличия php_for_del_connector.php
		 *
		 * @return bool
		 */
		public function testPhpDelConnector() {
			$headers = get_headers(getServerProtocol() . "://" . getServer("HTTP_HOST") . '/styles/common/other/elfinder/php/for_del_connector.php', 1);
			return ($headers && strpos($headers[0], "403") !== false);
		}
	};
?>