<?php
	abstract class __export_auto {
		
		
		public function export1C() {
			
			if (!permissionsCollection::getInstance()->isSv()) {
				return $this->reportError(getLabel('error-wrong-user'));
			}
			
			$mode = getRequest("mode");

			switch($mode) {
				case "catalog": {
					return $this->exportCatalog();
				}
				case "identification": {
					return $this->makeRelations();
				} 
				default: {
					return $this->reportError(getLabel('error-unknown-action-type') . ' "' . $mode . '".');
				}
			}
		}
		
		/**
		* Выводит сообщение об ошибкн
		* @param string $message сообщение
		*/
		public function reportError($message) {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/plain');
			$buffer->push("error\n{$message}");
			$buffer->end();
		}
		
		/**
		* Возвращает экспортер 
		* @return commerceMLExporter
		*/
		public function getExporter(umiObject $export) {
			
			$objects = umiObjectsCollection::getInstance();
		
			$formatId = $export->getValue('format');
			$exportFormat = $objects->getObject($formatId);
			if (!$exportFormat instanceof umiObject || $exportFormat->getGUID() != 'exchange-export-commerceml') {
				return $this->reportError(getLabel('error-wrong-export-format'));
			}

			$exportSuffix = $exportFormat->getValue('sid');
			try {
				$exporter = umiExporter::get($exportSuffix);	
			} catch (Exception $e) {
				return $this->reportError($e->getMessage());
			}
			
			return $exporter;
		}
		
		/**
		* Возвращает сценарий экспорта
		* @return umiObject
		*/
		public function getExport() {	
			$exportId = getRequest('param0');
			$export = umiObjectsCollection::getInstance()->getObject($exportId);
			if (!$export instanceof umiObject) {
				return $this->reportError(getLabel('error-wrong-export-id'));
			}
			return $export;
		}
		
		/** Выставляет связи между id в UMI.CMS и guid из 1С */
		public function makeRelations() {
			
			if (($handle = fopen("php://input", "r")) !== FALSE) {
				
				$export = $this->getExport();
				$exporter = $this->getExporter($export);
			
				$siteId = $exporter->getSourceName();
				$relations = umiImportRelations::getInstance();
				$sourceId = $relations->getSourceId('commerceML2');
				if (!$sourceId) {
					$sourceId = $relations->addNewSource('commerceML2');
				}
				
				while (($data = fgets($handle, 1000)) !== FALSE) {
					
					file_put_contents(CURRENT_WORKING_DIR . '/1cexport.log', "data: " . $data . "\n", FILE_APPEND);   
					
					$data = explode(';', $data);
					if (count($data) != 4) {
						return $this->reportError(getLabel('error-wrong-identification-data-format'));
					}
					
					list ($umiId, $type, $guid, $title) = $data;
					
					switch($type) {
						case "group":
						case "good": {
							$id = ltrim(substr_replace($umiId, '', 0, 2), '0');
							if (!filter_var($id, FILTER_VALIDATE_INT) || !umiHierarchy::getInstance()->isExists($id)) {
								return $this->reportError(getLabel('error-no-element', false, $id));
							} else {
								$relations->setIdRelation($sourceId, $guid, $id);
								break;
							}
						}
						case "property": {
							list($typeId, $fieldId) = explode('_', ltrim(substr_replace($umiId, '', 0, 2), '0'));
							if (!filter_var($typeId, FILTER_VALIDATE_INT) || !umiObjectTypesCollection::getInstance()->getType($typeId) instanceof umiObjectType) {
								return $this->reportError(getLabel('error-no-type', false, $typeId));
							}
							if (!filter_var($fieldId, FILTER_VALIDATE_INT) || !umiFieldsCollection::getInstance()->getField($fieldId) instanceof umiField) {
                            	return $this->reportError(getLabel('error-no-field', false, $fieldId));
							} else {
								$name = umiHierarchy::convertAltName(trim($title), "_");
								$name = umiObjectProperty::filterInputString($name);
								if(!strlen($name)) $name = '_';
								$name = substr($name, 0, 64);
								$relations->setFieldIdRelation($sourceId, $typeId, $name, $fieldId);	
								break;
							}
						}  
						default: {
							return $this->reportError(getLabel('error-wrong-entity-type') . ' "' . $type . '".');
						}
					}
				}
				fclose($handle);
				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->charset('utf-8');
				$buffer->contentType('text/plain');
				$buffer->push('complete');
				$buffer->end();
				
			} else {
				return $this->reportError(getLabel('error-cant-get-data'));
			}
		}
		
		/** Выводит результат экспорта каталога */
		public function exportCatalog() {
			
			$export = $this->getExport();
			$exporter = $this->getExporter($export);
			
			$elements = $this->get1CElementsToExport($export);			
			if (count($elements)) {
				
				$xmlExporter = new xmlExporter($exporter->getSourceName());
				$xmlExporter->addElements($elements);
				$xmlExporter->setIgnoreRelations();
				$umiDump = $xmlExporter->execute()->saveXML();

				$style_file = './xsl/export/' . $exporter->getType() . '.xsl';
				if (!is_file($style_file)) {
					return $this->reportError(getLabel('error-no-export-template', false, $style_file));
				}

				$doc = new DOMDocument("1.0", "utf-8");
				$doc->formatOutput = XML_FORMAT_OUTPUT;
				$doc->loadXML($umiDump);

				$templater = umiTemplater::create('XSLT', $style_file);
				$result = $templater->parse($doc);

				$buffer = $exporter->setOutputBuffer();
				$buffer->push($result);
				$buffer->end();	
			} else {
				@unlink(SYS_TEMP_PATH . "/export/{$export->getId()}.txt");
				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->charset('utf-8');
				$buffer->contentType('text/plain');
				$buffer->push('complete');
				$buffer->end();
			}
		} 

		/**
		* Возвращает массив элементов, которые должны быть экспортированы
		* @param umiObject $export сценарий экспорта
		* @return array
		*/
		public function get1CElementsToExport(umiObject $export) {
			
			$limit = getRequest('package_size');
			$packageNumber = getRequest('package');
			if ($limit <= 0 || $packageNumber < 0) {
				return $this->reportError(getLabel('error-impossible-export-parametrs'));
			}
			
			$elementsToExport = array();
			$cacheFileDir = SYS_TEMP_PATH . "/export/";
			if (!is_dir($cacheFileDir)) mkdir($cacheFileDir, 0777, true);			
			$cacheFile = $cacheFileDir . $export->getId() . '.txt';
			$hierarchy = umiHierarchy::getInstance();
			
			if (!file_exists($cacheFile) || getRequest('package') == 0) {
				
				$branches = $export->getValue('elements');
				$excludedBranches = $export->getValue('excluded_elements');
				if (!count($branches)) {
					$sel = new selector('pages');
					$sel->where('hierarchy')->page(0)->childs(0);
					$sel->types('hierarchy-type')->name('catalog', 'category');
					$sel->types('hierarchy-type')->name('catalog', 'object');
					$branches = $sel->result;
				}
				
				foreach ($branches as $element) {
					if (!$element instanceof umiHierarchyElement) {
						$element = $hierarchy->getElement($element, true, true);
					}
					if (!$element instanceof umiHierarchyElement) continue;
					
					$elementId = $element->getId();
					$elementsToExport[$elementId] = $elementId;
					
					$level = $hierarchy->getMaxNestingLevel($elementId);
					if (!$level) continue;
					
					for ($i = 1; $i <= $level; $i++) {
						$sel = new selector('pages');
						$sel->option('return')->value('id');
						$sel->where('hierarchy')->page($elementId)->childs($i);
						$sel->types('hierarchy-type')->name('catalog', 'category');
						$sel->types('hierarchy-type')->name('catalog', 'object');
						foreach($sel->result() as $res) {
							$elementsToExport[$res['id']] = $res['id'];
						}	
					}
				}
				
				foreach ($excludedBranches as $element) {
					if (!$element instanceof umiHierarchyElement) {
						$element = $hierarchy->getElement($element, true, true);
					}
					if (!$element instanceof umiHierarchyElement) continue;
					$elementId = $element->getId();
					
					if (isset($elementsToExport[$elementId])) {
						unset($elementsToExport[$elementId]);
					}
				
					$level = $hierarchy->getMaxNestingLevel($elementId);
					if (!$level) continue;
					
					for ($i = 1; $i <= $level; $i++) {
						$sel = new selector('pages');
						$sel->option('return')->value('id');
						$sel->where('hierarchy')->page($elementId)->childs($i);
						foreach($sel->result() as $res) {
							if (isset($elementsToExport[$res['id']])) {
								unset($elementsToExport[$res['id']]);
							}
						}	
					}
				}
			} else {
				$elementsToExport = unserialize(file_get_contents($cacheFile));
			}
			

			
			$offset =  $packageNumber * $limit;
			$nextElements = array_slice($elementsToExport, $offset, $limit);
		
			$elements = array();
			foreach($nextElements as $elementId) {
				$parents = $hierarchy->getAllParents($elementId, true);
				$usedParents = array_intersect($parents, $elementsToExport);
				$elements = array_merge($elements, $usedParents);
			}
			
			return array_unique($elements);
		}
	}
?>
