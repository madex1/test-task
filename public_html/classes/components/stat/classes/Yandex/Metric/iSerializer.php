<?php

	namespace UmiCms\Classes\Components\Stat\Yandex\Metric;

	/**
	 * Интерфейс сериализатора для API "Яндекс.Метрика"
	 * @package UmiCms\Classes\Components\Stat\Yandex\Metric\Serializer
	 */
	interface iSerializer {

		/**
		 * Конструктор
		 * @param \iDomainsCollection $domainCollection коллекция доменов
		 */
		public function __construct(\iDomainsCollection $domainCollection);

		/**
		 * Создает запрос на создание/изменение счетчика
		 * @link https://tech.yandex.ru/metrika/doc/api2/management/counters/addcounter-docpage/
		 * @link https://tech.yandex.ru/metrika/doc/api2/management/counters/editcounter-docpage/
		 * @param string $name название счетчика
		 * @param int $domainId идентификатор домена
		 * @return \stdClass
		 * @throws \RuntimeException
		 */
		public function getCounter($name, $domainId);
	}
