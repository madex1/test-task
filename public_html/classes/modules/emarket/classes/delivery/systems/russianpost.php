<?php
	/** Тип доставки "Почта России". */
	class russianpostDelivery extends delivery {
		/**
		 * Валидация типа доставки. Этот тип можно применять всегда.
		 * @implements delivery::validate()
		 * @param order $order заказ
		 * @return true
		 */
		public function validate(order $order) {
			return true;
		}

		/**
		 * Расчитать стоимость доставки.
		 * @implements delivery::getDeliveryPrice()
		 * @param order $order заказ
		 * @return int|string цена доставки или сообщение об ошибке
		 */
		public function getDeliveryPrice(order $order) {
			try {
				$address = $this->getOrderAddress($order);
				$weight = $this->getOrderWeight($order);
				$viewpost = umiObjectsCollection::getInstance()->getObject($this->viewpost);
				$typepost = umiObjectsCollection::getInstance()->getObject($this->typepost);

				if ($this->isEmsViewpost($viewpost)) {
					$price = $this->getEmsPrice($address, $weight);
				} else {
					$price = $this->getMailPrice($address, $weight, $order, $viewpost->identifier, $typepost->identifier);
				}

				return $price;

			} catch (privateException $e) {
				return $e->getMessage();
			}
		}

		/**
		 * Вернуть адрес заказа.
		 * @throws privateException если в заказе не указан адрес
		 * @param order $order заказ
		 * @return object $address адрес
		 */
		private function getOrderAddress($order) {
			$address = umiObjectsCollection::getInstance()->getObject($order->delivery_address);

			if (!$address) {
				throw new privateException(getLabel("error-russianpost-no-address"));
			}

			return $address;
		}

		/**
		 * Вернуть вес заказа.
		 * @throws privateException если в заказе нет товаров
		 * @throws privateException если у товара в заказе не указан вес
		 * @param order $order заказ
		 * @return int вес
		 */
		private function getOrderWeight($order) {
			$items = $order->getItems();

			if (!$items) {
				throw new privateException(getLabel("error-russianpost-empty-order"));
			}

			return $this->calculateOrderItemsWeight($items);
		}

		/**
		 * Вернуть вес товаров.
		 * @throws privateException если у товара не указан вес
		 * @param orderItems[] $items товары в заказе
		 * @return int $weight вес
		 */
		private function calculateOrderItemsWeight($items) {
			$weight = 0;

			foreach ($items as $item) {
				$itemWeight = (int) $item->getItemElement()->getValue("weight");

				if ($itemWeight === 0) {
					throw new privateException(getLabel("error-russianpost-no-weight"));
				}

				$weight += $itemWeight * $item->getAmount();
			}

			return $weight;
		}

		/**
		 * Относится ли вид отправления к ЕМС-доставке?
		 * @param object $viewpost вид отправления
		 * @return boolean
		 */
		private function isEmsViewpost($viewpost) {
			$emsNames = array(getLabel("object-ems_standart"), getLabel("object-ems_declared_value"));
			return in_array($viewpost->name, $emsNames);
		}

		/**
		 * Расчитать стоимость ЕМС-доставки.
		 * @throws privateException см. calculateEmsPrice()
		 * @param object $address адрес
		 * @param int $weight вес
		 * @return string информация о стоимости и сроках доставки
		 */
		private function getEmsPrice($address, $weight) {
			$defaultCity = "Москва";
			$kilogram = 1000;

			$weight = $weight / $kilogram;
			$fromCity = umiObjectsCollection::getInstance()->getObject($this->departure_city);

			if ($fromCity instanceof umiObject) {
				$fromCityName = $fromCity->name;
			} else {
				$fromCityName = $defaultCity;
			}

			$toCityName = $address->city;
			$response = $this->calculateEmsPrice($fromCityName, $toCityName, $weight);

			$price = $response->price;
			$min = $response->term->min;
			$max = $response->term->max;

			return "{$price} руб. (займет от {$min} до {$max} дней)";
		}

		/**
		 * Узнать стоимость ЕМС-доставки на сайте http://emspost.ru.
		 * @throws privateException если превышен вес заказа
		 * @throws privateException если указан несуществующий город отправления или получения
		 * @throws privateException если в ответ на запрос получена ошибка
		 * @param string $fromCityName название города отправления
		 * @param string $ToCityName название города получения
		 * @param int $weight вес
		 * @return object ЕМС-ответ
		 */
		private function calculateEmsPrice($fromCityName, $toCityName, $weight) {
			$response = json_decode(umiRemoteFileGetter::get('http://emspost.ru/api/rest/?method=ems.get.max.weight'));
			$maxWeight = $response->rsp->max_weight;

			if (($weight <= 0) || ($weight > $maxWeight)) {
				throw new privateException(getLabel("error-russianpost-max-weight", false, $maxWeight));
			}

			$response = json_decode(umiRemoteFileGetter::get('http://emspost.ru/api/rest?method=ems.get.locations&type=cities&plain=true'));
			$cities = $response->rsp->locations;

			$fromCityEms = $this->getCityEms(wa_strtoupper($fromCityName), $cities, 'from');
			$toCityEms = $this->getCityEms(wa_strtoupper($toCityName), $cities, 'to');

			$params = array(
				'method' => 'ems.calculate',
				'from'   => $fromCityEms,
				'to'     => $toCityEms,
				'weight' => $weight
			);

			$query = http_build_query($params);
			$response = json_decode(umiRemoteFileGetter::get("http://emspost.ru/api/rest?{$query}"));
			$flag = $response->rsp->stat;

			if ($flag != 'ok') {
				throw new privateException(getLabel("error-russianpost-no-to-city"));
			}

			return $response->rsp;
		}

		/**
		 * Достать из ЕМС-массива городов идентификатор города по его названию.
		 * @throws privateException если искомого города нет в массиве
		 * @param string $cityName название города
		 * @param array $cities массив с городами
		 * @param string 'from'|'to' $mode что ищем - город отправления или город получения
		 * @return string ЕМС-идентификатор города
		 */
		private function getCityEms($cityName, $cities, $mode) {
			foreach ($cities as $city) {
				if ($city->name == $cityName) {
					$cityEms = $city->value;
					break;
				}
			}

			if (!isset($cityEms)) {
				$fromError = getLabel("error-russianpost-no-from-city");
				$toError = getLabel("error-russianpost-no-to-city");

				$msg = ($mode == 'from') ? $fromError : $toError;
				throw new privateException($msg);
			}

			return $cityEms;
		}

		/**
		 * Узнать стоимость почтовой доставки на сайте http://www.russianpost.ru.
		 * @throws privateException если сервер вернул неожиданный ответ
		 * @param object $address адрес
		 * @param int $weight вес
		 * @param order $order название города отправления
		 * @param int $viewpostIdentifier ид вида отправления
		 * @param int $typepostIdentifier ид способа пересылки
		 * @return int|string цена доставки или сообщение об ошибке
		 */
		private function getMailPrice($address, $weight, $order, $viewpostIdentifier, $typepostIdentifier) {
			$value = $this->object->setpostvalue ? ceil($order->getActualPrice()) : 0;
			$zipcode = $address->getValue("index");

			$params = array(
				'viewPost'     => $viewpostIdentifier,
				'typePost'     => $typepostIdentifier,
				'countryCode'  => 643,
				'weight'       => $weight,
				'value1'       => $value,
				'postOfficeId' => $zipcode
			);

			$query = http_build_query($params);
			$url = "http://www.russianpost.ru/autotarif/Autotarif.aspx?{$query}";
			$content = umiRemoteFileGetter::get($url);

			if (preg_match("/<input id=\"key\" name=\"key\" value=\"(\d+)\"\/>/i", $content, $match)) {
				$key = trim($match[1]);
				$headers = array('Content-type' => 'application/x-www-form-urlencoded');
				$postVars = array('key' => $key);

				$content = umiRemoteFileGetter::get($url, false, $headers, $postVars);
				$content = umiRemoteFileGetter::get($url);
			}

			if (preg_match("/span\s+id=\"TarifValue\">([^<]+)<\/span/i", $content, $match)) {
				$price = floatval(str_replace(",", ".", trim($match[1])));

				if ($price > 0) {
					return $price;
				} elseif (preg_match("/span\s+id=\"lblErrStr\">([^<]+)<\/span/i", $content, $match)) {
					return $match[1];
				}
			}

			throw new privateException(getLabel("error-russianpost-undefined"));
		}

		/**
		* @deprecated
		* Старый способ расчета ЕМС-доставки.
		* Используйте $this->getDeliveryPrice()
		*/
		public function calculateSumEMS(&$ems_price, $city_from, $city_to, $weight, &$ems_min, &$ems_max, &$ems_flag) {
			$ems_max_weigt = json_decode(umiRemoteFileGetter::get('http://emspost.ru/api/rest/?method=ems.get.max.weight'));
			if (($weight <= 0) or ($weight > $ems_max_weigt->rsp->max_weight)) {
				$ems_flag = 'Недопустимый вес. Максимально возможный вес: ' . $ems_max_weigt->rsp->max_weight . 'кг. Разделите заказ на несколько.';
				return;
			}
			$ems_locations = json_decode(umiRemoteFileGetter::get('http://emspost.ru/api/rest?method=ems.get.locations&type=cities&plain=true'));
			$ems_locations = $ems_locations->rsp->locations;

			$city_from = wa_strtoupper($city_from);
			foreach ($ems_locations as $k => $value) {
				if ($value->name == $city_from) {
					$ems_city_from = $ems_locations[$k];
					break;
				}
			}
			$city_to = wa_strtoupper($city_to);
			foreach ($ems_locations as $k => $value) {
				if ($value->name == $city_to){
					$ems_city_to = $ems_locations[$k];
					break;
				}
			}

			if (!isset($ems_city_to)) {
				$ems_flag = 'Ошибка расчета цены. Уточните город на странице адреса.';
				return;
			}

			$ems_calculate_price = json_decode(umiRemoteFileGetter::get('http://emspost.ru/api/rest?method=ems.calculate&from=' . $ems_city_from->value . '&to=' . $ems_city_to->value . '&weight=' . $weight));

			$ems_flag = $ems_calculate_price->rsp->stat;
			if ($ems_flag == 'ok') {
				return $ems_calculate_price->rsp;
			}

			$ems_flag = 'Ошибка расчета цены. Уточните город на странице адреса.';
			return;
		}
	}
?>
