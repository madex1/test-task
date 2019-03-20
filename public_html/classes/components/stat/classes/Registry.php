<?php

	namespace UmiCms\Classes\Components\Stat;

	use UmiCms\System\Registry\Part;

	/**
	 * Класс реестра модуля "Статистика"
	 * @package UmiCms\Classes\Components\Stat;
	 */
	class Registry extends Part implements iRegistry {

		/** @const string PATH_PREFIX префикс пути для ключей */
		const PATH_PREFIX = '//modules/stat';

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
		public function getYandexToken() {
			return $this->get('yandex-token');
		}

		/** @inheritdoc */
		public function setYandexToken($token) {
			$this->set('yandex-token', $token);
			return $this;
		}
	}
