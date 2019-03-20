<?php

	class vote extends def_module {
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__vote");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__vote_custom_admin");
			} else {
				$this->__loadLib("__rate.php");
				$this->__implement("__rate_vote");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_vote");

			$this->__loadLib("__events_handlers.php");
			$this->__implement("__eventsHandlers");

			$this->is_private = intval(regedit::getInstance()->getVal("//modules/vote/is_private"));
		}


		public function poll($path = "", $template = "default") {
			$element_id = $this->analyzeRequiredPath($path);

			$element = umiHierarchy::getInstance()->getElement($element_id);

			if(!$element) return "";

			if($this->checkIsVoted($element->getObjectId())||$element->getValue('is_closed')) {
				return $this->results($element_id, $template);
			} else {
				return $this->insertvote($element_id, $template);
			}
		}


		public function insertvote($path = "", $template = "default") {
			list($template_block, $template_line, $template_submit) = def_module::loadTemplates("vote/".$template, "vote_block", "vote_block_line", "vote_block_submit");
			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();

			$elementId = $this->analyzeRequiredPath($path);

			$element = $hierarchy->getElement($elementId);

			if(!$element) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$block_arr = array(
			'id'	=> $elementId,
			'text'	=> $element->question
			);

			$result = $element->answers;
			if(!is_array($result)) $result = array();
			$this->loadAnswers($result);

			$lines = array();
			foreach($result as $item_id) {
				$item = $objects->getObject($item_id);
				$line_arr = array();
				$line_arr['attribute:id'] = $line_arr['void:item_id'] = $item_id;
				$line_arr['node:item_name'] = $item->name;
				$lines[] = self::parseTemplate($template_line, $line_arr, false, $item_id);
			}


			$is_closed = (bool) $element->getValue("is_closed");
			$block_arr['submit'] = ($is_closed) ? "" : $template_submit;
			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$umiLinkHelper = umiLinksHelper::getInstance();
			$block_arr['link'] = $umiLinkHelper->getLink($element);
			return self::parseTemplate($template_block, $block_arr, $elementId);
		}

		private function loadAnswers($answersIds) {
			if (!$answersIds) {
				return false;
			}
			if (!is_array($answersIds)) {
				$answersIds = array($answersIds);
			}
			if (count($answersIds) == 0) {
				return false;
			}

			$answers = new selector('objects');
			$answers->where('id')->equals($answersIds);
			$answers->option('no-length')->value(true);
			$answers->result();
			return true;
		}


		public function results($path, $template = "default") {
			if(!$template) $template = "default";
			list($template_block, $template_line) = def_module::loadTemplates("vote/".$template, "result_block", "result_block_line");

			$element_id = $this->analyzeRequiredPath($path);

			$element = umiHierarchy::getInstance()->getElement($element_id);
			if(!$element) return false;

			$block_arr = Array();

			$block_arr['id']          = $element_id;
			$block_arr['text']        = $element->getValue("question");
			$block_arr['vote_header'] = $element->getValue("h1");
			$block_arr['alt_name']    = $element->getAltName();
			$result                   = $element->getValue('answers');
			$items = Array();
			$total = 0;
			foreach($result as $item_id) {
				$item = umiObjectsCollection::getInstance()->getObject($item_id);
				$total += (int) $item->getPropByName("count")->getValue();
				$items[] = $item;
			}

			$lines = Array();
			foreach($items as $item) {
				$line_arr = Array();

				$line_arr['node:item_name'] = $item->getName();
				$line_arr['attribute:score'] = $line_arr['void:item_result'] = $c = (int) $item->getValue("count");

				$curr_procs = ($total > 0) ? round((100 * $c) / $total) : 0;
				$line_arr['attribute:score-rel'] = $line_arr['void:item_result_proc'] = $curr_procs;
				$line_arr['void:item_result_proc_reverce'] = 100 - $curr_procs;

				$lines[] = self::parseTemplate($template_line, $line_arr, false, $item->getId());
			}


			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total_posts'] = $total;
			$block_arr['link'] = umiHierarchy::getInstance()->getPathById($element_id);
			return self::parseTemplate($template_block, $block_arr, $element_id);
		}


		public function post($template = "default") {
			if(!$template) $template = getRequest('template');
			if(!$template) $template = "default";

			if (!def_module::isXSLTResultMode()) {
				list($template_block, $template_block_voted, $template_block_closed, $template_block_ok) = def_module::loadTemplates("vote/".$template, "js_block", "js_block_voted", "js_block_closed", "js_block_ok");
			} else {
				list($template_block, $template_block_voted, $template_block_closed, $template_block_ok) = array(false, false, false, false);
			}
			

			$item_id = (int) getRequest('param0');
			$item = umiObjectsCollection::getInstance()->getObject($item_id);

			$referer_url = getServer("HTTP_REFERER") ? getServer("HTTP_REFERER") : "/";
			$this->errorRegisterFailPage($referer_url);

			if (!$item instanceof umiObject) {
				$this->errorNewMessage(getLabel('error-page-does-not-exist', null, ''));
				$this->errorPanic();
			}

			$poll_rel = $item->getValue("poll_rel");
			$object_id = $poll_rel;
			$object = umiObjectsCollection::getInstance()->getObject($object_id);

			if (!$object instanceof umiObject) {
				$this->errorNewMessage(getLabel('error-page-does-not-exist', null, ''));
				$this->errorPanic();
			}

			if($this->checkIsVoted($object_id)) {
				$res = ($template_block_voted) ? $template_block_voted : "Вы уже проголосовали";
			} else {

				if($object->getValue("is_closed")) {
					$res = ($template_block_closed) ? $template_block_closed : "Ошибка. Голосование не активно, либо закрыто.";
				} else {

					$count = $item->getValue("count");
					$item->setValue("count", ++$count);
					$item->setValue("poll_rel", $poll_rel);
					$item->commit();

					if ($this->is_private) {
						$oUsersMdl = cmsController::getInstance()->getModule("users");
						if ($oUsersMdl) {
							$oUser = umiObjectsCollection::getInstance()->getObject($oUsersMdl->user_id);
							if ($oUser instanceof umiObject) {
								$arrRatedPages = $oUser->getValue("rated_pages");
								$arrRatedPagesIds = array();
								foreach ($arrRatedPages as $vVal) {
									if ($vVal instanceof umiHierarchyElement) {
										$arrRatedPagesIds[] = intval($vVal->getId());
									} else {
										$arrRatedPagesIds[] = intval($vVal);
									}
								}

								$arrVotePages = umiHierarchy::getInstance()->getObjectInstances($object_id);
								$arrVotePages = array_map("intval", $arrVotePages);
								$arrRated = array_merge($arrRatedPagesIds, $arrVotePages);
								$oUser->setValue("rated_pages", array_unique($arrRated));
								$oUser->commit();
							}
						}
					}
					
					
								
					$oEventPoint = new umiEventPoint("pollPost");
					$oEventPoint->setMode("after");
					$oEventPoint->setParam("poll", $object);
					$oEventPoint->setParam("answer", $item);
					$this->setEventPoint($oEventPoint);

					$res = ($template_block_ok) ? $template_block_ok : "Ваше мнение учтено";
				}
			}
			$session = \UmiCms\Service::Session();
			$polledVotes = $session->get('vote_polled');
			$polledVotes = (is_array($polledVotes)) ? $polledVotes : [];
			$polledVotes[] = $object_id;
			$session->set('vote_polled', $polledVotes);

			$res = def_module::parseTPLMacroses($res);

			if($template_block) {
				$block_arr = Array();
				$block_arr['res'] = $res;
				$r = $this->parseTemplate($template_block, $block_arr);
				$this->flush($r, "text/javascript");
			} else {
				$this->flush("alert('{$res}');", "text/javascript");
			}
		}


		private function checkIsVoted($object_id) {
			$session = \UmiCms\Service::Session();
			$vote_polled = $session->get('vote_polled');
			if ($this->is_private) {
				$userId = permissionsCollection::getInstance()->getUserId();
				$oUser = umiObjectsCollection::getInstance()->getObject($userId);
				if ($oUser instanceof umiObject) {
					$arrRatedPages = $oUser->getValue("rated_pages");
					$arrRatedPagesIds = array();
					foreach ($arrRatedPages as $vVal) {
						if ($vVal instanceof umiHierarchyElement) {
							$arrRatedPagesIds[] = intval($vVal->getId());
						} else {
							$arrRatedPagesIds[] = intval($vVal);
						}
					}

					$arrVotePages = umiHierarchy::getInstance()->getObjectInstances($object_id);

					$rpages = array();
					foreach ($arrRatedPages as $page) {
						if ($page instanceof umiHierarchyElement) {
							$rpages[] = $page->getId();
						}
					}

					$arrRatedPages = array_map("intval", $rpages);
					$arrVotePages = array_map("intval", $arrVotePages);

					$arrVoted = array_intersect($arrVotePages, $arrRatedPagesIds);

					return (bool) count($arrVoted);
				}
			}

			if(is_array($vote_polled)) {
				return in_array($object_id, $vote_polled);
			} else {
				return false;
			}
		}



		public function insertlast($template = "default") {
			if(!$template) $template = "default";

			$votes = new selector('pages');
			$votes->types('object-type')->name('vote', 'poll');
			$votes->option('no-length')->value(true);
			$votes->order('publish_time')->desc();
			$votes->option('load-all-props')->value(true);
			$votes->option('no-length')->value(true);
			$votes->limit(0, 1);

			if ($votes->first) {
				$element_id = $votes->first->getId();
				return $this->poll($element_id, $template);
			}
			return;
		}

		public function config() {
			return __vote::config();
		}
		
		public function getEditLink($element_id, $element_type) {
			switch($element_type) {
				case "poll": {
					$link_edit = $this->pre_lang . "/admin/vote/edit/{$element_id}/";

					return Array(false, $link_edit);
					break;
				}

				default: {
					return false;
				}
			}
		}

	};
?>
