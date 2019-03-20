<?php

	namespace UmiCms\Classes\Components\Config\Captcha;

	/** Интерфейс для управления настройками капчи в административной панели */
	interface iAdminSettingsManager {

		/**
		 * Возвращает настройки капчи (общие + специфические для каждого сайта)
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
		public function setSiteParams($params);
	}
