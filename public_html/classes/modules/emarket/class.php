<?php
	define('PUSH_SERVER', 'http://push.umi-cms.ru/');

	use UmiCms\Service;

	class emarket extends def_module {
		public $iMaxCompareElements;

		public function __construct() {
			parent::__construct();
			$regedit = $this->regedit;
			$config = $this->mainConfiguration;


			$this->iMaxCompareElements = $config->get('modules', 'emarket.compare.max-items');

			if(empty($this->iMaxCompareElements)) {
				$this->iMaxCompareElements = 3;
			}

			if($this->iMaxCompareElements<=1) {
				$this->iMaxCompareElements = 3;
			}

			preg_match("/[1-9]+.[0-9]+.[0-9]+/", PHP_VERSION, $matches);
			$yandexMarketAvailable = version_compare($matches[0], '5.3.0', '>');

			$this->loadCommonExtension();

			if($this->cmsController->getCurrentMode() == "admin") {
				$commonTabs = $this->getCommonTabs();
				$configTabs = $this->getConfigTabs();

				$this->__loadLib("__admin.php");
				$this->__implement("__emarket_admin");

				$this->__loadLib("__admin_orders.php");
				$this->__implement("__emarket_admin_orders");

				$this->__loadLib("__admin_mobile.php");
				$this->__implement("__emarket_admin_mobile");


				if($commonTabs) $commonTabs->add("orders", array('order_edit'));

				if($configTabs) {
					$configTabs->add("config");
					$configTabs->add("mail_config");
				}

				if ($yandexMarketAvailable) {
					if ($configTabs) {
						$configTabs->add("yandex_market_config");
						$this->__loadLib("__admin_yandex_market.php");
						$this->__implement("__emarket_admin_yandex_market");
					}
				}

				if($regedit->getVal('//modules/emarket/enable-discounts')) {
					if($commonTabs) $commonTabs->add("discounts", array('discount_add', 'discount_edit'));

					$this->__loadLib("__admin_discounts.php");
					$this->__implement("__emarket_admin_discounts");
				}

				if($regedit->getVal('//modules/emarket/enable-delivery')) {
					if($commonTabs) $commonTabs->add("delivery", array('delivery_add', 'delivery_edit', 'delivery_address_edit'));
					$this->__loadLib("__admin_delivery.php");
					$this->__implement("__emarket_admin_delivery");
				}

				if($regedit->getVal('//modules/emarket/enable-payment')) {
					if($commonTabs) $commonTabs->add("payment", array('payment_add', 'payment_edit'));

					$this->__loadLib("__admin_payments.php");
					$this->__implement("__emarket_admin_payment");
				}

				if($regedit->getVal('//modules/emarket/enable-currency')) {
					if($commonTabs) $commonTabs->add("currency", array('currency_add', 'currency_edit'));

					$this->__loadLib("__admin_currency.php");
					$this->__implement("__emarket_admin_currency");
				}

				if($regedit->getVal('//modules/emarket/enable-stores')) {
					if($commonTabs) $commonTabs->add("stores", array('store_add', 'store_edit'));

					$this->__loadLib("__admin_stores.php");
					$this->__implement("__emarket_admin_stores");
				}

				if($commonTabs) $commonTabs->add("stats");
				$this->__loadLib("__admin_stats.php");
				$this->__implement("__emarket_admin_stats");

				$this->__loadLib("__admin_realpayments.php");
				$this->__implement("__emarket_admin_realpayments");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__emarket_custom_admin");

			}

			$this->__loadLib("__purchasing.php");
			$this->__implement("__emarket_purchasing");

			$this->__loadLib("__purchasing_one_step.php");
			$this->__implement("__emarket_purchasing_one_step");

			$this->__loadLib("__discounts.php");
			$this->__implement("__emarket_discounts");

			$this->__loadLib("__stores.php");
			$this->__implement("__emarket_stores");

			$this->__loadLib("__currency.php");
			$this->__implement("__emarket_currency");

			$this->__loadLib("__compare.php");
			$this->__implement("__emarket_compare");

			$this->__loadLib("__notification.php");
			$this->__implement("__emarket_notification");

			$this->__loadLib("__events.php");
			$this->__implement("__emarket_events");

			if ($yandexMarketAvailable) {
				$this->__loadLib("__yandex_market.php");
				$this->__implement("__emarket_yandex_market");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__emarket_custom");

			$this->umiTypesHelper->getFieldsByObjectTypeGuid('emarket-currency');
		}

		/**
		 * Возвращает экземпляр класса фасада валют
		 * @return UmiCms\System\Trade\Offer\Price\Currency\iFacade
		 * @throws Exception
		 */
		public function getCurrencyFacade() {
			return Service::CurrencyFacade();
		}

		/**
		 * Возвращает данные для вывода личного кабинета покупателя
		 * @param string $template Название шаблона вывода
		 * @param int $customerId ID покупателя владельца личного кабинета
		 * @param string $checkSum Контрольная сумма для верификации покупателя
		 * @return array
		 */
		public function personal($template = 'default', $customerId = false, $checkSum = false) {
			
			if ($customerId !== false && $checkSum !== false) {
				$correctCheckSum = $this->getCheckSum($customerId);
				
				if ($correctCheckSum === $checkSum) {
					$customer = customer::get(false, $customerId);
				}
			}
			
			if (!$customer) {
				$customer = customer::get();
			}
			
			$data = array(
				'customer' => array(
					'@id' =>  $customer->getId()
				)
			);
			
			list($tpl_block) = def_module::loadTemplates("emarket/".$template, "personal");
			return def_module::parseTemplate($tpl_block, $data);
		}
		/**
		 * Возвращает контрольную сумму строки
		 * @param string $string
		 * @return string
		 */
		public function getCheckSum($string) {
			$config = mainConfiguration::getInstance();
			$salt = $config->get('system', 'salt');
			return md5($string . $salt);
		}
		
		/**
		 * Возвращает параметры ссылки личного кабинета покупателя
		 * @param int $customerId ID покупателя
		 * @return string
		 */
		public function getPersonalLinkParams($customerId) {
			$checkSum = $this->getCheckSum($customerId);
			return $customerId . '/' . $checkSum;
		}

		public function customerDeliveryList($template = 'default') {
			$this->__loadLib("__delivery.php");
			$this->__implement("__emarket_delivery");

			$order = $this->getBasketOrder();
			return $this->renderDeliveryAddressesList($order, $template);
		}

		public static function isBasket(order $order) {
			return !($order->getOrderStatus() && $order->getOrderStatus() != order::getStatusByCode('editing') && $order->getNumber());
  		}

		public function getObjectEditLink($objectId, $type = false) {
			switch($type) {
				case 'order':
					return $this->pre_lang . "/admin/emarket/order_edit/{$objectId}/";

				case 'discount':
					return $this->pre_lang . "/admin/emarket/discount_edit/{$objectId}/";

				case 'currency':
					return $this->pre_lang . "/admin/emarket/currency_edit/{$objectId}/";

				case 'delivery':
					return $this->pre_lang . "/admin/emarket/delivery_edit/{$objectId}/";

				case 'payment':
					return $this->pre_lang . "/admin/emarket/payment_edit/{$objectId}/";

				case 'store':
					return $this->pre_lang . "/admin/emarket/store_edit/{$objectId}/";

				default: {
					return false;
				}
			}
		}
	};
?>
