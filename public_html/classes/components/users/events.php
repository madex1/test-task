<?php

	/** События модуля "Пользователи" */
	new umiEventListener('users_settings_do', 'users', 'onAutoCreateAvatar');
	new umiEventListener('users_registrate', 'users', 'onAutoCreateAvatar');
	new umiEventListener('users_registrate', 'users', 'onRegisterAdminMail');

	/** События модуля "Форум" */
	new umiEventListener('forum_message_post_do', 'users', 'onSubscribeChanges');
	new umiEventListener('forum_topic_post_do', 'users', 'onSubscribeChanges');

	/** Системные события */
	new umiEventListener('systemCreateObject', 'users', 'onCreateObject');
	new umiEventListener('systemModifyObject', 'users', 'onModifyObject');
	new umiEventListener('dummy_message_init', 'users', 'checkMessage');
	$eipModifyEventListener = new umiEventListener('systemModifyPropertyValue', 'users', 'onModifyPropertyValue');
	$eipModifyEventListener->setIsCritical(true);
