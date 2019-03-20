<?php

	/** Класс языка */
	class lang extends umiEntinty implements iLang {

		/** @var string префикс языка */
		private $prefix;

		/** @var bool является ли язык основным */
		private $isDefaultFlag;

		/** @var string название языка */
		private $title;

		/** @var string тип сохраняемой сущности для кеширования */
		protected $store_type = 'lang';

		/** @inheritdoc */
		public function getTitle() {
			return $this->title;
		}

		/** @inheritdoc */
		public function setTitle($title) {
			if (!is_string($title) || empty($title)) {
				throw new wrongParamException('Wrong language title given');
			}

			if ($this->getTitle() != $title) {
				$this->title = $title;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getPrefix() {
			return $this->prefix;
		}

		/** @inheritdoc */
		public function setPrefix($prefix) {
			if (!is_string($prefix) || empty($prefix)) {
				throw new wrongParamException('Wrong language prefix given');
			}

			$prefix = $this->filterPrefix($prefix);

			if ($this->getPrefix() != $prefix) {
				$this->prefix = $prefix;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getIsDefault() {
			return $this->isDefaultFlag;
		}

		/** @inheritdoc */
		public function setIsDefault($flag) {
			$flag = (bool) $flag;

			if ($this->getIsDefault() != $flag) {
				$this->isDefaultFlag = $flag;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		protected function save() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$title = $connection->escape($this->getTitle());
			$prefix = $connection->escape($this->getPrefix());
			$isDefaultFlag = (int) $this->getIsDefault();
			$escapedId = (int) $this->getId();

			$sql = <<<SQL
UPDATE `cms3_langs`
	SET `prefix` = '$prefix', `is_default` = $isDefaultFlag, `title` = '$title'
		WHERE `id` = $escapedId
SQL;

			$connection->query($sql);
			return true;
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < 4) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT `id`, `prefix`, `is_default`, `title` FROM `cms3_langs` WHERE `id` = $escapedId LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 4) {
				return false;
			}

			list($id, $prefix, $isDefaultFlag, $title) = $row;

			$this->prefix = (string) $prefix;
			$this->title = (string) $title;
			$this->isDefaultFlag = (bool) $isDefaultFlag;
			return true;
		}

		/**
		 * Удаляет неподдерживаемые символы из префикса языка
		 * @param string $prefix
		 * @return string
		 */
		private function filterPrefix($prefix) {
			return preg_replace("/[^A-z0-9_\-]+/", '', $prefix);
		}
	}
