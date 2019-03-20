<?php

	use UmiCms\Service;

	/** Класс генерации фидов */
	class DataFeeds {

		/** @var data $module */
		public $module;

		/**
		 * Генерирует фид на основе данных списка дочерних страниц и выводит в буффер
		 * @param int $elementId идентификатор родительской страницы
		 * @param string $xslPath путь до xsl шаблона, с помощью которого генерируется фид
		 * @param null|int $typeId идентификатор объектного типа данных дочерних страниц
		 * @return mixed|string
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public function generateFeed($elementId, $xslPath, $typeId = null) {
			$this->module->errorSetErrorPage('/');
			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($elementId);

			if ($elementId && (!$element instanceof iUmiHierarchyElement || !$element->getIsActive())) {
				if (data::isXSLTResultMode()) {
					$result = [
						'error' => '%data_feed_nofeed%'
					];
					return data::parseTemplate('', $result);
				}

				return '%data_feed_nofeed%';
			}

			$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();

			if ($typeId) {
				list($name, $ext) = explode('-', $typeId);
				$hierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName($name, $ext);
				if ($hierarchyType) {
					$typeId = $umiObjectTypesCollection->getTypeIdByHierarchyTypeId($hierarchyType->getId());
				}
			} elseif ($elementId) {
				$typeId = $umiHierarchy->getDominantTypeId($elementId);
			} else {
				$typeId = $umiObjectTypesCollection->getTypeIdByGUID('news-item');
			}

			$type = $umiObjectTypesCollection->getType($typeId);

			if ($type instanceof iUmiObjectType) {
				$module = $type->getModule();
				$method = $type->getMethod();
			}

			if (!isset($module) && !isset($method)) {
				if (data::isXSLTResultMode()) {
					$result = [
						'error' => '%data_feed_nofeed%'
					];
					return data::parseTemplate('', $result);
				}

				return '%data_feed_nofeed%';
			}

			if (!$this->checkIfFeedable($module, $method)) {
				if (data::isXSLTResultMode()) {
					$result = [
						'error' => '%data_feed_wrong%'
					];
					return data::parseTemplate('', $result);
				}

				return '%data_feed_wrong%';
			}

			$rss_per_page = (int) Service::Registry()->get('//modules/news/rss_per_page');
			$rss_per_page = $rss_per_page > 0 ? $rss_per_page : 10;

			$sel = new selector('pages');
			$sel->option('return')->value('id');
			$sel->where('hierarchy')->page($elementId)->childs(100);
			$sel->types('hierarchy-type')->name($module, $method);
			if ($type->getFieldId('publish_time')) {
				$sel->order('publish_time')->desc();
			}
			$sel->limit(0, $rss_per_page);

			$result = [];

			foreach ($sel->result() as $res) {
				$result[] = $res['id'];
			}

			$exporter = new xmlExporter('rss');
			$exporter->addElements($result);
			$exporter->setIgnoreRelations();
			$umiDump = $exporter->execute();

			$styleFile = CURRENT_WORKING_DIR . '/' . $xslPath;
			if (!is_file($styleFile)) {
				throw new publicException("Can't load exporter {$styleFile}");
			}

			secure_load_dom_document($umiDump->saveXML(), $doc);
			$doc->formatOutput = XML_FORMAT_OUTPUT;

			if (!$elementId) {
				$elementId = $umiHierarchy->getDefaultElementId();
			}

			$element = $umiHierarchy->getElement($elementId);
			$language = Service::LanguageCollection()->getLang($element->getLangId())->getPrefix();
			$link = $umiHierarchy->getPathById($elementId);
			$description = $element->getIsDefault() ? '' : $element->getName();

			/** @var umiTemplaterXSLT $templater */
			$templater = umiTemplater::create('XSLT', $styleFile);
			$templater->setAdditionalVariables([
				'link' => $link,
				'description' => $description,
				'language' => $language,
			]);

			$resultXml = $templater->parse($doc);

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push($resultXml);
			$buffer->end();
		}

		/**
		 * Доступна ли генерация фида на основе данных страниц
		 * с заданным иерархическим типом
		 * @param string $module имя иерархического типа
		 * @param string $method расширения иерархического типа
		 * @return bool
		 */
		public function checkIfFeedable($module, $method) {
			$allowedSource = [
				['forum', 'topic'],
				['forum', 'message'],
				['news', 'item'],
				['blogs', 'post'],
				['blogs20', 'post'],
				['blogs20', 'comment'],
				['comments', 'comment'],
				['catalog', 'object']
			];

			foreach ($allowedSource as $allowed) {
				if ($module == $allowed[0] && $method == $allowed[1]) {
					return true;
				}
			}

			return false;
		}
	}

