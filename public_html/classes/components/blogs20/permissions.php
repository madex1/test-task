<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр блога */
		'common' => [
			'blog',
			'blogslist',
			'commentadd',
			'commentslist',
			'comment',
			'post',
			'postslist',
			'getpostslist',
			'postview',
			'viewblogauthors',
			'viewblogfriends',
			'postsbytag',
			'checkallowcomments',
			'prepareCut',
			'prepareTags',
			'prepareContent'
		],
		/** Права на добавление постов с клиентской части */
		'add' => [
			'placecontrols',
			'itemdelete',
			'postadd',
			'postedit',
			'edituserblogs',
			'draughtslist'
		],
		/** Права на администрирование модулей */
		'admin' => [
			'blogs',
			'posts',
			'comments',
			'listitems',
			'getallblogs',
			'add',
			'edit',
			'activity',
			'blog.edit',
			'post.edit',
			'comment.edit',
			'publish'
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление блогов, постов и комментариев */
		'delete' => [
			'del'
		]
	];

