<?php

	use UmiCms\Service;

	/** Класс тестов системы */
	class ConfigTest {

		/**
		 * Выводит в буффер время генерации
		 * результата работы данного метода.
		 * @throws coreException
		 */
		public function speedtest() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$callTime = $buffer->calltime();
			$buffer->push($callTime);
			$buffer->end();
		}

		/**
		 * Возвращает список имен тестов безопасности
		 * @return array
		 */
		public function getSecurityTestNames() {
			return [
				'UFS',
				'UObject',
				'DBLogin',
				'DBPassword',
				'ConfigIniAccess',
				'FoldersAccess',
				'PhpDisabledForFiles',
				'PhpDelConnector',
				'ConfigIniCsrfProtection',
				'UserCsrfProtection',
				'RequireUserPassword'
			];
		}

		/**
		 * Запускает тесты безопасности системы
		 * и возвращает результат
		 * @throws coreException
		 */
		public function securityRunTest() {
			$testName = getRequest('param0');

			if (in_array($testName, $this->getSecurityTestNames())) {
				$testName = 'test' . $testName;
			} else {
				throw new coreException('Selected test not allowed');
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();

			$buffer->push(
				json_encode([
						'result' => $this->$testName()
					]
				)
			);

			$buffer->end();
		}

		/**
		 * Тестирует проверку текущего пароля пользователя при изменении полей группы "idetntify_data"
		 * @return bool
		 */
		protected function testRequireUserPassword() {
			return (bool) Service::Registry()->get('//modules/users/require_current_password');
		}

		/**
		 * Тестирует защиту от CSRF-атаки в файле config.ini
		 * @return bool
		 */
		protected function testConfigIniCsrfProtection() {
			$config = mainConfiguration::getInstance();
			return (bool) $config->get('kernel', 'csrf_protection');
		}

		/**
		 * Тестирует защиту от CSRF-атаки при изменении настроек пользователя
		 * @return bool
		 */
		protected function testUserCsrfProtection() {
			return (bool) Service::Registry()->get('//modules/users/check_csrf_on_user_update');
		}

		/**
		 * Тестирует возможность доступа к стриму ufs по http
		 * и возвращает результат тестирования
		 * @return boolean
		 */
		protected function testUFS() {
			$config = mainConfiguration::getInstance();
			$enabledStreams = $config->get('streams', 'enable');
			$ufsEnabled = in_array('ufs', $enabledStreams);
			$ufsHttpStatus = (bool) $config->get('streams', 'ufs.http.allow');
			if (!($ufsEnabled && $ufsHttpStatus)) {
				return true;
			}
			return false;
		}

		/**
		 * Тестирует возможность доступа к стриму uobject по http
		 * и возвращает результат тестирования
		 * @return boolean
		 */
		protected function testUObject() {
			$config = mainConfiguration::getInstance();
			$enabledStreams = $config->get('streams', 'enable');
			$uobjectEnabled = in_array('uobject', $enabledStreams);
			$uobjectHttpStatus = $config->get('streams', 'uobject.http.allow');
			if (!($uobjectEnabled && $uobjectHttpStatus)) {
				return true;
			}
			return false;
		}

		/**
		 * Проверяет что доступ к БД осуществляется не из под рута
		 * и возвращает результат тестирования
		 * @return boolean
		 */
		protected function testDBLogin() {
			$config = mainConfiguration::getInstance();
			$dbLogin = $config->get('connections', 'core.login');
			return mb_strtolower($dbLogin) != 'root';
		}

		/**
		 * Проверяет что доступ к БД осуществляется с непустым паролем
		 * и возвращает результат тестирования
		 * @return boolean
		 */
		protected function testDBPassword() {
			$config = mainConfiguration::getInstance();
			$dbPassword = $config->get('connections', 'core.password');
			if (!empty($dbPassword)) {
				return true;
			}
			return false;
		}

		/**
		 * Проверяет что config.ini недоступен для чтения извне
		 * и возвращает результат тестирования
		 * @return boolean
		 */
		protected function testConfigIniAccess() {
			$headers = get_headers(getServerProtocol() . '://' . getServer('HTTP_HOST') . '/config.ini', 1);
			return ($headers && !contains($headers[0], '200'));
		}

		/**
		 * Проверяет что папка /classes недоступна извне
		 * и возвращает результат тестирования
		 * @return boolean
		 */
		protected function testFoldersAccess() {
			$headers = get_headers(getServerProtocol() . '://' . getServer('HTTP_HOST') . '/classes', 1);
			return ($headers && contains($headers[0], '403'));
		}

		/**
		 * Проверка доступности исполнение php файлов в директории files
		 * и возвращает результат тестирования
		 * @return bool
		 */
		protected function testPhpDisabledForFiles() {
			file_put_contents(CURRENT_WORKING_DIR . '/files/exploit.php', '<?php echo "exploit" ?>');
			chmod(CURRENT_WORKING_DIR . '/files/exploit.php', 0777);
			$result = file_get_contents(getServerProtocol() . '://' . getServer('HTTP_HOST') . '/files/exploit.php');
			unlink(CURRENT_WORKING_DIR . '/files/exploit.php');
			return ($result != 'exploit');
		}

		/**
		 * Проверка доступность скрипта php_for_del_connector.php
		 * и возвращает результат тестирования
		 * @return bool
		 */
		protected function testPhpDelConnector() {
			$headers = get_headers(getServerProtocol() . '://' . getServer('HTTP_HOST') .
				'/styles/common/other/elfinder/php/for_del_connector.php', 1);
			return ($headers && contains($headers[0], '403'));
		}
	}
