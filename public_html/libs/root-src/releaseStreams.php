<?php

	use UmiCms\Service;

	define('STAT_DISABLE', true);
	define('VIA_HTTP_SCHEME', true);

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	$buffer = Service::Response()
		->getCurrentBuffer();
	$buffer->charset('utf-8');

	$auth = Service::Auth();

	try {
		$auth->loginByEnvironment();
	} catch (\UmiCms\System\Auth\AuthenticationException $e) {
		$buffer->clear();
		$buffer->status('401 Unauthorized');
		$buffer->setHeader('WWW-Authenticate', 'Basic realm="UMI.CMS"');
		$buffer->push('HTTP Authenticate failed');
		$buffer->end();
	}

	checkMobileApplication();

	$safeSchemes = ['ulang', 'utype'];
	$request = Service::Request();
	$scheme = (string) $request->getStreamScheme();
	$path = (string) $request->getPath();

	$scheme = preg_replace("/[^\w]/im", '', $scheme);

	$config = mainConfiguration::getInstance();
	$permissions = permissionsCollection::getInstance();
	$objects = umiObjectsCollection::getInstance();

	$cmsController = cmsController::getInstance();
	$cmsController->analyzePath();
	$cmsController->getModule($cmsController->getCurrentModule());
	$currentTemplater = $cmsController->getCurrentTemplater();

	if (!isAllowedScheme($scheme)) {
		streamHTTPError('unknown-scheme', $scheme);
	}

	$path_srv = $request->uri();

	if ($path_srv) {
		preg_match("/\/(" . implode('|', $config->get('streams', 'enable')) . "):?\/{0,2}(.*)?/i", $path_srv, $out);
		$path = $out[2];
		$_SERVER['REQUEST_URI'] = '/' . $scheme . '/' . $path;
	}

	$buffer->contentType($request->isJson() ? 'text/javascript' : 'text/xml');
	$buffer->option('generation-time', !$request->isJson());

	if (!$config->get('streams', $scheme . '.http.allow') && !in_array($scheme, $safeSchemes)) {
		$securityLevel = $config->get('streams', $scheme . '.http.permissions');

		$isAllowedPermission = false;
		if ($securityLevel && $securityLevel != 'all') {
			$userId = $auth->getUserId();
			$user = $objects->getObject($userId);
			$groups = $user->getValue('groups');

			$isAllowedPermission = $permissions->isSv($userId);
			switch ($securityLevel) {
				case 'sv': {
					break;
				}
				case 'admin': {
					$isAllowedPermission = $permissions->isAdmin() || $isAllowedPermission;
					break;
				}
				case 'auth': {
					$isAllowedPermission = $auth->isAuthorized() || $isAllowedPermission;
					break;
				}
				default: {
					$idList = explode(',', $securityLevel);
					foreach ($idList as $id) {
						$id = trim($id);

						if (is_numeric($id) && ($id == $userId) || (is_array($groups) && in_array($id, $groups))) {
							$isAllowedPermission = true;
							break;
						}
					}
				}
			}
		}

		$data = explode('/', $path);
		$module = isset($data[0]) ? $data[0] : '';
		$method = isset($data[1]) ? $data[1] : '';

		$isAllowedIp = false;
		$isAllowedMethod = $config->get('streams', $scheme . '.http.allow.' . $module . '.' . $method) == '1';

		$remoteIP = $request->remoteAddress();

		if (!$isAllowedMethod && $remoteIP !== null) {
			$ipList = $config->get('streams', $scheme . '.http.ip-allow.' . $module . '.' . $method);
			$ipListWholeScheme = $config->get('streams', $scheme . '.http.ip-allow');

			if (!empty($ipList)) {
				$isAllowedIp = contains($ipList, $remoteIP);
			} elseif (!empty ($ipListWholeScheme)) {
				$isAllowedIp = contains($ipListWholeScheme, $remoteIP);
			}
		}

		if (!$isAllowedPermission && !$isAllowedIp && !$isAllowedMethod) {
			streamHTTPError('http-not-allowed', $scheme);
		}
	}

	if ($scheme == 'ulang') {
		$buffer->contentType('text/plain');
	}

	try {
		if (!$config->get('streams', 'udata.http.extended.allow')) {
			$oldValue = umiBaseStream::$allowExtendedOptions;
			umiBaseStream::$allowExtendedOptions = false;
		}

		$result = $cmsController->executeStream($scheme . '://' . $path);

		if (!$config->get('streams', 'udata.http.extended.allow')) {
			umiBaseStream::$allowExtendedOptions = $oldValue;
		}

		$buffer->push($result);
		$buffer->end();
	} catch (Exception $e) {
		streamHTTPError(false, $scheme, $e);
	}

	function isAllowedScheme($scheme) {
		static $allowedSchemes = null;

		if ($allowedSchemes === null) {
			$allowedSchemes = mainConfiguration::getInstance()->get('streams', 'enable');
		}

		return in_array($scheme, $allowedSchemes);
	}

	function streamHTTPError($errorCode = false, $scheme = false, Exception $exception = null) {
		$buffer = Service::Response()
			->getCurrentBuffer();

		$contentType = Service::Request()->isXml() ? 'text/xml' : 'text/javascript';
		$buffer->contentType($contentType);

		$message = getErrorMessage($errorCode, $scheme, $exception);
		$response = getErrorResponse($message, $scheme);

		$buffer->push($response);
		$buffer->end();
	}

	/**
	 * Возвращает сообщение об ошибке
	 * @param string $errorCode код ошибки
	 * @param string|bool $scheme название протокола потока
	 * @param Exception|null $exception исключение
	 * @return string
	 */
	function getErrorMessage($errorCode, $scheme = false, Exception $exception = null) {
		switch ($errorCode) {
			case 'unknown-scheme': {
				return "Unknown scheme \"{$scheme}\"";
			}

			case 'http-disabled': {
				return "Protocol \"{$scheme}://\" is not allowed on this site";
			}

			case 'http-not-allowed': {
				return "You don't have permissions to call protocol \"{$scheme}://\" via HTTP";
			}

			default: {
				return $exception ? $exception->getMessage() : 'Requested resource not found';
			}
		}
	}

	/**
	 * Возвращает ответ с сообщением об ошибке
	 * @param string $message сообщение
	 * @param string $scheme используемый протокол
	 * @return string
	 */
	function getErrorResponse($message, $scheme) {
		$scheme = is_string($scheme) ? $scheme : 'unknown';

		if (Service::Request()->isXml()) {
			return <<<XML
<?xml version="1.0" encoding="utf8" ?>			
<{$scheme} generation-time="0.0"><error><![CDATA[{$message}]]></error></{$scheme}>
XML;
		}

		$rawResponse = [
			$scheme => [
				'error' => $message
			]
		];

		return json_encode($rawResponse);
	}
