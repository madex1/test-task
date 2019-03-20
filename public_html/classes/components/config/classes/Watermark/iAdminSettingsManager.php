<?php

	namespace UmiCms\Classes\Components\Config\Watermark;

	/** Интерфейс для управления настройками водяного знака в административной панели */
	interface iAdminSettingsManager {

		/**
		 * Возвращает настройки водяного знака (общие + специфические для каждого сайта)
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
