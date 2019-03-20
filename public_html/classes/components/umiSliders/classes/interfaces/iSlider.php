<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Interface iSlider интерфейс слайдера
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	interface iSlider extends \iUmiCollectionItem {

		/**
		 * Возвращает название слайда
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает название слайда
		 * @param string $name название
		 * @return iSlider
		 */
		public function setName($name);

		/**
		 * Возвращает идентификатор домена
		 * @return int
		 */
		public function getDomainId();

		/**
		 * Устанавливает идентификатор домена
		 * @param int $domainId идентификатор домена
		 * @return iSlider
		 */
		public function setDomainId($domainId);

		/**
		 * Возвращает идентификатор языка
		 * @return int
		 */
		public function getLanguageId();

		/**
		 * Устанавливает идентификатор языка
		 * @param iSlider $languageId идентификатор языка
		 * @return mixed
		 */
		public function setLanguageId($languageId);

		/**
		 * Возвращает скорость пролистывания слайдов
		 * @return int|null
		 */
		public function getSlidingSpeed();

		/**
		 * Устанавливает скорость пролистывания слайдов
		 * @param int $speed скорость пролистывания слайдов в микросекундах
		 * @return iSlider
		 */
		public function setSlidingSpeed($speed);

		/**
		 * Возвращает длительность задержки перед пролистыванием слайда
		 * @return int|null
		 */
		public function getSlidingDelay();

		/**
		 * Устанавливает длительность задержки перед пролистыванием слайда
		 * @param int $delay длительность задержки перед пролистыванием слайда в микросекундах
		 * @return iSlider
		 */
		public function setSlidingDelay($delay);

		/**
		 * Устанавливает статус включенности цикличного пролистывания слайдов
		 * @param bool $status вкл/выкл
		 * @return iSlider
		 */
		public function setSlidingLoopEnableStatus($status);

		/**
		 * Возвращает статус включенности цикличного пролистывания слайдов
		 * @return bool
		 */
		public function isSlidingLoopEnable();

		/**
		 * Устанавливает статус включенности автоматического пролистывания слайдов
		 * @param bool $status вкл/выкл
		 * @return iSlider
		 */
		public function setSlidingAutoPlayEnableStatus($status);

		/**
		 * Возвращает статус включенности автоматического пролистывания слайдов
		 * @return bool
		 */
		public function isSlidingAutoPlayEnable();

		/**
		 * Устанавливает статус включенности случайного порядка вывода слайдов
		 * @param bool $status вкл/выкл
		 * @return iSlider
		 */
		public function setSlidesRandomOrderEnableStatus($status);

		/**
		 * Возвращает статус включенности случайного порядка вывода слайдов
		 * @return bool
		 */
		public function isSlidesRandomOrderEnable();

		/**
		 * Устанавливает количество отображаемых слайдов
		 * @param int $count количество отображаемых слайдов
		 * @return iSlider
		 */
		public function setSlidesCount($count);

		/**
		 * Возвращает количество отображаемых слайдов
		 * @return int
		 */
		public function getSlidesCount();

		/**
		 * Возвращает кастомный идентификатор
		 * @return string|null
		 */
		public function getCustomId();

		/**
		 * Устанавливает кастомный идентификатор
		 * @param string|null $customId кастомный идентификатор
		 * @return $this
		 */
		public function setCustomId($customId);
	}
