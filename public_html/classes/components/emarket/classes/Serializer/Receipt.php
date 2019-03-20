<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer;

	use UmiCms\System\Hierarchy\Domain\iDetector;
	use UmiCms\System\Trade\Offer\Price\Currency\iFacade as iCurrencyFacade;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFacade as iVatFacade;
	use UmiCms\Classes\Components\Emarket\Payment\Mode\iFacade as iPaymentModeFacade;
	use UmiCms\Classes\Components\Emarket\Payment\Subject\iFacade as iPaymentSubjectFacade;


	/**
	 * Класс абстрактного сериализатора для чека по ФЗ-54
	 * @package UmiCms\Classes\Components\Emarket\Payment\Serializer
	 */
	abstract class Receipt implements iReceipt {

		/** @var iCurrencyFacade $currencyFacade фасад валют */
		private $currencyFacade;

		/** @var iVatFacade $vatFacade фасад ставок НДС */
		private $vatFacade;

		/** @var iPaymentSubjectFacade $paymentSubjectFacade фасад признаков предмета расчета */
		private $paymentSubjectFacade;

		/** @var iPaymentModeFacade $paymentModeFacade фасад признаков способа расчета */
		private $paymentModeFacade;

		/** @var iDetector $domainDetector определитель домена */
		private $domainDetector;

		/** @inheritdoc */
		public function __construct(
			iCurrencyFacade $currencyFacade,
			iVatFacade $vatFacade,
			iDetector $domainDetector,
			iPaymentSubjectFacade $paymentSubjectFacade,
			iPaymentModeFacade $paymentModeFacade
		) {
			$this->currencyFacade = $currencyFacade;
			$this->vatFacade = $vatFacade;
			$this->domainDetector = $domainDetector;
			$this->paymentSubjectFacade = $paymentSubjectFacade;
			$this->paymentModeFacade = $paymentModeFacade;
		}

		/**
		 * @inheritdoc
		 * @throws \publicException
		 */
		public function getOrderItemInfoList(\order $order) {
			$orderItemInfoList = [];

			try {
				$orderItemInfoList[] = $this->getDeliveryInfo($order);
			} catch (\expectObjectException $e) {
				//nothing
			}

			foreach ($order->getItems() as $orderItem) {
				$orderItemInfoList[] = $this->getOrderItemInfo($order, $orderItem);
			}

			if (empty($orderItemInfoList)) {
				throw new \publicException(getLabel('error-payment-empty-order'));
			}

			return $this->fixItemPriceSummary($order, $orderItemInfoList);
		}

		/**
		 * @inheritdoc
		 * @throws \publicException
		 */
		public function getContact(\order $order) {
			$email = $this->getCustomer($order)
				->getEmail();

			//@todo: отрефакторить класс umiMail и передать его в зависимостях
			if (!\umiMail::checkEmail($email)) {
				throw new \publicException(getLabel('error-payment-wrong-customer-email'));
			}

			return $email;
		}

		/**
		 * Возвращает информацию о доставке
		 * @param \order $order заказ
		 * @return mixed
		 */
		abstract protected function getDeliveryInfo(\order $order);

		/**
		 * Возвращает информацию о товарном наименовании заказа
		 * @param \order $order заказ
		 * @param \orderItem $orderItem товарное наименование
		 * @return mixed
		 */
		abstract protected function getOrderItemInfo(\order $order, \orderItem $orderItem);

		/**
		 * Исправляет стоимости товарных наименований, если они "не бьются" со стоимостью заказа
		 * @param \order $order заказ
		 * @param array $orderItemList информацию о составе заказа для печати чека
		 * @return mixed
		 */
		abstract protected function fixItemPriceSummary(\order $order, array $orderItemList);

		/**
		 * Возвращает стоимость товарного наименования заказа
		 * @param \order $order заказ
		 * @param \orderItem $orderItem товарное наименование
		 * @return float|string
		 */
		protected function getOrderItemPrice(\order $order, \orderItem $orderItem) {
			if (!$order->getDiscountValue()) {
				return $orderItem->getActualPrice();
			}

			$orderItemPrice = $orderItem->getActualPrice() * (100 - $order->getDiscountPercent()) / 100;
			return round($orderItemPrice, -1, PHP_ROUND_HALF_DOWN);
		}

		/**
		 * Подготавливает название позиции заказа
		 * @param string $name название позиции заказа
		 * @return string
		 */
		protected function prepareItemName($name) {
			return trim($name);
		}

		/**
		 * Возвращает доставку заказа
		 * @param \order $order заказ
		 * @return \courierDelivery|\delivery|mixed|\russianpostDelivery|\selfDelivery
		 * @throws \expectObjectException
		 */
		protected function getDelivery(\order $order) {
			$id = $order->getDeliveryId();

			try {
				//@todo: отрефакторить класс delivery и передать его в зависимостях
				$delivery = \delivery::get($id);
			} catch (\coreException $exception) {
				throw new \expectObjectException(getLabel('error-unexpected-exception'));
			}

			return $delivery;
		}

		/**
		 * Возвращает покупателя заказа
		 * @param \order $order заказ
		 * @return \customer
		 * @throws \expectObjectException
		 */
		protected function getCustomer(\order $order) {
			//@todo: отрефакторить класс customer и передать его в зависимостях
			$customer = \customer::get(true, $order->getCustomerId());

			if (!$customer instanceof \customer) {
				throw new \expectObjectException(getLabel('error-unexpected-exception'));
			}

			return $customer;
		}

		/**
		 * @inheritdoc
		 * @throws \publicException
		 * @throws \coreException
		 */
		public function getVat($object) {
			$rateId = $object->getTaxRateId() ?: $this->getDefaultTaxRateId();

			if (!$rateId) {
				throw new \publicException(getLabel('error-payment-order-item-empty-tax'));
			}

			return $this->getVatFacade()
				->get($rateId);
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 * @throws \publicException
		 */
		public function getPaymentSubject($object) {
			$rateId = $object->getPaymentSubjectId() ?: $this->getDefaultPaymentSubjectId();

			if (!$rateId) {
				throw new \publicException(getLabel('error-payment-order-item-empty-payment-subject'));
			}

			return $this->getPaymentSubjectFacade()
				->get($rateId);
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 * @throws \publicException
		 * @throws \privateException
		 */
		public function getPaymentMode($object) {
			$rateId = $object->getPaymentModeId() ?: $this->getDefaultPaymentModeId();

			if (!$rateId) {
				throw new \publicException(getLabel('error-payment-order-item-empty-payment-mode'));
			}

			return $this->getPaymentModeFacade()
				->get($rateId);
		}

		/**
		 * Возвращает адрес домена с протоколом
		 * @return string
		 * @throws \coreException
		 */
		protected function getDomain() {
			return rtrim($this->getDomainDetector()->detectUrl(), '/');
		}

		/**
		 * Возвращает код валюты системы по умолчанию
		 * @return string
		 */
		protected function getCurrencyCode() {
			return $this->getCurrencyFacade()
				->getDefault()
				->getISOCode();
		}

		/**
		 * Возвращает фасад валют
		 * @return iCurrencyFacade
		 */
		protected function getCurrencyFacade() {
			return $this->currencyFacade;
		}

		/**
		 * Возвращает фасад ставок НДС
		 * @return iVatFacade
		 */
		protected function getVatFacade() {
			return $this->vatFacade;
		}

		/**
		 * Возвращает фасад признаков предмета расчета
		 * @return iPaymentSubjectFacade
		 */
		protected function getPaymentSubjectFacade() {
			return $this->paymentSubjectFacade;
		}

		/**
		 * Возвращает фасад признаков способа расчета
		 * @return iPaymentModeFacade
		 */
		protected function getPaymentModeFacade() {
			return $this->paymentModeFacade;
		}

		/**
		 * Возвращает определитель домена
		 * @return iDetector
		 */
		protected function getDomainDetector() {
			return $this->domainDetector;
		}

		/**
		 * Возвращает значение НДС по умолчанию
		 * @return bool|mixed|null
		 * @throws \coreException
		 */
		protected function getDefaultTaxRateId() {
			return $this->getDefaultOrderSetting('taxRateId');
		}

		/**
		 * Возвращает значение признака предмета расчета по умолчанию
		 * @return bool|mixed|null
		 * @throws \coreException
		 */
		protected function getDefaultPaymentSubjectId() {
			return $this->getDefaultOrderSetting('paymentSubjectId');
		}

		/**
		 * Возвращает значение признака способа расчета по умолчанию
		 * @return bool|mixed|null
		 * @throws \coreException
		 */
		protected function getDefaultPaymentModeId() {
			return $this->getDefaultOrderSetting('paymentModeId');
		}

		/**
		 * Возвращает значение по умолчанию
		 * @param $name
		 * @return bool|mixed|null
		 * @throws \coreException
		 */
		protected function getDefaultOrderSetting($name) {
			$settings = $this->getEmarketSettings();

			if ($settings instanceof \EmarketSettings) {
				return $settings->get(\EmarketSettings::ORDER_ITEM_SECTION, $name);
			}

			return false;
		}

		/**
		 * Возвращает настройки модуля "Интернет-магазин"
		 * @return \EmarketSettings|false
		 * @throws \coreException
		 */
		protected function getEmarketSettings() {
			/** @var \emarket $emarket */
			$emarket = \cmsController::getInstance()->getModule('emarket');
			return ($emarket instanceof \def_module) ? $emarket->getSettings() : false;
		}
	}
