<?php

	namespace UmiCms\Manifest\Catalog;

	use UmiCms\Service;

	/**
	 * Команда переиндексации фильтров
	 * @package UmiCms\Manifest\Catalog
	 */
	class FilterIndexingAction extends \IterableAction {

		/** @var \umiHierarchy $hierarchy модель иерархии */
		private $hierarchy;

		/** @var int INDEX_GENERATOR_LIMIT Ограничение на количество индексируемых товаров за один раз */
		const INDEX_GENERATOR_LIMIT = 200;

		/** @inheritdoc */
		public function __construct($name, array $params = []) {
			parent::__construct($name, $params);
			$this->hierarchy = \umiHierarchy::getInstance();
		}

		/**
		 * @inheritdoc
		 * @return $this
		 * @throws \publicAdminException
		 * @throws \publicException
		 * @throws \selectorException
		 * @throws \Exception
		 */
		public function execute() {
			$offset = (int) $this->getOffset();
			$limit = (int) $this->getParam('limit');
			$categoryList = $this->getCategoryListForIndexing($offset, $limit);
			$isIterationReady = true;

			foreach ($categoryList as $category) {

				try {
					$isReady = $this->indexCategory($category);

					if ($isReady) {
						$this->markChildren($category);
					} else {
						$isIterationReady = false;
					}

				} catch (\noObjectsFoundForIndexingException $exception) {
					//nothing
				}

				$this->unload($category);
			}

			if ($isIterationReady) {
				$this->setOffset($limit + $offset);
			}

			if (count($categoryList) === 0) {
				$this->setIsReady();
				$this->resetState();
			}

			return $this->saveState();
		}

		/** @inheritdoc */
		public function rollback() {
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
		 * Возвращает категории разделов каталога, нуждающиеся в переиндексации
		 * @param int $offset смещение выборки
		 * @param int $limit размер выборки
		 * @return \iUmiHierarchyElement[]
		 * @throws \selectorException
		 */
		private function getCategoryListForIndexing($offset, $limit) {
			$categories = Service::SelectorFactory()
				->createPageTypeName('catalog', 'category');
			$categories->where('index_choose')->equals(true);
			$categories->limit($offset, $limit);
			return $categories->result();
		}

		/**
		 * Индексирует фильтры раздела каталога
		 * @param \iUmiHierarchyElement $category объект раздела каталога
		 * @return bool закончена ли индексация
		 * @throws \publicAdminException
		 * @throws \publicException
		 * @throws \Exception
		 * @throws \noObjectsFoundForIndexingException
		 */
		private function indexCategory(\iUmiHierarchyElement $category) {
			$parentId = $category->getId();
			$level = (int) $category->getValue('index_level');

			$indexGenerator = new \FilterIndexGenerator($this->getProductTypeId(), 'pages');
			$indexGenerator->setHierarchyCondition($parentId, $level);
			$indexGenerator->setLimit(self::INDEX_GENERATOR_LIMIT);
			$indexGenerator->run();

			$category->setValue('index_source', $parentId);
			$category->setValue('index_date', Service::DateFactory()->create());
			$category->setValue('index_state', 100);
			$category->commit();

			return $indexGenerator->isDone();
		}

		/**
		 * Возвращает идентификатор иерархического типа данных объектов каталога,
		 * или false, если не удалось получить тип.
		 * @return bool|int
		 */
		private function getProductTypeId() {
			$type = \umiHierarchyTypesCollection::getInstance()
				->getTypeByName('catalog', 'object');

			if (!$type instanceof \umiHierarchyType) {
				return false;
			}

			return $type->getId();
		}

		/**
		 * Указывает у дочерних разделов каталога источник индекса фильтров
		 * @param \iUmiHierarchyElement $category объект раздела каталога
		 * @return $this
		 * @throws \selectorException
		 */
		private function markChildren(\iUmiHierarchyElement $category) {
			$parentId = $category->getId();
			$level = (int) $category->getValue('index_level');

			$children = Service::SelectorFactory()
				->createPageTypeName('catalog', 'category');
			$children->where('hierarchy')->page($parentId)->childs($level);
			$children->option('return', 'id');
			$children = $children->result();

			foreach ($children as $childId) {
				$child = $this->load(array_shift($childId));

				if (!$child instanceof \iUmiHierarchyElement) {
					continue;
				}

				$child->setValue('index_source', $parentId);
				$child->commit();
				$this->unload($child);
			}

			return $this;
		}

		/**
		 * Загружает страницу
		 * @param int $id идентификатор страницы
		 * @return bool|\iUmiHierarchyElement
		 */
		public function load($id) {
			return $this->getHierarchy()
				->getElement($id);
		}

		/**
		 * Выгружает страницу из памяти
		 * @param \iUmiHierarchyElement $page страница
		 * @return $this
		 */
		private function unload(\iUmiHierarchyElement $page) {
			$this->getHierarchy()
				->unloadElement($page->getId());
			return $this;
		}

		/**
		 * Возвращает модель иерархии
		 * @return \iUmiHierarchy
		 */
		private function getHierarchy() {
			return $this->hierarchy;
		}
	}
