<?php

	/** Уведомление подписчиков на топик форума о новом сообщении */
	new umiEventListener('forum_message_post_do', 'forum', 'onDispatchChanges');
	/** Добавление топика модуля "Форум" в выпуск рассылки модуля "Рассылки" */
	new umiEventListener('forum_topic_post_do', 'forum', 'onAddTopicToDispatch');
	/** Проверка сообщения форума на антиспам */
	new umiEventListener('forum_message_post_do', 'forum', 'onMessagePost');
	/** Актуализаторы счетчиков в конференция форума */
	new umiEventListener('systemCreateElementAfter', 'forum', 'onElementAppend');
	new umiEventListener('systemDeleteElement', 'forum', 'onElementRemove');
	new umiEventListener('systemSwitchElementActivity', 'forum', 'onElementActivity');
