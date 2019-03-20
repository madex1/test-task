<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer;

	use UmiCms\System\Hierarchy\Domain\iDetector;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Payment\iMode;
	use UmiCms\Classes\Components\Emarket\Payment\iSubject;
	use UmiCms\System\Trade\Offer\Price\Currency\iFacade as iCurrencyFacade;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFacade as iVatFacade;
	use UmiCms\Classes\Components\Emarket\Payment\Mode\iFacade as iPaymentModeFacade;
	use UmiCms\Classes\Components\Emarket\Payment\Subject\iFacade as iPaymentSubjectFacade;

	/**
	 * Интерфейс сериализатора для чека по ФЗ-54
	 * @package UmiCms\Classes\Components\Emarket\Payment\Serializer
	 */
	interface iReceipt {

		/**
		 * Конструктор
		 * @param iCurrencyFacade $currencyFacade фасад валют
		 * @param iVatFacade $vatFacade фасад НДС
		 * @param iDetector $domainDetector определитель домена
		 * @param iPaymentSubjectFacade $paymentSubjectFacade фасад признака предмета расчета
		 * @param iPaymentModeFacade $paymentModeFacade фасад признака способа расчета
		 */
		public function __construct(
			iCurrencyFacade $currencyFacade,
			iVatFacade $vatFacade,
			iDetector $domainDetector,
			iPaymentSubjectFacade $paymentSubjectFacade,
			iPaymentModeFacade $paymentModeFacade
		);

		/**
		 * Возвращает информацию о составе заказа для печати чека
		 * @param \order $order
		 * @return mixed
		 */
		public function getOrderItemInfoList(\order $order);

		/**
		 * Возвращает контакт покупателя заказа
		 * @param \order заказ
		 * @return mixed
		 */
		public function getContact(\order $customer);

		/**
		 * Возвращает ставку НДС
		 * @param \orderItem|\delivery $object товарное наименование или доставка
		 * @return iVat
		 */
		public function getVat($object);

		/**
		 * Возвращает признак предмета расчета
		 * @param \orderItem|\delivery $object товарное наименование или доставка
		 * @return iSubject
		 */
		public function getPaymentSubject($object);

		/**
		 * Возвращает ставку НДС
		 * @param \orderItem|\delivery $object товарное наименование или доставка
		 * @return iMode
		 */
		public function getPaymentMode($object);
	}
