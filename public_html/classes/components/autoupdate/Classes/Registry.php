<?php

	namespace UmiCms\Classes\Components\AutoUpdate;

	use UmiCms\System\Registry\Part;
	use UmiCms\System\Registry\Settings;

	/**
	 * Класс реестра модуля "Автообновления"
	 * @package UmiCms\Classes\Components\AutoUpdate
	 */
	class Registry extends Part implements iRegistry {

		/** @const string PATH_PREFIX префикс пути для ключей */
		const PATH_PREFIX = '//modules/autoupdate/';

		/** @var Settings $registrySettings общие настройки */
		public $registrySettings;

		/** @inheritdoc */
		public function __construct(\iRegedit $storage) {
			parent::__construct($storage);
			parent::setPathPrefix(self::PATH_PREFIX);
		}

		/** @inheritdoc */
		public function setPathPrefix($prefix) {
			return $this;
		}

		/** @inheritdoc */
		public function setRegistrySettings(Settings $settings) {
			$this->registrySettings = $settings;
			return $this;
		}

		/** @deprecated */
		public function getVersion() {
			return (string) $this->registrySettings->getVersion();
		}

		/** @deprecated */
		public function getRevision() {
			return (string) $this->registrySettings->getRevision();
		}

		/** @deprecated */
		public function setRevision($revision) {
			$this->registrySettings->setRevision($revision);
			return $this;
		}

		/** @deprecated */
		public function getEdition() {
			return (string) $this->registrySettings->getEdition();
		}

		/** @deprecated */
		public function getUpdateTime() {
			return (int) $this->registrySettings->getUpdateTime();
		}

		/** @deprecated */
		public function getStatus() {
			return (string) $this->registrySettings->getStatus();
		}
	}
