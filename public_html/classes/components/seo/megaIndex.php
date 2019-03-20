<?php

	use UmiCms\Service;

	/** Клиент для сервиса MegaIndex */
	class SeoMegaIndex {

		/** @var seo $module */
		public $module;

		/** @const string REQUEST_URL адрес api MegaIndex */
		const REQUEST_URL = 'http://api.megaindex.ru/';

		/**
		 * Возвращает видимость сайта / метод siteAnalyze
		 * @see http://api.megaindex.ru/description/siteAnalyze
		 * @param string $site исследуемый сайт в MegaIndex
		 * @param string $date дата, за которую возвращать статистику
		 * @param null|string $login логин в MegaIndex
		 * @param null|string $password пароль в MegaIndex
		 * @return stdClass
		 * @throws coreException
		 */
		public function getVisibility($site, $date, $login = null, $password = null) {
			$query = http_build_query([
				'login' => $login ?: $this->getMegaIndexLogin(),
				'password' => $password ?: $this->getMegaIndexPassword(),
				'url' => $site,
				'method' => 'siteAnalyze',
				'date' => $date
			]);

			try {
				$request = self::REQUEST_URL . '?' . $query;
				$response = umiRemoteFileGetter::get($request);
				$response = json_decode($response);
			} catch (umiRemoteFileGetterException $e) {
				$response = false;
			}

			if (!$response instanceof stdClass) {
				throw new coreException(getLabel('error-data'));
			}

			if ($response->status != 0) {
				$message = isset($response->error) ? getLabel('error') . $response->error : getLabel('error');
				throw new coreException($message);
			}

			return $response;
		}

		/**
		 * Возвращает ссылки на сайт / метод get_backlinks
		 * @see http://api.megaindex.ru/description/get_backlinks
		 * @param string $site исследуемый сайт в MegaIndex
		 * @param null|string $login логин в MegaIndex
		 * @param null|string $password пароль в MegaIndex
		 * @return stdClass|stdClass[]
		 * @throws coreException
		 */
		public function getBackLinks($site, $login = null, $password = null) {
			$query = http_build_query([
				'login' => $login ?: $this->getMegaIndexLogin(),
				'password' => $password ?: $this->getMegaIndexPassword(),
				'url' => $site,
				'method' => 'get_backlinks',
				'output' => 'json'
			]);

			$headerList = [
				'Content-type' => 'application/x-www-form-urlencoded'
			];

			try {
				$request = self::REQUEST_URL . '?' . $query;
				$response = umiRemoteFileGetter::get($request, false, $headerList);
				$response = json_decode($response);
			} catch (umiRemoteFileGetterException $e) {
				$response = [];
			}

			return $response;
		}

		/**
		 * Возвращает логин для MegaIndex
		 * @return string
		 */
		public function getMegaIndexLogin() {
			$login = Service::Registry()
				->get('//modules/seo/megaindex-login');
			return trim($login);
		}

		/**
		 * Устанавливает логин для MegaIndex
		 * @param string $login логин
		 * @return $this
		 */
		public function setMegaIndexLogin($login) {
			Service::Registry()
				->set('//modules/seo/megaindex-login', trim($login));
			return $this;
		}

		/**
		 * Возвращает пароль для MegaIndex
		 * @return string
		 */
		public function getMegaIndexPassword() {
			$password = Service::Registry()
				->get('//modules/seo/megaindex-password');
			return trim($password);
		}

		/**
		 * Устанавливает пароль для MegaIndex
		 * @param string $password пароль
		 * @return $this
		 */
		public function setMegaIndexPassword($password) {
			Service::Registry()
				->set('//modules/seo/megaindex-password', trim($password));
			return $this;
		}
	}
