<?php

	/** Системные события */
	$eipModifyEventListener = new umiEventListener(
		'systemModifyPropertyValue',
		'umiStub',
		'onModifyIpAddress'
	);
	$eipModifyEventListener->setIsCritical(true);
