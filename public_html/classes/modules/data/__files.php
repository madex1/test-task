<?php
abstract class __files_data {
	private $cwd = USER_FILES_PATH;

	public function elfinder_connector($needInfo = false) {
		$needInfo = (!$needInfo) ? getRequest('param0') : $needInfo;
		if ($needInfo == "getSystemInfo") {
			$arData = array(
				'maxFilesCount' => ini_get("max_file_uploads") ? ini_get("max_file_uploads") : 20
			);
			return def_module::flush(json_encode($arData), "text/javascript");
		}

		// full access mode for filemanager module (?full-access=1)
		$isFullAccess = (bool) getRequest('full-access');
		function elfinder_full_access($attr, $path, $data, $volume) {
			return strpos(basename($path), '.') === 0  ? !($attr == 'read' || $attr == 'write') : ($attr == 'read' || $attr == 'write');
		}

		function elfinder_access($attr, $path, $data, $volume) {

			if (strpos(basename($path), '.') === 0) {
				return !($attr == 'read' || $attr == 'write');
			} else {
				if (isDemoMode()) {
					return !($attr == 'write' || $attr == 'hidden');
				}
				return ($attr == 'read' || $attr == 'write');
			}
		}

		$opts = array(
			'debug' => true,
			'roots' => array()
		);

		$permissions = permissionsCollection::getInstance();
		$userId = $permissions->getUserId();
		$user = umiObjectsCollection::getInstance()->getObject($userId);

		$allowedDirectories = array();

		if (!isDemoMode() && $filemanagerDirectory = $user->getValue('filemanager_directory')) {

			$directories = explode(",", $filemanagerDirectory);

			foreach ($directories as $directory) {
				$directory = trim($directory);
				if (!strlen($directory)) continue;
				$directory = trim($directory, "/");
				$directoryPath = realpath(CURRENT_WORKING_DIR . "/" . $directory);
				if ((strpos($directoryPath, USER_FILES_PATH ) === false && strpos($directoryPath, USER_IMAGES_PATH) === false) || !is_dir($directoryPath)) continue;
				$allowedDirectories[] = $directory;
			}

		}

		if (count($allowedDirectories)) {
			$i = 1;
			foreach ($allowedDirectories as $directory) {
				$opts['roots'][] = array(
					'id'			=> 'files' . $i,
					'driver'		=> 'UmiLocalFileSystem',
					'path'			=> CURRENT_WORKING_DIR . "/" . $directory,
					'URL'			=> "/" . $directory,
					'accessControl'	=> 'elfinder_access'
				);
				$i++;
			}
		} else {
			$opts['roots'][] = array(
				'id'			=> 'images',
				'driver'		=> 'UmiLocalFileSystem',
				'path'			=> USER_IMAGES_PATH . '/',
				'URL'			=> '/images/',
				'accessControl'	=> $isFullAccess ? 'elfinder_full_access' : 'elfinder_access'
			);
			$opts['roots'][] = array(
				'id'			=> 'files',
				'driver'		=> 'UmiLocalFileSystem',   // driver for accessing file system (REQUIRED)
				'path'			=> USER_FILES_PATH . '/',         // path to files (REQUIRED)
				'URL'			=> '/files/' , // URL to files (REQUIRED)
				'accessControl'	=> $isFullAccess ? 'elfinder_full_access' : 'elfinder_access'
			);

		}

		// run elFinder
		$connector = new elFinderConnector(new elFinder($opts));
		$connector->run();
	}

	public function get_filemanager_info() {

		$buffer = \UmiCms\Service::Response()
			->getCurrentBuffer();
		$buffer->contentType('text/javascript');
		$buffer->clear();

		$json = new jsonTranslator;

		$folder = (string) getRequest('folder');
		$file = (string) getRequest('file');

		$folderHash = ($folder) ? elfinder_get_hash($folder) : '';
		$fileHash = ($file) ? elfinder_get_hash($file) : '';

		$objects = umiObjectsCollection::getInstance();
		$userId = permissionsCollection::getInstance()->getUserId();
		$user = $objects->getObject($userId);
		$fmId = $user->getValue('filemanager');

		if ($fmId) {
			$fm = $objects->getObject($fmId);
			$fmPrefix = $fm->getValue('fm_prefix') ? $fm->getValue('fm_prefix') : 'elfinder';
		} else {
			$fmPrefix = 'elfinder';
		}

		$lang = cmsController::getInstance()->getCurrentLang()->getPrefix();

		$data = array(
			'folder_hash' 	=> $folderHash,
			'file_hash' 	=> $fileHash,
			'filemanager'	=> $fmPrefix,
			'lang'			=> $lang
		);

		$result = $json->translateToJson($data);
		$buffer->push($result);
		$buffer->end();
	}

