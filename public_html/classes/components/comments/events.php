<?php

	/** Проверка комментария на антиспам */
	new umiEventListener('comments_message_post_do', 'comments', 'onCommentPost');

