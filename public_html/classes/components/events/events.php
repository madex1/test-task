<?php

	use UmiCms\Service;

	if (!Service::Registry()->get('//modules/events/collect-events')) {
		new umiEventListener('blogs20PostAdded', 'events', 'onBlogsPostAdded');
		new umiEventListener('blogs20CommentAdded', 'events', 'onBlogsCommentAdded');
		new umiEventListener('comments_message_post_do', 'events', 'onCommentsCommentPost');
		new umiEventListener('order-status-changed', 'events', 'onEmarketOrderAdded');
		new umiEventListener('faq_post_question', 'events', 'onFaqQuestionPost');
		new umiEventListener('forum_message_post_do', 'events', 'onForumMessagePost');
		new umiEventListener('users_registrate', 'events', 'onUsersRegistered');
		new umiEventListener('users_login_successfull', 'events', 'onUsersLoginSuccessfull');
		new umiEventListener('users_prelogin_successfull', 'events', 'onUsersLoginSuccessfull');
		new umiEventListener('pollPost', 'events', 'onVotePollPost');
		new umiEventListener('webforms_post', 'events', 'onWebformsPost');
		new umiEventListener('sysytemBeginPageEdit', 'events', 'onPageView');
		new umiEventListener('sysytemBeginObjectEdit', 'events', 'onObjectView');
		new umiEventListener('hierarchyDeleteElement', 'events', 'onPageHierarchyDelete');
		new umiEventListener('systemDeleteElement', 'events', 'onPageSystemDelete');
		new umiEventListener('collectionDeleteObject', 'events', 'onObjectDelete');
		new umiEventListener('createTicket', 'events', 'onCreateTicket');
		new umiEventListener('deleteTicket', 'events', 'onDeleteTicket');
	}
