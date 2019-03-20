<?php

	use UmiCms\Service;

	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	require_once CURRENT_WORKING_DIR . '/libs/root-src/standalone.php';
	require_once CURRENT_WORKING_DIR . '/libs/lib.php';

	$sImgPath = isset($_GET['img']) ? trim($_GET['img']) : '';
	$sImgPath = '/' . str_replace('./', '/', $sImgPath);

	$checkPath = realpath(dirname(CURRENT_WORKING_DIR . $sImgPath));
	$allowedPath = [USER_IMAGES_PATH, USER_FILES_PATH];

	$buffer = Service::Response()
		->getCurrentBuffer();

	if (strcmp(mb_substr($checkPath, 0, mb_strlen($allowedPath[0])), $allowedPath[0]) != 0 &&
		strcmp(mb_substr($checkPath, 0, mb_strlen($allowedPath[1])), $allowedPath[1]) != 0) {
		$buffer->status(404);
		$buffer->end();
	}

	define('UMI_AUTHOTHUMBS_PATH', USER_IMAGES_PATH . '/cms/autothumbs');

	if ($sImgPath !== '') {
		$sRealThumbFName = md5($sImgPath);
		$sRealThumbPath = UMI_AUTHOTHUMBS_PATH . '/' . $sRealThumbFName;

		$sImgPath = ltrim($sImgPath, "/\\");

		$arrPath = explode('/', $sImgPath);
		$sThumbFileName = array_pop($arrPath);

		$arrThumbFN = explode('.', $sThumbFileName);
		$sThumbExt = array_pop($arrThumbFN);
		$sThumbBaseName = implode('.', $arrThumbFN);

		$arrThumbFNParts = explode('_', $sThumbBaseName);
		$iTumbHeight = (int) array_pop($arrThumbFNParts);
		$iTumbWidth = (int) array_pop($arrThumbFNParts);

		$arrTmp = $arrThumbFNParts;
		$bSlide = array_pop($arrTmp) === 'sl';
		if ($bSlide) {
			array_pop($arrThumbFNParts);
		}
		unset($arrTmp);

		$sRealImagePath = './' . implode('/', $arrPath) . '/' . implode('_', $arrThumbFNParts) . '.' . $sThumbExt;

		if (!file_exists($sRealImagePath) || ($imageInfo = getimagesize($sRealImagePath)) === false) {
			$buffer->status(404);
			$buffer->end();
		}

		$imageType = $imageInfo[2];

		if ((!file_exists($sRealThumbPath)) || (filemtime($sRealImagePath) > filemtime($sRealThumbPath))) {
			ini_set('include_path', str_replace("\\", '/', dirname(__FILE__)) . '/');

			check_autothumbs_bytes($allowedPath);
			$sRealThumbPath = createThumbnail($sRealImagePath, $iTumbWidth, $iTumbHeight, $sRealThumbPath, 90, $bSlide);
		}

		if (file_exists($sRealThumbPath)) {
			$imageType = (int) $imageType;

			$aliases = [
				1 => 'gif',
				2 => 'jpg',
				3 => 'png',
				6 => 'bmp',
				15 => 'wbmp',
				16 => 'xbmp'
			];

			if (isset($aliases[$imageType])) {
				$buffer->contentType('image/' . $aliases[$imageType]);
				$buffer->setHeader('Content-Length', (string) filesize($sRealThumbPath));
				$buffer->push(file_get_contents($sRealThumbPath));
				$buffer->end();
			}
		}

		$buffer->status(404);
	}

	function check_autothumbs_bytes($dirs) {
		$max_size = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-files-and-images'));
		if ($max_size != 0) {
			$busy_size = 0;
			foreach ($dirs as $dir) {
				$busy_size += getDirSize($dir);
			}
			if ($busy_size >= $max_size) {
				/** @var HTTPOutputBuffer $buffer */
				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->status(404);
				$buffer->end();
			}
		}
	}

	function createThumbnail(
		$sImgPath,
		$iWidth = 0,
		$iHeight = 0,
		$sThumbFile = '',
		$iJpgQuality = 90,
		$bSlide = false,
		$bReplace = false
	) {
		if (!file_exists($sImgPath)) {
			return false;
		}
		$pr = imageUtils::getGDProcessor();
		$sFileName = getPathInfo($sImgPath, PATHINFO_BASENAME);
		$extension = getPathInfo($sImgPath, PATHINFO_EXTENSION);
		$sFileExt = mb_strtolower($extension);
		$sFileExtCaseSensitive = $extension;
		$arrInfo = $pr->info($sImgPath);
		$iImgWidth = (int) $arrInfo['width'];
		$iImgHeight = (int) $arrInfo['height'];
		$sThumbFile = (string) $sThumbFile;

		if ($sThumbFile === '') {
			$sThumbName = $sFileName . '_' . $iWidth . '_' . $iHeight . '_' . $sFileExtCaseSensitive . '.' . $sFileExt;
			$sThumbFile = UMI_AUTHOTHUMBS_PATH . '/' . $sThumbName;
		}

		if (!$bReplace && file_exists($sThumbFile)) {
			return $sThumbFile;
		}

		if (($iWidth > $iImgWidth && $iHeight > $iImgHeight) // Оба параметра больше размеров исходной картинки
			|| ($iWidth > $iImgWidth && $iHeight == 0) // Ширина больше исходной, высота автоматическая
			|| ($iWidth == 0 && $iHeight > $iImgHeight)) { // Высота больше исходной, ширина автоматическая
			$iWidth = 0;
			$iHeight = 0;
		}

		if ($iWidth > 0 || $iHeight > 0) {
			// resize
			if (!$iHeight) {
				$iHeight = (int) round($iImgHeight * ($iWidth / $iImgWidth));
			}

			if (!$iWidth) {
				$iWidth = (int) round($iImgWidth * ($iHeight / $iImgHeight));
			}

			imageUtils::getImageProcessor()->thumbnail($sImgPath, $sThumbFile, $iWidth, $iHeight);
		} else {
			copy($sImgPath, $sThumbFile);
		}
		return $sThumbFile;
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">

<html>
<head>
	<meta name="generator" content=
	"HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">

	<title></title>
</head>

<body>
</body>
</html>
