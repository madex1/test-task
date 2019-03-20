<?php

	namespace UmiCms\Manifest\Seo;

	use UmiCms\Service;
	use UmiCms\Utils\SiteMap\iUpdater;

	/** Команда индексации всех страниц для формирования карты сайта */
	class SiteMapIndexingAction extends \IterableAction {

		/** @var iUpdater $siteMapUpdater экземпляр класс обновления карты сайта */
		private $siteMapUpdater;

		/** @var \umiHierarchy $umiHierarchy модель иерархии */
		private $umiHierarchy;

		/**
		 * Конструктор
		 * @param string $name
		 * @param array $params
		 */
		public function __construct($name, array $params = []) {
			parent::__construct($name, $params);
			$this->siteMapUpdater = Service::SiteMapUpdater();
			$this->umiHierarchy = \umiHierarchy::getInstance();
		}

		/** @inheritdoc */
		public function execute() {
			$limit = (int) $this->getParam('limit');
			$offset = (int) $this->getOffset();

			$elementList = $this->getUmiHierarchy()
				->getList($limit, $offset);

			$this->getUpdater()
				->updateList($elementList);

			$this->setOffset($limit + $offset);

			if (empty($elementList)) {
				$this->setIsReady();
				$this->resetState();
			}

			$this->saveState();

			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			$this->getUpdater()
				->deleteAll();
			return $this;
		}

		/** @inheritdoc */
		protected function getStartState() {
			return [
				'offset' => 0
			];
		}

		/**
		 * Возвращает смещение
		 * @return int
		 */
		private function getOffset() {
			$offset = $this->getStatePart('offset');
			return is_numeric($offset) ? $offset : 0;
		}

		/**
		 * Устанавливает смещение
		 * @param int $offset смещение
		 * @return $this
		 */
		private function setOffset($offset) {
			return $this->setStatePart('offset', $offset);
		}

		/**
		 * Возвращает экземпляр класс обновления карты сайта
		 * @return iUpdater
		 */
		private function getUpdater() {
			return $this->siteMapUpdater;
		}

		/**
		 * Возвращает модель иерархии
		 * @return \umiHierarchy
		 */
		private function getUmiHierarchy() {
			return $this->umiHierarchy;
		}
	}
