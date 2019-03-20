<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\ProvidersKeys;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Exceptions\UnsupportedProviderKeyException;

	/**
	 * Интерфейс фабрики провайдеров (служб доставки, сд)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	interface iProvidersFactory {

		/**
		 * Создает провайдера по его строковому идентификатору
		 * @param ProvidersKeys $providerKeys строковой идентификатор провайдера
		 * @return iProvider
		 * @throws UnsupportedProviderKeyException
		 */
		public static function create(ProvidersKeys $providerKeys);

		/**
		 * Создает провайдера по его строковому идентификатору и инициализирует значения его настроек
		 * @param ProvidersKeys $providerKey строковой идентификатор провайдера
		 * @param iProvidersSettings $settings настройки провайдеров
		 * @return iProvider
		 * @throws UnsupportedProviderKeyException
		 */
		public static function createWithSettings(ProvidersKeys $providerKey, iProvidersSettings $settings);

		/**
		 * Возвращает список идентификаторов поддерживаемых служб доставок
		 * @return array
		 */
		public static function getSupportedProviderKeyList();

		/**
		 * Возвращает список поддерживаемы провайдеров
		 * @return iProvider[]
		 * @throws \publicAdminException
		 */
		public static function getProvidersList();
	}
