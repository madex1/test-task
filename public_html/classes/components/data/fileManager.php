<?php

	use UmiCms\Service;

	/** Класс функционала файлового менеджера */
	class DataFileManager {

		/** @var data $module */
		public $module;

		/** @var string $cwd путь до директории, в которой работает файловый менеджер */
		private $cwd = USER_FILES_PATH;

		/** @const string FILES_HASH_PREFIX префикс для хеша адреса файла, @see elfinder_get_hash() */
		const FILES_HASH_PREFIX = 'umifiles';

		/** @const string IMAGES_HASH_PREFIX префикс для хеша адреса изображения, @see elfinder_get_hash() */
		const IMAGES_HASH_PREFIX = 'umiimages';

		/**
		 * Запускает файловый менеджер "Elfinder", либо выводит в буффер максимальный размер
		 * загружаемого файла
		 * @param bool $needInfo вернуть максимальный разме возвращаемого файла
		 * @throws coreException
		 */
		public function elfinder_connector($needInfo = false) {
			$needInfo = (!$needInfo) ? getRequest('param0') : $needInfo;

			if ($needInfo == 'getSystemInfo') {
				$arData = [
					'maxFilesCount' => ini_get('max_file_uploads') ?: 20
				];
				$this->module->flush(json_encode($arData), 'text/javascript');
			}

			$isFullAccess = (bool) getRequest('full-access');

			function elfinder_full_access($attr, $path, $data, $volume) {
				if ($attr == 'hidden') {
					return null;
				}

				$readOrWrite = ($attr == 'read' || $attr == 'write');
				return startsWith(basename($path), '.') ? !$readOrWrite : $readOrWrite;
			}

			function elfinder_access($attr, $path, $data, $volume) {
				if ($attr == 'hidden') {
					return null;
				}

				if (startsWith(basename($path), '.')) {
					return !($attr == 'read' || $attr == 'write');
				}

				if (isDemoMode()) {
					return !($attr == 'write' || $attr == 'hidden');
				}
				return ($attr == 'read' || $attr == 'write');
			}

			$opts = [
				'debug' => true,
				'roots' => []
			];

			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$user = umiObjectsCollection::getInstance()->getObject($userId);
			$allowedDirectories = [];

			if (!isDemoMode() && $fileManagerDirectory = $user->getValue('filemanager_directory')) {
				$directories = explode(',', $fileManagerDirectory);

				foreach ($directories as $directory) {
					$directory = trim($directory);

					if ($directory === '') {
						continue;
					}

					$directory = trim($directory, '/');
					$directoryPath = realpath(CURRENT_WORKING_DIR . '/' . $directory);
					$pathNotInUserFilesPath = !contains($directoryPath, USER_FILES_PATH);
					$pathNotInUserImagesPath = !contains($directoryPath, USER_IMAGES_PATH);

					if (($pathNotInUserFilesPath && $pathNotInUserImagesPath) || !is_dir($directoryPath)) {
						continue;
					}

					$allowedDirectories[] = $directory;
				}
			}

			$cwdLength = mb_strlen(CURRENT_WORKING_DIR);
			$imagesDir = mb_substr(USER_IMAGES_PATH, $cwdLength) . '/';
			$filesDir = mb_substr(USER_FILES_PATH, $cwdLength) . '/';
			$target = isset($_REQUEST['target']) ? $_REQUEST['target'] : '';

			$hiddenFilePattern = '|\/\.|';
			$attributes = [
				[
					'pattern' => '|\/cms\/admin|',
					'hidden' => true
				],
				[
					'pattern' => $hiddenFilePattern,
					'hidden' => true
				]
			];

			if (umiCount($allowedDirectories)) {
				$i = 1;
				foreach ($allowedDirectories as $directory) {
					$opts['roots'][] = [
						'id' => 'files' . $i,
						'driver' => 'UmiLocalFileSystem',
						'path' => CURRENT_WORKING_DIR . '/' . $directory,
						'URL' => '/' . $directory,
						'accessControl' => 'elfinder_access',
						'attributes' => $attributes
					];
					$i++;
				}
			} else {
				$rootImagesCategoryOptions = [
					'id' => 'images',
					'driver' => 'UmiLocalFileSystem',
					'path' => USER_IMAGES_PATH . '/',
					'URL' => $imagesDir,
					'accessControl' => $isFullAccess ? 'elfinder_full_access' : 'elfinder_access',
					'attributes' => $attributes
				];

				$rootFilesCategoryOptions = [
					'id' => 'files',
					'driver' => 'UmiLocalFileSystem',
					'path' => USER_FILES_PATH . '/',
					'URL' => $filesDir,
					'accessControl' => $isFullAccess ? 'elfinder_full_access' : 'elfinder_access',
					'attributes' => [
						[
							'pattern' => $hiddenFilePattern,
							'hidden' => true
						]
					]
				];

				switch (true) {
					case startsWith($target, self::IMAGES_HASH_PREFIX) : {
						$opts['roots'][] = $rootImagesCategoryOptions;
						$opts['roots'][] = $rootFilesCategoryOptions;
						break;
					}
					default : {
						$opts['roots'][] = $rootFilesCategoryOptions;
						$opts['roots'][] = $rootImagesCategoryOptions;
					}
				}
			}

			/** @var umiEventPoint $eventPoint */
			$eventPoint = Service::EventPointFactory()
				->create('filemanager_options_create', 'after');
			$eventPoint->setParam('options', $opts);
			$eventPoint->call();

			$options = $eventPoint->getParam('options');
			
			$connector = new elFinderConnector(new elFinder($options));
			$connector->run();
		}

		/**
		 * Выводит в буффер настройки файлового менеджера
		 * @throws coreException
		 */
		public function get_filemanager_info() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->clear();

			$module = $this->module;
			$folder = (string) getRequest('folder');
			$file = (string) getRequest('file');
			$folderHash = $folder ? $module->createElFinderFileHash($folder) : '';
			$fileHash = $file ? $module->createElFinderFileHash($file) : '';

			$objects = umiObjectsCollection::getInstance();
			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$user = $objects->getObject($userId);
			$fmId = $user->getValue('filemanager');

			if ($fmId) {
				$fm = $objects->getObject($fmId);
				$fmPrefix = $fm->getValue('fm_prefix') ?: 'elfinder';
			} else {
				$fmPrefix = 'elfinder';
			}

			$data = [
				'folder_hash' => $folderHash,
				'file_hash' => $fileHash,
				'filemanager' => $fmPrefix,
				'lang' => Service::LanguageDetector()->detectPrefix()
			];

			$json = new jsonTranslator;
			$result = $json->translateToJson($data);
			$buffer->push($result);
			$buffer->end();
		}

		/**
		 * Создает hash файла для файлового менеджера
		 * @param string $file путь к файлу/папке
		 * @return string
		 */
		public function createElFinderFileHash($file) {
			return elfinder_get_hash($file);
		}

		/**
		 * Возвращает список файлов, если в $_REQUEST
		 * передана дополнительная операци (копировать, удалить и переместить),
		 * то также выполняет ее
		 * @return array
		 * @throws coreException
		 */
		public function getfilelist() {
			$this->module->flushAsXML('getfilelist');
			$this->setupCwd();

			$param = [
				[
					'delete',
					'unlink',
					1
				],
				[
					'copy',
					'copy',
					2
				],
				[
					'move',
					'rename',
					2
				]
			];

			for ($i = 0; $i < umiCount($param); $i++) {
				if ($param != 'copy' && isDemoMode()) {
					continue;
				}
				if (isset($_REQUEST[$param[$i][0]]) && !empty($_REQUEST[$param[$i][0]])) {
					foreach ($_REQUEST[$param[$i][0]] as $item) {
						$item = CURRENT_WORKING_DIR . base64_decode($item);
						$arguments = [$item];
						if ($param[$i][2] > 1) {
							$arguments[] = $this->cwd . '/' . basename($item);
						}
						@call_user_func_array($param[$i][1], $arguments);
					}
				}
			}

			$imageExt = [
				'jpg',
				'jpeg',
				'gif',
				'png'
			];
			$sizeMeasure = [
				'b',
				'Kb',
				'Mb',
				'Gb',
				'Tb'
			];
			$allowedExt = true;

			if (isset($_REQUEST['showOnlyImages'])) {
				$allowedExt = $imageExt;
			} elseif (isset($_REQUEST['showOnlyVideos'])) {
				$allowedExt = [
					'flv',
					'mp4'
				];
			} elseif (isset($_REQUEST['showOnlyMedia'])) {
				$allowedExt = [
					'swf',
					'flv',
					'dcr',
					'mov',
					'qt',
					'mpg',
					'mp3',
					'mp4',
					'mpeg',
					'avi',
					'wmv',
					'wm',
					'asf',
					'asx',
					'wmx',
					'wvx',
					'rm',
					'ra',
					'ram'
				];
			}

			$directory = new DirectoryIterator($this->cwd);
			$cwd = mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR));

			$warning = false;
			$filesData = [];
			$countFiles = 0;
			$wrongFileNameMessage =
				'Error: Присутствуют файлы с недопустимыми названиями! Ошибка: http://errors.umi-cms.ru/13050/';

			foreach ($directory as $file) {
				if ($file->isDir() || $file->isDot()) {
					continue;
				}

				$name = $file->getFilename();
				$ext = mb_substr($name, mb_strrpos($name, '.') + 1);

				if ($allowedExt !== true && !in_array(mb_strtolower($ext), $allowedExt)) {
					continue;
				}

				$ts = $file->getCTime();
				$time = date('G:i, d.m.Y', $ts);
				$size = $file->getSize();

				$img = $file;

				$sCharset = detectCharset($name);

				if (function_exists('iconv') && $sCharset !== 'UTF-8') {
					$warning = $wrongFileNameMessage;
					continue;
				}

				if (!empty($ext)) {
					$sCharset = detectCharset($ext);
					if (function_exists('iconv') && $sCharset !== 'UTF-8') {
						$warning = $wrongFileNameMessage;
						continue;
					}
				}

				$countFiles++;

				$maxFilesCount = (int) mainConfiguration::getInstance()->get('kernel', 'max-guided-items');

				if ($maxFilesCount <= 0) {
					$maxFilesCount = 50;
				}

				if (getRequest('rrr') === null && $maxFilesCount < $countFiles) {
					$data = [
						'empty' => [
							'attribute:result' => 'Too much items'
						]
					];

					return $data;
				}

				$file = [
					'attribute:name' => $name,
					'attribute:type' => $ext,
					'attribute:size' => $size,
					'attribute:ctime' => $time,
					'attribute:timestamp' => $ts
				];

				$i = 0;
				while ($size > 1024.0) {
					$size /= 1024;
					$i++;
				}
				$convertedSize = (int) round($size);

				if ($convertedSize == 1 && (int) floor($size) != $convertedSize) {
					$i++;
				}
				$file['attribute:converted-size'] = $convertedSize . $sizeMeasure[$i];

				if (in_array($ext, $imageExt) && $info = @getimagesize($img->getPath() . '/' . $img->getFilename())) {
					$file['attribute:mime'] = $info['mime'];
					$file['attribute:width'] = $info[0];
					$file['attribute:height'] = $info[1];
				}

				$filesData[] = $file;
			}

			$data = [
				'attribute:folder' => $cwd,
				'data' => [
					'list' => [
						'files' => [
							'nodes:file' => $filesData
						]
					]
				]
			];

			if ($warning != '') {
				$data['data']['warning'] = $warning;
			}

			return $data;
		}

		/**
		 * Загружает файл на сервер
		 * @return array
		 * @throws coreException
		 */
		public function uploadfile() {
			$this->module->flushAsXML('uploadfile');
			$this->setupCwd();

			$quota_byte = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-files-and-images'));
			if ($quota_byte != 0) {
				$all_size = getBusyDiskSize();
				if ($all_size >= $quota_byte) {
					return [
						'attribute:folder' => mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR)),
						'attribute:upload' => 'error',
						'nodes:error' => [getLabel('error-files_quota_exceeded')]
					];
				}
			}

			if (isDemoMode()) {
				return [
					'attribute:folder' => mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR)),
					'attribute:upload' => 'done',
				];
			}

			$file = null;

			if (isset($_FILES['Filedata']['name'])) {

				foreach ($_FILES['Filedata'] as $k => $v) {
					$_FILES['Filedata'][$k] = [
						'upload' => $v
					];
				}

				$file = umiFile::upload('Filedata', 'upload', $this->cwd);
			} elseif (isset($_REQUEST['filename'])) {
				$file = umiFile::upload(false, false, $this->cwd);
			}

			$cwd = mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR));
			$result = [
				'attribute:folder' => $cwd,
				'attribute:upload' => 'done',
			];

			if ($file instanceof iUmiFile) {
				$item = $this->cwd . '/' . $file->getFileName();

				$imageExt = [
					'jpg',
					'jpeg',
					'gif',
					'png'
				];

				$sizeMeasure = [
					'b',
					'Kb',
					'Mb',
					'Gb',
					'Tb'
				];

				$name = $file->getFileName();
				$type = mb_strtolower($file->getExt());
				$ts = $file->getModifyTime();
				$time = date('g:i, d.m.Y', $ts);
				$size = $file->getSize();
				$path = $file->getFilePath(true);

				if (isset($_REQUEST['imagesOnly']) && !in_array($type, $imageExt)) {
					unlink($item);
					return $result;
				}

				$file = [
					'attribute:name' => $name,
					'attribute:type' => $type,
					'attribute:size' => $size,
					'attribute:ctime' => $time,
					'attribute:timestamp' => $ts,
					'attribute:path' => $path
				];

				$i = 0;

				while ($size > 1024.0) {
					$size /= 1024;
					$i++;
				}

				$convertedSize = (int) round($size);

				if ($convertedSize == 1 && (int) floor($size) != $convertedSize) {
					$i++;
				}

				$file['attribute:converted-size'] = $convertedSize . $sizeMeasure[$i];

				if (in_array($type, $imageExt)) {
					$info = @getimagesize($item);

					if ($info) {
						umiImageFile::addWatermark('.' . $cwd . '/' . $name);
						$file['attribute:mime'] = $info['mime'];
						$file['attribute:width'] = $info[0];
						$file['attribute:height'] = $info[1];
					} else {
						unlink($item);
						return $result;
					}
				}

				$result['file'] = $file;
			}

			return $result;
		}

		/**
		 * Устанавливает директорию, в рамках которой производятся работы с файлами
		 * @return mixed|string
		 */
		public function setupCwd() {
			$this->cwd = str_replace("\\", '/', realpath(USER_FILES_PATH));
			$newCwd = getRequest('folder');

			if ($newCwd) {
				$newCwd = rtrim(base64_decode($newCwd), "/\\");
				$newCwd = str_replace("\\", '/', $newCwd);
				if ($this->checkPath($newCwd)) {
					$this->cwd = str_replace("\\", '/', realpath(CURRENT_WORKING_DIR . $newCwd));
				}
			}

			return $this->cwd;
		}

		/**
		 * Проверяет имеет ли право класс работать с данным файлом или директорий
		 * @param string $path путь проверяемого файла или директории
		 * @return bool
		 */
		private function checkPath($path) {
			$allowedRoots = [
				USER_FILES_PATH,
				USER_IMAGES_PATH
			];

			$path = rtrim($path, '/');
			$path = str_replace("\\", '/', realpath(CURRENT_WORKING_DIR . $path));
			if ($path !== '') {
				foreach ($allowedRoots as $test) {
					$test = str_replace("\\", '/', realpath($test));
					if (mb_substr($path, 0, mb_strlen($test)) == $test) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Возвращает рабочую директорию файлового менеджера
		 * @return mixed|string
		 */
		public function getCwd() {
			return $this->cwd;
		}

		/** @deprecated  */
		public function getfolderlist() {}

		/** @deprecated  */
		public function createfolder() {}

		/** @deprecated  */
		public function deletefolder() {}

		/** @deprecated  */
		public function deletefiles() {}

		/** @deprecated  */
		public function rename() {}

		/** @deprecated */
		public function getimagepreview() {}
	}
