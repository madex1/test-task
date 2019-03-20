<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Interface iSlide интерфейс слайда
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	interface iSlide extends \iUmiCollectionItem {

		/**
		 * Устанавливает название слайда
		 * @param string $name название слайда
		 * @return iSlide
		 */
		public function setName($name);

		/**
		 * Возвращает название слайда
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает идентификатор слайдера, к которому относится слайд
		 * @param int $sliderId идентификатор слайдера
		 * @return iSlide
		 */
		public function setSliderId($sliderId);

		/**
		 * Возвращает идентификатор слайдера, к которому относится слайд
		 * @return int
		 */
		public function getSliderId();

		/**
		 * Устанавливает название слайда
		 * @param string $title название слайда
		 * @return iSlide
		 */
		public function setTitle($title);

		/**
		 * Возвращает название слайда
		 * @return string|null
		 */
		public function getTitle();

		/**
		 * Устанавливает путь до изображение слайда
		 * @param mixed $image изображение
		 * @return iSlide
		 */
		public function setImagePath($image = null);

		/**
		 * Возвращает путь до изображения слайда
		 * @return string|null
		 */
		public function getImagePath();

		/**
		 * Устанавливает текст слайда
		 * @param string $text текст слайда
		 * @return iSlide
		 */
		public function setText($text);

		/**
		 * Возвращает текст слайда
		 * @return string|null
		 */
		public function getText();

		/**
		 * Устанавливает адрес ссылки слайда
		 * @param string $link адрес ссылки слайда
		 * @return iSlide
		 */
		public function setLink($link);

		/**
		 * Возвращает адрес ссылки слайда
		 * @return string|null
		 */
		public function getLink();

		/**
		 * Устанавливает значение опции открывать ссылки слайдера в новом окне
		 * @param bool $needToOpen открывать/не открывать
		 * @return iSlide
		 */
		public function setItIsNeedToOpenLinkInNewTab($needToOpen);

		/**
		 * Отвечает на вопрос: нужно ли открывать ссылку слайдера в новом окне
		 * @return bool
		 */
		public function isNeedToOpenLinkInNewTab();

		/**
		 * Устанавливает статус активности слайда
		 * @param bool $status статус активности слайда
		 * @return iSlide
		 */
		public function setActiveStatus($status);

		/**
		 * Возвращает статус активности слайда
		 * @return bool
		 */
		public function isActive();

		/**
		 * Устанавливает порядок вывода слайда
		 * @param int $order порядок вывода слайда
		 * @return iSlide
		 */
		public function setOrder($order);

		/**
		 * Возвращает порядок вывода слайда
		 * @return int
		 */
		public function getOrder();
	}
