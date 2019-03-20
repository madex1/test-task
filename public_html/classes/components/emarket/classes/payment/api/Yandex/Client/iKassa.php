<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Yandex\Client;

	use UmiCms\Utils\Logger\iFactory as LoggerFactory;

	/**
	 * Интерфейс клиента API Яндекс.Касса
	 * @link https://kassa.yandex.ru/docs/guides/
	 * @link https://yandex.ru/support/checkout/payments/api.html
	 * @link https://kassa.yandex.ru/docs/checkout-api/
	 * @package UmiCms\Classes\Components\Emarket\Payment\Yandex\Client
	 */
	interface iKassa {

		/**
		 * Конструктор
		 * @param LoggerFactory $loggerFactory экземпляр фабрики логгеров
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(LoggerFactory $loggerFactory, \iConfiguration $configuration);

		/**
		 * Устанавливает идентификатор магазина
		 * @param string $shopId идентификатор магазина
		 * @return $this
		 */
		public function setShopId($shopId);

		/**
		 * Устанавливает секретный ключ
		 * @param string $secretKey секретный ключ
		 * @return $this
		 */
		public function setSecretKey($secretKey);

		/**
		 * Устанавливает флаг ведения журнала
		 * @param bool $flag значение флага
		 * @return $this
		 */
		public function setKeepLog($flag = true);

		/**
		 * Создает платеж и возвращает его данные
		 * @param array $request данные запроса
		 * @return array
		 *
		 * [
		 *      'id' => 'Идентификатор платежа',
		 *      'confirmation' => [
		 *          'confirmation_url'  => 'Адрес, куда нужно перенаправить пользователя для оплаты'
		 *      ]
		 * ]
		 */
		public function createPayment(array $request);

		/**
		 * Подтверждает заказ
		 * @param string $id идентификатор платежа
		 * @param array $request данные запроса
		 * @return bool
		 */
		public function approvePayment($id, array $request);

		/**
		 * Отменяет заказ
		 * @param string $id идентификатор платежа
		 * @return bool
		 */
		public function cancelPayment($id);
	}
