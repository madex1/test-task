<?php
	abstract class __yandex_webmaster extends baseModuleAdmin {

		public function webmaster() {
			$this->isCurlAllowed();

			$this->setDataType("settings");
			$this->setActionType("view");

			$preParams = array();
			$hostsData = $this->getList();
			$data = $this->prepareData($preParams, 'settings');
			$data['hosts']['nodes:host'] = $hostsData;

			$this->setData($data);
			return $this->doData();
		}

		public function yandex() {
			$this->isCurlAllowed();

			$regedit = regedit::getInstance();

			$params = Array();

			if ($regedit->getVal("//modules/seo/yandex-token")) {
				$params['yandex']['string:token'] = $regedit->getVal("//modules/seo/yandex-token");
			} else {
				$params['yandex']['string:code'] = '';
			}

			$mode = (string) getRequest('param0');

			if($mode == "do") {
				$params = $this->expectParams($params);

				if(isset($params['yandex']['string:code']) && strlen($params['yandex']['string:code'])){
					$queryParams = Array(
						'grant_type' => "authorization_code",
						'code' => $params['yandex']['string:code'],
						'client_id' => "47fc30ca18e045cdb75f17c9779cfc36",
						'client_secret' => "8c744620c2414522867e358b74b4a2ff",
					);
					$headers = [
						'Content-Type' => 'application/x-www-form-urlencoded'
					];
					$response = umiRemoteFileGetter::get("https://oauth.yandex.ru/token", false, $headers, $queryParams, true, "POST");
					$response = preg_split("|(\r\n\r\n)|", $response);
					$result = json_decode($response[1]);

					if (!isset($result) || isset($result->error) || !$result->access_token) {
						$this->errorNewMessage(getLabel('webmaster-wrong-code'));
						$this->errorPanic();
					}

					$token = $result->access_token;
					$regedit->setVal("//modules/seo/yandex-token", $token);
				} else {
					$regedit->setVal("//modules/seo/yandex-token", $params['yandex']['string:token']);
				}

				$this->chooseRedirect();
			}

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, 'settings');
			$this->setData($data);
			return $this->doData();
		}

		public function isCurlAllowed() {
			if (!function_exists('curl_init')) {
				throw new publicAdminException(getLabel('label-error-no-curl'));
			}
		}

		public function getList() {
			$url = '/api/me';
			$serviceDocUrl = $this->getYandexData($url, false, array(), array(), '302 Found');
			$serviceDoc = secure_load_simple_xml($this->getYandexData($serviceDocUrl));
			$hostsList = (string) $serviceDoc->workspace->collection->attributes()->href;
			$hostsData = secure_load_simple_xml($this->getYandexData($hostsList));
			$result = array();

			$domainsCollection = domainsCollection::getInstance();

			foreach ($hostsData->host as $host) {
				if ($currentHost = $this->getHostData($host->attributes()->href)) {
					$result[$currentHost['@id']] = $currentHost;
				}
			}

			foreach ($domainsCollection->getList() as $id=>$host) {
				if(array_key_exists($id, $result)) {
					continue;
				}
				$currentHost = array(
					'@id' => $id,
					'name' => $host->getHost(),
					'addLink' => $hostsList
				);
				$result[] = $currentHost;
			}
			return $result;
		}

		public function add_site() {
			$this->setDataType("settings");
			$this->setActionType("view");

			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');

			$addLink = getRequest('addLink');
			$hostName = getRequest('hostName');
			$params = "<host><name>$hostName</name></host>";
			$headers = [
				'Content-Type' => 'application/x-www-form-urlencoded'
			];
			$hostLink = $this->getYandexData($addLink, "POST", $headers, $params, '201 Created');
			$hostLinks = secure_load_simple_xml($this->getYandexData($hostLink));

			foreach ($hostLinks->link as $link) {
				$link = (string) $link->attributes()->href;
				if (array_pop(explode('/', $link)) == 'verify') {
					$verification = secure_load_simple_xml($this->getYandexData($link));
					$uin = (string) $verification->verification->uin;
					$this->verifySiteViaHTMLFile($uin, $link);
				}
			}

			$data['hosts']['host'] = $this->getHostData($hostLink);

			$this->setData($data);
			return $this->doData();
		}

		public function verify_site() {
			$this->setDataType("settings");
			$this->setActionType("view");

			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');

			$verifyLink = getRequest('verifyLink');
			$hostLink = getRequest('hostLink');
			$verification = secure_load_simple_xml($this->getYandexData($verifyLink));
			$uin = (string) $verification->verification->uin;
			$this->verifySiteViaHTMLFile($uin, $verifyLink);
			$data['hosts']['host'] = $this->getHostData($hostLink);

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Подтверждает права владения сайтом в webmaster.yandex.ru
		 * с помощью html файла.
		 * @param int $uin идентификатор пользователя в webmaster.yandex.ru
		 * @param string $verifyLink адрес куда отправляется запрос на подтверждение прав
		 * @return bool
		 * @throws publicAdminException если $uin не является строкой
		 * @throws publicAdminException если $verifyLink не является строкой
		 * @throws publicAdminException если не удалось создать html файл
		 */
		public function verifySiteViaHTMLFile($uin, $verifyLink) {
			if (!is_string($uin)) {
				throw new publicAdminException(__METHOD__ . ' uin expected');
			}

			if (!is_string($verifyLink)) {
				throw new publicAdminException(__METHOD__ . ' verify link expected');
			}

			$uin = (string) $uin;

			$filePath = CURRENT_WORKING_DIR . DIRECTORY_SEPARATOR . "yandex_$uin.html";
			$fileContent =
				"<html>
	   <head>
		  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
	   </head>
	   <body>Verification: $uin</body>
	</html>";
			$fileCreated = file_put_contents($filePath, $fileContent);

			if (!$fileCreated) {
				throw new publicAdminException(__METHOD__ . ' cant create html file');
			}

			$request = '<host><type>HTML_FILE</type></host>';
			$this->getYandexData($verifyLink, "PUT", array(), $request, '204 No Content');
			return true;
		}

		public function refresh_site() {
			$this->setDataType("settings");
			$this->setActionType("view");

			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');

			$hostLink = getRequest('hostLink');
			$data['hosts']['host'] = $this->getHostData($hostLink);

			$this->setData($data);
			return $this->doData();
		}

		public function handle_url($url=false) {
			if (!$url) {
				$url = getRequest('url');
			}

			$preParams = array();
			$hostsData = $this->getYandexData($url);
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($preParams, 'settings');
			$data['xml:yandex'] = $hostsData;

			$this->setData($data);
			return $this->doData();
		}


		public function getYandexData($url, $method="GET", $headers = array(), $params = array(), $responseCode = '200 OK'){
			$regedit = regedit::getInstance();
			$token = (string) trim($regedit->getVal("//modules/seo/yandex-token"));

			if (!$token) throw new publicAdminException(getLabel('label-error-no-token', false, cmsController::getInstance()->getPreLang()));

			$headers["Authorization"] = "OAuth " . $token;

			$apiUrl = 'https://webmaster.yandex.ru';
			if (strpos($url, '/') === 0) {
				$url = $apiUrl . $url;
			}

			$response = umiRemoteFileGetter::get($url, false, $headers, $params, true, $method);

			$result = preg_split("|(\r\n\r\n)|", $response);
			$headerLines = $result[0];
			$xml = $result[1];

			$headerLines = preg_split("|(\r\n)|", $headerLines);
			$responseHeaders = array();
			foreach ($headerLines as $headerLine) {
				if (strpos($headerLine, ':')) {
					list($key, $value) = explode(": ", $headerLine);
					$responseHeaders[strtolower(trim($key))] = trim($value);
				} else {
					$responseHeaders['code'] = trim(preg_replace("#HTTP([^\s]*)\s#", '', trim($headerLine)));
				}
			}

			if ($responseHeaders['code'] != $responseCode) {
				switch($responseHeaders['code']) {
					case '401 Unauthorized': {
						throw new publicAdminException(getLabel('label-error-no-token', false, cmsController::getInstance()->getPreLang()));
					}
					case '400 Bad request':
					case '409 Unknown status': {
						$dom = secure_load_simple_xml($xml);
						$message = (string) $dom;

						switch(true) {
							case (strpos(strtolower($message), 'host is a mirror of') !== false): {
								$host = str_ireplace('host is a mirror of ', '', $message);
								$host = trim($host);
								$message = getLabel('label-error-host-is-a-mirror-of', false, $host);
								break;
							}
							case (strpos(strtolower($message), 'is not responding') !== false): {
								$host = str_ireplace('host', '', $message);
								$host = str_ireplace('is not responding', '', $host);
								$host = trim($host);
								$message = getLabel('label-error-host-is-not-responding', false, $host);
								break;
							}
						}
						throw new publicAdminException($message);
					}
					default: {
						throw new publicAdminException(getLabel('label-error-service-down'));
					}
				}
			}

			switch ($responseCode) {
				case "302 Found":
				case "201 Created":
					return $responseHeaders['location'];
					break;
			}

			return $xml;
		}

		public function getHostData($hostUrl) {
			$domainsCollection = domainsCollection::getInstance();
			$hostLinks = secure_load_simple_xml($this->getYandexData($hostUrl));

			if (!$hostId = $domainsCollection->getDomainId(strtolower($hostLinks->name))) {
				return false;
			}
			$currentHost['@id'] = $hostId;
			$currentHost['@link'] = (string) $hostUrl;
			$links = array();
			foreach ($hostLinks->link as $link) {
				$link = (string) $link->attributes()->href;
				$links[array_pop(explode('/', $link))] = $link;
			}
			if (isset($links['stats'])) {
				$hostStats = secure_load_simple_xml($this->getYandexData($links['stats']));
				unset($links['stats']);
			}
			foreach ($hostStats as $key=>$value) {
				switch ($key) {
					case 'verification':
						$currentHost[$key] = array(
							'state' => (string) $value->attributes()->state,
						);
						if (trim((string) $value->attributes()->state) != 'VERIFIED' && isset($links['verify'])) {
							$currentHost[$key]['link'] = $links['verify'];
						}
						unset($links['verify']);
						break;
					case 'crawling':
						$currentHost[$key] = array(
							'state' => getLabel('js-webmaster-crawling-state-' . (string) $value->attributes()->state),
						);
						break;
					case 'last-access':
						$currentHost[$key] = date('d.m.Y H:i:s', strtotime((string) $value));
						break;
					case 'virused':
						$currentHost[$key] = ((string) $value == 'true') ? 'Да' : 'Нет';
						break;
					default :
						$currentHost[$key] = (string) $value;
				}
			}
			$currentHost['name'] = strtolower($currentHost['name']);
			$currentHost['links'] = $links;
			return $currentHost;
		}
	}
?>
