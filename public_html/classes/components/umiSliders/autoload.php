<?php

	$moduleDirectory = dirname(__FILE__);
	/** @var array $classes конфигурация для автозагрузки классов модуля */
	$classes = [
		'UmiCms\Classes\Components\UmiSliders\SlidersConstantMap' => [
			$moduleDirectory . '/classes/constants/SlidersConstantMap.php'
		],

		'UmiCms\Classes\Components\UmiSliders\SlidesConstantMap' => [
			$moduleDirectory . '/classes/constants/SlidesConstantMap.php'
		],

		'UmiCms\Classes\Components\UmiSliders\SlidersCollection' => [
			$moduleDirectory . '/classes/collections/SlidersCollection.php'
		],

		'UmiCms\Classes\Components\UmiSliders\SlidesCollection' => [
			$moduleDirectory . '/classes/collections/SlidesCollection.php'
		],

		'UmiCms\Classes\Components\UmiSliders\iSlide' => [
			$moduleDirectory . '/classes/interfaces/iSlide.php'
		],

		'UmiCms\Classes\Components\UmiSliders\iSlider' => [
			$moduleDirectory . '/classes/interfaces/iSlider.php'
		],

		'UmiCms\Classes\Components\UmiSliders\Slide' => [
			$moduleDirectory . '/classes/entities/Slide.php'
		],

		'UmiCms\Classes\Components\UmiSliders\Slider' => [
			$moduleDirectory . '/classes/entities/Slider.php'
		],

		'UmiCms\Classes\Components\UmiSliders\iSlidersCollection' => [
			$moduleDirectory . '/classes/interfaces/iSlidersCollection.php'
		],

		'UmiCms\Classes\Components\UmiSliders\iSlidesCollection' => [
			$moduleDirectory . '/classes/interfaces/iSlidesCollection.php'
		],

		'UmiCms\Classes\Components\UmiSliders\ExpectSliderException' => [
			$moduleDirectory . '/classes/exceptions/ExpectSliderException.php'
		],

		'UmiCms\Classes\Components\UmiSliders\ExpectSlideException' => [
			$moduleDirectory . '/classes/exceptions/ExpectSlideException.php'
		]
	];
