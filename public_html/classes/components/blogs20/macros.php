<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class BlogsMacros {

		/** @var blogs20 $module */
		public $module;

		/**
		 * Возвращает содержимое блога (список постов) по умолчанию
		 * @return string|array
		 */
		public function blog() {
			$cmsController = cmsController::getInstance();
			$blogId = $cmsController->getCurrentElementId();
			blogs20::pushEditable('blogs20', 'blog', $blogId);
			return $this->postsList($blogId);
		}

		/**
		 * Возвращает содержимое поста по умолчанию
		 * @return string|array
		 */
		public function post() {
			$cmsController = cmsController::getInstance();
			$postId = $cmsController->getCurrentElementId();
			blogs20::pushEditable('blogs20', 'post', $postId);
			return $this->postView($postId);
		}

		/**
		 * Производит редирект со страницы комментария на странице поста,
		 * к которому оставлен комментарий.
		 * @throws publicException
		 */
		public function comment() {
			$umiTypesHelper = umiTypesHelper::getInstance();
			$iCommentHTID = $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'comment');
			$cmsController = cmsController::getInstance();
			$commentId = $cmsController->getCurrentElementId();
			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($commentId);

			if ($element instanceof iUmiHierarchyElement) {
				while ($element->getTypeId() == $iCommentHTID) {
					$element = $hierarchy->getElement($element->getParentId());
				}
				blogs20::pushEditable('blogs20', 'comment', $commentId);
				blogs20::simpleRedirect($hierarchy->getPathById($element->getId()) . '#comment_' . $commentId);
			} else {
				throw new publicException(getLabel('error-page-does-not-exist'));
			}
		}

		/**
		 * Возвращает список блогов
		 * @param bool|int $blogsCount ограничение на количество выводимых блогов
		 * @param bool|int|string $sortType тип сортировки
		 * @param bool $domainId не используется
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws selectorException
		 */
		public function blogsList($blogsCount = false, $sortType = false, $domainId = false, $template = 'default') {
			list($sTemplateBlock, $sTemplateLine) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'blogs_list_block',
				'blogs_list_line'
			);

			$page = (int) getRequest('p');

			$blogs = new selector('pages');
			$blogs->types('object-type')->name('blogs20', 'blog');

			if ($blogsCount) {
				$blogs->limit(0, $blogsCount);
			} else {
				$blogs->limit($page * $this->module->blogs_per_page, $this->module->blogs_per_page);
			}

			switch ($sortType) {
				case 1:
				case 'name': {
					$blogs->order('name')->asc();
					break;
				}
				case 2:
				case 'ord': {
					$blogs->order('ord')->asc();
					break;
				}
				case 4:
				case 'rand': {
					$blogs->order('rand');
					break;
				}
			}

			$blogs->option('load-all-props')->value(true);
			$result = $blogs->result();
			$total = $blogs->length();

			$aLines = [];
			$umiLinksHelper = umiLinksHelper::getInstance();
			/** @var iUmiHierarchyElement $oBlog */
			foreach ($result as $oBlog) {
				$iBlogId = $oBlog->getId();
				$aLineParam = [];
				$aLineParam['attribute:bid'] = $iBlogId;
				$aLineParam['attribute:title'] = $oBlog->getValue('title');
				$aLineParam['attribute:link'] = $umiLinksHelper->getLinkByParts($oBlog);
				$aLineParam['node:name'] = $oBlog->getName();
				$aLines[] = blogs20::parseTemplate($sTemplateLine, $aLineParam, $iBlogId);
				blogs20::pushEditable('blogs20', 'blog', $iBlogId);
			}

			$aBlockParam = [];
			$aBlockParam['subnodes:items'] = $aBlockParam['void:lines'] = $aLines;
			$aBlockParam['per_page'] = $blogsCount ?: $this->module->blogs_per_page;
			$aBlockParam['total'] = $total;

			return blogs20::parseTemplate($sTemplateBlock, $aBlockParam);
		}

		/**
		 * Алиас для postsList()
		 * Возвращает посты из заданного блога
		 * @param bool|int $blogId идентификатор блога
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $limit ограничение на количество выводимых постов
		 * @return mixed
		 */
		public function getPostsList($blogId = false, $template = 'default', $limit = false) {
			return $this->postsList($blogId, $template, $limit);
		}

		/**
		 * Возвращает посты из заданного блога
		 * @param bool|int $blogId идентификатор блога
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $limit ограничение на количество выводимых постов
		 * @return mixed
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public function postsList($blogId = false, $template = 'default', $limit = false) {
			$blogId = $blogId ?: (int) getRequest('param0');
			$limit = $limit ?: $this->module->posts_per_page;

			$umiHierarchy = umiHierarchy::getInstance();
			$umiLinksHelper = umiLinksHelper::getInstance();

			list($postsListBlock, $postsListLine, $postsListBlockEmpty) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'posts_list_block',
				'posts_list_line',
				'posts_list_block_empty'
			);

			$blog = null;

			$sel = new selector('pages');
			$sel->types('object-type')->name('blogs20', 'post');
			$sel->where('is_spam')->notequals(1);

			if ($blogId) {
				$blog = $umiHierarchy->getElement($blogId);

				if (!$blog) {
					throw new publicException(getLabel('error-page-does-not-exist', null, $blogId));
				}

				$umiLinksHelper->loadLinkPartForPages([$blogId]);
			}

			if ($blogId) {
				$permissions = permissionsCollection::getInstance();
				$auth = Service::Auth();
				$userId = $auth->getUserId();
				$friendList = $blog->getValue('friendlist');
				$systemUsersPermissions = Service::SystemUsersPermissions();

				$friendList[] = $systemUsersPermissions->getSvUserId();

				if ($friendList === null) {
					$friendList = [];
				}

				$authorList = $permissions->getUsersByElementPermissions($blogId, 2);
				$authorList[] = $blog->getObject()->getOwnerId();
				$sel->where('hierarchy')->page($blogId);

				if (!in_array($userId, $friendList) && !in_array($userId, $authorList)) {
					$sel->where('only_for_friends')->notequals(1);
				}
			} else {
				$sel->where('only_for_friends')->notequals(1);
			}

			$this->applyTimeRange($sel);
			$sel->option('load-all-props')->value(true);
			$sel->order('publish_time')->desc();

			$page = (int) getRequest('p');
			$sel->limit($page * $limit, $limit);

			$postList = $sel->result();
			$total = $sel->length();

			if (!$postList) {
				return blogs20::parseTemplate($postsListBlockEmpty, ['bid' => $blogId]);
			}

			$lines = [];

			/** @var iUmiHierarchyElement $post */
			foreach ($postList as $post) {
				if (!$post instanceof iUmiHierarchyElement) {
					continue;
				}

				$postId = $post->getId();

				if (!$blogId) {
					$blog = $umiHierarchy->getElement($post->getParentId());
				}

				$postLink = $umiLinksHelper->getLinkByParts($post);
				$blogLink = $umiLinksHelper->getLinkByParts($blog);

				/** @var iUmiObject $postObject */
				$postObject = $post->getObject();

				$lineVariables = [];
				$lineVariables['attribute:id'] = $postId;
				$lineVariables['attribute:author_id'] = $postObject->getOwnerId();
				$lineVariables['name'] = $post->getName();
				$lineVariables['post_link'] = $postLink;
				$lineVariables['blog_link'] = $blogLink;
				$lineVariables['bid'] = $blog->getId();
				$lineVariables['blog_name'] = $blog->getName();
				$lineVariables['blog_title'] = $blog->getValue('title');
				$lineVariables['title'] = $post->getValue('title');
				$lineVariables['cut'] =
					system_parse_short_calls($this->prepareCut($post->getValue('content'), $postLink, $template), $postId);
				$lineVariables['subnodes:tags'] = $this->prepareTags($post->getValue('tags'), $template);
				$lineVariables['comments_count'] = $umiHierarchy->getChildrenCount($postId, false);

				/** @var umiDate $publishTime */
				$publishTime = $post->getValue('publish_time');
				$lineVariables['publish_time'] = ($publishTime instanceof umiDate)
					? $publishTime->getFormattedDate('U')
					: '';

				$lines[] = blogs20::parseTemplate($postsListLine, $lineVariables, $postId);
				blogs20::pushEditable('blogs20', 'post', $postId);
			}

			$blockVariables = [];
			$blockVariables['void:lines'] = $blockVariables['subnodes:items'] = $lines;
			$blockVariables['bid'] = $blogId;
			$blockVariables['per_page'] = $limit;
			$blockVariables['total'] = $total;

			return blogs20::parseTemplate($postsListBlock, $blockVariables);
		}

		/**
		 * Возвращает список постов, содержащих указаный тег
		 * @param bool|string $tag тег
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $limit ограничение на количество выводимых постов
		 * @return mixed
		 * @throws selectorException
		 */
		public function postsByTag($tag = false, $template = 'default', $limit = false) {
			list(
				$templateBlock,
				$templateLine
				) = blogs20::loadTemplates('blogs20/' . $template,
				'posts_list_block',
				'posts_list_line'
			);

			if ($tag === false) {
				$tag = ($tmp = getRequest('param0')) ? $tmp : $tag;
			}

			$page = (int) getRequest('p');
			$perPage = $limit ?: $this->module->posts_per_page;

			$sel = new selector('pages');
			$sel->types('object-type')->name('blogs20', 'post');
			$sel->where('only_for_friends')->notequals(1);
			$sel->where('tags')->equals($tag);
			$sel->order('publish_time')->desc();
			$sel->option('load-all-props')->value(true);
			$sel->limit($page * $perPage, $perPage);

			$result = $sel->result();
			$total = $sel->length();

			/** @var umiHierarchyElement[] $postList */
			$postList = array_filter($result, function ($post) {
				return ($post instanceof iUmiHierarchyElement);
			});

			$parentIds = [];

			foreach ($postList as $post) {
				$parentIds[] = $post->getParentId();
			}

			$umiLinksHelper = umiLinksHelper::getInstance();
			$umiLinksHelper->loadLinkPartForPages($parentIds);
			$umiHierarchy = umiHierarchy::getInstance();
			$umiHierarchy->loadElements($parentIds);

			$lines = [];

			foreach ($postList as $post) {
				$postId = $post->getId();
				$blog = $umiHierarchy->getElement($post->getParentId());
				$postLink = $umiLinksHelper->getLinkByParts($post);
				$blogLink = $umiLinksHelper->getLinkByParts($blog);

				$line = [];
				$line['attribute:id'] = $postId;
				$line['attribute:author_id'] = $post->getObject()->getOwnerId();
				$line['name'] = $post->getName();
				$line['post_link'] = $postLink;
				$line['blog_link'] = $blogLink;
				$line['bid'] = $blog->getId();
				$line['blog_title'] = $blog->getValue('title');
				$line['blog_name'] = $blog->getName();
				$line['title'] = $post->getValue('title');
				$line['cut'] = $this->prepareCut($post->getValue('content'), $postLink, $template);
				$line['subnodes:tags'] = $this->prepareTags($post->getValue('tags'));
				$line['comments_count'] = $umiHierarchy->getChildrenCount($postId, false);
				$line['publish_time'] = ($d = $post->getValue('publish_time')) ? $d->getFormattedDate('U') : '';

				$lines[] = blogs20::parseTemplate($templateLine, $line, $postId);
				blogs20::pushEditable('blogs20', 'post', $postId);
			}

			$block = [];
			$block['void:lines'] = $block['subnodes:items'] = $lines;
			$block['per_page'] = $perPage;
			$block['total'] = $total;

			return blogs20::parseTemplate($templateBlock, $block);
		}

		/**
		 * Возвращает список черновиков (неактивных постов) текущего пользователя
		 * из заданного блога
		 * @param bool|int $blogId идентификатор блога
		 * @param string $template имя шаблона (для tpl)
		 * @param bool $limit ограничение на количество выводимых черновиков
		 * @return mixed
		 * @throws selectorException
		 */
		public function draughtsList($blogId = false, $template = 'default', $limit = false) {
			$umiHierarchy = umiHierarchy::getInstance();
			$umiLinksHelper = umiLinksHelper::getInstance();

			list($sTemplateBlock, $sTemplateLine) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'posts_list_block',
				'posts_list_line'
			);

			$page = (int) getRequest('p');
			$oBlog = null;

			if ($blogId === false) {
				$iTmp = getRequest('param0');
				if ($iTmp) {
					$blogId = (int) $iTmp;
				}
			}

			if ($blogId !== false) {
				$oBlog = $umiHierarchy->getElement($blogId);
				if ($oBlog instanceof iUmiHierarchyElement) {
					$umiLinksHelper->loadLinkPartForPages([$blogId]);
				}
			}

			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$per_page = $limit ?: $this->module->posts_per_page;

			$posts = new selector('pages');
			$posts->types('object-type')->name('blogs20', 'post');
			if ($oBlog instanceof iUmiHierarchyElement) {
				$posts->where('hierarchy')->page($blogId);
			}
			$posts->where('owner')->equals($userId);
			$posts->where('is_active')->equals(false);
			$posts->order('publish_time')->desc();
			$posts->option('load-all-props')->value(true);
			$posts->limit($page * $per_page, $per_page);
			$this->applyTimeRange($posts);

			$result = $posts->result();
			$total = $posts->length();

			if (!$oBlog) {
				$parentIds = [];

				foreach ($result as $oPost) {
					if (!$oPost instanceof iUmiHierarchyElement) {
						continue;
					}
					$parentIds[] = $oPost->getParentId();
				}

				$umiLinksHelper->loadLinkPartForPages($parentIds);
			}

			$aLines = [];

			foreach ($result as $oPost) {
				if (!$oPost instanceof iUmiHierarchyElement) {
					continue;
				}
				$iPostId = $oPost->getId();
				if (!$oBlog) {
					$oBlog = $umiHierarchy->getElement($oPost->getParentId());
				}
				if (!$oBlog instanceof iUmiHierarchyElement) {
					continue;
				}
				$sPostLink = '/blogs20/postView/' . $iPostId . '/';
				$sBlogLink = $umiLinksHelper->getLinkByParts($oBlog);
				$aLineParam = [];
				$aLineParam['attribute:id'] = $iPostId;
				$aLineParam['attribute:author_id'] = $oPost->getObject()->getOwnerId();
				$aLineParam['node:name'] = $oPost->getName();
				$aLineParam['post_link'] = $sPostLink;
				$aLineParam['blog_link'] = $sBlogLink;
				$aLineParam['bid'] = $oBlog->getId();
				$aLineParam['blog_title'] = $oBlog->getValue('title');
				$aLineParam['title'] = $oPost->getValue('title');
				$aLineParam['content'] = $oPost->getValue('content');
				$aLineParam['cut'] = $this->prepareCut($aLineParam['content'], $sPostLink, $template);
				$aLineParam['subnodes:tags'] = $this->prepareTags($oPost->getValue('tags'));
				$aLineParam['comments_count'] = $umiHierarchy->getChildrenCount($iPostId, false);
				blogs20::pushEditable('blogs20', 'post', $iPostId);
				$aLines[] = blogs20::parseTemplate($sTemplateLine, $aLineParam, $iPostId);
			}

			$aBlockParam = [];
			$aBlockParam['void:lines'] = $aBlockParam['subnodes:items'] = $aLines;
			$aBlockParam['bid'] = $blogId;
			$aBlockParam['per_page'] = $limit ?: $this->module->posts_per_page;
			$aBlockParam['total'] = $total;

			return blogs20::parseTemplate($sTemplateBlock, $aBlockParam);
		}

		/**
		 * Выводит дерево комментариев поста блога
		 * @param bool|int $postId идентификатор поста блога
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function commentsList($postId = false, $template = 'default') {
			list($sTemplateBlock, $sTemplateLine) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'comments_list_block',
				'comments_list_line'
			);

			$total = 0;
			$aLines = $this->placeComments($postId, $sTemplateLine, $total);

			$aBlockParam = [];
			$aBlockParam['subnodes:items'] = $aBlockParam['void:lines'] = $aLines;
			$aBlockParam['per_page'] = $this->module->comments_per_page;
			$aBlockParam['total'] = $total;

			return blogs20::parseTemplate($sTemplateBlock, $aBlockParam);
		}

		/**
		 * Выводит ветвь дерева комментариев
		 * @param int $parentId идентификатор родительской страницы
		 * @param mixed $templateString блок шаблона (для tpl)
		 * @param int $total ограничение на количество
		 * значение передает по ссылке, так как метод вызывается рекурсивно
		 * @return array
		 * @throws publicException
		 * @throws selectorException
		 */
		public function placeComments($parentId, $templateString, &$total) {
			static $postHType = 0;

			if (!$postHType) {
				$umiTypesHelper = umiTypesHelper::getInstance();
				$postHType = $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post');
			}

			$hierarchy = umiHierarchy::getInstance();
			$parent = $hierarchy->getElement($parentId, true);

			if (!($parent instanceof iUmiHierarchyElement)) {
				throw new publicException('Unknown parent element for comments');
			}

			$rootComments = ($parent->getTypeId() == $postHType);

			$sel = new selector('pages');
			$sel->types('object-type')->name('blogs20', 'comment');
			$sel->where('hierarchy')->page($parentId);
			$sel->where('is_spam')->notequals(1);
			$sel->option('load-all-props')->value(true);

			if ($rootComments) {
				$page = (int) getRequest('p');
				$sel->limit($page * $this->module->comments_per_page, $this->module->comments_per_page);
			}

			$result = $sel->result();
			$total = $sel->length();
			$aLines = [];

			/** @var iUmiHierarchyElement $oComment */
			foreach ($result as $oComment) {
				$commentId = $oComment->getId();
				$temp = 0;
				$pubTime = $oComment->getValue('publish_time');
				$aLineParam = [];
				$aLineParam['attribute:cid'] = $commentId;
				$aLineParam['name'] = $oComment->getName();
				$aLineParam['content'] = $this->prepareContent($oComment->getValue('content'));
				$aLineParam['author_id'] = $oComment->getValue('author_id');
				$aLineParam['publish_time'] = ($pubTime instanceof umiDate) ? $pubTime->getFormattedDate('U') : time();
				$aLineParam['subnodes:subcomments'] = $this->placeComments($commentId, $templateString, $temp);
				$aLines[] = blogs20::parseTemplate($templateString, $aLineParam, $commentId);
			}

			return $aLines;
		}

		/**
		 * Выводит содержимое поста
		 * @param bool|int $postId идентификатор поста
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 */
		public function postView($postId = false, $template = 'default') {
			$auth = Service::Auth();
			$userId = $auth->getUserId();

			if (!$postId) {
				$postId = ($tmp = getRequest('param0')) ? $tmp : $postId;
			}
			if ($postId === false) {
				blogs20::simpleRedirect(getServer('HTTP_REFERER'));
			}

			$postId = umiObjectProperty::filterInputString($postId);
			list($sTemplate) = blogs20::loadTemplates('blogs20/' . $template, 'post_view');

			$oHierarchy = umiHierarchy::getInstance();
			$oPost = $oHierarchy->getElement($postId);

			if (!$oPost) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $postId));
			}

			$umiLinksHelper = umiLinksHelper::getInstance();
			$umiLinksHelper->loadLinkPartForPages([$postId, $oPost->getParentId()]);
			$umiTypesHelper = umiTypesHelper::getInstance();

			if ($oPost->getTypeId() != $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post')) {
				throw new publicException("The id(#{$postId}) given is not an id of the blog's post");
			}

			if (!$oPost->getIsActive() && $oPost->getObject()->getOwnerId() != $userId) {
				blogs20::simpleRedirect('/blogs20/draughtsList/');
			}

			$oBlog = $oHierarchy->getElement($oPost->getParentId());
			$sPostLink = $umiLinksHelper->getLinkByParts($oPost);
			$sBlogLink = $umiLinksHelper->getLinkByParts($oBlog);

			$aParams = [];
			$aParams['name'] = $oPost->getName();
			$aParams['content'] = $this->prepareContent(system_parse_short_calls($oPost->getValue('content'), $postId));
			$aParams['pid'] = $postId;
			$aParams['bid'] = $oBlog->getId();
			$aParams['blog_title'] = $oBlog->getValue('title');
			$aParams['blog_name'] = $oBlog->getName();
			$aParams['post_link'] = $sPostLink;
			$aParams['blog_link'] = $sBlogLink;
			$aParams['author_id'] = $oPost->getObject()->getOwnerId();
			blogs20::pushEditable('blogs20', 'post', $postId);

			return blogs20::parseTemplate($sTemplate, $aParams, $postId);
		}

		/**
		 * Возвращает список авторов блога
		 * @param bool|int $blogId идентификатор блога
		 * @param string $template имя шаблона (для tpl)
		 * @return string
		 * @throws publicException
		 * @throws selectorException
		 */
		public function viewBlogAuthors($blogId = false, $template = 'default') {
			list($sTemplateBlock, $sTemplateLine) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'blog_author_list_block',
				'blog_author_list_line'
			);

			$oPermissions = permissionsCollection::getInstance();
			$userTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('users', 'user');
			$aResult = [];
			$aXMLResult = [];

			$blog = umiHierarchy::getInstance()->getElement($blogId);

			if (!$blog instanceof iUmiHierarchyElement) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $blogId));
			}

			$blogOwnerId = $blog->getObject()->getOwnerId();
			$ownersIds = $oPermissions->getUsersByElementPermissions($blogId, 2);
			$ownersIds[] = $blogOwnerId;

			$owners = new selector('objects');
			$owners->where('id')->equals($ownersIds);
			$owners->option('no-length')->value(true);
			$owners->option('load-all-props')->value(true);
			$owners = $owners->result();
			$allUsers = [];
			$groupsIds = [];

			/** @var iUmiObject $owner */
			foreach ($owners as $owner) {
				if ($owner->getTypeId() == $userTypeId) {
					$allUsers[] = $owner;
					continue;
				}
				$groupsIds[] = $owner->getId();
			}

			$users = new selector('objects');
			$users->types('object-type')->name('users', 'user');
			$users->where('groups')->equals($groupsIds);
			$users->option('no-length')->value(true);
			$users->option('load-all-props')->value(true);
			$allUsers = array_merge($users->result(), $allUsers);

			if (empty($allUsers)) {
				return '1';
			}

			$allUsers = array_unique($allUsers);
			foreach ($allUsers as $user) {
				if (!$user instanceof iUmiObject) {
					continue;
				}
				$userId = $user->getId();
				$aLine = [];
				$aLine['attribute:user_id'] = $userId;
				$aLine['attribute:login'] = $user->getValue('login');
				$aLine['attribute:fname'] = $user->getValue('fname');
				$aLine['attribute:lname'] = $user->getValue('lname');
				$name = $user->getValue('fname') . ' ' . $user->getValue('lname');
				$login = $user->getName();

				$aLine['attribute:name'] = trim($name) !== '' ? $name : $login;

				if ($userId == $blogOwnerId) {
					$aLine['attribute:is_owner'] = '1';
				}

				$aResult[] = blogs20::parseTemplate($sTemplateLine, $aLine);
				$aXMLResult[] = $aLine;
			}
			if (empty($aResult)) {
				return '';
			}
			$lines = (!empty($aResult) && !is_array($aResult[0])) ? implode(', ', $aResult) : '';
			return blogs20::parseTemplate($sTemplateBlock, [
				'void:lines' => $lines,
				'subnodes:users' => $aXMLResult
			]);
		}

		/**
		 * Возвращает список друзей блога
		 * @param int $blogId идентификатор блога
		 * @param string $template имя шаблона (для tpl)
		 * @return string
		 * @throws publicException
		 */
		public function viewBlogFriends($blogId, $template = 'default') {
			if ($blogId === false) {
				$iTmp = getRequest('param0');
				if ($iTmp) {
					$blogId = $iTmp;
				}
			}

			$blogId = (int) $blogId;

			/** @var UsersMacros $oUsersModule */
			$cmsController = cmsController::getInstance();
			$oUsersModule = $cmsController->getModule('users');

			if (!$oUsersModule instanceof def_module) {
				throw new publicException("Can't find users module");
			}

			$oBlog = umiHierarchy::getInstance()->getElement($blogId);

			if (!$oBlog) {
				throw new publicException('Incorrect Blog ID');
			}

			$template = ($template == 'default') ? 'blogs20' : $template;
			$aFriendsList = $oBlog->getValue('friendlist');
			$aResult = [];

			foreach ($aFriendsList as $userId) {
				$aResult[] = $oUsersModule->viewAuthor($userId, $template);
			}
			return implode(', ', $aResult);
		}

		/**
		 * Применяет к конструктору выборок фильтр по диапазонам дат
		 * @param Selector $selector конструктор выборок
		 * @param iUmiObjectType $type объектный типа сущностей, по которым производится выборка
		 * @throws selectorException
		 */
		public function applyTimeRange($selector, iUmiObjectType $type = null) {
			$stringFrom = (string) getRequest('from_date');
			$stringTo = (string) getRequest('to_date');

			if ($stringFrom !== '' && $stringTo !== '') {
				$arrayFrom = explode('-', $stringFrom);
				$arrayTo = explode('-', $stringTo);
				$timeFrom = mktime(0, 0, 0, $arrayFrom[1], $arrayFrom[2], $arrayFrom[0]);
				$timeTo = mktime(23, 59, 59, $arrayTo[1], $arrayTo[2], $arrayTo[0]);

				$selector->where('publish_time')->between($timeFrom, $timeTo);
			} elseif ($stringFrom !== '' && $stringTo === '') {
				$arrayFrom = explode('-', $stringFrom);
				$timeFrom = mktime(0, 0, 0, $arrayFrom[1], $arrayFrom[2], $arrayFrom[0]);

				$selector->where('publish_time')->more($timeFrom);
			} elseif ($stringTo !== '') {
				$arrayTo = explode('-', $stringTo);
				$timeTo = mktime(23, 59, 59, $arrayTo[1], $arrayTo[2], $arrayTo[0]);

				$selector->where('publish_time')->less($timeTo);
			}
		}

		/**
		 * Обрезает все содержимое контента за пределами псевдотега [cut]
		 * @param string $content контент
		 * @param string $readLink адрес страницы, где можно прочитать контент полностью
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed|string
		 */
		public function prepareCut($content, $readLink, $template = 'default') {
			static $sReadAllLink = false;
			$iPos = mb_strpos($content, '[cut]');

			if ($iPos === false) {
				return $this->prepareContent($content);
			}

			if ($sReadAllLink === false) {
				list($sReadAllLink) = blogs20::loadTemplates('blogs20/' . $template, 'post_cut_link');
			}

			$iPosEnd = mb_strpos($content, '[/cut]');

			if ($iPosEnd === false) {
				$iPosEnd = $iPos;
				$iPos = 0;
			}
			$content = mb_substr($content, $iPos, $iPosEnd - $iPos);
			$content = $this->prepareContent($content);

			$link = blogs20::parseTemplate($sReadAllLink, ['link' => $readLink]);

			if (!is_array($link)) {
				$content .= $link;
			}

			return $content;
		}

		/**
		 * Применяет шаблон для вывода тегов
		 * @param array $Tags теги
		 * @param string $template имя шаблона (для tpl)
		 * @return array|string
		 */
		public function prepareTags($Tags, $template = 'default') {
			static $sTemplate = null;

			if ($sTemplate == null) {
				list($sTemplate) = blogs20::loadTemplates('blogs20/' . $template, 'tag_decoration');
			}

			$Result = [];

			foreach ($Tags as $tag) {
				$Result[] = blogs20::parseTemplate($sTemplate, ['link' => '/blogs20/postsByTag/' . $tag, 'tag' => $tag]);
			}

			return (!empty($Result) && is_array($Result[0])) ? $Result : implode(', ', $Result);
		}

		/**
		 * Заменяет псевдотеги на html теги
		 * и возвращает результат замены
		 * @param string $content контент, в котором произходит замена
		 * @return mixed
		 */
		public function prepareContent($content) {
			$replaceData = $this->module->getPseudoTagsReplaceData();
			$content = str_replace($replaceData['from'], $replaceData['to'], $content);
			$content = preg_replace("@\[img\](.+?)\[\/img\]@i", '<img src="$1" alt="" />', $content);
			$content = preg_replace("@\[url\](.+?)\[\/url\]@i", '<a href="$1">[Link]</a>', $content);
			$content = preg_replace("@\[url=(.+?)\]((.|\n)+?)\[\/url\]@i", '<a href="$1" target="_blank">$2</a>', $content);
			$content = preg_replace("@\[code\]((.|\n)+?)\[\/code\]@i", '<tt>$1</tt>', $content);
			$content = preg_replace("@\[color=([A-Za-z0-9#]+?)\]((.|\n)+?)\[\/color\]@i", '<span style="color:$1;">$2</span>', $content);
			$content = preg_replace("@\[smile:([0-9]+?)\]@i", '<img src="/images/forum/smiles/$1.gif" alt="$1">', $content);
			return $content;
		}

		/**
		 * Возвращает массив псевдотегов и html тегов, 
		 * заменяющих их при подготовке контента
		 * @return array
		 */
		public function getPseudoTagsReplaceData() {
			return [
				'from' => [
					'[b]', '[/b]', 
					'[i]', '[/i]', 
					'[s]', '[/s]', 
					'[u]', '[/u]', 
					'[quote]', '[/quote]', 
					">\r\n", "\n", 
					'[cut]', '[/cut]' 
				],
				'to' => [
					'<b>', '</b>', 
					'<i>', '</i>', 
					'<span style="text-decoration:line-through;">', '</span>', 
					'<span style="text-decoration:underline;">', '</span>', 
					'<div class="quote">', '</div>', 
					'>', "<br />\n", 
					'', '' 
				]
			];
		}

		/**
		 * Возвращает данные для формы редактирования поста блога
		 * на клиентской части.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения поста блога.
		 * @param bool|int $postId идентификатор поста блога
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed|null
		 * @throws publicException
		 */
		public function postEdit($postId = false, $template = 'default') {
			if (!$postId) {
				$iTmp = getRequest('param0');
				if ($iTmp) {
					$postId = $iTmp;
				} else {
					$this->module->redirect(getServer('HTTP_REFERER'));
				}
			}

			$postId = (int) $postId;
			$oPost = null;
			$oHierarchy = umiHierarchy::getInstance();
			$oPost = $oHierarchy->getElement($postId);
			$umiTypesHelper = umiTypesHelper::getInstance();

			if (!$oPost ||
				$oPost->getTypeId() != $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post')
			) {
				throw new publicException("The id(#{$postId}) given is not an id of the blog's post");
			}

			$permissions = permissionsCollection::getInstance();
			$auth = Service::Auth();
			$userId = $auth->getUserId();
			list($read, $edit) = $permissions->isAllowedObject($userId, $postId);

			if (!$edit) {
				throw new publicException(getLabel('error-post-edit'));
			}

			if (getRequest('param1') == 'do') {
				$sTitle = (string) getRequest('title');
				$sContent = htmlspecialchars(trim(getRequest('content')));
				if ($sTitle !== '' && $sContent !== '') {
					$iFriendsOnly = getRequest('visible_for_friends') ? 1 : 0;
					$bActivity = getRequest('draught') ? false : true;
					$sTags = getRequest('tags');
					$iBlogId = getRequest('bid');
					if ($iBlogId && $iBlogId != $oPost->getParentId()) {
						$oHierarchy->moveBefore($postId, $iBlogId);
					}

					if ($bActivity) {
						$bActivity = antiSpamHelper::checkContent($sContent . $sTitle . $sTags);
					}

					$oPost->setIsActive($bActivity);
					$oPost->setValue('title', $sTitle);
					$oPost->setValue('content', $sContent);
					$oPost->setValue('tags', $sTags);
					$oPost->setValue('only_for_friends', $iFriendsOnly);
					$sRefererUri = (string) getRequest('redirect');

					if ($sRefererUri !== '') {
						$this->module->redirect($sRefererUri);
					}

					$this->module->redirect($oHierarchy->getPathById($postId));
				}
			}

			if (!$oPost) {
				$oPost = $oHierarchy->getElement($postId);
			}

			list($sFormTemplate) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'post_edit_form'
			);

			$aParams = [
				'action' => '/blogs20/postEdit/' . $postId . '/do/',
				'id' => $postId,
				'blog_select' => $this->prepareBlogSelect($oPost->getParentId(), true, $template),
				'visible_for_friends' => $oPost->getValue('only_for_friends') ? 'checked="checked"' : ''
			];

			return blogs20::parseTemplate($sFormTemplate, $aParams, $postId);
		}

		/**
		 * Возвращает данные для формы добавления поста блога
		 * на клиентской части.
		 * Если переданы все необходимые данные - добавляет пост.
		 * @param bool|int $blogId идентификатор поста блога
		 * @param string $template имя шаблона (для tpl)
		 * @return bool|mixed|null
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function postAdd($blogId = false, $template = 'default') {
			if ($blogId === false) {
				$iTmp = getRequest('param0');
				if ($iTmp) {
					$blogId = $iTmp;
				} else {
					$blogId = getRequest('bid');
				}
			}

			$blogId = (int) $blogId;
			$permissions = permissionsCollection::getInstance();
			$auth = Service::Auth();
			list($canRead, $canWrite) = $permissions->isAllowedObject($auth->getUserId(), $blogId);

			if (!Service::Auth()->isAuthorized() || (!$canWrite && $blogId)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$sTitle = htmlspecialchars(trim(getRequest('title')));
			$sContent = htmlspecialchars(trim(getRequest('content')));
			$umiTypesHelper = umiTypesHelper::getInstance();

			if ($sTitle !== '' && $sContent !== '' && $blogId) {
				if (!umiCaptcha::checkCaptcha()) {
					$this->module->errorNewMessage('%errors_wrong_captcha%', true, false, 'captcha');
					$this->module->errorPanic();
				}
				if (
					!($blog = $umiHierarchy->getElement($blogId)) ||
					($blog->getTypeId() != $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'blog'))
				) {
					$this->module->errorNewMessage('%error_wrong_parent%');
					$this->module->errorPanic();
				}

				$iFriendsOnly = getRequest('visible_for_friends') ? 1 : 0;
				$bActivity = getRequest('draught') ? false : true;
				$sTags = getRequest('tags');
				$umiTypesHelper = umiTypesHelper::getInstance();
				$hierarchy_type_id = $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post');
				$iPostId = $umiHierarchy->addElement($blogId, $hierarchy_type_id, $sTitle, $sTitle);
				$permissions->setDefaultPermissions($iPostId);
				$oPost = $umiHierarchy->getElement($iPostId, true);

				if ($bActivity) {
					$bActivity = antiSpamHelper::checkContent($sContent . $sTitle . $sTags);
				}

				$oPost->setIsActive($bActivity);
				$oPost->setValue('title', $sTitle);
				$oPost->setValue('content', $sContent);
				$oPost->setValue('tags', $sTags);
				$oPost->setValue('publish_time', new umiDate());
				$oPost->setValue('only_for_friends', $iFriendsOnly);

				$oEventPoint = new umiEventPoint('blogs20PostAdded');
				$oEventPoint->setMode('after');
				$oEventPoint->setParam('id', $iPostId);
				$oEventPoint->setParam('template', $template);
				blogs20::setEventPoint($oEventPoint);

				$sRefererUri = (string) getServer('HTTP_REFERER');
				if ($sRefererUri !== '') {
					$this->module->redirect(str_replace('_err=', '', $sRefererUri));
				}

				return null;
			}

			if ($blogId && $sTitle === '' && $sContent !== '') {
				$this->module->errorNewMessage(getLabel('error-empty-header'));
			} else {
				if ($blogId && $sTitle !== '' && $sContent === '') {
					$this->module->errorNewMessage(getLabel('error-empty-content'));
				}
			}

			list($sFormTemplate) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'post_add_form'
			);

			$aParams = [
				'action' => '/blogs20/postAdd/' . $blogId . '/',
				'id' => 'new',
				'title' => '',
				'content' => '',
				'tags' => '',
				'visible_for_friends' => '',
				'blog_select' => $this->prepareBlogSelect($blogId, false, $template)
			];

			return blogs20::parseTemplate($sFormTemplate, $aParams);
		}

		/**
		 * Возвращает данные для формы добавления комментария
		 * на клиентской части.
		 * Если переданы все необходимые данные - добавляет комментарий.
		 * @param bool|int $postId идентификатор поста блога
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed|null|void
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws publicException
		 */
		public function commentAdd($postId = false, $template = 'default') {
			$bNeedFinalPanic = false;
			$umiRegistry = Service::Registry();
			$isAuthorized = Service::Auth()->isAuthorized();

			if (!($isAuthorized || $umiRegistry->get('//modules/blogs20/allow_guest_comments'))) {
				return;
			}

			if ($postId === false) {
				$iTmp = getRequest('param0');
				if ($iTmp) {
					$postId = $iTmp;
				} else {
					$cmsController = cmsController::getInstance();
					$postId = $cmsController->getCurrentElementId();
				}
			}
			$postId = (int) $postId;
			$oHierarchy = umiHierarchy::getInstance();

			if (!($oPost = $oHierarchy->getElement($postId))) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $postId));
			}

			$umiTypesHelper = umiTypesHelper::getInstance();

			if (
				$oPost->getTypeId() != $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post') &&
				$oPost->getTypeId() != $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'comment')
			) {
				throw new publicException("The id(#{$postId}) given is not an id of the blog's post");
			}

			$sTitle = ($tmp = getRequest('title')) ? $tmp : 'Re: ' . $oPost->getName();
			$sContent = htmlspecialchars(trim(getRequest('content')));

			if ($postId !== false && mb_strlen($sContent) > 0) {
				if (!umiCaptcha::checkCaptcha()) {
					$this->module->errorNewMessage('%errors_wrong_captcha%', true, false, 'captcha');
					$this->module->errorPanic();
				}

				$umiTypesHelper = umiTypesHelper::getInstance();
				$hierarchy_type_id = $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'comment');
				$iCommentId = $oHierarchy->addElement($postId, $hierarchy_type_id, $sTitle, $sTitle);
				permissionsCollection::getInstance()->setDefaultPermissions($iCommentId);

				/** @var users $oUsersModule */
				$cmsController = cmsController::getInstance();
				$oUsersModule = $cmsController->getModule('users');

				if ($isAuthorized) {
					$userId = Service::Auth()->getUserId();
					$authorId = $oUsersModule->createAuthorUser($userId);
					$oActivity = antiSpamHelper::checkContent($sContent . $sTitle);
				} else {
					$nick = getRequest('nick');
					$email = getRequest('email');
					$ip = getServer('REMOTE_ADDR');
					$authorId = $oUsersModule->createAuthorGuest($nick, $email, $ip);
					$oActivity = antiSpamHelper::checkContent($sContent . $sTitle . $nick . $email);
				}

				$oComment = $oHierarchy->getElement($iCommentId, true);
				$is_active = $this->module->moderate ? 0 : 1;

				if ($is_active) {
					$is_active = $oActivity;
				}
				if (!$is_active) {
					$this->module->errorNewMessage('%comments_posted_moderating%', false);
					$bNeedFinalPanic = true;
				}

				$oComment->setIsActive($is_active);
				$oComment->setValue('title', $sTitle);
				$oComment->setValue('content', $sContent);
				$oComment->setValue('author_id', $authorId);
				$oComment->setValue('publish_time', new umiDate());
				$oComment->commit();

				$oEventPoint = new umiEventPoint('blogs20CommentAdded');
				$oEventPoint->setMode('after');
				$oEventPoint->setParam('id', $iCommentId);
				$oEventPoint->setParam('template', $template);
				blogs20::setEventPoint($oEventPoint);

				if ($bNeedFinalPanic) {
					$this->module->errorPanic();
				} else {
					$cmsController = cmsController::getInstance();
					$referrerUri = (string) $cmsController->getCalculatedRefererUri();

					if ($referrerUri !== '') {
						$this->module->redirect($referrerUri . '#comment_' . $iCommentId);
					}
				}
				return null;
			}

			if ($sContent === '' && getRequest('content') !== null) {
				$this->module->errorNewMessage('%errors_missed_field_value%');
				$this->module->errorPanic();
			}

			$sTplName = $isAuthorized ? 'comment_add_form' : 'comment_add_form_guest';

			list($sFormTemplate) = blogs20::loadTemplates(
				'blogs20/' . $template,
				$sTplName
			);

			return blogs20::parseTemplate($sFormTemplate, ['parent_id' => $postId]);
		}

		/**
		 * Удаляет сущности модуля на клиентской части.
		 * @param bool|int $elementId идентификатор поста или комментария
		 */
		public function itemDelete($elementId = false) {
			if ($elementId === false) {
				$elementId = getRequest('param0');
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$permissions = permissionsCollection::getInstance();
			$element = $umiHierarchy->getElement($elementId);

			if ($element instanceof iUmiHierarchyElement) {
				/** @var iUmiHierarchyType $hierarchyType */
				$hierarchyType = $element->getHierarchyType();

				if ($hierarchyType->getName() == 'blogs20') {
					/** @var iUmiObject $object */
					$object = $element->getObject();
					$auth = Service::Auth();

					if ($permissions->isSv() || $object->getOwnerId() == $auth->getUserId()) {
						$umiHierarchy->delElement($elementId);
					}
				}
			}

			$sRedirect = getRequest('redirect');

			if ($sRedirect != null) {
				$this->module->redirect($sRedirect);
			} else {
				$sReferer = getServer('HTTP_REFERER');
				$this->module->redirect($sReferer);
			}
		}

		/**
		 * Возвращает данные для формы редактирования|добавления блога
		 * на клиентской части.
		 * Если переданы все необходимые данные - добавляет или сохраняет блог.
		 * @param bool|int|string $blogId идентификатор редактируемого блога, либо "new"
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 */
		public function editUserBlogs($blogId = false, $template = 'default') {
			if ($blogId === false) {
				$firstParam = getRequest('param0');
				if ($firstParam) {
					$blogId = $firstParam;
				}
			}

			if ($blogId != 'new') {
				$blogId = (int) $blogId;
			}

			$umiRegistry = Service::Registry();
			$umiHierarchy = umiHierarchy::getInstance();
			$umiTypesHelper = umiTypesHelper::getInstance();
			$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'blog');
			$permissions = permissionsCollection::getInstance();
			$auth = Service::Auth();
			$oUsersId = $auth->getUserId();
			$umiObjectsCollection = umiObjectsCollection::getInstance();

			if ((int) $blogId > 0 || $blogId == 'new') {
				$aBlogInfo = getRequest('blog');
				$aBlogInfo = $aBlogInfo[$blogId];

				if ($aBlogInfo && isset($aBlogInfo['title']) && trim(($aBlogInfo['title'])) !== '') {
					$title = (string) $aBlogInfo['title'];
					$description = isset($aBlogInfo['description']) ? $aBlogInfo['description'] : '';
					$friendlist = isset($aBlogInfo['friendlist']) ? array_map('intval', $aBlogInfo['friendlist']) : [];

					if ($blogId == 'new') {
						$path = $umiRegistry->get('//modules/blogs20/autocreate_path');
						$parentId = (int) $umiHierarchy->getIdByPath($path);
						$blogId = $umiHierarchy->addElement($parentId, $hierarchyTypeId, $title, $title);
						$permissions->setDefaultPermissions($blogId);
						$user = $umiObjectsCollection->getObject($oUsersId);
						$groups = $user->getValue('groups');

						foreach ($groups as $id) {
							$permissions->setElementPermissions($id, $blogId, 1);
						}

						$permissions->setElementPermissions($oUsersId, $blogId, 31);
					}

					$blog = $umiHierarchy->getElement($blogId);
					if ($blog) {
						$blog->setIsActive();
						$blog->setValue('title', $title);
						$blog->setValue('description', $description);
						$blog->setValue('friendlist', $friendlist);
						$blog->commit();
					}

					$sRedirectURI = getRequest('redirect');

					if ($sRedirectURI) {
						$this->module->redirect($sRedirectURI);
					}
				}

				if ($blogId != 'new') {
					$blog = $umiHierarchy->getElement($blogId);
					$result = [$blog];
				} else {
					$result = [];
				}
			} else {
				$sel = new selector('pages');
				$sel->types('hierarchy-type')->id($hierarchyTypeId);
				$sel->where('permissions')->level(2);
				$sel->order('name');
				$result = $sel->result();
			}

			list($templateBlock, $templateLine, $templateNew) = blogs20::loadTemplates(
				'blogs20/' . $template,
				'blod_edit_block',
				'blog_edit_line',
				'blog_new_line'
			);

			$oCollection = umiObjectsCollection::getInstance();
			$userTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('users', 'user');
			$aUsers = $oCollection->getGuidedItems($userTypeId);
			$aLines = [];
			$ownerBlogs = 0;

			foreach ($result as $blog) {
				if (!$blog instanceof iUmiHierarchyElement) {
					continue;
				}

				$blogId = $blog->getId();

				$aLineParam = [];
				$aLineParam['bid'] = $blogId;
				$aLineParam['title'] = $blog->getValue('title');
				$aLineParam['description'] = $blog->getValue('description');
				$aLineParam['path'] = $umiHierarchy->getPathById($blogId);
				$aFriendList = $blog->getValue('friendlist');
				$sOptions = '';

				foreach ($aUsers as $userId => $userName) {
					$sOptions .= '<option value="' . $userId . '" ' . (in_array($userId, $aFriendList) ? 'selected' : '') .
						'>' . $userName . '</option>';
				}

				$aLineParam['friends'] = $sOptions;
				$aLineParam['current_page'] = getServer('REQUEST_URI');
				$aLines[] = blogs20::parseTemplate($templateLine, $aLineParam);
				/** @var iUmiObject $blogObject */
				$blogObject = $blog->getObject();

				if ($blogObject->getOwnerId() == $oUsersId) {
					$ownerBlogs++;
				}
			}
			if ($ownerBlogs < $umiRegistry->get('//modules/blogs20/blogs_per_user')) {
				$aLineParam = ['bid' => 'new', 'title' => '', 'description' => ''];
				$sOptions = '';

				foreach ($aUsers as $userId => $userName) {
					$sOptions .= '<option value="' . $userId . '">' . $userName . '</option>';
				}

				$aLineParam['friends'] = $sOptions;
				$aLineParam['current_page'] = getServer('REQUEST_URI');
				$aLines[] = blogs20::parseTemplate($templateNew, $aLineParam);
			}

			$aBlock = [];
			$aBlock['subnodes:blogs'] = $aBlock['void:lines'] = $aLines;

			return blogs20::parseTemplate($templateBlock, $aBlock);
		}

		/**
		 * Возвращает элементы управления комментарием или публикацией
		 * @param int $elementId идентификатор публикации или комментария
		 * @param string $template имя шаблона (для tpl)
		 * @return string
		 */
		public function placeControls($elementId, $template = 'default') {
			static $bInited = false;
			static $sPostBlock, $sPostDelete, $sPostEdit, $sCommentBlock, $sCommentDelete, $sCommentEdit;
			static $userId = false;
			static $oHierarchy;
			static $iCommentHTID;
			static $iPostHTID;

			if (!$bInited) {
				list(
					$sPostBlock, $sPostDelete, $sPostEdit, $sCommentBlock, $sCommentDelete, $sCommentEdit
					) = blogs20::loadTemplates(
					'blogs20/' . $template,
					'post_control_block',
					'post_control_delete',
					'post_control_edit',
					'comment_control_block',
					'comment_control_delete',
					'comment_control_edit'
				);

				$auth = Service::Auth();
				$userId = $auth->getUserId();
				$oHierarchy = umiHierarchy::getInstance();
				$iCommentHTID = umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'comment')->getId();
				$iPostHTID = umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'post')->getId();
				$bInited = true;
			}
			if ($userId === false) {
				return;
			}
			if (!$oElement = $oHierarchy->getElement($elementId, true)) {
				return '';
			}
			$ownerElement = $oElement;

			while ($ownerElement->getTypeId() == $iCommentHTID) {
				$ownerElement = $oHierarchy->getElement($ownerElement->getParentId(), true);
			}

			if ($ownerElement->getObject()->getOwnerId() != $userId) {
				return;
			}
			if ($oElement->getTypeId() == $iCommentHTID) {
				$sWrkBlock = $sCommentBlock;

				$sWrkDelete = blogs20::parseTemplate($sCommentDelete, [
					'attribute:link' => '/blogs20/itemDelete/' . $elementId . '/'
				]);

				$sWrkEdit = blogs20::parseTemplate($sCommentEdit, [
					'attribute:link' => '/blogs20/commentEdit/' . $elementId . '/'
				]);
			} elseif ($oElement->getTypeId() == $iPostHTID) {
				$sBlogUri = $oHierarchy->getPathById($ownerElement->getParentId());
				$sWrkBlock = $sPostBlock;

				$sWrkDelete = blogs20::parseTemplate($sPostDelete, [
					'attribute:link' => '/blogs20/itemDelete/' . $elementId . '/?redirect=' . urlencode($sBlogUri)
				]);

				$sWrkEdit = blogs20::parseTemplate($sPostEdit, [
					'attribute:link' => '/blogs20/postEdit/' . $elementId . '/'
				]);
			} else {
				return '';
			}

			$line_arr = [];
			$line_arr['edit'] = $sWrkEdit;
			$line_arr['delete'] = $sWrkDelete;

			$block_arr = [];
			$block_arr['controls'] = $line_arr;
			return blogs20::parseTemplate($sWrkBlock, $block_arr);
		}

		/**
		 * Разрешено ли оставлять комментарии незарегистрированным пользователям
		 * @return int
		 * @throws coreException
		 */
		public function checkAllowComments() {
			$systemUsersPermissions = Service::SystemUsersPermissions();
			$auth = Service::Auth();

			if ($auth->getUserId() == $systemUsersPermissions->getGuestUserId()) {
				return (int) Service::Registry()->get('/modules/blogs20/allow_guest_comments');
			}

			return 1;
		}

		/**
		 * Возвращает варианты выбора блога для добавления в него комментария или поста
		 * @param bool $blogIdCurrent идентификатор выбранного блога
		 * @param bool $force показать варианты выбора блога, не смотря на то, что он уже выбран
		 * @param string $template
		 * @return mixed|void
		 */
		public function prepareBlogSelect($blogIdCurrent = false, $force = false, $template = 'default') {
			if ($blogIdCurrent && !$force) {
				return;
			}

			static $bInited = false;
			static $sBlock, $sOption;
			static $blogs = [];

			if (!$bInited) {
				list($sBlock, $sOption) = blogs20::loadTemplates(
					'blogs20/' . $template,
					'blog_choose_block',
					'blog_choose_line'
				);

				$hierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'blog')->getId();

				$sel = new selector('pages');
				$sel->types('hierarchy-type')->id($hierarchyTypeId);
				$sel->where('permissions')->level(2);
				$result = $sel->result();

				/** @var iUmiHierarchyElement $blog */
				foreach ($result as $blog) {
					$blogs[$blog->getId()] = $blog->getValue('title');
				}

				$bInited = true;
			}

			$aLines = [];

			foreach ($blogs as $blogId => $blogTitle) {
				$aLines[] = blogs20::parseTemplate($sOption, [
					'bid' => $blogId,
					'title' => $blogTitle,
					'selected' => $blogId == $blogIdCurrent ? 'selected' : ''
				]);
			}

			return blogs20::parseTemplate($sBlock, [
				'subnodes:options' => $aLines
			]);
		}
	}
