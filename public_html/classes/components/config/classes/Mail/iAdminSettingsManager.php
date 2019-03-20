<?php

	namespace UmiCms\Classes\Components\Config\Mail;

	/**
	 * Интерфейс для управления настройками почты в административной панели
	 * @package UmiCms\Classes\Components\Config\Mail
	 */
	interface iAdminSettingsManager {

		/**
		 * Возвращает настройки почты (общие + специфические для каждого сайта)
		 * @return array
		 */
		public function getParams();

		/**
		 * Сохраняет общие настройки
		 * @param array $params новые значения настроек
		 */
		public function setCommonParams($params);

		/**
		 * Сохраняет настройки, специфические для каждого сайта (домен + язык)
		 * @param array $params новые значения настроек
		 */
		public function setCustomParams($params);
	}