<?php

	abstract class __exchange_import extends baseModuleAdmin {

		public function import_do() {
			if (isDemoMode()) {
				throw new publicAdminException(getLabel('label-stop-in-demo'));
			}
			$this->setDataType("list");
			$this->setActionType("view");

			$id = getRequest('param0');
			$objects = umiObjectsCollection::getInstance();

			$settings = $objects->getObject($id);
			if (!$settings instanceof umiObject) {
				throw new publicException(getLabel("exchange-err-settings_notfound"));
			}

			$importFile = $settings->file;
			if (!($importFile instanceof umiFile) || ($importFile->getIsBroken())) {
				throw new publicException(getLabel("exchange-err-importfile"));
			}

			$format_id = $settings->format;
			$importFormat = $objects->getObject($format_id);
			if (!$importFormat instanceof umiObject) {
				throw new publicException(getLabel("exchange-err-format_undefined"));
			}

			$suffix = $importFormat->sid;
			$session = \UmiCms\Service::Session();
			$import_offset = (int) $session->get("import_offset_" . $id);
			$blockSize = mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") ? mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") : 25;

			$splitter = umiImportSplitter::get($suffix);

			if ($splitter instanceof csvSplitter) {
				$scenarioEncodingId = $settings->getValue('encoding_import');
				$scenarioEncodingCode = '';
				$scenarioEncoding = $objects->getObject($scenarioEncodingId);

				if ($scenarioEncoding instanceof iUmiObject) {
					$scenarioEncodingCode = $scenarioEncoding->getName();
				}

				$defaultConfigEncoding = mainConfiguration::getInstance()->get('system', 'default-exchange-encoding');
				$defaultEncoding = 'windows-1251';

				$encoding = $scenarioEncodingCode ? $scenarioEncodingCode : $defaultConfigEncoding;

				try {
					$splitter->setEncoding($encoding);
				} catch (InvalidArgumentException $e) {
					$splitter->setEncoding($defaultEncoding);
				}
			}

			$splitter->load($importFile->getFilePath(), $blockSize, $import_offset);
			$doc = $splitter->getDocument();
			$dump = $splitter->translate($doc);

			$oldIgnoreSiteMap =  umiHierarchy::$ignoreSiteMap;
			umiHierarchy::$ignoreSiteMap = true;

			$importer = new xmlImporter();
			$importer->loadXmlString($dump);

			$elements = $settings->elements;
			if (is_array($elements) && count($elements)) {
				$importer->setDestinationElement($elements[0]);
			}

			$importer->setIgnoreParentGroups($splitter->ignoreParentGroups);
			$importer->setAutoGuideCreation($splitter->autoGuideCreation);
			$importer->setRenameFiles($splitter->getRenameFiles());

			$eventPoint = new umiEventPoint("exchangeImport");
			$eventPoint->setMode("before");
			$eventPoint->addRef("importer", $importer);
			$eventPoint->call();

			$importer->execute();

			umiHierarchy::$ignoreSiteMap = $oldIgnoreSiteMap;

			$progressKey = "import_offset_" . $id;
			$session = \UmiCms\Service::Session();
			$session->set($progressKey, $splitter->getOffset());

			if ($splitter->getIsComplete()) {
				$session->del($progressKey);
			}

			if ($splitter->getIsComplete()) {
				$importFinished = new umiEventPoint('exchangeOnImportFinish');
				$importFinished->setMode('after');
				$importFinished->addRef('settings', $settings);
				$importFinished->addRef('splitter', $splitter);
				$importFinished->setParam('scenario_id', $id);
				$importFinished->call();
			}

			$data = array(
				"attribute:complete" => (int) $splitter->getIsComplete(),
				"attribute:created" => $importer->created_elements,
				"attribute:updated" => $importer->updated_elements,
				"attribute:deleted" => $importer->deleted_elements,
				"attribute:errors" => $importer->import_errors,
				"nodes:log" => $importer->getImportLog()
			);

			$this->setData($data);
			return $this->doData();
		}

	}
?>