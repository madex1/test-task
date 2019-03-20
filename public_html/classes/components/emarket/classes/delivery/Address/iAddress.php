<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\Address;

	/**
	 * Интерфейс адреса
	 * @package UmiCms\Classes\Components\Emarket\Delivery\Address
	 */
	interface iAddress {

		/**
		 * Возвращает название страны если задано, иначе - пустую строку.
		 * @return string
		 */
		public function getCountry();

		/**
		 * Возвращает ISO код страны если задано, иначе - пустую строку.
		 * @return string
		 */
		public function getCountryISOCode();

		/**
		 * Возвращает название города если задано, иначе - пустую строку.
		 * @return string
		 */
		public function getCity();

		/**
		 * Возвращает почтовый индекс если задан, иначе - 0.
		 * @return int
		 */
		public function getPostIndex();

		/**
		 * Возвращает название региона если задано, иначе - пустую строку.
		 * @return string
		 */
		public function getRegion();

		/**
		 * Возвращает название улицы если задана, иначе - пустую строку
		 * @return string
		 */
		public function getStreet();

		/**
		 * Возвращает номер дома если задан, иначе - пустую строку
		 * @return string
		 */
		public function getHouseNumber();

		/**
		 * Возвращает номер квартиры если задан, иначе - пустую строку
		 * @return string
		 */
		public function getFlatNumber();

		/**
		 * Возвращает комментарий к адресу если задан, иначе - пустую строку
		 * @return string
		 */
		public function getComment();
	}
