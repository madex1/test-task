<?php
	abstract class __seo extends baseModuleAdmin {

		public $cook = '';

		public function oldseo() {
			$params = Array();

			$this->setDataType("settings");
			$this->setActionType("view");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}


		public function get() {
			$url = getServer("REQUEST_URI");
			preg_match("/q=(.*)/", $url, $out);
			$url = $out[1];

			$headers = Array(
				"User-Agent" => "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7",
				"Accept-Language" => "ru",
				"Accept" => "text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
				"Accept-Charset" => "utf-8"
			);

			try {
				$res = umiRemoteFileGetter::get($url, false, $headers);
			} catch (umiRemoteFileGetterException $e) {
				$res = false;
			}

			header("Content-type: text/html; charset=windows-1251");
			echo $res = iconv("UTF-8", "CP1251//IGNORE", $res);
			exit();
		}

		public function megaindex() {

			$regedit = regedit::getInstance();
			$params = Array (
				"config" => Array (
					"string:megaindex-login" => null,
					"string:megaindex-password" => null
				)
			);

			$mode = getRequest("param0");

			if ($mode == "do"){
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/seo/megaindex-login", $params["config"]["string:megaindex-login"]);
				$regedit->setVar("//modules/seo/megaindex-password", $params["config"]["string:megaindex-password"]);
				$this->chooseRedirect();
			}

			$params["config"]["string:megaindex-login"] = $regedit->getVal("//modules/seo/megaindex-login");
			$params["config"]["string:megaindex-password"] = $regedit->getVal("//modules/seo/megaindex-password");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");
			$this->setData($data);
			return $this->doData();

		}

		public function config() {
			$regedit = regedit::getInstance();
			$domains = domainsCollection::getInstance()->getList();
			$langId = cmsController::getInstance()->getCurrentLang()->getId();

			$params = Array();

			/** @var domain $domain */
			foreach ($domains as $domain) {
				$domainId = $domain->getId();
				$domainName = $domain->getHost();

				$seoInfo = Array();
				$seoInfo['status:domain'] = $domainName;
				$seoInfo['string:title-' . $domainId] = $regedit->getVal("//settings/title_prefix/{$langId}/{$domainId}");
				$seoInfo['string:default-title-' . $domainId] = $regedit->getVal("//settings/default_title/{$langId}/{$domainId}");
				$seoInfo['string:keywords-' . $domainId] = $regedit->getVal("//settings/meta_keywords/{$langId}/{$domainId}");
				$seoInfo['string:description-' . $domainId] = $regedit->getVal("//settings/meta_description/{$langId}/{$domainId}");
				$params[$domainName] = $seoInfo;
			}

			$mode = (string) getRequest('param0');

			if ($mode == "do") {
				$params = $this->expectParams($params);

				foreach ($domains as $domain) {
					$domainId = $domain->getId();
					$domainName = $domain->getHost();

					$title = $params[$domainName]['string:title-' . $domainId];
					$defaultTitle = $params[$domainName]['string:default-title-' . $domainId];
					$keywords = $params[$domainName]['string:keywords-' . $domainId];
					$description = $params[$domainName]['string:description-' . $domainId];

					$regedit->setVal("//settings/title_prefix/{$langId}/{$domainId}", $title);
					$regedit->setVal("//settings/default_title/{$langId}/{$domainId}", $defaultTitle);
					$regedit->setVal("//settings/meta_keywords/{$langId}/{$domainId}", $keywords);
					$regedit->setVal("//settings/meta_description/{$langId}/{$domainId}", $description);
				}

				$this->chooseRedirect();
			}

			$this->setDataType('settings');
			$this->setActionType('modify');
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);
			$this->doData();
		}


		public function links() {

			$regedit = regedit::getInstance();
			$login = trim($regedit->getVal("//modules/seo/megaindex-login"));
			$password = trim($regedit->getVal("//modules/seo/megaindex-password"));

			if (isDemoMode() && getRequest("host") == '') {
				$host = 'umi-cms.ru';
			} else {
				$host = (string) (strlen(getRequest ("host"))) ? getRequest ("host") : getServer('HTTP_HOST');
			}

			$params = array(
				'login' => $login,
				'password' => $password,
				'url' => $host,
				'method' => 'get_backlinks',
				'output' => 'json'
			);

			$headers = array(
				"Content-type" => "application/x-www-form-urlencoded"
			);

			$response = umiRemoteFileGetter::get('http://api.megaindex.ru/?' . http_build_query($params), false, $headers);
			$result = json_decode($response);


			$this->setDataType("settings");
			$this->setActionType("view");

			$preParams = Array(
				"config" => Array(
					"url:http_host" => $host
				)
			);

			$links = array('nodes:link' => array());
			$errors = array('nodes:error' => array());

			if (!is_array($result)) $result = array($result);


			foreach ($result as $link) {
				if (!empty($link->error)) {
					$error = $link->error;
					if ($error == "Сайт не проиндексирован! Добавьте пожалуйста на индексацию.") {
						$error = ulangStream::getLabelSimple('label-seo-noindex', array($host));
					}
					$errors['nodes:error'][] = array(
						'node:value' => $error
					);
				} else {
					$links['nodes:link'][] = array(
						'attribute:vs_from' => $link->vs_from,
					    'attribute:vs_to' => $link->vs_to,
					    'attribute:tic_from' => $link->tic_from,
					    'attribute:tic_to' => $link->tic_to,
					    'attribute:text' => $link->text,
					    'attribute:noi' => $link->noi,
					    'attribute:nof' => $link->nof
					);
				}
			}

			$data = $this->prepareData($preParams, 'settings');
			$data['links'] = $links;
			$data['errors'] = $errors;

			$this->setData($data);
			return $this->doData();



		}

		public function seo() {
			$regedit = regedit::getInstance();
			$login = trim($regedit->getVal("//modules/seo/megaindex-login"));
			$password = trim($regedit->getVal("//modules/seo/megaindex-password"));

			if (isDemoMode() && getRequest("host") == '') {
				$host = 'umi-cms.ru';
			} else {
				$host = (string) (strlen(getRequest ("host"))) ? getRequest ("host") : getServer('HTTP_HOST');
			}

			$date = date('Y-m-d');

			$this->cook = '';

			$this->setDataType("settings");
			$this->setActionType("view");

			$preParams = Array(
				"config" => Array(
					"url:http_host" => $host
					)
				);

			$data = $this->prepareData($preParams, 'settings');
			if($this->ifNotXmlMode()) {
				$this->setData($data);
				return $this->doData();
			}

			if ($password && $login) {
				/** @var stdClass $params */
				$params = $this->siteAnalyzeJson($login, $password, $host, $date);
			}

			if (isset($params)) {
				$items = array();
				foreach ($params->data as $k => $param) {
					$item = array(
						'@word' => $param['0'],
						'@pos_y' => $param['1'],
						'@pos_g' => $param['3'],
						'@show_month' => $param['5'],
						'@wordstat' => $param['7'],
						);
					$items[] = $item;
				}

				unset($items[0]);
				$data['items'] = array(
					'nodes:item' => $items
					);
			} else {
				$data['error'] = 'Для использования модуля необходима регистрация на сайте <a href="http://www.megaindex.ru" target="_blank" title="">MegaIndex</a>. Зарегистрируйтесь на нём, затем впишите свой логин и пароль в <a href="/admin/seo/megaindex/" title="" >Настройках модуля</a>.';
			}

			$this->setData($data);
			return $this->doData();
		}


		/**
		 * Видимость сайта / метод siteAnalyze
		 * @see http://api.megaindex.ru/description/siteAnalyze
		 *
		 * @param string $login логин в MegaIndex
		 * @param string $password пароль в MegaIndex
		 * @param string $site исследуемый сайт в MegaIndex
		 * @param string $date дата, за которую возвращать статистику
		 *
		 * @return mixed
		 * @throws coreException
		 */
		public function siteAnalyzeJson($login, $password, $site, $date) {
			$array = array(
				'login' => $login,
				'password' => $password,
				'url' => $site,
				'date' => $date
			);

			try {
				$content = umiRemoteFileGetter::get("http://api.megaindex.ru/?method=siteAnalyze&" . http_build_query($array));
			} catch (umiRemoteFileGetterException $e) {
				$content = false;
			}

			$json = json_decode($content);

			if (!is_object($json)) {
				throw new coreException(getLabel('error-data'));
			}

			if ($json->status != 0) {
				$message = (isset($json->error)) ? getLabel('error') . $json->error : getLabel('error');
				throw new coreException($message);
			}

			return $json;
		}

		public function siteAnalyzeXML($login, $password, $site, $date){

			$doc = new DOMDocument('1.0', 'windows-1251');
			$request = $doc->createElement('request');
			$doc->appendChild($request);
			$query = $doc->createElement('query');
			$request->appendChild($query);
			$query->setAttribute('login', $login);
			$query->setAttribute('pswd', $password);
			$xml = $doc->saveXML();

			$response = $this->openXML($xml);

			if(!secure_load_dom_document($response, $dom)) {
				$response = '<error>' . getLabel('error-invalid_answer') . '</error>';
				return $response;
			}

			$xpath = new DOMXPath($dom);
			$error = $xpath->query("/response/error");
			if($error->length){
				$errorMessage = $error->item(0)->nodeValue;
				if ($error->item(0)->nodeValue == "Invalid `login` | `pswd`") $errorMessage = getLabel('error-authorization-failed');
				$response = '<error>' . $errorMessage . '</error>';
				return $response;

			}

			$doc = new DOMDocument('1.0', 'windows-1251');
			$request = $doc->createElement('request');
			$doc->appendChild($request);
			$query = $doc->createElement('query');
			$request->appendChild($query);
			$query->setAttribute('report', 'siteAnalyze');
			$query->setAttribute('site', $site);
			$query->setAttribute('date', $date);
			$xml = $doc->saveXML();

			$response = $this->openXML($xml);

			if(!secure_load_dom_document($response, $dom)) {
				$response = '<error>' . getLabel('error-invalid_answer') . '</error>';
				return $response;
			}

			$xpath = new DOMXPath($dom);
			$error = $xpath->query("/response/error");
			if($error->length){
				$errorMessage = $error->item(0)->nodeValue;
				$response = '<error>' . $errorMessage . '</error>';
				return $response;

			}

			if (strpos($response, '<items>') === false){
				$response = '<error>' . getLabel('error-invalid_answer') . '</error>';
			} else {
				$response = str_replace('windows-1251', 'utf-8', $response);
				$response = preg_replace("/<item\s+word\s*=\s*\"[^a-zA-Zа-яА-Я0-9-]+(.*?)\/>/uim", '', $response);
			}

			return $response;
		}

		public function openXML($xml){

			$url = "http://www.megaindex.ru/xml.php";
			$addHeaders = array(
				"Content-type" => "application/x-www-form-urlencoded"
			);
			$postVars = array('text' => $xml);

			if (!$this->cook){

				$response = umiRemoteFileGetter::get($url, false, $addHeaders, $postVars, true);

				$result = preg_split("|(\r\n\r\n)|", $response);
				$header = array_shift($result);
				$response = implode('', $result);

				preg_match_all("!Set\-Cookie\: (.*)=(.*);!siU", $header, $matches);
				foreach($matches[1] as $i => $k){
					$this->cook .= "{$k}={$matches[2][$i]}; ";
				}

			} else {
				$addHeaders['Cookie'] = $this->cook;
				$response = umiRemoteFileGetter::get($url, false, $addHeaders, $postVars);
			}

			return $response;
		}

		public function getDatasetConfiguration($param = '') {
			switch($param) {
				default:
					$loadMethod = 'islands';
					$delMethod  = 'island_delete';
					$typeId		= umiObjectTypesCollection::getInstance()->getTypeIdByGUID('seo-yandex-island');
					$defaults	= 'format[200px]';
					break;
			}

			return array(
				'methods' => array(
					array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'seo', '#__name'=>$loadMethod),
					array('title'=>getLabel('smc-delete'), 				  'module'=>'seo', '#__name'=>$delMethod, 'aliases' => 'tree_delete_element,delete,del')
				),
				'types' => array(
					array('common' => 'true', 'id' => $typeId)
				),
				'stoplist' => array('settings'),
				'default' => $defaults
			);
		}
	};
?>
