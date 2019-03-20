<?php

	/** Класс импорта фидов */
	class NewsFeeds {

		/** @var news $module */
		public $module;

		/**
		 * Импортирует RSS-фиды
		 * @return boolean
		 * @throws coreException
		 */
		public function import_feeds() {
			$type_id = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('12c6fc06c99a462375eeb3f43dfd832b08ca9e17');
			$umiObjects = umiObjectsCollection::getInstance();
			$result = $umiObjects->getGuidedItems($type_id);

			foreach ($result as $id => $name) {
				$object = $umiObjects->getObject($id);
				$url = $object->getValue('url');
				$type = $object->getValue('rss_type');
				$target = $object->getValue('news_rubric');
				$charsetId = $object->getValue('charset_id');
				$charset = false;

				if ($charsetId) {
					$charset = $umiObjects->getObject($charsetId)->getValue('charset');
				}

				$this->import_feed($url, $type, $target, $name, $charset);
			}

			return true;
		}

		/**
		 * Импортирует RSS-фид
		 * @param string $url url, по которому нужно получить ланные фида
		 * @param int $type_id id объекта, опеределяюшего тип фида
		 * @param int[] $target список ID целевых разделов
		 * @param bool|string $source имя фида
		 * @param bool|string $charset кодировка данных фида
		 * @return bool
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function import_feed($url, $type_id, $target, $source = false, $charset = false) {

			if (!isset($target[0])) {
				return false;
			}

			$parents = umiHierarchy::getInstance()->getObjectInstances($target[0]);
			if (!umiCount($parents)) {
				return false;
			}

			list($parent_id) = $parents;
			$typeObj = umiObjectsCollection::getInstance()->getObject($type_id);
			$typeName = $typeObj->getName();

			$feed = new RSSFeed($url);

			$feed->loadContent($charset);

			switch ($typeName) {
				case 'RSS': {
					$feed->loadRSS();
					break;
				}

				case 'ATOM': {
					$feed->loadAtom();
					break;
				}

				default: {
					return false;
				}
			}

			/** @var umiImportRelations $relations */
			$relations = umiImportRelations::getInstance();

			$source_id = $relations->getSourceId($url);
			if ($source_id === false) {
				$source_id = $relations->addNewSource($url);
			}

			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName('news', 'item');
			$hierarchy_type_id = $hierarchy_type->getId();

			$result = $feed->returnItems();

			/** @var RSSItem $item */
			foreach ($result as $item) {
				$item_title = $item->getTitle();
				$item_url = $item->getUrl();

				if ($relations->getNewIdRelation($source_id, $item_url)) {
					continue;
				}

				$item_content = $item->getContent();
				$item_date = $item->getDate();
				$item_date = strtotime($item_date);

				$element_id = umiHierarchy::getInstance()->addElement(
					$parent_id,
					$hierarchy_type_id,
					$item_title,
					$item_title
				);
				$relations->setIdRelation($source_id, $item_url, $element_id);
				permissionsCollection::getInstance()->setDefaultPermissions($element_id);

				$element = umiHierarchy::getInstance()->getElement($element_id, true);

				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$element->getObject()->setName($item_title);
				$element->setAltName($item_title);
				$element->setIsActive(true);
				$element->setValue('title', $item_title);
				$element->setValue('h1', $item_title);
				$element->setValue('publish_time', $item_date);
				$element->setValue('anons', $item_content);
				$element->setValue('content', $item_content);
				$element->setValue('source', $source);
				$element->setValue('source_url', $item_url);
				$element->commit();

				$eventPoint = new umiEventPoint('news_import_feed_item');
				$eventPoint->setMode('after');
				$eventPoint->setParam('feed_item', $item);
				$eventPoint->setParam('news_item', $element);
				news::setEventPoint($eventPoint);
			}
			return true;
		}
	}

