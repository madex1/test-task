<?php

	namespace UmiCms\Manifest\Menu;

	/**
	 * Команда обновления ссылок меню
	 * @package UmiCms\Manifest\Menu
	 */
	class UpdateLinksAction extends \Action {

		/** @var \umiImportRelations $importRelation экземпляр класс связей идентификаторов импортируемых сущностей */
		private $importRelation;

		/**
		 * Конструктор
		 * @param string $name
		 * @param array $params
		 */
		public function __construct($name, array $params = []) {
			parent::__construct($name, $params);
			$this->importRelation = \umiImportRelations::getInstance();
		}

		/** @inheritdoc */
		public function execute() {
			$menuList = $this->getMenuList();

			foreach ($menuList as $menu) {
				$sourceId = $this->getImportRelation()
					->getSourceIdByObjectId($menu->getId());

				if ($sourceId === null) {
					continue;
				}

				$menuContent = json_decode($menu->getValue('menuhierarchy'));

				if (!is_array($menuContent)) {
					continue;
				}

				$updatedMenuContent = $this->updateMenuLinks($menuContent, $sourceId);

				$menu->setValue('menuhierarchy', json_encode($updatedMenuContent));
				$menu->commit();
			}

			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}

		/**
		 * Возвращает экземпляр класс связей идентификаторов импортируемых сущностей
		 * @return \umiImportRelations
		 */
		private function getImportRelation() {
			return $this->importRelation;
		}

		/**
		 * Возвращает список меню
		 * @return \iUmiObject[]
		 */
		private function getMenuList() {
			$queryBuilder = new \selector('objects');
			$queryBuilder->types('object-type')->guid('menu-menu');
			return $queryBuilder->result();
		}

		/**
		 * Обновляет ссылки в содержимом меню - изменяется идентификаторы страниц, так как они могут не
		 * соответствовать идентификаторам страниц, созданных в результате импорта.
		 * @param \stdClass[] $menuContent содержимое меню
		 * @param int $sourceId идентификатор ресурса из которого было импортировано меню
		 * @return \stdClass[]
		 */
		private function updateMenuLinks(array $menuContent, $sourceId) {
			/** @var \stdClass $menuItem */
			foreach ($menuContent as $menuItem) {
				if (!$menuItem instanceof \stdClass) {
					continue;
				}

				if (isset($menuItem->children) && is_array($menuItem->children)) {
					$menuItem->children = $this->updateMenuLinks($menuItem->children, $sourceId);
				}

				if (!isset($menuItem->rel)) {
					continue;
				}

				$oldPageId = $menuItem->rel;

				if (!is_numeric($oldPageId)) {
					continue;
				}

				$newPageId = $this->getImportRelation()
					->getNewIdRelation($sourceId, $oldPageId);

				if (!is_numeric($newPageId)) {
					continue;
				}

				$menuItem->rel = $newPageId;
			}

			return $menuContent;
		}
	}
