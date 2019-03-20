<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Exceptions;

	/**
	 *  Исключение, которое выбрасывается при попытке получить провайдера по невалидному ключу
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Exceptions
	 */
	class UnsupportedProviderKeyException extends \Exception {

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Конструктор
		 * @param string $providerKey ключ неподдерживаемого провайдера
		 */
		public function __construct($providerKey = '') {
			$exceptionMessageFormat = getLabel('label-api-ship-error-cant-get-provider-by-key', self::I18N_PATH);
			$message = sprintf($exceptionMessageFormat, $providerKey);
			parent::__construct($message);
		}
	}
