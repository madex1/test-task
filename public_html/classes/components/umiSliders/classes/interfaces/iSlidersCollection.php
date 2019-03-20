<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Interface iSlidersCollection интерфейс коллекции слайдеров
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	interface iSlidersCollection extends \iUmiCollection {

		/**
		 * Возвращает слайдер по его имени
		 * @param string $name имя слайда
		 * @return Slider|null
		 */
		public function getByName($name);

		/**
		 * Возвращает слайдер по его кастомному идентификатору
		 * @param string $customId кастомный идентификатор
		 * @return Slider|null
		 */
		public function getByCustomId($customId);

		/**
		 * Возвращает слайдер по его кастомному идентификатор, идентификатор домена и языка
		 * @param string $customId кастомный идентификатор
		 * @param int $domainId идентификатор домена
		 * @param int $languageId идентификатор языка
		 * @return Slider|null
		 */
		public function getByCustomIdDomainAndLanguage($customId, $domainId, $languageId);

		/**
		 * Возвращает слайдеры по идентификатору домена и/или идентификатору языка
		 * @param array|int|null $domainId идентификатор(ы) домена
		 * @param array|int|null $languageId идентификатор(ы) языка
		 * @return array
		 */
		public function getSliderIdListByDomainIdAndLanguageId($domainId = null, $languageId = null);

		/**
		 * Устанавливает коллекция слайдов
		 * @param iSlidesCollection $collection
		 * @return $this
		 */
		public function setSlidesCollection(iSlidesCollection $collection);
	}
