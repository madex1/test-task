<?php
	new umiEventListener("systemCreateObject", "dispatches", "onCreateObject");
	new umiEventListener("systemModifyObject", "dispatches", "onModifyObject");

	$forumModule = cmsController::getInstance()->getModule('forum');

	if ($forumModule instanceof def_module) {
		new umiEventListener("systemModifyObject", "dispatches", "changeLoadForumOptionModify");
		new umiEventListener("systemModifyPropertyValue", "dispatches", "changeLoadForumOptionQuickEdit");
	}

	new umiEventListener("cron", "dispatches", "onAutosendDispathes");
	$eipModifyEventListener = new umiEventListener("systemModifyPropertyValue", "dispatches", "onPropertyChanged");
	$eipModifyEventListener->setIsCritical(true);
?>