<?php

	new umiEventListener('blogs20CommentAdded', 'blogs20', 'onCommentAdd');
	/** Обработчики для проверки наличия спама */
	new umiEventListener('blogs20PostAdded', 'blogs20', 'onPostAdded');
	new umiEventListener('blogs20CommentAdded', 'blogs20', 'onCommentAdded');

