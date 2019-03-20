<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Интерфейс контейнера настроек провайдеров
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	interface iProvidersSettings {

		/**
		 * Конструктор
		 * @param \ApiShipDelivery $delivery экземпляр способа доставки "ApiShip"
		 * @param iProvidersFactory $providersFactory фабрика провайдеров
		 */
		public function __construct(\ApiShipDelivery $delivery, iProvidersFactory $providersFactory);

		/**
		 * Возвращает настройки всех провайдеров или конкретного, если задан ключ
		 * @param string|null $key ключ провайдера
		 * @return array|null
		 */
		public function get($key = null);

		/**
		 * Устанавливает настройки всех провайдеров или конкретного, если задан ключ
		 * @param array $settings настройки
		 * @param string|null $key ключ провайдера
		 * @return iProvidersSettings
		 */
		public function set(array $settings, $key = null);

		/**
		 * Сохраняет настройки
		 * @return iProvidersSettings
		 */
		public function save();

		/**
		 * Добавляет настройки заданных провайдеров
		 * @param array $keys ключ провайдеров
		 * @return iProvidersSettings
		 */
		public function appendProvidersSettings(array $keys);

		/**
		 * Удаляет настройки заданных провайдеров
		 * @param array $keys ключ провайдеров
		 * @return iProvidersSettings
		 */
		public function removeProvidersSettings(array $keys);

		/**
		 * Удаляет настройки всех провайдеров или заданного, если указан его ключ
		 * @param string|null $key ключ провайдера
		 * @return iProvidersSettings
		 */
		public function remove($key = null);
	}
