<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\ProvidersKeys;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Exceptions\UnsupportedProviderKeyException;

	/**
	 * Фабрика провайдеров (служб доставки, сд)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	class ProvidersFactory implements iProvidersFactory {

		/** @var string PROVIDER_NAME_SPACE пространство имен класса провайдера */
		const PROVIDER_NAME_SPACE = 'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\\';

		/** @inheritdoc */
		public static function create(ProvidersKeys $providerKeys) {
			$providerKey = (string) $providerKeys;
			$className = self::getProviderClassName($providerKey);

			if (!class_exists($className)) {
				throw new UnsupportedProviderKeyException($providerKey);
			}

			return new $className();
		}

		/** @inheritdoc */
		public static function createWithSettings(ProvidersKeys $providerKey, iProvidersSettings $settings) {
			$provider = self::create($providerKey);
			$providerSettings = $settings->get($provider->getKey());
			$providerSettings = is_array($providerSettings) ? $providerSettings : [];
			$provider->import($providerSettings);

			return $provider;
		}

		/** @inheritdoc */
		public static function getSupportedProviderKeyList() {
			$providerKeyList = new ProvidersKeys();
			$providerKeyValues = $providerKeyList->getAllValues();

			return array_values($providerKeyValues);
		}

		/** @inheritdoc */
		public static function getProvidersList() {
			$providers = [];

			foreach (self::getSupportedProviderKeyList() as $key) {
				$providerKey = new ProvidersKeys($key);
				$providers[] = self::create($providerKey);
			}

			return $providers;
		}

		/**
		 * Возвращает имя класса провайдера по его ключу
		 * @param string $providerKey ключ провайдера
		 * @return string
		 */
		private static function getProviderClassName($providerKey) {
			return self::PROVIDER_NAME_SPACE . ucfirst($providerKey);
		}
	}
