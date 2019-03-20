<?php
	abstract class __exchange_auto {
		protected static $importDirectory = "/1c_import/";
		/** @var string статус успешного завершения синхронизации */
		protected static $success = 'success';

		/**
		 * Сохраняет файл, отправленный при синхронизации
		 * @return string статус сохранения
		 */
		protected function saveIncomingFile() {
			$fileName = getRequest('filename');

			if (!strlen($fileName)) {
				return "failure\nEmpty filename.";
			}

			$extension = getPathInfo($fileName, 'extension');

			if (!strlen($extension) || !umiFile::isAllowedFileType($extension)) {
				return "failure\nUnknown file type.";
			}

			if (!is_dir(self::$importDirectory)) {
				mkdir(self::$importDirectory, 0777, true);
			}
			
			return self::saveFile($fileName);
		}

		/**
		 * Сохраняет файл на сервер
		 * @param string $fileName имя файла
		 * @param string $fileContent содержимое файла, если не передано, то содержмое файла
		 * будет получено из тела запроса
		 * @return string статус сохранения
		 * @throws coreException
		 */
		protected static function saveFile($fileName, $fileContent = '') {
			$extension = getPathInfo($fileName, 'extension');

			if (!strlen($extension) || !umiFile::isAllowedFileType($extension)) {
				return "failure\nUnknown file type.";
			}

			$buffer = \UmiCms\Service::Response()
				->getHttpBuffer();
			$content = $fileContent ? $fileContent : $buffer->getHTTPRequestBody();
			$session = \UmiCms\Service::Session();
			$writingMode = (($session->get('1c_latest_catalog-file') == $fileName && !$fileContent) ? FILE_APPEND : 0);

			$dirName = getPathInfo($fileName, 'dirname');
			$status = '';

			switch ($extension) {
				case 'xml':
					$status = self::saveXmlFile($fileName, $content, $writingMode);
					break;
				case 'zip':
					$status = self::saveZipFile($fileName, $content, $writingMode);
					break;
				default:
					$status = self::saveImageFile($fileName, $content, $dirName, $writingMode);
			}

			$session->set('1c_latest_catalog-file', $fileName);
			return $status;
		}

		/**
		 * Сохраняет XML-файл на сервер
		 * @param string $fileName имя файла
		 * @param string $content содержимое файла
		 * @param int $writingMode режим записи файла
		 * @return string статус сохранения
		 */
		protected static function saveXMLFile($fileName, $content, $writingMode) {
			file_put_contents(self::$importDirectory . $fileName, $content, $writingMode);
			return self::$success;
		}

		/**
		 * Сохраняет ZIP-архив на сервер
		 * @param string $fileName имя файла
		 * @param string $content содержимое файла
		 * @param int $writingMode режим записи файла
		 * @return string статус сохранения
		 */
		protected static function saveZipFile($fileName, $content, $writingMode) {
			$filePath = self::$importDirectory . $fileName;
			file_put_contents($filePath, $content, $writingMode);

			$workingDir = getcwd();
			$importDirectoryFull = self::$importDirectory;
			chdir($importDirectoryFull);

			$zipArchive = new UmiZipArchive($fileName);
			$extractedFiles = $zipArchive->extract();
			chdir($workingDir);

			if (!is_array($extractedFiles) || (is_int($extractedFiles) && $extractedFiles === 0)) {
				return "failure\nCan't extract zip archive.";
			}

			foreach ($extractedFiles as $fileInfo) {
				$fileName = $fileInfo['stored_filename'];
				$filePath = self::$importDirectory . $fileName;

				if (is_file($filePath)) {
					$fileContent = file_get_contents($filePath);
					self::saveFile($fileName, $fileContent);
				}
			}

			return self::$success;
		}

		/**
		 * Сохраняет изображение на сервер
		 * @param string $fileName имя файла
		 * @param string $content содержимое файла
		 * @param string $dirName директория, в которой находится файл
		 * @param int $writingMode режим записи файла
		 * @return string статус сохранения
		 */
		protected static function saveImageFile($fileName, $content, $dirName, $writingMode) {
			$quota = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-files-and-images'));

			if ($quota != 0) {
				if((getBusyDiskSize() + strlen($content)) >= $quota) {
					return "failure\nReached maximum allowed size for /files and /images directories.";
				}
			}

			$imagesDir = USER_IMAGES_PATH . "/cms/data/";
			if ($dirName !== '.' || $dirName !== '..'){
				$imagesDir .= $dirName . "/";
			}

			if (!is_dir($imagesDir)) {
				mkdir($imagesDir, 0777, true);
			}

			file_put_contents(USER_IMAGES_PATH . "/cms/data/" . $fileName, $content, $writingMode);

			if (realpath(USER_IMAGES_PATH . "/cms/data/" . $fileName) != USER_IMAGES_PATH . "/cms/data/" . $fileName) {
				unlink(USER_IMAGES_PATH . "/cms/data/" . $fileName);
				return "failure\nWrong file path.";
			}

			return self::$success;
		}

		protected function importCommerceML() {
			$file_name = getRequest('filename');
			$file_path = self::$importDirectory . $file_name;

			if (!is_file($file_path)) return "failure\nFile $file_path does not exist.";
			$session = \UmiCms\Service::Session();
			$import_offset = (int) $session->get("1c_import_offset");

			$blockSize = (int) mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit");
			if($blockSize < 0) $blockSize = 25;

			$splitterName = (string) mainConfiguration::getInstance()->get("modules", "exchange.commerceML.splitter");
			if(!trim(strlen($splitterName))) $splitterName = "commerceML2";

			$splitter = umiImportSplitter::get($splitterName);
			$splitter->load($file_path, $blockSize, $import_offset);
			$doc = $splitter->getDocument();
			$xml = $splitter->translate($doc);

			$oldIgnoreSiteMap =  umiHierarchy::$ignoreSiteMap;
			umiHierarchy::$ignoreSiteMap = true;

			$importer = new xmlImporter();
			$importer->loadXmlString($xml);
			$importer->setIgnoreParentGroups($splitter->ignoreParentGroups);
			$importer->setAutoGuideCreation($splitter->autoGuideCreation);
			$importer->setRenameFiles($splitter->getRenameFiles());
			$importer->execute();

			umiHierarchy::$ignoreSiteMap = $oldIgnoreSiteMap;

			$session = \UmiCms\Service::Session();
			$session->set('1c_import_offset', $splitter->getOffset());
			$resultMessage = "progress\nImported elements: " . $splitter->getOffset();

			if ($splitter->getIsComplete()) {
				$importFinished = new umiEventPoint('exchangeOnAutoFinish');
				$importFinished->setMode('after');
				$importFinished->addRef('splitter', $splitter);
				$importFinished->call();
				$session->set('1c_import_offset', 0);
				$resultMessage = "success\nComplete. Imported elements: " . $splitter->getOffset();
			}

			return $resultMessage;
		}

		protected function exportOrders() {
			$exporter = umiExporter::get("ordersCommerceML");
			$exporter->setOutputBuffer();
			$result = $exporter->export(array(), array());
			return $result;
		}

		protected function markExportedOrders() {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('need_export')->equals(1);

			if (mainConfiguration::getInstance()->get('modules', 'exchange.commerceML.ordersByDomains')) {
				$currentDomainId = cmsController::getInstance()->getCurrentDomain()->getId();
				$sel->where('domain_id')->equals($currentDomainId);
			}

			$orders = $sel->result;
			foreach ($orders as $order) {
				$order->need_export = 0;
			}

			return "success";
		}

		protected function importOrders() {
			self::saveIncomingFile();
			$file_name = getRequest('filename');
			$file_path = self::$importDirectory . $file_name;

			if (!is_file($file_path)) return "failure\nFile $file_path does not exist.";

			$splitterName = (string) mainConfiguration::getInstance()->get("modules", "exchange.commerceML.splitter");
			if(!trim(strlen($splitterName))) $splitterName = "commerceML2";

			$splitter = umiImportSplitter::get($splitterName);
			$splitter->load($file_path);
			$doc = $splitter->getDocument();
			$xml = $splitter->translate($doc);

			$importer = new xmlImporter();
			$importer->loadXmlString($xml);
			$importer->setIgnoreParentGroups($splitter->ignoreParentGroups);
			$importer->setAutoGuideCreation($splitter->autoGuideCreation);
			$importer->execute();


			return "success";
		}


		public function auto() {
			$timeOut = (int) mainConfiguration::getInstance()->get("modules", "exchange.commerceML.timeout");
			if ($timeOut < 0) $timeOut = 0;

			sleep($timeOut);

			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/plain');

			$type = getRequest("type");
			$mode = getRequest("mode");
			$instance1c = getRequest('param0') ? md5(getRequest('param0')) . "/" : '';
			self::$importDirectory = SYS_TEMP_PATH . "/1c_import/" . $instance1c;

			if (!permissionsCollection::getInstance()->isSv()) {
				$buffer->push("failure\nNot authorized as supervisor.");
				$buffer->end();
				exit();
			}

			$session = \UmiCms\Service::Session();
			$sessionId = $session->getId();
			$sessionName = $session->getName();

			switch($type . "-" . $mode) {
				case "catalog-checkauth":
					// clear temp
					removeDirectory(self::$importDirectory);
				case "sale-checkauth": {
					$buffer->push("success\n$sessionName\n" . $sessionId);
				} break;
				case "catalog-init":
				case "sale-init": {
					removeDirectory(self::$importDirectory);
					$config = mainConfiguration::getInstance();
					$maxFileSize = (int) $config->get("modules", "exchange.commerceML.maxFileSize");
					if ($maxFileSize <= 0) {
						$maxFileSize = 102400;
					}

					$isZipAcceptable = $config->get("modules", "exchange.commerceML.accept-zip");
					$zipResponse = $isZipAcceptable ? 'yes' : 'no';

					$buffer->push("zip={$zipResponse}\nfile_limit={$maxFileSize}");
				} break;
				case "catalog-file": {
					$buffer->push(self::saveIncomingFile());
				} break;
				case "catalog-import" : {
					$buffer->push(self::importCommerceML());
				} break;

				case "sale-query" : {
					$buffer->push(self::exportOrders());
				} break;

				case "sale-success" : {
					$buffer->push(self::markExportedOrders());
				} break;

				case "sale-file" : {
					$buffer->push(self::importOrders());
				} break;

				default:
					$buffer->push("failure\nUnknown import type ($type) or mode ($mode).");
			}

			$buffer->end();
		}

	}

?>
