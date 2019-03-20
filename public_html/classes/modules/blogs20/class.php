<?php
class blogs20 extends def_module {
	/** @var Int Количество элементов на странице */
	private $blogs_per_page    = 0;
	private $posts_per_page    = 0;
	private $comments_per_page = 0;
	/** @desc Конструктор */
	public function __construct() {
		parent::__construct();

		$this->loadCommonExtension();

		if($this->cmsController->getCurrentMode() === 'admin') {
			$this->__loadLib("__admin.php");
			$this->__implement("__admin");
			// Preparing the import library
			$this->__loadLib("__import.php");
			$this->__implement("__import");

			// Creating tabs
			$commonTabs = $this->getCommonTabs();
			if($commonTabs) {
				$commonTabs->add("posts");
				$commonTabs->add("blogs");
				$commonTabs->add("comments");
			}

			$this->loadAdminExtension();

			$this->__loadLib("__custom_adm.php");
			$this->__implement("__blogs20_custom_admin");
		} else {
			$this->__loadLib("__events_handlers.php");
			$this->__implement("__eventsHandlersBlogs");
		}

		$this->loadSiteExtension();

		$this->__loadLib("__custom.php");
		$this->__implement("__custom_blogs20");

		$regedit = $this->regedit;
		$this->blogs_per_page 	 = (int) $regedit->getVal("//modules/blogs20/paging/blogs") > 0 ? (int) $regedit->getVal("//modules/blogs20/paging/blogs") : $this->blogs_per_page ;
		$this->posts_per_page 	 = (int) $regedit->getVal("//modules/blogs20/paging/posts") > 0 ? (int) $regedit->getVal("//modules/blogs20/paging/posts") : $this->posts_per_page;
		$this->comments_per_page = (int) $regedit->getVal("//modules/blogs20/paging/comments") > 0 ? (int) $regedit->getVal("//modules/blogs20/paging/comments") : $this->comments_per_page;
		$this->moderate 		 = (bool) $regedit->getVal("//modules/blogs20/moderate_comments");
	}
	/**
	* @desc Возвращает ссылки на редактирование и добавление подэлемента
	* @param Int $element_id
	* @param String $element_type Строковый идентификатор базового типа
	* @return array(string, string)|false
	*/
	public function getEditLink($element_id, $element_type) {
		switch($element_type) {
			case "blog": {
				$link_add  = $this->pre_lang . "/admin/blogs20/add/{$element_id}/post/";
				$link_edit = $this->pre_lang . "/admin/blogs20/edit/{$element_id}/";
				return Array($link_add, $link_edit);
				break;
			}
			case "comment":
			case "post": {
				$link_edit = $this->pre_lang . "/admin/blogs20/edit/{$element_id}/";
				return Array(false, $link_edit);
				break;
			}
			default: {
				return false;
			}
		}
	}
	/**
	* @desc Метод по-умолчанию для отрисовки содержимого блога
	* @return string|array
	*/
	public function blog() {
		$blogId = $this->cmsController->getCurrentElementId();
		$this->pushEditable("blogs20", "blog", $blogId);
		return $this->postsList($blogId);
	}
	/**
	* @desc Метод по-умолчанию для отрисовки содержимого публикации
	* @return string|array
	*/
	public function post() {
		$postId = $this->cmsController->getCurrentElementId();
		return $this->postView($postId);
	}
	/** @desc Метод по-умолчанию для отрисовки содержимого комментария. Производит редирект на страницу с публикацией */
	public function comment() {
		$iCommentHTID = $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'comment');
		$commentId    = $this->cmsController->getCurrentElementId();
		$hierarchy    = umiHierarchy::getInstance();
		$element      = $hierarchy->getElement($commentId);
		if($element instanceof umiHierarchyElement) {
			while($element->getTypeId() == $iCommentHTID) {
				$element = $hierarchy->getElement($element->getRel());
			}
			$this->pushEditable("blogs20", "comment", $commentId);
			$this->redirect( $hierarchy->getPathById($element->getId()) . '#comment_' . $commentId );
		} else {
			throw new publicException(getLabel('error-page-does-not-exist'));
		}
	}
	/**
	* @desc Выводит список блогов
	* @param Int $blogsCount Количество блогов для вывода (если отличается от умолчания)
	* @param Int|String $sortType Вид сортировки результата (по-алфавиту, случайно и т.д.)
	* @param Int $domainId Идентификатор домена
	* @param String $template Имя файла шаблона для вывода
	* @return string|array
	*/
	public function blogsList($blogsCount = false, $sortType=false, $domainId = false, $template = 'default') {
		list($sTemplateBlock, $sTemplateLine) = self::loadTemplates('blogs20/'.$template, 'blogs_list_block', 'blogs_list_line');

		$page = (int)getRequest('p');

		$blogs = new selector('pages');
		$blogs->types('object-type')->name('blogs20', 'blog');
		if ($blogsCount) {
			$blogs->limit(0, $blogsCount);
		} else {
			$blogs->limit($page * $this->blogs_per_page, $this->blogs_per_page);
		}
		switch($sortType) {
			case 1:
			case 'name': $blogs->order('name')->asc(); break;
			case 2:
			case 'ord':  $blogs->order('ord')->asc(); break;
			case 4:
			case 'rand': $blogs->order('rand'); break;
		}
		$blogs->option('load-all-props')->value(true);
		$result = $blogs->result();
		$total  = $blogs->length();

		$aLines = array();
		foreach($result as $oBlog) {
			$iBlogId = $oBlog->getId();
			$aLineParam = array();
			$aLineParam['attribute:bid']   = $iBlogId;
			$aLineParam['attribute:title'] = $oBlog->getValue('title');
			$aLineParam['attribute:link']  = $this->umiLinksHelper->getLinkByParts($oBlog);
			$aLineParam['node:name']       = $oBlog->getName();
			$aLines[] = self::parseTemplate($sTemplateLine, $aLineParam, $iBlogId);
			$this->pushEditable("blogs20", "blog", $iBlogId);
		}
		$aBlockParam = array();
		$aBlockParam['subnodes:items'] = $aBlockParam['void:lines'] = $aLines;
		$aBlockParam['per_page'] = $blogsCount ? $blogsCount : $this->blogs_per_page;
		$aBlockParam['total']    = $total;

		return self::parseTemplate($sTemplateBlock, $aBlockParam);
	}

	/**
	 * @desc Выводит список публикаций
	 * @param bool|Int $blogId Идентификатор блога
	 * @param String $template Имя файла шаблона для вывода
	 * @param bool $limit
	 * @return string|array
	 */
	public function getPostsList($blogId = false, $template = 'default', $limit = false) {
		return $this->postsList($blogId, $template, $limit, false);
	}

	/** @deprecated */
	public function postsList($blogId = false, $template = 'default', $limit = false, $ignoreLang = true) {
		list($sTemplateBlock, $sTemplateLine, $sTemplateEmpty) =
				self::loadTemplates('blogs20/'.$template, 'posts_list_block', 'posts_list_line', 'posts_list_block_empty');

		$page = (int)getRequest('p');

		$oBlog = null;

		$oHierarchy = umiHierarchy::getInstance();
		if($blogId == false) {
			$iTmp = (int)getRequest('param0');
			if($iTmp) $blogId = $iTmp;
		}

		$sel = new selector('pages');
		$sel->types('object-type')->name('blogs20', 'post');
		$sel->where('is_spam')->notequals(1);

		if($blogId) {
			$oBlog = $oHierarchy->getElement($blogId);
			if(!$oBlog) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $blogId));
			}
			$this->loadElements($blogId, true, $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'blog'));
		}

		if($blogId != false) {
			$permissions = permissionsCollection::getInstance();
			$userId        = $permissions->getUserId();
			$aFriendList   = $oBlog->getValue('friendlist');

			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$aFriendList[] = $systemUsersPermissions->getSvUserId();

			if($aFriendList === NULL) {
				$aFriendList = Array();
			}

			$aAuthorList   = $permissions->getUsersByElementPermissions($blogId, 2);
			$aAuthorList[] = $oBlog->getObject()->getOwnerId();
			$sel->where('hierarchy')->page($blogId);
			if(!in_array($userId, $aFriendList) && !in_array($userId, $aAuthorList)) {
				$sel->where('only_for_friends')->notequals(1);
			}
		} else {
			$sel->where('only_for_friends')->notequals(1);
		}

		self::applyTimeRange($sel);
		$sel->option('load-all-props')->value(true);
		$sel->order('publish_time')->desc();

		if($limit) {
			$sel->limit($page * $limit, $limit);
		} else {
			$sel->limit($page * $this->posts_per_page, $this->posts_per_page);
		}

		$result = $sel->result();
		$total  = $sel->length();
		if(!empty($result)) {
			$aLines = array();
			foreach($result as $oPost) {
				$iPostId = $oPost->getId();
				if(!$oPost) continue;
				if(!$blogId) {
					$oBlog = $oHierarchy->getElement( $oPost->getRel() );
				}
				$sPostLink = $this->umiLinksHelper->getLinkByParts($oPost);
				$sBlogLink = $this->umiLinksHelper->getLinkByParts($oBlog);
				$aLineParam = array();
				$aLineParam['attribute:id'] = $iPostId;
				$aLineParam['attribute:author_id'] = $oPost->getObject()->getOwnerId();
				$aLineParam['name']			= $oPost->getName();
				$aLineParam['post_link']    = $sPostLink;
				$aLineParam['blog_link']    = $sBlogLink;
				$aLineParam['bid']   		= $oBlog->getId();
				$aLineParam['blog_name']    = $oBlog->getName();
				$aLineParam['blog_title']   = $oBlog->getValue('title');
				$aLineParam['title']		= $oPost->getValue('title');
				$aLineParam['cut']			= system_parse_short_calls($this->prepareCut($oPost->getValue('content'), $sPostLink, $template), $iPostId);
				$aLineParam['subnodes:tags'] = $this->prepareTags($oPost->getValue('tags'), $template);
				$aLineParam['comments_count'] = $this->getCommentsCount($iPostId);
					 $aLineParam['publish_time']   = ($t = $oPost->getValue('publish_time')) ? $t->getFormattedDate('U') : '';
				$aLines[] = self::parseTemplate($sTemplateLine, $aLineParam, $iPostId);
				$this->pushEditable("blogs20", "post", $iPostId);
			}

			$aBlockParam 		  = array();
			$aBlockParam['void:lines'] = $aBlockParam['subnodes:items'] = $aLines;
			$aBlockParam['bid']	  = $blogId;
			$aBlockParam['per_page'] = $limit ? $limit : $this->posts_per_page;
			$aBlockParam['total']    = $total;

			return self::parseTemplate($sTemplateBlock, $aBlockParam);
		} else {
			return self::parseTemplate($sTemplateEmpty, array('bid'=>$blogId));
		}
	}
	
	/**
	* Возвращает количество комментариев поста блога
	* @param int $postId id поста
	* @return int
	*/
	private function getCommentsCount($postId) {
		$postId = (int) $postId;
		$sel = new selector('pages');
		$sel->types('object-type')->name('blogs20', 'comment');
		$sel->where('hierarchy')->page($postId)->childs(1000);
		$sel->where('is_spam')->notequals(1);
		$sel->option('return')->value('count');
		return (int) $sel->result();
	}
	
	/**
	* @desc Выводит список постов, содержащих указаный тег
	* @param string $tag Тег
	* @param string $template Имя файла шаблона для вывода
	* @return string|array
	*/
	public function postsByTag($tag = false, $template = 'default', $limit = false) {
		list($sTemplateBlock, $sTemplateLine) = self::loadTemplates('blogs20/'.$template, 'posts_list_block', 'posts_list_line');

		$page = (int)getRequest('p');

		$oBlog = null;

		$oHierarchy = umiHierarchy::getInstance();
		if($tag === false) {
			$tag = ($tmp = getRequest('param0')) ? $tmp : $tag;
		}

		$per_page = $limit ? $limit : $this->posts_per_page;
		$posts = new selector('pages');
		$posts->types('object-type')->name('blogs20', 'post');
		$posts->where('only_for_friends')->notequals(1);
		$posts->where('tags')->equals($tag);
		$posts->order('publish_time')->desc();
		$posts->option('load-all-props')->value(true);
		$posts->limit($page * $per_page, $per_page);
		$result = $posts->result();
		$total  = $posts->length();

		$aLines = array();

		$parentIds = array();
		foreach ($result as $oPost) {
			if (!$oPost instanceof umiHierarchyElement) {
				continue;
			}
			$parentIds[] = $oPost->getParentId();
		}
		$this->loadElements($parentIds);

		foreach($result as $oPost) {
			if (!$oPost instanceof umiHierarchyElement) {
				continue;
			}
			$iPostId    = $oPost->getId();
			$oBlog = $oHierarchy->getElement( $oPost->getRel() );
			$sPostLink = $this->umiLinksHelper->getLinkByParts($oPost);
			$sBlogLink = $this->umiLinksHelper->getLinkByParts($oBlog);
			$aLineParam = array();
			$aLineParam['attribute:id'] = $iPostId;
			$aLineParam['attribute:author_id'] = $oPost->getObject()->getOwnerId();
			$aLineParam['name']			= $oPost->getName();
			$aLineParam['post_link']    = $sPostLink;
			$aLineParam['blog_link']    = $sBlogLink;
			$aLineParam['bid']   		= $oBlog->getId();
			$aLineParam['blog_title']   = $oBlog->getValue('title');
			$aLineParam['blog_name']    = $oBlog->getName();
			$aLineParam['title']		= $oPost->getValue('title');
			$aLineParam['cut']			= $this->prepareCut($oPost->getValue('content'), $sPostLink, $template);
			$aLineParam['subnodes:tags'] = $this->prepareTags($oPost->getValue('tags'));
			$aLineParam['comments_count'] = $oHierarchy->getChildrenCount($iPostId, false);
				$aLineParam['publish_time']   = ($d = $oPost->getValue('publish_time')) ? $d->getFormattedDate('U') : "";
			$aLines[] = self::parseTemplate($sTemplateLine, $aLineParam, $iPostId);
			$this->pushEditable("blogs20", "post", $iPostId);
		}

		$aBlockParam 		  = array();
		$aBlockParam['void:lines'] = $aBlockParam['subnodes:items'] = $aLines;

		$aBlockParam['per_page'] = $limit ? $limit : $this->posts_per_page;
		$aBlockParam['total']    = $total;

		return self::parseTemplate($sTemplateBlock, $aBlockParam);
	}
	/**
	* @desc Выводит список черновиков текущего пользователя
	* @param Int $blogId Идентификатор блога
	* @param String $template Имя файла шаблона для вывода
	* @return string|array
	*/
	public function draughtsList($blogId = false, $template = 'default', $limit = false) {
		list($sTemplateBlock, $sTemplateLine) = self::loadTemplates('blogs20/'.$template, 'posts_list_block', 'posts_list_line');

		$page = (int) getRequest('p');

		$oBlog = null;

		$oHierarchy = umiHierarchy::getInstance();
		if($blogId === false) {
			$iTmp = getRequest('param0');
			if($iTmp) $blogId = (int) $iTmp;
		}

		if($blogId !== false) {
			$oBlog = $oHierarchy->getElement( $blogId );
			if($oBlog instanceof umiHierarchyElement) {
				$this->loadElements($blogId, true, $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'blog'));
			}
		}

		$userId = permissionsCollection::getInstance()->getUserId();
		$per_page = $limit ? $limit : $this->posts_per_page;
		$posts = new selector('pages');
		$posts->types('object-type')->name('blogs20', 'post');
		if ($oBlog instanceof umiHierarchyElement) {
			$posts->where('hierarchy')->page($blogId);
		}
		$posts->where('owner')->equals($userId);
		$posts->where('is_active')->equals(false);
		$posts->order('publish_time')->desc();
		$posts->option('load-all-props')->value(true);
		$posts->limit($page * $per_page, $per_page);
		self::applyTimeRange($posts);

		$result = $posts->result();
		$total  = $posts->length();

		if (!$oBlog) {
			$parentIds = array();
			foreach ($result as $oPost) {
				if (!$oPost instanceof umiHierarchyElement) {
					continue;
				}
				$parentIds[] = $oPost->getParentId();
			}
			$this->loadElements($parentIds);
		}

		$aLines = array();
		foreach($result as $oPost) {
			if (!$oPost instanceof umiHierarchyElement) {
				continue;
			}
			$iPostId = $oPost->getId();
			if(!$oBlog) {
				$oBlog = $oHierarchy->getElement( $oPost->getRel() );
			}
			if(!$oBlog instanceof umiHierarchyElement) {
				continue;
			}
			$sPostLink  = '/blogs20/postView/'.$iPostId.'/';
			$sBlogLink  = $this->umiLinksHelper->getLinkByParts($oBlog);
			$aLineParam = array();
			$aLineParam['attribute:id'] = $iPostId;
			$aLineParam['attribute:author_id'] = $oPost->getObject()->getOwnerId();
			$aLineParam['node:name']    = $oPost->getName();
			$aLineParam['post_link']    = $sPostLink;
			$aLineParam['blog_link']    = $sBlogLink;
			$aLineParam['bid']   		= $oBlog->getId();
			$aLineParam['blog_title']   = $oBlog->getValue('title');
			$aLineParam['title']		= $oPost->getValue('title');
			$aLineParam['content']		= $oPost->getValue('content');
			$aLineParam['cut']			= $this->prepareCut($aLineParam['content'], $sPostLink, $template);
			$aLineParam['subnodes:tags']			= $this->prepareTags($oPost->getValue('tags'));
			$aLineParam['comments_count'] = $oHierarchy->getChildrenCount($iPostId, false);
			$this->pushEditable("blogs20", "post", $iPostId);
			$aLines[] = self::parseTemplate($sTemplateLine, $aLineParam, $iPostId);
		}

		$aBlockParam 		  = array();
		$aBlockParam['void:lines'] = $aBlockParam['subnodes:items'] = $aLines;
		$aBlockParam['bid']	  = $blogId;
		$aBlockParam['per_page'] = $limit ? $limit : $this->posts_per_page;
		$aBlockParam['total']    = $total;

		return self::parseTemplate($sTemplateBlock, $aBlockParam);
	}
	/**
	* @desc Выводит список (дерево) комментариев для поста
	* @param int $postId идентификатор публикации
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	public function commentsList($postId = false, $template = 'default') {
		list($sTemplateBlock, $sTemplateLine) = self::loadTemplates('blogs20/'.$template, 'comments_list_block', 'comments_list_line');
		$total       = 0;
		$aLines 	 = $this->placeComments($postId, $sTemplateLine, $total);
		$aBlockParam = array();
		$aBlockParam['subnodes:items'] = $aBlockParam['void:lines'] = $aLines;
		$aBlockParam['per_page'] = $this->comments_per_page;
		$aBlockParam['total']    = $total;
		return self::parseTemplate($sTemplateBlock, $aBlockParam);
	}
	/**
	* @desc Выводит содержимое публикации
	* @param int $postId Идентификатор публикации
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	public function postView($postId = false, $template = 'default') {
		$userId = permissionsCollection::getInstance()->getUserId();
		if(!$postId) {
			$postId = ($tmp = getRequest('param0')) ? $tmp : $postId;
		}
		if($postId === false) {
			$this->redirect(getServer('HTTP_REFERER'));
		}
		$postId = umiObjectProperty::filterInputString($postId);
		list($sTemplate) = self::loadTemplates('blogs20/'.$template, 'post_view');
		$oHierarchy = umiHierarchy::getInstance();
		$oPost      = $oHierarchy->getElement($postId);
		if(!$oPost) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $postId));
		}
		$this->loadElements(array($postId, $oPost->getRel()));
		if($oPost->getTypeId() != $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post')) {
			throw new publicException("The id(#{$postId}) given is not an id of the blog's post");
		}
		if(!$oPost->getIsActive() && $oPost->getObject()->getOwnerId() != $userId) {
			$this->redirect('/blogs20/draughtsList/');
		}
		$oBlog		= $oHierarchy->getElement($oPost->getRel());
		$sPostLink  = $this->umiLinksHelper->getLinkByParts($oPost);
		$sBlogLink  = $this->umiLinksHelper->getLinkByParts($oBlog);
		$aParams    = array();
		$aParams['name']	   = $oPost->getName();
		$aParams['content']    = $this->prepareContent(system_parse_short_calls($oPost->getValue('content'), $postId) );
		$aParams['pid']        = $postId;
		$aParams['bid']        = $oBlog->getId();
		$aParams['blog_title'] = $oBlog->getValue('title');
		$aParams['blog_name']  = $oBlog->getName();
		$aParams['post_link']  = $sPostLink;
		$aParams['blog_link']  = $sBlogLink;
		$aParams['author_id']  = $oPost->getObject()->getOwnerId();
		$this->pushEditable("blogs20", "post", $postId);
		return self::parseTemplate($sTemplate, $aParams, $postId);
	}
	/**
	* @desc Выводит форму для редактирования публикации и выполняет все действия по сохранению изменений
	* @param int $postId Идентификатор публикации
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	public function postEdit($postId = false, $template = 'default') {
		if(!$postId) {
			$iTmp = getRequest('param0');
			if($iTmp) {
				$postId = $iTmp;
			} else {
				$this->redirect(getServer('HTTP_REFERER'));
			}
		}

		$postId = (int) $postId;

		$oPost = null;
		$oHierarchy   = umiHierarchy::getInstance();
		$oPost        = $oHierarchy->getElement($postId);

		if(!$oPost ||
			 $oPost->getTypeId() != $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post')) {
				throw new publicException("The id(#{$postId}) given is not an id of the blog's post");
		}

		$permissions = permissionsCollection::getInstance();
		$userId = $permissions->getUserId();
		list($read, $edit) = $permissions->isAllowedObject($userId, $postId);
		if (!$edit) {
			throw new publicException(getLabel('error-post-edit'));
		}


		if(getRequest('param1') == 'do') {
			$sTitle   = getRequest('title');
			$sContent = htmlspecialchars(trim(getRequest('content')));
			if(strlen($sTitle) && strlen($sContent)) {
				$iFriendsOnly = getRequest('visible_for_friends') ? 1 : 0;
				$bActivity	  = getRequest('draught') ? false : true;
				$sTags 		  = getRequest('tags');
				$iBlogId      = getRequest('bid');
				if($iBlogId && $iBlogId != $oPost->getRel()) {
					$oHierarchy->moveBefore($postId, $iBlogId);
				}

				if($bActivity) {
					$bActivity = antiSpamHelper::checkContent($sContent.$sTitle.$sTags);
				}

				$oPost->setIsActive($bActivity);
				$oPost->setValue('title', $sTitle);
				$oPost->setValue('content', $sContent);
				$oPost->setValue('tags', $sTags);
				//$oPost->setValue('publish_time', new umiDate());
				$oPost->setValue('only_for_friends', $iFriendsOnly);
				$sRefererUri = getRequest('redirect');
				if(strlen($sRefererUri)) $this->redirect($sRefererUri);
				$this->redirect($oHierarchy->getPathById($postId));
				return null;
			} else {
				// ToDo: display errors
			}
		}
		if(!$oPost) {
			$oPost = $oHierarchy->getElement($postId);
		}
		list($sFormTemplate) = self::loadTemplates('blogs20/'.$template, 'post_edit_form');
		$aParams = array('action'  		=> '/blogs20/postEdit/'.$postId.'/do/',
						 'id' 			=> $postId,
						 'blog_select'  => $this->prepareBlogSelect($oPost->getRel(), true, $template),
						 'visible_for_friends' => (($oPost->getValue('only_for_friends'))? 'checked="checked"' : '' )	);
		return self::parseTemplate($sFormTemplate, $aParams, $postId);
	}
	/**
	* @desc Выводит форму для добавления публикации и выполняет все действия по сохранению
	* @param int $blogId Идентификтор блога, в котором публикуется
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	 public function postAdd($blogId = false, $template = 'default') {
		if($blogId === false) {
			$iTmp = getRequest('param0');
			if($iTmp) $blogId = $iTmp;
			else      $blogId = getRequest('bid');
		}

		$blogId = intval($blogId);

		$permissions = permissionsCollection::getInstance();

		list($canRead, $canWrite) = $permissions->isAllowedObject($permissions->getUserId(), $blogId);
		if(!$permissions->isAuth() || (!$canWrite && $blogId)) {
			return false;
		}
		$umiHierarchy = umiHierarchy::getInstance();
		$sTitle   = htmlspecialchars(trim(getRequest('title')));
		$sContent = htmlspecialchars(trim(getRequest('content')));
		if(strlen($sTitle) && strlen($sContent) && $blogId) {
			if (!umiCaptcha::checkCaptcha()) {
				$this->errorNewMessage("%errors_wrong_captcha%");
				$this->errorPanic();
			}
			if(!($blog = $umiHierarchy->getElement($blogId)) ||
				($blog->getTypeId() != $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'blog')) ) {
				$this->errorNewMessage('%error_wrong_parent%');
				$this->errorPanic();
			}
			$iFriendsOnly = getRequest('visible_for_friends') ? 1 : 0;
			$bActivity	  = getRequest('draught') ? false : true;
			$sTags 		  = getRequest('tags');
			$hierarchy_type_id = $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post');
			$iPostId = $umiHierarchy->addElement($blogId, $hierarchy_type_id, $sTitle, $sTitle);
			permissionsCollection::getInstance()->setDefaultPermissions($iPostId);
			$oPost   = $umiHierarchy->getElement($iPostId, true);

			if($bActivity) {
				$bActivity = antiSpamHelper::checkContent($sContent.$sTitle.$sTags);
			}

			$oPost->setIsActive($bActivity);
			$oPost->setValue('title', $sTitle);
			$oPost->setValue('content', $sContent);
			$oPost->setValue('tags', $sTags);
			$oPost->setValue('publish_time', new umiDate());
			$oPost->setValue('only_for_friends', $iFriendsOnly);
			// Raise Event
			$oEventPoint = new umiEventPoint("blogs20PostAdded");
			$oEventPoint->setMode("after");
			$oEventPoint->setParam("id", $iPostId);
			$oEventPoint->setParam('template', $template);
			$this->setEventPoint($oEventPoint);
			// Redirecting
			$sRefererUri = getServer('HTTP_REFERER');
			if(strlen($sRefererUri)) $this->redirect(str_replace("_err=","",$sRefererUri));
			return null;
		} else {
			if($blogId && !strlen($sTitle) && strlen($sContent)) {
				$this->errorNewMessage('Не заполнен заголовок');
			} else if($blogId && strlen($sTitle) && !strlen($sContent)) {
				$this->errorNewMessage('Не заполнен текст публикации');
			}
		}

		list($sFormTemplate) = self::loadTemplates('blogs20/'.$template, 'post_add_form');
		$aParams = array('action'  => '/blogs20/postAdd/'.$blogId.'/',
						 'id'	   => 'new',
						 'title'   => '',
						 'content' => '',
						 'tags'    => '',
						 'visible_for_friends' => '',
						 'blog_select' => $this->prepareBlogSelect($blogId, false, $template)
						 );
		return self::parseTemplate($sFormTemplate, $aParams);
	}
	/**
	* @desc Выводит форму для добавления комментария и выполняет все действия по сохранению
	* @param int $postId Идентификатор публикации или комментария
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	public function commentAdd($postId = false, $template = 'default') {
		$bNeedFinalPanic = false;
		$permissions = permissionsCollection::getInstance();
		if(!($permissions->isAuth() || $this->regedit->getVal("//modules/blogs20/allow_guest_comments"))) return;
		if ($postId === false) {
			$iTmp = getRequest('param0');
			if($iTmp) $postId = $iTmp;
			else $postId = $this->cmsController->getCurrentElementId();
		}
		$postId = (int) $postId;
		$oHierarchy 	   = umiHierarchy::getInstance();
		if(!($oPost = $oHierarchy->getElement($postId))) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $postId));
		}
		if($oPost->getTypeId() != $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post') &&
			$oPost->getTypeId() != $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'comment')) {
				throw new publicException("The id(#{$postId}) given is not an id of the blog's post");
		}
		$sTitle   = ($tmp = getRequest('title')) ? $tmp : 'Re: '.$oPost->getName();
		$sContent = htmlspecialchars(trim(getRequest('content')));

		if($postId !== false && strlen($sContent) > 0) {
			if (!umiCaptcha::checkCaptcha()) {
				$this->errorNewMessage("%errors_wrong_captcha%");
				$this->errorPanic();
			}

			$hierarchy_type_id = $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'comment');
			$iCommentId = $oHierarchy->addElement($postId, $hierarchy_type_id, $sTitle, $sTitle);

			$permissions->setDefaultPermissions($iCommentId);

			$oUsersModule = $this->cmsController->getModule('users');
			if ($permissions->isAuth()) {
				$userId		= $permissions->getUserId();
				$authorId	= $oUsersModule->createAuthorUser($userId);
				$oActivity	= antiSpamHelper::checkContent($sContent.$sTitle);
			} else {
				$nick  = getRequest('nick');
				$email = getRequest('email');
				$ip    = getServer('REMOTE_ADDR');
				$authorId = $oUsersModule->createAuthorGuest($nick, $email, $ip);
				$oActivity = antiSpamHelper::checkContent($sContent.$sTitle.$nick.$email);
			}
			$oComment = $oHierarchy->getElement($iCommentId, true);
			$is_active = ($this->moderate) ? 0 : 1;
			if($is_active) {
				$is_active = $oActivity;
			}
			if (!$is_active) {
				$this->errorNewMessage('%comments_posted_moderating%', false);
				$bNeedFinalPanic = true;
			}
			$oComment->setIsActive($is_active);

			$oComment->setValue('title',        $sTitle);
			$oComment->setValue('content',      $sContent);
			$oComment->setValue('author_id',    $authorId);
			$oComment->setValue('publish_time', new umiDate());
			$oComment->commit();
			// Raise Event
			$oEventPoint = new umiEventPoint("blogs20CommentAdded");
			$oEventPoint->setMode("after");
			$oEventPoint->setParam("id", $iCommentId);
			$oEventPoint->setParam('template', $template);
			$this->setEventPoint($oEventPoint);
			// Redirecting
			if ($bNeedFinalPanic) {
					$this->errorPanic();
			} else {
				$sRefererUri = $this->cmsController->getCalculatedRefererUri();
				if(strlen($sRefererUri)) {
					$this->redirect($sRefererUri.'#comment_'.$iCommentId);
				}
			}
			return null;
		} else if (!strlen($sContent) && !is_null(getRequest('content'))) {
			$this->errorNewMessage("%errors_missed_field_value%");
			$this->errorPanic();
		}

		$sTplName = $permissions->isAuth() ? 'comment_add_form' : 'comment_add_form_guest';
		list($sFormTemplate) = self::loadTemplates('blogs20/'.$template, $sTplName);
		return self::parseTemplate($sFormTemplate, array('parent_id' => $postId) );
	}
	/**
	* @desc Удаляет публикацию или комментарий
	* @param int $elementId Идентификатор публикации или комментария
	*/
	public function itemDelete($elementId = false) {
		if($elementId === false) {
			$elementId = getRequest('param0');
		}
		if($elementId) {
			try {
				umiHierarchy::getInstance()->delElement($elementId);
			} catch(Exception $e) {
				// Nothing to do; just shut up all exceptions
			}
		}
		$sRedirect = getRequest('redirect');
		if($sRedirect != null) {
			$this->redirect($sRedirect);
		} else {
			$sReferer  = getServer('HTTP_REFERER');
			$this->redirect($sReferer);
		}
	}
	/**
	* @desc Выводит форму редактирования/добавления пользовательских блогов, производит сохранение изменений
	* @param int $blogId Идентификатор редактируемого блога
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	public function editUserBlogs($blogId = false, $template = 'default') {
		if($blogId === false) {
			$iTmp = getRequest('param0');
			if($iTmp) $blogId = $iTmp;
		}
		if ($blogId != 'new') $blogId = (int) $blogId;
		$regedit    = $this->regedit;
		$oHierarchy = umiHierarchy::getInstance();
		$hierarchy_type_id = $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'blog');
		$permissions = permissionsCollection::getInstance();
		$oUsersId = $permissions->getUserId();
		if(intval($blogId)>0 || $blogId == 'new') {
			$aBlogInfo = getRequest('blog');
			$aBlogInfo = $aBlogInfo[$blogId];
			if($aBlogInfo && isset($aBlogInfo['title']) && strlen($aBlogInfo['title'])) {
				$title       = $aBlogInfo['title'];
				$description = isset($aBlogInfo['description']) ? $aBlogInfo['description'] : '';
				$friendlist  = isset($aBlogInfo['friendlist'])  ? array_map('intval', $aBlogInfo['friendlist']) : array();
				if($blogId == 'new') {
					$path     = $regedit->getVal("//modules/blogs20/autocreate_path");
					$parentId = intval($oHierarchy->getIdByPath($path));
					$blogId   = $oHierarchy->addElement($parentId, $hierarchy_type_id, $title, $title);
					$permissions->setDefaultPermissions($blogId);
					$user     = $this->umiObjectsCollection->getObject($oUsersId);
					$groups   = $user->getValue('groups');
					foreach($groups as $id) {
						$permissions->setElementPermissions($id, $blogId, 1);
					}
					$permissions->setElementPermissions($oUsersId, $blogId, 31);
				}
				if($oBlog = $oHierarchy->getElement($blogId)) {
				$oBlog->setIsActive();
				$oBlog->setValue('title', $title);
				$oBlog->setValue('description', $description);
				$oBlog->setValue('friendlist', $friendlist);
				$oBlog->commit();
				}
				$sRedirectURI = getRequest('redirect');
				if($sRedirectURI) $this->redirect($sRedirectURI);
			}
				if($blogId != 'new') $result = array($blogId);
				else                 $result = array();
		} else {
			$sel = new umiSelection;
			$sel->addElementType($hierarchy_type_id);
			$sel->setOrderByName(true);
			$sel->addPermissions();
			$sel->setPermissionsLevel(2);
			$result = umiSelectionsParser::runSelection($sel);
		}
		list($templateBlock, $templateLine, $templateNew) =
			self::loadTemplates('blogs20/'.$template, 'blod_edit_block', 'blog_edit_line', 'blog_new_line');
		$oCollection = umiObjectsCollection::getInstance();
		$userTypeId  = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('users', 'user');
		$aUsers    = $oCollection->getGuidedItems($userTypeId);
		$aLines = array();
		$ownerBlogs = 0;
		foreach($result as $blogId) {
			$oBlog    = $oHierarchy->getElement($blogId);
			if(!$oBlog) continue;
			$aLineParam = array();
			$aLineParam['bid']   	   = $blogId;
			$aLineParam['title']       = $oBlog->getValue('title');
			$aLineParam['description'] = $oBlog->getValue('description');
			$aLineParam['path']		   = $oHierarchy->getPathById($blogId);
			$aFriendList = $oBlog->getValue('friendlist');
			$sOptions    = '';
			foreach($aUsers as $userId => $userName) {
				$sOptions .= '<option value="'.$userId.'" '.( in_array($userId, $aFriendList) ? 'selected' : '' ). '>' . $userName . '</option>';
			}
			$aLineParam['friends'] = $sOptions;
			$aLineParam['current_page'] = getServer('REQUEST_URI');
			$aLines[] = self::parseTemplate($templateLine, $aLineParam);
			if($oBlog->getObject()->getOwnerId() == $oUsersId) $ownerBlogs++;
		}
		if($ownerBlogs < $regedit->getVal("//modules/blogs20/blogs_per_user")) {
			$aLineParam = array('bid'=>'new', 'title'=>'', 'description'=>'');
			$sOptions    = '';
			foreach($aUsers as $userId => $userName) {
				$sOptions .= '<option value="'.$userId.'">' . $userName . '</option>';
			}
			$aLineParam['friends'] = $sOptions;
			$aLineParam['current_page'] = getServer('REQUEST_URI');
			$aLines[] = self::parseTemplate($templateNew, $aLineParam);
		}
		$aBlock = array();
		$aBlock['subnodes:blogs'] = $aBlock['void:lines'] = $aLines;
		return self::parseTemplate($templateBlock, $aBlock);
	}
	/**
	* @desc Выводит список авторов блога
	* @param int $blogId Идентификатор блога
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	public function viewBlogAuthors($blogId = false, $template = 'default') {
		list($sTemplateBlock, $sTemplateLine) = self::loadTemplates('blogs20/'.$template, 'blog_author_list_block', 'blog_author_list_line');
		$oPermissions = permissionsCollection::getInstance();
		$userTypeId  = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('users', 'user');
		$aResult    = array();
		$aXMLResult = array();

		$blog = umiHierarchy::getInstance()->getElement($blogId);

		if (!$blog instanceof umiHierarchyElement) {
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
		$allUsers = array();
		$groupsIds = array();

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
		if(empty($allUsers)) return '1';
		$allUsers = array_unique($allUsers);
		foreach($allUsers as $user) {
			if (!$user instanceof umiObject) {
				continue;
			}
			$userId = $user->getId();
			$aLine   = array();
			$aLine['attribute:user_id'] = $userId;
			$aLine['attribute:login']	= $user->getValue('login');
			$aLine['attribute:fname']   = $user->getValue('fname');
			$aLine['attribute:lname']   = $user->getValue('lname');
			$name  = $user->getValue('fname') . ' ' . $user->getValue('lname');
			$login = $user->getName();
			$aLine['attribute:name']    = strlen(trim($name)) ? $name : $login;
			if ($userId == $blogOwnerId) {
				$aLine['attribute:is_owner'] = '1';
			}
			$aResult[]    = self::parseTemplate($sTemplateLine, $aLine);
			$aXMLResult[] = $aLine;
		}
		if(empty($aResult)) return '';
		$lines = (!empty($aResult) && !is_array($aResult[0])) ? implode(', ', $aResult) : '';
		return self::parseTemplate($sTemplateBlock, array('void:lines' => $lines, 'subnodes:users' => $aXMLResult));
	}
	/**
	* @desc Выводит список друзей блога
	* @param int $blogId Идентификатор блога
	* @param string $template имя файла шаблона
	* @return string|array
	*/
	public function viewBlogFriends($blogId, $template = 'default') {
		if($blogId === false) {
			$iTmp = getRequest('param0');
			if($iTmp) $blogId = $iTmp;
		}
		$blogId = (int) $blogId;
		
		if(!$oUsersModule = $this->cmsController->getModule("users")) {
			throw new publicException("Can't find users module");
		}
		$oBlog = umiHierarchy::getInstance()->getElement($blogId);
		if(!$oBlog) {
			throw new publicException("Incorrect Blog ID");
		}
		$aFriendsList = $oBlog->getValue('friendlist');
		$aResult      = array();
		foreach($aFriendsList as $userId) {
			$aResult[] = $oUsersModule->viewAuthor($userId, 'blogs20');
		}
		return implode(', ', $aResult);
	}
	/**
	* @desc Выводит элементы управления комментарием или публикацией
	* @param int $elementId Идентификатор публикации или комментария
	* @param string $template имя файла шаблона
	* @return string
	*/
	public function placeControls($elementId, $template = 'default') {
		static $bInited = false;
		static $sPostBlock, $sPostDelete, $sPostEdit, $sCommentBlock, $sCommentDelete, $sCommentEdit;
		static $userId  = false;
		static $oHierarchy;
		static $iCommentHTID;
		static $iPostHTID;
		if(!$bInited) {
			list(
				$sPostBlock, $sPostDelete, $sPostEdit, $sCommentBlock, $sCommentDelete, $sCommentEdit
			) = self::loadTemplates('blogs20/'.$template,
				'post_control_block', 'post_control_delete', 'post_control_edit',
				'comment_control_block', 'comment_control_delete', 'comment_control_edit'
			);
			$userId       = permissionsCollection::getInstance()->getUserId();
			$oHierarchy   = umiHierarchy::getInstance();
			$iCommentHTID = umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'comment')->getId();
			$iPostHTID = umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'post')->getId();
			$bInited      = true;
		}
		if($userId === false) return;
		if(!$oElement = $oHierarchy->getElement($elementId, true)) return '';
		$ownerElement = $oElement;
		while($ownerElement->getTypeId() == $iCommentHTID) $ownerElement = $oHierarchy->getElement($ownerElement->getRel(), true);
		if($ownerElement->getObject()->getOwnerId() != $userId) return;
		if($oElement->getTypeId() == $iCommentHTID) {
			$sWrkBlock  = $sCommentBlock;
			$sWrkDelete = self::parseTemplate($sCommentDelete, array('attribute:link' => '/blogs20/itemDelete/'.$elementId.'/'));
			$sWrkEdit   = self::parseTemplate($sCommentEdit,   array('attribute:link' => '/blogs20/commentEdit/'.$elementId.'/'));
		} else if($oElement->getTypeId() == $iPostHTID) {
			$sBlogUri   = $oHierarchy->getPathById( $ownerElement->getRel() );
			$sWrkBlock  = $sPostBlock;
			$sWrkDelete = self::parseTemplate($sPostDelete, array('attribute:link' => '/blogs20/itemDelete/'.$elementId.'/?redirect='.urlencode($sBlogUri)));
			$sWrkEdit   = self::parseTemplate($sPostEdit,   array('attribute:link' => '/blogs20/postEdit/'.$elementId.'/'));
		} else {
			return '';
		}
		// ToDo: Place the buttons according to the module settings

		$line_arr = Array();
		$line_arr['edit'] = $sWrkEdit;
		$line_arr['delete'] = $sWrkDelete;

		$block_arr = Array();
		$block_arr['controls'] = $line_arr;

		return self::parseTemplate($sWrkBlock, $block_arr);

	}
	 
	 public function checkAllowComments() {
		 $umiPermissions = permissionsCollection::getInstance();

		 $systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
		 $guestId = $systemUsersPermissions->getGuestUserId();

		if ($umiPermissions->getUserId() == $guestId) {
			  return regedit::getInstance()->getVal('/modules/blogs20/allow_guest_comments');
		}
		else return 1;
	 }
	/** @desc */
	private function prepareBlogSelect($blogIdCurrent = false, $force = false, $template = 'default') {
		if($blogIdCurrent && !$force) return;
		static $bInited   = false;
		static $sBlock, $sOption;
		static $aBlogList = array();
		if(!$bInited) {
			list($sBlock, $sOption) = self::loadTemplates('blogs20/'.$template, 'blog_choose_block', 'blog_choose_line');
			$sel = new umiSelection;
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("blogs20", "blog")->getId();
			$sel->addElementType($hierarchy_type_id);
			$sel->addPermissions();
			$sel->setPermissionsLevel(2);
			$result 	= umiSelectionsParser::runSelection($sel);
			$oHierarchy = umiHierarchy::getInstance();
			foreach($result as $blogId) {
				$aBlogList[$blogId] = $oHierarchy->getElement($blogId)->getValue('title');
			}
			$bInited = true;
		}
		$aLines = array();
		foreach($aBlogList as $blogId => $blogTitle) {
			$aLines[] = self::parseTemplate($sOption, array('bid'   => $blogId,
															'title' => $blogTitle,
															'selected' => ( $blogId == $blogIdCurrent ? 'selected' : '') ));
		}
		return self::parseTemplate($sBlock, array('subnodes:options'=>$aLines));
	}
	/** @desc */
	private function prepareCut($content, $readLink, $template = 'default') {
		static $sReadAllLink = false;
		$iPos = strpos($content, '[cut]');
		if($iPos === false) {
			return $this->prepareContent($content);
		}
		if($sReadAllLink === false) {
			list($sReadAllLink) = self::loadTemplates('blogs20/'.$template, 'post_cut_link');
		}
		$iPosEnd = strpos($content, '[/cut]');
		if($iPosEnd === false) {
			$iPosEnd = $iPos;
			$iPos    = 0;
		}
		$content = substr($content, $iPos, $iPosEnd-$iPos);
		$content = $this->prepareContent($content);
		$link    = self::parseTemplate($sReadAllLink, array('link'=>$readLink));
		if(!is_array($link)) $content .= $link;
		return $content;
	}
	/** @desc */
	private function prepareTags($Tags, $template = 'default') {
		static $sTemplate = null;
		if($sTemplate == null) {
			list($sTemplate) = self::loadTemplates('blogs20/'.$template, 'tag_decoration');
		}
		$Result = array();
		foreach($Tags as $tag) {
			$Result[] = self::parseTemplate($sTemplate, array('link'=>'/blogs20/postsByTag/'.$tag, 'tag'=>$tag));
		}
		return (!empty($Result) && is_array($Result[0])) ? $Result : implode(', ', $Result);
	}
	/** @desc */
	private function prepareContent($content) {
        $from = array('[b]', '[/b]', '[i]', '[/i]', '[s]', '[/s]', '[u]', '[/u]', '[quote]', '[/quote]', ">\r\n", "\n", '[cut]', '[/cut]');
		$to   = array('<b>', '</b>', '<i>', '</i>', '<span style="text-decoration:line-through;">', '</span>', '<span style="text-decoration:underline;">', '</span>', '<div class="quote">', '</div>', ">", "<br />\n", '', '');
		$content = str_replace($from, $to, $content);
		$content = preg_replace("@\[img\](.+?)\[\/img\]@i", "<img src=\"$1\" alt=\"\" />", $content);
		$content = preg_replace("@\[url\](.+?)\[\/url\]@i", "<a href=\"$1\">[Link]</a>", $content);
		$content = preg_replace("@\[url=(.+?)\]((.|\n)+?)\[\/url\]@i", "<a href=\"$1\" target=\"_blank\">$2</a>", $content);
		$content = preg_replace("@\[code\]((.|\n)+?)\[\/code\]@i", "<tt>$1</tt>", $content);
		$content = preg_replace("@\[color=([A-Za-z0-9#]+?)\]((.|\n)+?)\[\/color\]@i", "<span style=\"color:$1;\">$2</span>", $content);
		$content = preg_replace("@\[smile:([0-9]+?)\]@i", "<img src=\"/images/forum/smiles/$1.gif\" alt=\"$1\">", $content);
		return $content;
	}
	/** @desc */
	private function placeComments($parentId, $templateString, &$total) {
		static $postHType = 0;
		if(!$postHType) {
			$postHType = $this->umiTypesHelper->getHierarchyTypeIdByName('blogs20', 'post');
		}
		$hierarchy = umiHierarchy::getInstance();
		$this->loadElements($parentId);
		$parent = $hierarchy->getElement($parentId, true);
		if(!($parent instanceof umiHierarchyElement)) {
			throw new publicException("Unknown parent element for comments");
		}
		$rootComments = ($parent->getTypeId() == $postHType);
		
		$sel = new selector('pages');
		$sel->types('object-type')->name('blogs20', 'comment');
		$sel->where('hierarchy')->page($parentId);
		$sel->where('is_spam')->notequals(1);
		$sel->option('load-all-props')->value(true);

		if ($rootComments) {
			$page = (int) getRequest('p');
			$sel->limit($page * $this->comments_per_page, $this->comments_per_page);
		}
		
		$result = $sel->result();
		$total  = $sel->length();
		$aLines = array();
		
		foreach($result as $oComment) {
			$commentId = $oComment->getId();
			$temp       = 0;
			$pubTime    = $oComment->getValue('publish_time');
			$aLineParam = array();
			$aLineParam['attribute:cid'] = $commentId;
			$aLineParam['name']			 = $oComment->getName();
			$aLineParam['content'] 		 = $this->prepareContent( $oComment->getValue('content') );
			$aLineParam['author_id']	 = $oComment->getValue('author_id');
			$aLineParam['publish_time']	 = ($pubTime instanceof umiDate) ? $pubTime->getFormattedDate('U') : time() ;
			$aLineParam['subnodes:subcomments']	 = $this->placeComments($commentId, $templateString, $temp);
			$aLines[] = self::parseTemplate($templateString, $aLineParam, $commentId);
		}
		return $aLines;
	}
	/** @desc */
	private static function applyTimeRange($selection, umiObjectType $type = null) {
		$stringFrom = getRequest('from_date');
		$stringTo   = getRequest('to_date');
		
		 if(strlen($stringFrom) && strlen($stringTo)) {
			$arrayFrom = explode('-', $stringFrom);
			$arrayTo   = explode('-', $stringTo);
			$timeFrom = mktime(0, 0, 0,    $arrayFrom[1], $arrayFrom[2], $arrayFrom[0]);
			$timeTo   = mktime(23, 59, 59, $arrayTo[1],   $arrayTo[2],   $arrayTo[0]);
			if ($selection instanceof umiSelection) {
				$selection->addPropertyFilterBetween($type->getFieldId('publish_time'), $timeFrom, $timeTo);
			} elseif ($selection instanceof selector) {
				$selection->where('publish_time')->between($timeFrom, $timeTo);
			}	
		} else if(strlen($stringFrom) && !strlen($stringTo)) {
			$arrayFrom = explode('-', $stringFrom);
			$timeFrom = mktime(0, 0, 0,    $arrayFrom[1], $arrayFrom[2], $arrayFrom[0]);
			if ($selection instanceof umiSelection) {
				$selection->addPropertyFilterMore($type->getFieldId('publish_time'), $timeFrom);
			} elseif ($selection instanceof selector) {
				$selection->where('publish_time')->more($timeFrom);
			}
		} else if(strlen($stringTo)) {
			$arrayTo   = explode('-', $stringTo);
			$timeTo   = mktime(23, 59, 59, $arrayTo[1],   $arrayTo[2],   $arrayTo[0]);
			if ($selection instanceof umiSelection) {
				$selection->addPropertyFilterLess($type->getFieldId('publish_time'), $timeTo);
			} elseif ($selection instanceof selector) {
				$selection->where('publish_time')->less($timeTo);
			}
		}
	}
	
};
?>
