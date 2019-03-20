<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Блоги".
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Опросы;
	 * 2) Варианты ответов;
	 *
	 * Дополнительно, модуль отвечает за рейтингование страниц.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_oprosy/
	 */
	class vote extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();
			$this->is_private = (int) Service::Registry()->get('//modules/vote/is_private');

			if (Service::Request()->isAdmin()) {
				$this->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('VoteAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('VoteCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('VoteMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('VoteHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('VoteCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}

		/**
		 * Возвращает вес голоса
		 * @param int $bid исходный вес
		 * @return int
		 */
		public function calculateBid($bid) {
			$bid = (int) $bid;

			if (!Service::Registry()->get('//modules/vote/is_graded')) {
				return ($bid > 0) ? 5 : 1;
			}

			switch ($bid) {
				case ($bid > 5) : {
					return 5;
				}
				case ($bid < 1) : {
					return 1;
				}
				default : {
					return $bid;
				}
			}
		}

		/**
		 * Возвращает рейтинг
		 * @param int $rateSum сумма голосов пользователей
		 * @param int $rateVoters количество проголосовавших
		 * @return float
		 */
		public function calculateRate($rateSum, $rateVoters) {
			if (Service::Registry()->get('//modules/vote/is_graded')) {
				$rateVoters = $rateVoters != 0 ? $rateVoters : 1;
				return round($rateSum / $rateVoters, 2);
			}

			return ($rateSum - 3 * $rateVoters) / 2;
		}

		/**
		 * Проголосовал ли текущий пользователь за указанную страницу
		 * @param int $elementId идентификатор страницы
		 * @return bool
		 */
		public function getIsRated($elementId) {
			if (Service::Registry()->get('//modules/vote/is_private')) {
				$user = umiObjectsCollection::getInstance()->getObject(Service::Auth()->getUserId());
				$ratedPages = $user->getValue('rated_pages');
				$element = umiHierarchy::getInstance()->getElement($elementId);
			} else {
				$ratedPages = Service::Session()->get('rated');
				$element = $elementId;
			}

			return $ratedPages ? in_array($element, $ratedPages) : false;
		}

		/**
		 * Помечает указанную страницу, как страницу за которую уже голосовал текущий пользователь
		 * @param int $elementId идентификатор страницы
		 */
		public function setIsRated($elementId) {
			if (Service::Registry()->get('//modules/vote/is_private')) {
				$user = umiObjectsCollection::getInstance()->getObject(Service::Auth()->getUserId());
				$element = umiHierarchy::getInstance()->getElement($elementId);

				$ratedPages = $user->getValue('rated_pages');
				$ratedPages[] = $element;

				$user->setValue('rated_pages', $ratedPages);
				$user->commit();
			} else {
				$session = Service::Session();
				$ratedPages = $session->get('rated');

				$ratedPages = is_array($ratedPages) ? $ratedPages : [];
				$ratedPages[] = $elementId;

				$session->set('rated', $ratedPages);
			}
		}

		/**
		 * Зачисляет опросу голос за вопрос
		 * @param iUmiObject $vote опрос
		 * @param iUmiObject $poll вопрос
		 * @throws coreException
		 */
		public function incrementVote(iUmiObject $vote, iUmiObject $poll) {
			/** @var iUmiObject $poll */
			$count = $poll->getValue('count');
			$poll->setValue('count', ++$count);
			$poll->setValue('poll_rel', $vote);
			$poll->commit();

			if ($this->is_private) {
				$userId = Service::Auth()->getUserId();
				$oUser = umiObjectsCollection::getInstance()->getObject($userId);

				if ($oUser instanceof iUmiObject) {
					$arrRatedPages = $oUser->getValue('rated_pages');
					$arrRatedPagesIds = [];

					foreach ($arrRatedPages as $vVal) {

						if ($vVal instanceof iUmiHierarchyElement) {
							$arrRatedPagesIds[] = (int) $vVal->getId();
						} else {
							$arrRatedPagesIds[] = (int) $vVal;
						}
					}

					$arrVotePages = umiHierarchy::getInstance()->getObjectInstances($vote->getId());
					$arrVotePages = array_map('intval', $arrVotePages);
					$arrRated = array_merge($arrRatedPagesIds, $arrVotePages);
					$oUser->setValue('rated_pages', array_unique($arrRated));
					$oUser->commit();
				}
			}

			$oEventPoint = new umiEventPoint('pollPost');
			$oEventPoint->setMode('after');
			$oEventPoint->setParam('poll', $vote);
			$oEventPoint->setParam('answer', $poll);
			self::setEventPoint($oEventPoint);
		}

		/**
		 * Участвовал ли текущий пользователь в заданном опросе
		 * @param int $object_id идентификатор опроса
		 * @return bool
		 */
		public function checkIsVoted($object_id) {
			$vote_polled = Service::Session()->get('vote_polled');

			if ($this->is_private) {
				$userId = Service::Auth()->getUserId();
				$oUser = umiObjectsCollection::getInstance()->getObject($userId);

				if ($oUser instanceof iUmiObject) {
					$arrRatedPages = $oUser->getValue('rated_pages');
					$arrRatedPagesIds = [];

					foreach ($arrRatedPages as $vVal) {
						if ($vVal instanceof iUmiHierarchyElement) {
							$arrRatedPagesIds[] = (int) $vVal->getId();
						} else {
							$arrRatedPagesIds[] = (int) $vVal;
						}
					}

					$arrVotePages = umiHierarchy::getInstance()->getObjectInstances($object_id);
					$arrVotePages = array_map('intval', $arrVotePages);
					$arrVoted = array_intersect($arrVotePages, $arrRatedPagesIds);

					return (bool) umiCount($arrVoted);
				}
			}

			if (is_array($vote_polled)) {
				return in_array($object_id, $vote_polled);
			}

			return false;
		}

		/**
		 * Возвращает ссылки на страницу редактирование сущности и
		 * страницу добавления дочерней сущности
		 * @param int $element_id идентификатор сущности
		 * @param string $element_type тип сущности
		 * @return array|bool
		 */
		public function getEditLink($element_id, $element_type) {
			switch ($element_type) {
				case 'poll': {
					$link_edit = $this->pre_lang . "/admin/vote/edit/{$element_id}/";
					return [false, $link_edit];
				}
				default: {
					return false;
				}
			}
		}
	}
