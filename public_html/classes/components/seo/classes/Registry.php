<?php

	namespace UmiCms\Classes\Components\Seo;

	use UmiCms\System\Registry\Part;

	/**
	 * Класс реестра модуля "SEO"
	 * @package UmiCms\Classes\Components\Seo;
	 */
	class Registry extends Part implements iRegistry {

		/** @const string PATH_PREFIX префикс пути для ключей */
		const PATH_PREFIX = '//modules/seo';

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