	public function getfilelist() {
		$this->flushAsXml('getfilelist');
		$this->setupCwd();

		$param = array(
			array('delete', 'unlink', 1),
			array('copy', 'copy', 2),
			array('move', 'rename', 2)
		);

		for ($i=0; $i<count($param); $i++) {
			if ($param!= 'copy' && isDemoMode()) {
				continue; // disable in demo
			}

			if (isset($_REQUEST[$param[$i][0]]) && !empty($_REQUEST[$param[$i][0]])) {
				foreach ($_REQUEST[$param[$i][0]] as $item) {
					$item = CURRENT_WORKING_DIR . base64_decode($item);
					$arguments = array($item);
					if ($param[$i][2] > 1) {
						$arguments[] = $this->cwd . '/' . basename($item);
					}
					@call_user_func_array($param[$i][1], $arguments);
				}
			}
		}

		$imageExt = array("jpg", "jpeg", "gif", "png");
		$sizeMeasure = array("b", "Kb", "Mb", "Gb", "Tb");
		$allowedExt = true;
		if (isset($_REQUEST['showOnlyImages'])) {
			$allowedExt = $imageExt;
		} elseif (isset($_REQUEST['showOnlyVideos'])) {
			$allowedExt = array("flv", "mp4");
		} elseif (isset($_REQUEST['showOnlyMedia'])) {
			$allowedExt = array("swf","flv","dcr","mov","qt","mpg","mp3","mp4","mpeg","avi","wmv","wm","asf","asx","wmx","wvx","rm","ra","ram");
		}

		$directory = new DirectoryIterator($this->cwd);

		$cwd = substr($this->cwd, strlen(CURRENT_WORKING_DIR));

		$warning = false;
		$filesData = array();
		$countFiles = 0;
		foreach ($directory as $file) {
			if ($file->isDir()) continue;
			if ($file->isDot()) continue;
			$name = $file->getFilename();
			$ext = substr($name, strrpos($name, ".")+1);
			if ($allowedExt !== true && !in_array(strtolower($ext), $allowedExt)) continue;

			$ts = $file->getCTime();
			$time = date('G:i, d.m.Y' , $ts );
			$size = $file->getSize();

			$img = $file;

			$sCharset = detectCharset($name);
			if (function_exists('iconv') && $sCharset !== 'UTF-8') {
				$warning = 'Error: Присутствуют файлы с недопустимыми названиями! Ошибка: http://errors.umi-cms.ru/13050/';
				continue;
			}

			if (!empty($ext)) {
				$sCharset = detectCharset($ext);
				if (function_exists('iconv') && $sCharset !== 'UTF-8') {
					continue;
					$textConverted = @iconv('windows-1251', 'UTF-8', $ext);
					if ($textConverted) $ext = $textConverted;
				}
			}

			$countFiles++;

			$maxFilesCount = (int) mainConfiguration::getInstance()->get("kernel", "max-guided-items");

			if ($maxFilesCount <= 0) {
				$maxFilesCount = 50;
			}

			if (is_null(getRequest('rrr')) && $maxFilesCount < $countFiles) {
				$data = Array(
					'empty' => Array(
						'attribute:result' => 'Too much items'
					)
				);
				return $data;
			}

			$file = array(
				'attribute:name' => $name,
				'attribute:type' => $ext,
				'attribute:size' => $size,
				'attribute:ctime' => $time,
				'attribute:timestamp' => $ts
			);

			$i = 0;
			while ($size > 1024.0) {
				$size /= 1024;
				$i++;
			}
			$convertedSize = (int)round($size);
			if ($convertedSize == 1 && (int)floor($size) != $convertedSize) {
				$i++;
			}
			$file['attribute:converted-size'] = $convertedSize.$sizeMeasure[$i];
			if (in_array($ext, $imageExt) && $info = @getimagesize($img->getPath() . "/" . $img->getFilename())) {
				$file['attribute:mime']   = $info['mime'];
				$file['attribute:width']  = $info[0];
				$file['attribute:height'] = $info[1];
			}
			$filesData[] = $file;

		}

		$data = array(
			'attribute:folder' => $cwd,
			'data' => array(
			'list' => array(
				'files' => array('nodes:file' => $filesData)
			))
		);

		if($warning!='') {
			$data['data']['warning'] = $warning;
		}

		return $data;
	}

