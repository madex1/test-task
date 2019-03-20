<?php

	/** @var array $classes конфигурация для автозагрузки классов модуля */
	$classes = [
		'UmiCms\Classes\Components\AutoUpdate\UpdateServer\iClient' => [
			dirname(__FILE__) . '/Classes/UpdateServer/iClient.php'
		],

		'UmiCms\Classes\Components\AutoUpdate\UpdateServer\Client' => [
			dirname(__FILE__) . '/Classes/UpdateServer/Client.php'
		],

		'UmiCms\Classes\Components\AutoUpdate\iRegistry' => [
			dirname(__FILE__) . '/Classes/iRegistry.php'
		],

		'UmiCms\Classes\Components\AutoUpdate\Registry' => [
			dirname(__FILE__) . '/Classes/Registry.php'
		],
	];
