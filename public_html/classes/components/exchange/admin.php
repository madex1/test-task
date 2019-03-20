<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class ExchangeAdmin {

		use baseModuleAdmin;

		/** @var exchange $module */
		public $module;

		/**
		 * Выполняет одну итерацию импорта заданного
		 * сценария и возвращает результат операции.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws publicException
		 */
		public function import_do() {
			if (isDemoMode()) {
				throw new publicAdminException(getLabel('label-stop-in-demo'));
			}

			$this->setDataType('list');
			$this->setActionType('view');

			$id = getRequest('param0');
			$umiObjects = umiObjectsCollection::getInstance();

			$settings = $umiObjects->getObject($id);
			if (!$settings instanceof iUmiObject) {
				throw new publicException(getLabel('exchange-err-settings_notfound'));
			}

			$importFile = $settings->getValue('file');
			if (!($importFile instanceof umiFile) || $importFile->getIsBroken()) {
				throw new publicException(getLabel('exchange-err-importfile'));
			}

			$format_id = $settings->getValue('format');
			$importFormat = $umiObjects->getObject($format_id);
			if (!$importFormat instanceof iUmiObject) {
				throw new publicException(getLabel('exchange-err-format_undefined'));
			}

			$suffix = $importFormat->getValue('sid');
			$session = Service::Session();
			$importOffset = (int) $session->get('import_offset_' . $id);
			$umiConfig = mainConfiguration::getInstance();
			$blockSize = $umiConfig->get('modules', 'exchange.splitter.limit') ?: 25;

			$splitter = umiImportSplitter::get($suffix);

			if ($splitter instanceof csvSplitter) {
				$this->setCsvEncoding($splitter, $settings);
				$this->setCsvSourceName($splitter, $settings);
			}

			$splitter->load($importFile->getFilePath(), $blockSize, $importOffset);
			$doc = $splitter->getDocument();
			$dump = $splitter->translate($doc);

			$oldIgnoreSiteMap = umiHierarchy::$ignoreSiteMap;
			umiHierarchy::$ignoreSiteMap = true;

			$importer = new xmlImporter();
			$importer->loadXmlString($dump);

			$elements = $settings->getValue('elements');

			if (is_array($elements) && umiCount($elements)) {
				$importer->setDestinationElement($elements[0]);
			}

			$importer->setIgnoreParentGroups($splitter->ignoreParentGroups);
			$importer->setAutoGuideCreation($splitter->autoGuideCreation);
			$importer->setRenameFiles($splitter->getRenameFiles());

			$eventPoint = new umiEventPoint('exchangeImport');
			$eventPoint->setMode('before');
			$eventPoint->addRef('importer', $importer);
			$eventPoint->call();

			$importer->execute();
			umiHierarchy::$ignoreSiteMap = $oldIgnoreSiteMap;

			$progressKey = 'import_offset_' . $id;
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

			$data = [
				'attribute:complete' => (int) $splitter->getIsComplete(),
				'attribute:created' => $importer->getCreatedEntityCount(),
				'attribute:updated' => $importer->getUpdatedEntityCount(),
				'attribute:deleted' => $importer->getDeletedEntityCount(),
				'attribute:errors' => $importer->getErrorCount(),
				'nodes:log' => $importer->getImportLog(),
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Устанавливает кодировку импорта в формате CSV
		 * @param csvSplitter $splitter тип импорта в формате CSV
		 * @param iUmiObject $settings настройки сценария импорта
		 */
		private function setCsvEncoding(csvSplitter $splitter, iUmiObject $settings) {
			$scenarioEncodingId = $settings->getValue('encoding_import');
			$scenarioEncodingCode = '';
			$scenarioEncoding = umiObjectsCollection::getInstance()
				->getObject($scenarioEncodingId);

			if ($scenarioEncoding instanceof iUmiObject) {
				$scenarioEncodingCode = $scenarioEncoding->getName();
			}

			$configEncoding = mainConfiguration::getInstance()
				->get('system', 'default-exchange-encoding');
			$defaultEncoding = 'windows-1251';
			$encoding = $scenarioEncodingCode ?: $configEncoding;

			try {
				$splitter->setEncoding($encoding);
			} catch (InvalidArgumentException $e) {
				$splitter->setEncoding($defaultEncoding);
			}
		}

		/**
		 * Устанавливает имя источника импорта в формате CSV
		 * @param csvSplitter $splitter тип импорта в формате CSV
		 * @param iUmiObject $settings настройки сценария импорта
		 * @return csvSplitter
		 */
		private function setCsvSourceName(csvSplitter $splitter, iUmiObject $settings) {
			$sourceName = (string) $settings->getValue('source_name');
			$sourceName = trim($sourceName);

			if ($sourceName === '') {
				return $splitter;
			}

			return $splitter->setSourceName($sourceName);
		}

		/**
		 * Выполняет одну итерацию подготовки страниц к экспорту в
		 * формате YML и возвращает результат операции.
		 * @throws publicException
		 */
		public function prepareElementsToExport() {
			$objectId = getRequest('param0');
			$complete = false;

			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($objectId);
			$formatId = $object->getValue('format');
			$format = $objects->getObject($formatId);
			$suffix = $format->getValue('sid');

			if ($suffix != 'YML') {
				$data = [
					'attribute:complete' => (int) $complete,
					'attribute:preparation' => (int) !$complete,
				];

				$this->setData($data);
				$this->doData();
				return;
			}

			$session = Service::Session();
			$offset = (int) $session->get('export_offset_' . $objectId);
			$umiConfig = mainConfiguration::getInstance();
			$blockSize = $umiConfig->get('modules', 'exchange.splitter.limit') ?: 25;

			if (!file_exists(SYS_TEMP_PATH . '/yml/' . $objectId . 'el')) {
				throw new publicException(
					'<a href="' . getLabel('label-errors-no-information') . '" target="blank">' .
					getLabel('label-errors-no-information') . '</a>'
				);
			}

			$elementsToExport = unserialize(file_get_contents(SYS_TEMP_PATH . '/yml/' . $objectId . 'el'));
			$elements = umiHierarchy::getInstance();

			$errors = [];
			for ($i = $offset; $i <= $offset + $blockSize - 1; $i++) {

				if (!array_key_exists($i, $elementsToExport)) {
					$complete = true;
					break;
				}

				$element = $elements->getElement($elementsToExport[$i]);

				if ($element instanceof iUmiHierarchyElement) {
					try {
						$element->updateYML();
					} catch (Exception $e) {
						$errors[] = $e->getMessage() . " #{$elementsToExport[$i]}";
					}
				}
			}

			$progressKey = 'export_offset_' . $objectId;
			$progress = $offset + $blockSize;

			$session->set($progressKey, $progress);

			if ($complete) {
				$session->del($progressKey);
			}

			$data = [
				'attribute:complete' => (int) $complete,
				'nodes:log' => $errors,
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Выполняет сценарий экспорта и возвращает содержимое полученного файла
		 * в буфер, либо инициирует скачивание этого файла.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws publicException
		 */
		public function get_export() {
			if (isDemoMode()) {
				throw new publicAdminException(getLabel('label-stop-in-demo'));
			}

			$id = (int) getRequest('param0');
			$scenario = $this->getExportScenario($id);
			$exporter = $this->getExporterByScenario($scenario);
			$exportDir = $this->getExportTemporaryPath();

			if (!is_dir($exportDir)) {
				mkdir($exportDir, 0777, true);
			}

			$exportPrefix = $exportDir . $id;
			$exportFile = new umiFile("{$exportPrefix}.{$exporter->getFileExt()}");

			$asFile = getRequest('as_file');
			if ($asFile === '1') {
				// Конец запроса
				$this->downloadExport($exportPrefix, $exportFile);
			}

			if (!$this->isExportCached($exportFile, $scenario)) {
				$result = $exporter->export(
					$scenario->getValue('elements'), $scenario->getValue('excluded_elements')
				);

				if ($result) {
					$exportFile->putContent($result);
				}
			}

			$isCompleted = $exporter->getIsCompleted();
			if ($isCompleted) {
				$this->triggerExportFinishedEvent($scenario, $exporter);
			}

			if ($asFile === '0') {
				// Конец запроса
				$this->flushExportedContent($exportFile, $exporter);
			}

			$data = [
				'attribute:complete' => (int) $isCompleted,
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает сценарий экспорта по его ID
		 * @param int $id Идентификатор сценария экспорта
		 * @return iUmiObject
		 * @throws publicException
		 */
		private function getExportScenario($id) {
			$scenario = umiObjectsCollection::getInstance()
				->getObject($id);
			if (!$scenario instanceof iUmiObject) {
				throw new publicException(getLabel('exchange-err-settings_notfound'));
			}
			return $scenario;
		}

		/**
		 * Начинает скачивание файла экспорта и завершает запрос.
		 * @param string $dirPath путь до директории с экспортированными данными
		 * @param iUmiFile $file файл экспорта
		 */
		private function downloadExport($dirPath, iUmiFile $file) {
			if (is_dir($dirPath)) {
				$this->downloadExportArchive($dirPath);
			} else {
				$file->download();
			}
		}

		/**
		 * Начинает скачивание архива с экспортированными данными и завершает запрос.
		 * @param string $dirPath путь до директории с экспортированными данными
		 */
		private function downloadExportArchive($dirPath) {
			$archivePath = "{$dirPath}.zip";

			if (file_exists($archivePath)) {
				unlink($archivePath);
			}

			$archive = new UmiZipArchive($archivePath);
			$archive->add(["{$dirPath}.xml", $dirPath], SYS_TEMP_PATH . '/export');

			$dir = new umiDirectory($dirPath);
			$dir->deleteRecursively();

			$zipFile = new umiFile($archivePath);
			$zipFile->download();
		}

		/**
		 * Определяет, существует ли достаточно свежий закэшированный файл экспорта
		 * @param iUmiFile $exportFile файл экспорта
		 * @param iUmiObject $scenario сценарий экспорта
		 * @return bool
		 */
		private function isExportCached(iUmiFile $exportFile, iUmiObject $scenario) {
			$cacheTime = (int) $scenario->getValue('cache_time');
			if (!$cacheTime) {
				return false;
			}

			if (!$exportFile->isReadable()) {
				return false;
			}

			$isCacheFresh = time() <= ($exportFile->getModifyTime() + $cacheTime * 60);
			return $isCacheFresh;
		}

		/**
		 * Вызывает событие успешного окончания экспорта
		 * @param iUmiObject $scenario сценарий экспорта
		 * @param iUmiExporter $exporter тип экспорта
		 * @return mixed
		 * @throws coreException
		 */
		private function triggerExportFinishedEvent(iUmiObject $scenario, iUmiExporter $exporter) {
			$exportFinished = new umiEventPoint('exchangeOnExportFinish');
			$exportFinished->setMode('after');
			$exportFinished->addRef('settings', $scenario);
			$exportFinished->addRef('exporter', $exporter);
			$exportFinished->setParam('scenario_id', $scenario->getId());
			$exportFinished->call();
		}

		/**
		 * Передает результат экспорта в буфер вывода и
		 * прекращает работу скрипта.
		 * @param iUmiFile $exportFile файл экспорта
		 * @param iUmiExporter $exporter тип экспорта
		 */
		private function flushExportedContent(iUmiFile $exportFile, iUmiExporter $exporter) {
			$buffer = $this->setBufferHeaders($exportFile->getExt(), $exporter->setOutputBuffer());
			$buffer->push($exportFile->getContent());
			$buffer->end();
		}

		/**
		 * Изменяет заголовки буфера
		 * @param string $extension расширение файла
		 * @param iOutputBuffer $buffer буфер вывода
		 * @return iOutputBuffer
		 */
		private function setBufferHeaders($extension, iOutputBuffer $buffer) {
			if ($extension == 'xml') {
				$buffer->contentType("text/xml");
			}

			return $buffer;
		}

		/**
		 * Возвращает путь до директории с временными файлами экспорта
		 * @return string
		 */
		public function getExportTemporaryPath() {
			return SYS_TEMP_PATH . '/export/';
		}

		/**
		 * Возвращает экспортер, соответствующий сценарию
		 * @param iUmiObject $scenario сценарий экспорта
		 * @return iUmiExporter
		 * @throws publicException
		 */
		public function getExporterByScenario(iUmiObject $scenario) {
			$formatId = $scenario->getValue('format');
			$exportFormat = selector::get('object')->id($formatId);

			if (!$exportFormat instanceof iUmiObject) {
				throw new publicException(getLabel('exchange-err-format_undefined'));
			}

			$prefix = $exportFormat->getValue('sid');
			$exporter = umiExporter::get($prefix);

			if ($scenario->getValue('source_name')) {
				$exporter->setSourceName($scenario->getValue('source_name'));
			}

			if ($exporter instanceof csvExporter) {
				$this->setEncodingToCsvExporter($exporter, $scenario);
			}

			return $exporter;
		}

		/**
		 * Устанавливает кодировку экспортера
		 * @param csvExporter $exporter экспортер
		 * @param iUmiObject $scenario сценарий
		 */
		public function setEncodingToCsvExporter(csvExporter $exporter, iUmiObject $scenario) {
			$scenarioEncodingId = $scenario->getValue('encoding_export');
			$scenarioEncodingCode = '';
			$scenarioEncoding = selector::get('object')->id($scenarioEncodingId);

			if ($scenarioEncoding instanceof iUmiObject) {
				$scenarioEncodingCode = $scenarioEncoding->getName();
			}

			$defaultConfigEncoding = mainConfiguration::getInstance()->get('system', 'default-exchange-encoding');
			$defaultEncoding = 'windows-1251';
			$encoding = $scenarioEncodingCode ?: $defaultConfigEncoding;

			try {
				$exporter->setEncoding($encoding);
			} catch (InvalidArgumentException $e) {
				$exporter->setEncoding($defaultEncoding);
			}
		}

		/**
		 * Возвращает список сценариев импорта
		 * @throws coreException
		 * @throws selectorException
		 */
		public function import() {
			$this->setDataType('list');
			$this->setActionType('view');

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('exchange', 'import');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает список сценариев экспорта
		 * @throws coreException
		 * @throws selectorException
		 */
		public function export() {
			$this->setDataType('list');
			$this->setActionType('view');

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('exchange', 'export');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные для построения формы создания сущности модуля.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то создает сущность
		 * и перенаправляет на страницу, где ее можно отредактировать.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws publicException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$type = (string) getRequest('param0');
			$this->setHeaderLabel('header-exchange-add-' . $type);
			$inputData = [
				'type' => $type,
				'allowed-element-types' => [
					'import',
					'export',
				],
			];

			if ($this->isSaveMode('param1')) {
				$object = $this->saveAddedObjectData($inputData);
				$this->module->saveScenarioCache($object->getId());
				$this->chooseRedirect($this->module->pre_lang . '/admin/exchange/edit/' . $object->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');

			$data = $this->prepareData($inputData, 'object');
			$data['default-encoding'] = $this->module->getDefaultEncoding();
			$data['object-type'] = $type;

			$csvFormat = $this->module->getFormatByCode('CSV', $type);
			if ($csvFormat instanceof iUmiObject) {
				$data['csv-format-id'] = $csvFormat->getId();
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для построения формы редактирования сущности модуля.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения сущности
		 * и осуществляет перенаправление. Адрес перенаправления зависит от режиме кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function edit() {
			$object = $this->expectObject('param0', true);
			$objectId = $object->getId();
			$this->setHeaderLabel('header-exchange-edit-' . $this->getObjectTypeMethod($object));
			$inputData = [
				'object' => $object,
				'allowed-element-types' => [
					'import',
					'export',
				],
			];

			if ($this->isSaveMode('param1')) {
				$this->saveEditedObjectData($inputData);
				$this->module->saveScenarioCache($objectId);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($inputData, 'object');
			$data['default-encoding'] = $this->module->getDefaultEncoding();
			$objectType = $object->getType();

			$type = '';
			if ($objectType instanceof iUmiObjectType) {
				$type = $objectType->getMethod();
			}

			$data['object-type'] = $type;

			$csvFormat = $this->module->getFormatByCode('CSV', $type);
			if ($csvFormat instanceof iUmiObject) {
				$data['csv-format-id'] = $csvFormat->getId();
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет сущности модуля
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = [$objects];
			}

			foreach ($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = [
					'object' => $object,
					'allowed-element-types' => [
						'import',
						'export',
					],
				];

				$this->deleteObject($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();

			switch ($param) {
				case 'export' : {
					$loadMethod = 'export';
					$typeId = $umiObjectTypesCollection->getTypeIdByHierarchyTypeName('exchange', 'export');
					$defaults = 'name[400px]|format[350px]';
					break;
				}

				default: {
					$loadMethod = 'import';
					$typeId = $umiObjectTypesCollection->getTypeIdByHierarchyTypeName('exchange', 'import');
					$defaults = 'name[400px]|format[350px]';
				}
			}

			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'exchange',
						'#__name' => $loadMethod,
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'exchange',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del',
					],
				],
				'types' => [
					[
						'common' => 'true',
						'id' => $typeId,
					],
				],
				'stoplist' => [''],
				'default' => $defaults,
			];
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do, то сохраняет настройки.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		public function config() {
			$params = [
				'config' => [
					'bool:use_cml_trade_offers' => null,
					'bool:restore_deleted_catalog_items_from_cml' => null
				]
			];

			$module = $this->module;

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$module->setTradeOffersUsedInCMl($params['config']['bool:use_cml_trade_offers']);
				$module->setNeedToRestoreCatalogItemsFromTrashImCML($params['config']['bool:restore_deleted_catalog_items_from_cml']);
				$this->chooseRedirect();
			}

			$params['config']['bool:use_cml_trade_offers'] = (int) $module->isTradeOffersUsedInCMl();
			$params['config']['bool:restore_deleted_catalog_items_from_cml'] = (int) $module->isNeedToRestoreCatalogItemsFromTrashImCML();
			$this->setConfigResult($params);
		}
	}