	public function getfolderlist() {
		$this->flushAsXml('getfolderlist');
		$this->setupCwd();

		$folders = glob($this->cwd . '/*', GLOB_ONLYDIR);
		$cwd = substr($this->cwd, strlen(CURRENT_WORKING_DIR));
		$foldersData = array();
		if (is_array($folders)) {
			foreach ($folders as $item) {
				$name = basename($item);
				$foldersData[] = array('attribute:name' => $name);
			}
 		}

		$data = array(
			'attribute:folder' => $cwd,
			'data' => array(
			'list' => array(
				'folders' => array(
					'nodes:folder' => $foldersData
				)
			))
		);

		return $data;
	}

	public function createfolder() {
		$this->flushAsXml('createfolder');

		if (isDemoMode()) {
			return $this->getfilelist();
		}

		$folder = rtrim(base64_decode(getRequest('folder')), "/");
		$_REQUEST['folder'] = base64_encode(dirname($folder));
		$folder = basename($folder);
		$this->setupCwd();
		if (!is_dir($this->cwd . "/" . $folder)) {
			mkdir($this->cwd . "/" . $folder);
		}
		return array();
	}

	public function deletefolder() {
		$this->flushAsXml('deletefolder');

		if (isDemoMode()) {
			return array();
		}

		$this->setupCwd();
		if (is_dir($this->cwd)) {
			@rmdir($this->cwd);
		}
		return array();
	}

	public function uploadfile() {
		$this->flushAsXml('uploadfile');

		$this->setupCwd();

		$quota_byte = getBytesFromString( mainConfiguration::getInstance()->get('system', 'quota-files-and-images') );
		if ( $quota_byte != 0 ) {
			$all_size = getBusyDiskSize();
			if ( $all_size >= $quota_byte ) {
				return array(
					'attribute:folder'	=> substr($this->cwd, strlen(CURRENT_WORKING_DIR)),
					'attribute:upload'	=> 'error',
					'nodes:error'		=> array('Ошибка: превышено ограничение на размер дискового пространства')
				);
			}
		}

		if (isDemoMode()) {
			return array(
				'attribute:folder'	=> substr($this->cwd, strlen(CURRENT_WORKING_DIR)),
				'attribute:upload'	=> 'done',
			);
		}

		if (isset($_FILES['Filedata']['name'])) {
			foreach($_FILES['Filedata'] as $k => $v) {
				$_FILES['Filedata'][$k] = array('upload' => $v);
			}
			$file = umiFile::upload('Filedata', 'upload', $this->cwd);
		} elseif (isset($_REQUEST['filename'])) {
			$file = umiFile::upload(false, false, $this->cwd);
		}

		$cwd = substr($this->cwd, strlen(CURRENT_WORKING_DIR));
		$result = array(
			'attribute:folder'	=> $cwd,
			'attribute:upload'	=> 'done',
		);

		if ($file) {

			$item = $this->cwd . "/" . $file->getFileName();

			// Collect some file info
			$imageExt = array("jpg", "jpeg", "gif", "png");
			$sizeMeasure = array("b", "Kb", "Mb", "Gb", "Tb");

			$name = $file->getFileName();
			$type = strtolower($file->getExt());
			$ts   = $file->getModifyTime();
			$time = date('g:i, d.m.Y' , $ts );
			$size = $file->getSize();
			$path = $file->getFilePath(true);

			if (isset($_REQUEST['imagesOnly']) && !in_array($type, $imageExt)) {
				unlink($item);
				return $result;
			}

			$file = array(
				'attribute:name' => $name,
				'attribute:type' => $type,
				'attribute:size' => $size,
				'attribute:ctime'     => $time,
				'attribute:timestamp' => $ts,
				'attribute:path' => $path
			);

			$i = 0;
			while ($size > 1024.0) {
				$size /= 1024;
				$i++;
			}
			$convertedSize = (int)round($size);
			if ($convertedSize == 1 && (int)floor($size) != $convertedSize) {
				$i++;
			}
			$file['attribute:converted-size']   = $convertedSize.$sizeMeasure[$i];
			if (in_array($type, $imageExt)) {
				if ($info = @getimagesize($item)) {
					umiImageFile::addWatermark("." . $cwd . "/" . $name);

					$file['attribute:mime']   = $info['mime'];
					$file['attribute:width']  = $info[0];
					$file['attribute:height'] = $info[1];
				} else {
					unlink($item);
					return $result;
				}
			}

			$result["file"] = $file;
		}

		return $result;
	}

