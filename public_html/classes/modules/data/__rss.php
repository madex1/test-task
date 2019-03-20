<?php
	abstract class __rss_data {

		public function rss($elementId = null, $typeId = null) {
			if (!$elementId) $elementId = (int) getRequest('param0');
			if (!$typeId) $typeId = getRequest('param1');

			if(defined("VIA_HTTP_SCHEME")) {
				throw new publicException("Not available via scheme");
			}

			$xslPath = "xsl/rss.xsl";

			return $this->generateFeed($elementId, $xslPath, $typeId);
		}


		public function atom($elementId = null, $typeId = null) {
			if (!$elementId) $elementId = (int) getRequest('param0');
			if (!$typeId) $typeId = getRequest('param1');
			
			if(defined("VIA_HTTP_SCHEME")) {
				throw new publicException("Not available via scheme");
			}

			$xslPath = "xsl/atom.xsl";

			return $this->generateFeed($elementId, $xslPath, $typeId);
		}

		public function generateFeed($elementId, $xslPath, $typeId = null) {
			

			$this->errorSetErrorPage('/');
			
			if ($elementId && (!umiHierarchy::getInstance()->isExists($elementId) || !umiHierarchy::getInstance()->getElement($elementId)->getIsActive())) {
				if (def_module::isXSLTResultMode()) {
					$result = array(
						'error' => '%data_feed_nofeed%'
					);
					return def_module::parseTemplate('', $result);	
				} else {
					return '%data_feed_nofeed%';
				}
			}
			
			if ($typeId) {
				list($name, $ext) = explode('-', $typeId);
				$hierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName($name, $ext);
				if ($hierarchyType)	$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeId($hierarchyType->getId());	
			} elseif ($elementId) {
				$typeId = umiHierarchy::getInstance()->getDominantTypeId($elementId);	
			} else {
				$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('news-item');
			}
			
			$type = umiObjectTypesCollection::getInstance()->getType($typeId);
			if ($type instanceof umiObjectType) {
				$module = $type->getModule();
				$method = $type->getMethod();
			}
			
			if(!isset($module) && !isset($method)) {
				if (def_module::isXSLTResultMode()) {
					$result = array(
						'error' => '%data_feed_nofeed%'
					);
					return def_module::parseTemplate('', $result);	
				} else {
					return '%data_feed_nofeed%';
				}
			}			

			if(!$this->checkIfFeedable($module, $method)) {
				if (def_module::isXSLTResultMode()) {
					$result = array(
						'error' => '%data_feed_wrong%'
					);
					return def_module::parseTemplate('', $result);	
				} else {
					return '%data_feed_wrong%';
				}
			}
			
			$rss_per_page = (int) regedit::getInstance()->getVal("//modules/news/rss_per_page");
			$rss_per_page = $rss_per_page > 0 ? $rss_per_page : 10;
			
			$sel = new selector('pages');
			$sel->option('return')->value('id');
			$sel->where('hierarchy')->page($elementId)->childs(100);
			$sel->types('hierarchy-type')->name($module, $method);
			if ($type->getFieldId('publish_time')) $sel->order('publish_time')->desc();
			$sel->limit(0, $rss_per_page);
			
			$result = array();
			foreach($sel->result() as $res) {
				$result[] = $res['id'];
			}
						
			$exporter = new xmlExporter('rss');
			$exporter->addElements($result);
			$exporter->setIgnoreRelations();
			$umiDump = $exporter->execute();
					
			$styleFile = CURRENT_WORKING_DIR . "/" . $xslPath;
			if (!is_file($styleFile)) {
				throw new publicException("Can't load exporter {$styleFile}");
			}

			secure_load_dom_document($umiDump->saveXML(), $doc);
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			
			if (!$elementId) $elementId = umiHierarchy::getInstance()->getDefaultElementId();
			$element = umiHierarchy::getInstance()->getElement($elementId);
			$language = langsCollection::getInstance()->getLang($element->getLangId())->getPrefix();
			$link = umiHierarchy::getInstance()->getPathById($elementId);
			$description = $element->getIsDefault() ? '' : $element->getName();
					
			$templater = umiTemplater::create('XSLT', $styleFile);
			$templater->setAdditionalVariables(array(
				'link' => $link,
				'description' => $description,
				'language'	=> $language, 
			));
						
			$resultXml = $templater->parse($doc);
		
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push($resultXml);
			$buffer->end();
		}


		public function getRssMeta($element_id = false, $title_prefix = "") {
			$element_id = $this->analyzeRequiredPath($element_id);

			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return "";
			}
			
			$typeId = umiHierarchy::getInstance()->getDominantTypeId($element_id);
			$type = umiObjectTypesCollection::getInstance()->getType($typeId);
			if ($type instanceof umiObjectType) {
				$module = $type->getModule();
				$method = $type->getMethod();
				if(!$this->checkIfFeedable($module, $method)) {
					return "";
				}
			} else {
				return "";
			}

			$element = umiHierarchy::getInstance()->getElement($element_id);
			$element_title = $title_prefix . $element->getName();

			return "<link rel=\"alternate\" type=\"application/rss+xml\" href=\"/data/rss/{$element_id}/\" title=\"{$element_title}\" />";
		}


		public function getRssMetaByPath($path, $title_prefix = "") {
			if($element_id = umiHierarchy::getInstance()->getIdByPath($path)) {
				return $this->getRssMeta($element_id, $title_prefix);
			} else {
				return "";
			}
		}


		public function getAtomMeta($element_id = false, $title_prefix = "") {
			$element_id = $this->analyzeRequiredPath($element_id);

			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return "";
			}

			$typeId = umiHierarchy::getInstance()->getDominantTypeId($element_id);
			$type = umiObjectTypesCollection::getInstance()->getType($typeId);
			if ($type instanceof umiObjectType) {
				$module = $type->getModule();
				$method = $type->getMethod();
				if(!$this->checkIfFeedable($module, $method)) {
					return "";
				}
			} else {
				return "";
			}
			
			$element = umiHierarchy::getInstance()->getElement($element_id);
			$element_title = $title_prefix . $element->getName();

			return "<link rel=\"alternate\" type=\"application/rss+xml\" href=\"/data/atom/{$element_id}/\" title=\"{$element_title}\" />";
		}

		public function getAtomMetaByPath($path, $title_prefix = "") {
			if($element_id = umiHierarchy::getInstance()->getIdByPath($path)) {
				return $this->getAtomMeta($element_id, $title_prefix);
			} else {
				return "";
			}
		}


		public function checkIfFeedable($module, $method) {
			
			$alowedSource = array(
				array("forum", "topic"),
				array("forum", "message"),
				array("news", "item"),
				array("blogs", "post"),
				array("blogs20", "post"),
				array("blogs20", "comment"),
				array("comments", "comment"),
				array("catalog", "object")
			);
			
			foreach($alowedSource as $allowed) {
				if($module == $allowed[0] && $method == $allowed[1]) {
					return true;
				}
			}

			return false;
		}
	};
?>
