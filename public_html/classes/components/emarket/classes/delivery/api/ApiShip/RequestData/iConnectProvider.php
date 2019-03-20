<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iProvider;

	/**
	 * Интерфейс данных запроса установления подключения к провайдеру (службе доставки)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData
	 */
	interface iConnectProvider {

		/** @const string COMPANY_ID_KEY ключ данных запроса с идентификатором компании клиента */
		const COMPANY_ID_KEY = 'companyId';

		/** @const string PROVIDER_ID_KEY ключ данных запроса с идентификатором подключаемого провайдера */
		const PROVIDER_ID_KEY = 'providerKey';

		/** @const string PROVIDER_PARAMS_KEY ключ данных запроса с параметрами подключаемого провайдера */
		const PROVIDER_PARAMS_KEY = 'connectParams';

		/**
		 * Конструктор
		 * @param array $data данные запроса
		 */
		public function __construct(array $data);

		/**
		 * Устанавливает данные запроса
		 * @param array $data данные запроса
		 * @return iConnectProvider
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Устанавливает идентификатор компании пользователя ApiShip
		 * @param int $companyId идентификатор компании пользователя ApiShip
		 * @return iConnectProvider
		 */
		public function setCompanyId($companyId);

		/**
		 * Возвращает идентификатор компании пользователя ApiShip
		 * @return int
		 */
		public function getCompanyId();

		/**
		 * Устанавливает идентификатор подключаемого провайдера
		 * @param string $providerKey идентификатор подключаемого провайдера
		 * @return iConnectProvider
		 */
		public function setProviderKey($providerKey);

		/**
		 * Возвращает идентификатор подключаемого провайдера
		 * @return string
		 */
		public function getProviderKey();

		/**
		 * Устанавливает подключаемого провайдера
		 * @param iProvider $provider подключаемый провайдер
		 * @return $this
		 */
		public function setProvider(iProvider $provider = null);

		/**
		 * Возвращает подключаемого провайдера
		 * @return iProvider|null
		 */
		public function getProvider();
	}
