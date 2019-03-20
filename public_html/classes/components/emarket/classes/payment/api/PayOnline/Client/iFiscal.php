<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client;

	use UmiCms\Utils\Logger\iFactory as LoggerFactory;

	/**
	 * Интерфейс клиента сервиса онлайн-фискализации интернет-платежей PayOnline
	 * @package UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client
	 */
	interface iFiscal {

		/**
		 * Конструктор
		 * @param LoggerFactory $loggerFactory фабрика логгеров
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(LoggerFactory $loggerFactory, \iConfiguration $configuration);

		/**
		 * Устанавливает идентификатор клиента сервиса
		 * @param string $id идентификатор
		 * @return $this
		 */
		public function setMerchantId($id);

		/**
		 * Устанавливает приватный ключ безопасности сервиса
		 * @param string $key ключ
		 * @return $this
		 */
		public function setPrivateSecurityKey($key);

		/**
		 * Устанавливает флаг ведения журнала
		 * @param bool $flag значение флага
		 * @return $this
		 */
		public function setKeepLog($flag = true);

		/**
		 * Запрашивает печать чека
		 * @param \stdClass $request данные чека
		 * @return \stdClass
		 */
		public function requestReceipt(\stdClass $request);
	}
