<?php

	namespace UmiCms\Classes\Components\AutoUpdate;

	use UmiCms\System\Registry\iPart;
	use UmiCms\System\Registry\Settings;

	/**
	 * Интерфейс реестра модуля "Автообновления"
	 * @package UmiCms\Classes\Components\AutoUpdate
	 */
	interface iRegistry extends iPart {

		/**
		 * Устнавливает класс общих настроек регистра
		 * @param Settings $settings
		 * @return $this
		 */
		public function setRegistrySettings(Settings $settings);
	}
