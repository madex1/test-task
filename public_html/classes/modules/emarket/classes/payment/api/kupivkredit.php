<?php
	/**
	 * Class kvkAPI
	 *
	 * Класс работы с API КупиВКредит
	 */
	class kvkAPI {
		/**
		 * Ключ API
		 * @var string $apiKey
		 */
		private $apiKey = "";
		/**
		 * ID Партнера
		 * @var string $partnerId
		 */
		private $partnerId = "";
		/**
		 * Секретная строка
		 * @var string $secretKey
		 */
		private $secretKey = "";

		private $demoMode = false;

		/**
		 * Создание объекта, установка ключей
		 *
		 * @param string $apiKey
		 * @param string $partnerId
		 * @param string $secretKey
		 */
		public function __construct($apiKey, $partnerId, $secretKey, $demoMode) {
			$this->apiKey = $apiKey;
			$this->partnerId = $partnerId;
			$this->secretKey = $secretKey;
			$this->demoMode = $demoMode;
		}

		/**
		 * Вызов метода API
		 *
		 * @param string $method Имя метода
		 * @param array $params Массив параметров
		 *
		 * @return kvkAPIResponse Ответ сервера
		 */
		public function call($method, $params = array()) {
			$apiUrl = $this->demoMode ? "https://kupivkredit-test-api.tcsbank.ru/api/" : 'https://api.kupivkredit.ru/api/';

			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => $apiUrl . $method,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => kvkAPIRequest::encode($this->apiKey, $this->partnerId, $this->secretKey, $params),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false
			));

			$response = curl_exec($ch);
			curl_close($ch);

			return new kvkAPIResponse($response);
		}
	}

	/** Обертка ответа для KupiVKredit API */
	class kvkAPIResponse {
		/**
		 * @var SimpleXMLElement $xml Ответ
		 * @var string $status Статус ответа
		 * @var int $statusCode Код статуса
		 * @var string $result Результат
		 */
		private $xml, $status, $statusCode, $result;

		/**
		 * Получ
		 * @param $xpath Путь
		 *
		 * @return SimpleXMLElement
		 */
		private function getResponseSingleNode($xpath) {
			if(!$this->xml) {
				return null;
			}

			$nodes = $this->xml->xpath($xpath);
			if (!$nodes) {
				return null;
			}
			list($node) = $nodes;
			return $node;
		}

		public function __construct($xmlData) {
			$this->xml = secure_load_simple_xml($xmlData);
		}

		/** @return string */
		public function __toString() {
			return $this->xml->asXml();
		}

		/**
		 * Получить статус ответа
		 *
		 * OK или FAILED
		 *
		 * @return string
		 */
		public function getStatus() {
			if (!$this->status) {
				$this->status = (string) $this->getResponseSingleNode("//response/status");
			}

			return $this->status;
		}

		/**
		 * Получить код статуса ответа
		 *
		 * @return string
		 */
		public function getStatusCode() {
			if (!$this->statusCode) {
				$this->statusCode = (string) $this->getResponseSingleNode("//response/statusCode");
			}

			return $this->statusCode;
		}

		/**
		 * Преобразует SimpleXMLElement в array
		 *
		 * @param SimpleXMLElement $xml
		 *
		 * @return array
		 */
		private static function xmlToArray($xml) {
			$xml = (array) $xml;

			if(empty($xml)) {
				return null;
			}

			foreach ($xml as $key=>$val) {
				if ($val instanceof SimpleXMLElement) {
					$xml[$key] = self::xmlToArray($val);
				} elseif (empty($val)) {
					$xml[$key] = null;
				}
			}

			return $xml;
		}

		/** * @return string */
		public function getResult() {
			if (!$this->result) {
				$this->result = self::xmlToArray($this->getResponseSingleNode("//response/result"));
				if(count($this->result) == 1) {
					list($this->result) = $this->result;
				}
			}

			return $this->result;
		}

		/**
		 * Успешен ли ответ
		 *
		 * @return bool
		 */
		public function isSuccess() {
			return $this->getStatus() == "OK";
		}

		/**
		 * Преобразовать полученный результат во ViewModel
		 *
		 * @return array
		 */
		public function toBlockArray() {
			return array(
				"status" => $this->getStatus(),
				"statusCode" => $this->getStatusCode(),
				"result" => (string) $this->getResponseSingleNode("//result")
			);
		}
	}

	/**
	 * Class kvkAPIRequest
	 *
	 * Работа с запросом на сервер КупиВКредит
	 */
	class kvkAPIRequest {
		/**
		 * Кодирование запроса для сервера KVK
		 * @param string $apiKey API ключ
		 * @param string $partnerId Id партнера
		 * @param string $secretKey Секретный ключ
		 * @param array $params Параметры
		 *
		 * @return string Сообщение(конверт)
		 */
		public static function encode($apiKey, $partnerId, $secretKey, $params) {
			return self::createAPICover( self::createAPIRequest($apiKey, $partnerId, $params), $secretKey);
		}

		/**
		 * Декодирование запроса от сервера KVK
		 *
		 * @param string $message Сообщение
		 * @param string $secretKey Секретный ключ
		 *
		 * @return kvkAPIResponse
		 */
		public static function decode($message, $secretKey = null) {
			$xml = @simplexml_load_string($message);

			if(!$xml) {
				return null;
			}

			list($request) = $xml->xpath("//Base64EncodedMessage");
			list($signature) = $xml->xpath("//RequestSignature");

			if (!is_null($secretKey) && self::signMessage((string) $request, $secretKey) != (string) $signature) {
				return false;
			} else {
				$xml = @simplexml_load_string(base64_decode($request));
				if(!$xml) return false;

				/** @var SimpleXMLElement $result */
				list($result) = $xml->xpath("//request");
				$result = str_replace(array("<request>", "</request>"), "", $result->asXML());

				return new kvkAPIResponse("<response><status>OK</status><statusCode>0</statusCode><result>$result</result></response>");
			}
		}

		/**
		 * Страница с показом widget
		 *
		 * @param $orderData Элементы заказа
		 * @param $secretKey Секретный ключ для кодирования
		 *
		 * @return array ViewModel
		 */
		public static function encodeWidget($orderData, $secretKey) {
			$orderData = base64_encode(json_encode($orderData));
			return array(
				"@action" => "widget",
				"order" => $orderData,
				"sig" => self::signMessage($orderData, $secretKey)
			);
		}

		/**
		 * Подписать сообщение ключом $secretKey
		 *
		 * @param $message Сообщение
		 * @param $secretKey Секретный ключ
		 *
		 * @return string Подпись
		 */
		private static function signMessage($message, $secretKey) {
			$message = $message . $secretKey;
			$result = md5($message) . sha1($message);
			for($i = 0; $i < 1102; $i++) {
				$result = md5($result);
			}
			return $result;
		}

		/**
		 * Создать конверт запроса на сервер
		 *
		 * @param $message Сообщение
		 * @param $secretKey Секретный ключ
		 *
		 * @return string Запрос на сервер
		 */
		protected static function createAPICover($message, $secretKey) {
			$msg = base64_encode($message);
			$xmlRequest  = '<' . '?xml version="1.0" encoding="utf-8" ?' . ">\n";
			$xmlRequest .= "<envelope>\n";
			$xmlRequest .= "<Base64EncodedMessage>" . $msg . "</Base64EncodedMessage>\n";
			$xmlRequest .= "<RequestSignature>" . self::signMessage($msg, $secretKey) . "</RequestSignature>\n";
			$xmlRequest .= "</envelope>";

			return $xmlRequest;
		}

		/**
		 * Формирует xml строку параметров для запроса
		 *
		 * @param $params Параметры
		 *
		 * @return string
		 */
		private static function formParamsRequest($params){
			$xmlRequest = "";
			foreach ($params as $key => $param) {
				if(!is_array($param)) {
					$xmlRequest .= "<{$key}>{$param}</{$key}>\n";
				} else {
					$xmlRequest .= "<{$key}>" . self::formParamsRequest($param) . "</{$key}>\n";
				}
			}

			return $xmlRequest;
		}

		/**
		 * Создает запрос к серверу API
		 *
		 * @param string $apiKey ключ API
		 * @param string $partnerId Id партнера в системе
		 * @param array $params Параметры
		 *
		 * @return string Запрос
		 */
		protected static function createAPIRequest($apiKey, $partnerId, $params) {
			$xmlRequest  = '<' . '?xml version="1.0" encoding="utf-8" ?' . ">\n";
			$xmlRequest .= "<request>\n";
			$xmlRequest .= "<partnerId>{$partnerId}</partnerId>\n";
			$xmlRequest .= "<apiKey>{$apiKey}</apiKey>\n";

			$xmlRequest .= "<params>\n";
			$xmlRequest .= self::formParamsRequest($params);
			$xmlRequest .= "</params>\n";

			$xmlRequest .= "</request>";

			return $xmlRequest;
		}
	}
?>