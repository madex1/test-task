<?php

	namespace UmiCms\Manifest\Seo;

	use UmiCms\Service;
	use UmiCms\Utils\SiteMap\iUpdater;

	/** Команда удаления индекса карты сайта */
	class DeleteIndexAction extends \Action {

		/** @var iUpdater $siteMapUpdater экземпляр класс обновления карты сайта */
		private $siteMapUpdater;

		/** @inheritdoc */
		public function __construct($name, array $params = []) {
			parent::__construct($name, $params);
			$this->siteMapUpdater = Service::SiteMapUpdater();
		}

		/** @inheritdoc */
		public function execute() {
			$this->getUpdater()
				->deleteAll();
			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}

		/**
		 * Возвращает экземпляр класс обновления карты сайта
		 * @return iUpdater
		 */
		private function getUpdater() {
			return $this->siteMapUpdater;
		}
	}
