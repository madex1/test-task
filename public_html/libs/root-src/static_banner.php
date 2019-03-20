<?php

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	$buffer = Service::Response()
		->getCurrentBuffer();

	/** @var banners|BannersMacros $banners */
	$banners = cmsController::getInstance()->getModule('banners');

	if (!($banners instanceof def_module)) {
		$buffer->end();
	}

	$buffer->contentType('text/javascript');
	$buffer->charset('utf-8');

	$place = addslashes(getRequest('place'));
	$currentElementId = (int) getRequest('current_element_id');

	$result = $banners->insert($place, 0, false, $currentElementId);
	$result = trim($result);
	$connection = ConnectionPool::getInstance()->getConnection();
	$result = $connection->escape($result);
	$result = str_replace('\"', '"', $result);

	$response = <<<JS
var response = {
	'place':	'{$place}',
	'data':		'{$result}'
};

if(typeof window.onBannerLoad == "function") {
	window.onBannerLoad(response);
} else {
	var placer = document.getElementById('banner_place_{$place}');
	if(placer) {
		placer.innerHTML = response['data'];
	}
}
JS;

	$buffer->push($response);
	$buffer->end();
