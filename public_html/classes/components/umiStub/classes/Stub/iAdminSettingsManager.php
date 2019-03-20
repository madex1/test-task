<?php

	namespace UmiCms\Classes\Components\Stub;

	/**
	 * Интерфейс для управления настройками доступа к сайту в административной панели
	 * @package UmiCms\Classes\Components\Stub
	 */
	interface iAdminSettingsManager {

		/**
		 * Возвращает настройки доступа к сайту (общие + специфические для каждого сайта)
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

		/**
		 * Возвращает белый список адресов (общие + специфические для каждого сайта)
		 * @return array
		 */
		public function getWhiteList();

		/**
		 * Возвращает черный список адресов (общие + специфические для каждого сайта)
		 * @return array
		 */
		public function getBlackList();
	}