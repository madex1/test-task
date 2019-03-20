<?php

	/** Обработчики изменения вопроса, отправляют уведомления автору вопроса. */
	new umiEventListener('systemSwitchElementActivity', 'faq', 'onChangeActivity');
	new umiEventListener('systemModifyElement', 'faq', 'onChangeActivity');
	/** Обработчик создания вопроса с клиентской части, проверяет вопрос на антиспам */
	new umiEventListener('faq_post_question', 'faq', 'onQuestionPost');
