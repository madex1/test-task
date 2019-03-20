<?php
	abstract class __rss_import_news {

		public function rss_list() {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();

			$type_id = $typesCollection->getTypeIdByGUID('12c6fc06c99a462375eeb3f43dfd832b08ca9e17');
			$result = $objectsCollection->getGuidedItems($type_id);

			$mode = (string) getRequest('param0');

			if($mode == "do") {
				$params = Array(
					"type_id" => $type_id
				);
				$this->saveEditedList("objects", $params);

				$this->import_feeds();

				$this->chooseRedirect();
			}

			$result = array_keys($result);
			$total = count($result);

			$this->setDataType("list");
			$this->setActionType("modify");
			$this->setDataRange($total, 0);

			$data = $this->prepareData($result, "objects");
			$this->setData($data, $total);
			return $this->doData();
		}

		/**
		 * Возвращает информацию об объектах для неудаленных рубрик новостей
		 * @return array
		 */
		public function getObjectNamesForRubrics() {
			$rubrics = new selector('pages');
			$rubrics->types('hierarchy-type')->name('news', 'rubric');
			$rubrics->where('is_deleted')->equals(0);
			$items = [];

			/** @var umiHierarchyElement $page */
			foreach ($rubrics as $page) {
				$object = $page->getObject();

				$items[] = [
					'attribute:id' => $object->getId(),
					'node:name' => $object->getName()
				];
			}

			return [
				'items' => [
					'nodes:item' => array_unique($items, SORT_REGULAR)
				]
			];
		}

		public function import_feeds() {
			$type_id = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('12c6fc06c99a462375eeb3f43dfd832b08ca9e17');
			$result = umiObjectsCollection::getInstance()->getGuidedItems($type_id);

			foreach($result as $id => $name) {
				$object = umiObjectsCollection::getInstance()->getObject($id);
				$url = $object->getValue("url");
				$type = $object->getValue("rss_type");
				$target = $object->getValue("news_rubric");
				$charsetId = $object->getValue('charset_id');
				$charset = false;
				if ($charsetId) {
					$charset = umiObjectsCollection::getInstance()->getObject($charsetId)->getValue('charset');
				}
				$this->import_feed($url, $type, $target, $name, $charset);
			}
		}


		public function import_feed($url, $type_id, $target, $source = false, $charset = false) {
			
			if(!isset($target[0])) {
				return false;
			}
			
			$parents = umiHierarchy::getInstance()->getObjectInstances($target[0]);
			if(!count($parents)) {
				return false;
			}
			
			list($parent_id) = $parents;
			$typeObj = umiObjectsCollection::getInstance()->getObject($type_id);
			$typeName = $typeObj->getName();

			$feed = new RSSFeed($url);

			$feed->loadContent($charset);

			switch($typeName) {
				case "RSS": {
					$feed->loadRSS();
					break;
				}

				case "ATOM": {
					$feed->loadAtom();
					break;
				}

				default: {
					return false;
				}
			}

			$relations = umiImportRelations::getInstance();

			$source_id = $relations->getSourceId($url);
			if($source_id === false) {
				$source_id = $relations->addNewSource($url);
			}

			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName("news", "item");
			$hierarchy_type_id = $hierarchy_type->getId();

			$result = $feed->returnItems();

			foreach ($result as $item) {
				$element_id = false;
				$item_title = $item->getTitle();
				$item_url = $item->getUrl();

				if($relations->getNewIdRelation($source_id, $item_url)) {
					continue;
				}
				
				$item_content = $item->getContent();
				$item_date = $item->getDate();
				$item_date = strtotime($item_date);

				$element_id = umiHierarchy::getInstance()->addElement($parent_id, $hierarchy_type_id, $item_title, $item_title);
				$relations->setIdRelation($source_id, $item_url, $element_id);
				permissionsCollection::getInstance()->setDefaultPermissions($element_id);
				
				$element = umiHierarchy::getInstance()->getElement($element_id, true);
				
				if (!$element instanceof umiHierarchyElement) continue;
				
				$element->getObject()->setName($item_title);
				$element->setAltName($item_title);
				$element->setIsActive(true);
				$element->setValue("title", $item_title);
				$element->setValue("h1", $item_title);
				$element->setValue("publish_time", $item_date);
				$element->setValue("anons", $item_content);
				$element->setValue("content", $item_content);
				$element->setValue("source", $source);
				$element->setValue("source_url", $item_url);
				$element->commit();
				
				$eventPoint = new umiEventPoint("news_import_feed_item");
				$eventPoint->setMode("after");
				$eventPoint->setParam("feed_item", $item);
				$eventPoint->setParam("news_item", $element);
				$this->setEventPoint($eventPoint);
				
			}
			return true;
		}


		public function feedsImportListener($event) {
			$counter = &$event->getRef("counter");
			$buffer = &$event->getRef("buffer");
			$counter++;

			$buffer[ __METHOD__] = $this->import_feeds();
		}
	};
?>
