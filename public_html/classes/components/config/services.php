<?php

	/** @var array $parameters параметры инициализации сервисов */
	$parameters = [
	];

	/** @var array $rules правила инициализации сервисов */
	$rules = [
		'WatermarkAdminSettingsManager' => [
			'class' => 'UmiCms\Classes\Components\Config\Watermark\AdminSettingsManager',
		],
		'CaptchaAdminSettingsManager' => [
			'class' => 'UmiCms\Classes\Components\Config\Captcha\AdminSettingsManager',
		],
		'MailAdminSettingsManager' => [
			'class' => 'UmiCms\Classes\Components\Config\Mail\AdminSettingsManager',
		],
	];
