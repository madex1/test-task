<?php

	namespace UmiCms\Manifest\Search;

	/** Команда удаления поискового индекса */
	class DeleteIndexAction extends \Action {

		/** @var \iSearchModel $searchModel модель поиска */
		private $searchModel;

		/**
		 * Конструктор
		 * @param string $name
		 * @param array $params
		 */
		public function __construct($name, array $params = []) {
			parent::__construct($name, $params);
			$this->searchModel = \searchModel::getInstance();
		}

		/** @inheritdoc */
		public function execute() {
			$this->getSearchModel()
				->truncate_index();
			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}

		/**
		 * Возвращает модель поиска
		 * @return \searchModel
		 */
		private function getSearchModel() {
			return $this->searchModel;
		}
	}
