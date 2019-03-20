<?php
	/**
	 * Рейтингование страниц
	 *
	 * Реализация возможности голосовать за страницы.
	 * Реализованы 2 системы подсчета рейтинга: +/- и 5-балльная
	 *
	 */
	abstract class __rate_vote {

		/**
		 * Расчет "веса" голоса, в зависимости от системы подсчета рейтинга
		 *
		 * @param int $bid Голос
		 *
		 * @return int Голос, в системе подсчета
		 */
		protected function calculateBid($bid) {
			$bid = (int) $bid;
			if(regedit::getInstance()->getVal("//modules/vote/is_graded")) {
				if ($bid > 5) {
					return 5;
				} elseif ($bid < 1) {
					return 1;
				} else {
					return $bid;
				}
			} else {
				return ($bid > 0) ? 5 : 1;
			}
		}

		/**
		 * Расчет рейтинга
		 *
		 * Основывается на выбраной пользователем системе подсчета рейтинга:
		 * 5-бальной или +1/-1
		 *
		 * @param $rateSum Сумма рейтингов пользователей
		 * @param $rateVoters Количество проголосовавших
		 *
		 * @return float Рейтинг
		 */
		protected function calculateRate($rateSum, $rateVoters) {
			if(regedit::getInstance()->getVal("//modules/vote/is_graded")) {
				$rateVoters = $rateVoters != 0 ? $rateVoters : 1;
				return round($rateSum / $rateVoters, 2);
			} else {
				return ($rateSum - 3*$rateVoters)/2;
			}
		}

		/**
		 * @internal
		 * @param umiHierarchyElement $element
		 *
		 * @return array
		 */
		protected function buildBlockArray($element) {
			$rateVoters = (int) $element->getValue("rate_voters");
			$rateSum = (int) $element->getValue("rate_sum");
			$rate = self::calculateRate($rateSum, $rateVoters);

			return array(
				'@is_graded' => regedit::getInstance()->getVal("//modules/vote/is_graded") ? '1' : '0',
				'request_id' => getRequest('requestId'),
				'element_id' => $element->getId(),
				'rate_sum' => $rateSum,
				'rate_voters' => $rateVoters,
				'rate' => (float) $rate,
				'ceil_rate' => round($rate)
			);
		}

		/**
		 * Проголосовать за страницу
		 *
		 * @param int $elementId Id элемента
		 * @param int $bid Выбранный рейтинг
		 * @param string $template Шаблон(TPL-only)
		 *
		 * @return mixed
		 */
		public function setElementRating($template = "default", $elementId = null, $bid = null) {
			if(!$template) $template = getRequest('param0');
			if(!$elementId) $elementId = (int) getRequest('param1');
			if(!$bid) $bid = (int) getRequest('param2');

			$element = umiHierarchy::getInstance()->getElement($elementId);
			/* Разрешено ли голосовать незарегистрированным пользователям */
			if($isPrivate = (bool) regedit::getInstance()->getVal("//modules/vote/is_private")) {
				/** @var users $usersModule */
				$usersModule = cmsController::getInstance()->getModule("users");
				if(!$usersModule->is_auth()) {
					return def_module::renderTemplate("vote/rate/" . $template, "rate_permitted", array("@permitted"=>"permitted"), $elementId);
				}
			}

			/* Элемент не найден */
			if (!$element) {
				return def_module::renderTemplate("vote/rate/" . $template, "rate_not_found", array(), $elementId);
			}

			/* Уже голосовали */
			if(self::getIsRated($elementId)) {
				return def_module::renderTemplate("vote/rate/" . $template, "rate_rated", self::buildBlockArray($element), $elementId);
			}

			$rateVoters = (int) $element->getValue("rate_voters");
			$rateSum = (int) $element->getValue("rate_sum");
			$rateSum += self::calculateBid($bid);
			$rateVoters++;

			$element->setValue("rate_voters", $rateVoters);
			$element->setValue("rate_sum", $rateSum);
			$element->commit();
			umiHierarchy::getInstance()->unloadElement($elementId);

			self::setIsRated($elementId);

			return def_module::renderTemplate("vote/rate/" . $template, "rate_ok", self::buildBlockArray($element), $elementId);
		}

		/**
		 * Узнать рейтинг страницы
		 *
		 * @param int $elementId Id элемента
		 * @param string $template Шаблон
		 *
		 * @return mixed
		 */
		public function getElementRating($template = "default", $elementId = null) {
			if(!$template) $template = getRequest('param0');
			if(!$elementId) $elementId = (int) getRequest('param1');

			$element = umiHierarchy::getInstance()->getElement($elementId);

			/* Элемент не найден */
			if (!$element) {
				return def_module::renderTemplate("vote/rate/" . $template, "rate_not_found", array(), $elementId);
			}

			$blockArray = self::buildBlockArray($element);
			$blockArray['is_rated'] = self::getIsRated($elementId);

			if(regedit::getInstance()->getVal("//modules/vote/is_graded")) {
				list($ratedTpl, $ratingTpl) = def_module::loadTemplates("vote/rate/" . $template, "rate_rated_graded", "rate_rating_graded");
			} else {
				list($ratedTpl, $ratingTpl) = def_module::loadTemplates("vote/rate/" . $template, "rate_rated", "rate_rating");
			}

			if((bool) regedit::getInstance()->getVal("//modules/vote/is_private")) {
				$blockArray['@permitted'] = "permitted";
				/** @var users $usersModule */
				$usersModule = cmsController::getInstance()->getModule("users");
				if(!$usersModule->is_auth()) {
					return def_module::renderTemplate("vote/rate/" . $template, "rate_rating_permitted", $blockArray, $elementId);
				}
			}

			if ($blockArray['is_rated']) {
				return def_module::parseTemplate($ratedTpl, $blockArray, $elementId);
			} else {
				return def_module::parseTemplate($ratingTpl, $blockArray, $elementId);
			}
		}

		/**
		 * Голосовали ли мы за эту страницу
		 *
		 * @param int $elementId Id элемента
		 *
		 * @return bool true если уже голосовали, иначе false
		 */
		public static function getIsRated($elementId) {
			if((bool) regedit::getInstance()->getVal("//modules/vote/is_private")) {
				/** @var users $usersModule */
				$usersModule = cmsController::getInstance()->getModule("users");
				$user = umiObjectsCollection::getInstance()->getObject($usersModule->user_id);

				$ratedPages = $user->getValue("rated_pages");
				$element = umiHierarchy::getInstance()->getElement($elementId);
			} else {
				$session = \UmiCms\Service::Session();
				$ratedPages = $session->get('rated');
				$element = $elementId;
			}

			return $ratedPages ? in_array($element, $ratedPages) : false;
		}

		/**
		 * Устанавливает флаг, о том что мы голосовали за эту страницу
		 *
		 * @param int $elementId Id элемента
		 */
		public static function setIsRated($elementId) {
			if((bool) regedit::getInstance()->getVal("//modules/vote/is_private")) {
				/** @var users $usersModule */
				$usersModule = cmsController::getInstance()->getModule("users");
				$user = umiObjectsCollection::getInstance()->getObject($usersModule->user_id);

				$element = umiHierarchy::getInstance()->getElement($elementId);

				$ratedPages = $user->getValue("rated_pages");
				$ratedPages[] = $element;

				$user->setValue("rated_pages", $ratedPages);
				$user->commit();
			} else {
				$session = \UmiCms\Service::Session();
				$ratedPages = $session->get('rated');

				$ratedPages = is_array($ratedPages) ? $ratedPages : array();
				$ratedPages[] = $elementId;

				$session->set('rated', $ratedPages);
			}
		}

		/**
		 * @deprecated Переименован
		 * @see __rate_vote::setElementRating
		 *
		 *
		 * @param int $elementId
		 * @param int $bid
		 * @param string $template
		 *
		 * @return mixed
		 */
		public function json_rate($elementId, $bid, $template = "default") {
			return $this->setElementRating($template, $elementId, $bid);
		}

	};
?>