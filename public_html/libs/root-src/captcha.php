<?php

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/root-src/standalone.php';

	$session = Service::Session();
	$umiCaptcha = $session->get('umi_captcha');
	$drawer = umiCaptcha::getDrawer();

	$code = $drawer->getRandomCode();
	$id = getRequest('id');

	if ($id !== null) {
		$captchaId = (string) $id;

		if (is_string($umiCaptcha)) {
			$umiCaptcha = [];
		}

		$umiCaptcha[$captchaId] = md5($code);
	} else {
		$umiCaptcha = md5($code);
	}

	$session->set('umi_captcha', $umiCaptcha);

	$drawer->draw($code);
