<?php

	namespace UmiCms\Classes\Components\Seo;

	/**
	 * Интерфейс для управлением настройками SEO в административной панели
	 * @package UmiCms\Classes\Components\Seo
	 */
	interface iAdminSettingsManager {

		/** Возвращает настройки доступа к сайту (общие + специфические для каждого сайта) */
		public function getParams();

		/**
		 * Сохраняет настройки, специфические для каждого сайта (домен + язык)
		 * @param array $params новые значения настроек
		 */
		public function setCustomParams($params);
	}