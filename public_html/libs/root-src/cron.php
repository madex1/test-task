<?php

	use UmiCms\Service;

	define('CRON', (isset($_SERVER['HTTP_HOST']) ? 'HTTP' : 'CLI'));
	require_once CURRENT_WORKING_DIR . '/libs/root-src/standalone.php';

	@ob_clean();
	if (CRON == 'HTTP') {
		$buffer = Service::Response()
			->getHttpBuffer();
		$umiPermissions = permissionsCollection::getInstance();
		$auth = Service::Auth();

		try {
			$auth->loginByEnvironment();
		} catch (UmiCms\System\Auth\AuthenticationException $e) {
			$buffer->clear();
			$buffer->status('401 Unauthorized');
			$buffer->setHeader('WWW-Authenticate', 'Basic realm="UMI.CMS"');
			$buffer->push('HTTP Authenticate failed');
			$buffer->end();
		}

		$currentUserId = $auth->getUserId();

		if (!$umiPermissions->isAllowedMethod($currentUserId, 'config', 'cron_http_execute')) {
			$status = '403 Forbidden';
			$message = <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<title>$status</title>
	</head>
	<body>
		<h1>$status</h1>
	</body>
</html>
HTML;
			$buffer->status($status);
			$buffer->push($message);
			$buffer->end();
		}

		$buffer->contentType('text/plain');

		$comment = <<<END
This file should be executed by cron only. Please, run it via HTTP for test only.
Notice: maximum priority level can accept values between "1" and "10", where "1" is maximum priority.


END;
		$buffer->push($comment);
	} else {
		$buffer = Service::Response()
			->getCliBuffer();
	}

	$modules = [];

	if (!empty($argv[1])) {
		$modules = explode(',', $argv[1]);
	}

	if (!empty($_GET['module'])) {
		$modules = (array) $_GET['module'];
	}

	$cron = new umiCron;
	$cron->setModules($modules);
	$cron->run();

	$buffer->push($cron->getParsedLogs());
	$buffer->end();