	public function deletefiles() {
		$this->flushAsXml('deletefiles');

		if(isDemoMode()) {
			return $this->getfilelist();
		}

		$this->setupCwd();

		if(isset($_REQUEST['delete']) && is_array($_REQUEST['delete'])) {
			foreach($_REQUEST['delete'] as $item) {
				$item = $this->cwd . '/' . base64_decode($item);
				if(is_dir($item)) @rmdir($item);
				else @unlink($item);
			}
		}
		if(!isset($_REQUEST['nolisting'])) {
			return $this->getfilelist();
		}
	}

	public function rename() {
		$this->flushAsXml('rename');

		$path = CURRENT_WORKING_DIR . base64_decode( getRequest("oldName") );
		$newName = dirname($path) . "/" . basename(base64_decode( getRequest("newName") ));

		$old = getPathInfo($path);
		$new = getPathInfo($newName);

		if(strtolower($old['extension']) != strtolower($new['extension'])) return array();

		$oldDir =  str_replace('\\', '/',  $old['dirname']);
		$newDir =  str_replace('\\', '/',  $new['dirname']);

		if( strpos($newDir, USER_IMAGES_PATH ) === false &&
			strpos($newDir, USER_FILES_PATH ) === false &&
			strpos($oldDir, USER_IMAGES_PATH ) === false &&
			strpos($oldDir, USER_FILES_PATH ) === false)
			return array();

		if(!isDemoMode()) {
			rename($path, $newName);
		}

		else {
			$newName = $path;
		}

		return array(
			'attribute:path' => substr($newName, strlen(CURRENT_WORKING_DIR))
		);
	}

	public function getimagepreview() {
		//$this->setupCwd();
		if($file = getRequest('file')){
			$file = base64_decode($file);
			if($this->checkPath($file)) {
				$file = CURRENT_WORKING_DIR . $file;
				if(@getimagesize($file) !== false) {
					readfile($file);
				}
			}
		}
		exit();
	}

	public function setupCwd() {
		$this->cwd = str_replace("\\", "/", realpath(USER_FILES_PATH));
		if ($newCwd = getRequest('folder')) {
			$newCwd = rtrim(base64_decode($newCwd), "/\\");
			$newCwd = str_replace("\\", "/", $newCwd);
			if ($this->checkPath($newCwd)) {
				$this->cwd = str_replace("\\", "/", realpath(CURRENT_WORKING_DIR . $newCwd));
			}
		}
		return $this->cwd;
	}

	public function checkPath($path) {

		$allowedRoots = array(USER_FILES_PATH , USER_IMAGES_PATH );
		$path = rtrim($path, "/");
		$path = str_replace("\\", "/", realpath(CURRENT_WORKING_DIR . $path));

		if (strlen($path)) {
			foreach ($allowedRoots as $test) {
				$test = str_replace("\\", "/", realpath($test));
				if (substr($path, 0, strlen($test)) == $test) return true;
			}
		}
		return false;
	}
};
?>
