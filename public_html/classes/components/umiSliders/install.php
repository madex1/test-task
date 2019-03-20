<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'umiSliders',
		'config' => '1',
		'default_method' => 'empty',
		'default_method_admin' => 'getSliders',
		'default_sliding_speed' => '1',
		'default_sliding_delay' => '1',
		'default_sliding_slides_count' => '10'
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/umiSliders/classes/collections/SlidersCollection.php',
		'./classes/components/umiSliders/classes/collections/SlidesCollection.php',
		'./classes/components/umiSliders/classes/constants/SlidersConstantMap.php',
		'./classes/components/umiSliders/classes/constants/SlidesConstantMap.php',
		'./classes/components/umiSliders/classes/entities/Slider.php',
		'./classes/components/umiSliders/classes/entities/Slide.php',
		'./classes/components/umiSliders/classes/exceptions/ExpectSliderException.php',
		'./classes/components/umiSliders/classes/exceptions/ExpectSlideException.php',
		'./classes/components/umiSliders/classes/interfaces/iSlide.php',
		'./classes/components/umiSliders/classes/interfaces/iSlidesCollection.php',
		'./classes/components/umiSliders/classes/interfaces/iSlider.php',
		'./classes/components/umiSliders/classes/interfaces/iSlidersCollection.php',
		'./classes/components/umiSliders/admin.php',
		'./classes/components/umiSliders/autoload.php',
		'./classes/components/umiSliders/class.php',
		'./classes/components/umiSliders/customAdmin.php',
		'./classes/components/umiSliders/customMacros.php',
		'./classes/components/umiSliders/handlers.php',
		'./classes/components/umiSliders/i18n.en.php',
		'./classes/components/umiSliders/i18n.php',
		'./classes/components/umiSliders/includes.php',
		'./classes/components/umiSliders/install.php',
		'./classes/components/umiSliders/lang.en.php',
		'./classes/components/umiSliders/lang.php',
		'./classes/components/umiSliders/macros.php',
		'./classes/components/umiSliders/permissions.php',
		'./classes/components/umiSliders/services.php'
	];
