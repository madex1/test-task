<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class VoteMacros {

		/** @var vote $module */
		public $module;

		/**
		 * Возвращает данные для создания формы опроса или его результаты,
		 * если посетитель уже голосовал или опрос закрыт.
		 * @param string|int $path адрес опроса или его идентификатор
		 * @param string $template имя шаблона (для tpl)
		 * @return string
		 * @throws publicException
		 */
		public function poll($path = '', $template = 'default') {
			$element_id = $this->module->analyzeRequiredPath($path);
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if (!$element) {
				return '';
			}

			if ($this->module->checkIsVoted($element->getObjectId()) || $element->getValue('is_closed')) {
				return $this->results($element_id, $template);
			}

			return $this->insertvote($element_id, $template);
		}

		/**
		 * Учитывает ответ пользователя на опрос.
		 * Идентификатор ответа берется либо из get-параметра "param0", либо из get-параметра "vote_answer".
		 *
		 * Если был передан параметр "param0", запрос завершается отдачей результата выполнения макроса в буфер.
		 * Если был передан параметр "vote_answer", запрос завершается редиректом на предыдущую страницу.
		 *
		 * @param string $template имя шаблона (для tpl)
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function post($template = 'default') {
			$template = $template ?: getRequest('template') ?: 'default';

			if (vote::isXSLTResultMode()) {
				list(
					$templateBlock,
					$templateBlockVoted,
					$templateBlockClosed,
					$templateBlockOk
					) = [
					false,
					false,
					false,
					false,
				];
			} else {
				list(
					$templateBlock,
					$templateBlockVoted,
					$templateBlockClosed,
					$templateBlockOk
					) = vote::loadTemplates(
					"vote/{$template}",
					'js_block',
					'js_block_voted',
					'js_block_closed',
					'js_block_ok'
				);
			}

			$umiObjects = umiObjectsCollection::getInstance();
			$referrer = getServer('HTTP_REFERER') ?: '/';
			$module = $this->module;
			$module->errorRegisterFailPage($referrer);

			$answerId = (int) (getRequest('param0') ?: getRequest('vote_answer'));
			$answer = $umiObjects->getObject($answerId);
			if (!$answer instanceof iUmiObject) {
				$this->reportError(getLabel('error-page-does-not-exist'));
			}

			$pollId = $answer->getValue('poll_rel');
			$poll = $umiObjects->getObject($pollId);
			if (!$poll instanceof iUmiObject) {
				$this->reportError(getLabel('error-page-does-not-exist'));
			}

			switch (true) {
				case ($module->checkIsVoted($pollId)): {
					$result = $templateBlockVoted ?: getLabel('error-already-voted');
					break;
				}

				case ($poll->getValue('is_closed')): {
					$result = $templateBlockClosed ?: getLabel('error-vote-not-active-or-closed');
					break;
				}

				default: {
					$module->incrementVote($poll, $answer);
					$result = $templateBlockOk ?: getLabel('vote-success');
				}
			}

			$session = Service::Session();
			$polledVotes = $session->get('vote_polled');
			$polledVotes = is_array($polledVotes) ? $polledVotes : [];
			$polledVotes[] = $pollId;
			$session->set('vote_polled', $polledVotes);

			if (getRequest('vote_answer')) {
				Service::Response()
					->getCurrentBuffer()
					->redirect($referrer);
			}

			$this->pollResult($result, $templateBlock);
		}

		/**
		 * Возвращает данные для создания формы опроса
		 * @param string|int $path адрес опроса или его идентификатор
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 */
		public function insertvote($path = '', $template = 'default') {
			list(
				$template_block,
				$template_line,
				$template_submit
				) = vote::loadTemplates(
				'vote/' . $template,
				'vote_block',
				'vote_block_line',
				'vote_block_submit'
			);

			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$elementId = $this->module->analyzeRequiredPath($path);

			$element = $hierarchy->getElement($elementId);

			if (!$element) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$block_arr = [
				'id' => $elementId,
				'text' => $element->getValue('question'),
			];

			$result = $element->getValue('answers');

			if (!is_array($result)) {
				$result = [];
			}

			$this->loadAnswers($result);
			$lines = [];

			foreach ($result as $item_id) {
				$item = $objects->getObject($item_id);
				$line_arr = [];
				$line_arr['attribute:id'] = $line_arr['void:item_id'] = $item_id;
				$line_arr['node:item_name'] = $item->getName();
				$lines[] = vote::parseTemplate($template_line, $line_arr, false, $item_id);
			}

			$is_closed = (bool) $element->getValue('is_closed');
			$block_arr['submit'] = $is_closed ? '' : $template_submit;
			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$umiLinkHelper = umiLinksHelper::getInstance();
			$block_arr['link'] = $umiLinkHelper->getLink($element);

			return vote::parseTemplate($template_block, $block_arr, $elementId);
		}

		/**
		 * Возвращает результаты опроса
		 * @param string|int $path адрес опроса или его идентификатор
		 * @param string $template имя шаблона (для tpl)
		 * @return bool
		 */
		public function results($path, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			list($template_block, $template_line) = vote::loadTemplates(
				'vote/' . $template,
				'result_block',
				'result_block_line'
			);

			$element_id = $this->module->analyzeRequiredPath($path);
			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($element_id);

			if (!$element) {
				return false;
			}

			$block_arr = [];
			$block_arr['id'] = $element_id;
			$block_arr['text'] = $element->getValue('question');
			$block_arr['vote_header'] = $element->getValue('h1');
			$block_arr['alt_name'] = $element->getAltName();
			$result = $element->getValue('answers');
			$items = [];
			$total = 0;
			$umiObjectsCollection = umiObjectsCollection::getInstance();

			foreach ($result as $item_id) {
				$item = $umiObjectsCollection->getObject($item_id);
				$total += (int) $item->getPropByName('count')->getValue();
				$items[] = $item;
			}

			$lines = [];

			/** @var iUmiObject $item */
			foreach ($items as $item) {
				$line_arr = [];
				$line_arr['node:item_name'] = $item->getName();
				$line_arr['attribute:score'] = $line_arr['void:item_result'] = $c = (int) $item->getValue('count');
				$curr_procs = ($total > 0) ? round((100 * $c) / $total) : 0;
				$line_arr['attribute:score-rel'] = $line_arr['void:item_result_proc'] = $curr_procs;
				$line_arr['void:item_result_proc_reverce'] = 100 - $curr_procs;
				$lines[] = vote::parseTemplate($template_line, $line_arr, false, $item->getId());
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total_posts'] = $total;
			$block_arr['link'] = $umiHierarchy->getPathById($element_id);
			return vote::parseTemplate($template_block, $block_arr, $element_id);
		}

		/**
		 * Завершает голосование опроса
		 * @param mixed $result результат опроса
		 * @param bool $templateBlock блок шаблона (для tpl)
		 */
		public function pollResult($result, $templateBlock = false) {
			$result = vote::parseTPLMacroses($result);

			if ($templateBlock) {
				$block_arr = [];
				$block_arr['res'] = $result;
				$r = vote::parseTemplate($templateBlock, $block_arr);
				$this->module->flush($r, 'text/javascript');
			} else {
				$this->module->flush("alert('{$result}');", 'text/javascript');
			}
		}

		/**
		 * Обрабатывает ошибку.
		 * Совершает редирект на referrer, подставляя
		 * в $_GET параметр идентификатор ошибки.
		 * @param string $errorMessage текст ошибки
		 * @param bool $interrupt прерывать выполнение скрипта
		 */
		public function reportError($errorMessage, $interrupt = true) {
			$this->module->errorNewMessage($errorMessage, $interrupt);
		}

		/**
		 * Возвращает последний добавленный опрос
		 * @param string $template имя шаблона (для tpl)
		 * @return string|void
		 * @throws selectorException
		 */
		public function insertlast($template = 'default') {
			if (!$template) {
				$template = 'default';
			}

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
		}

		/**
		 * Начисляет странице рейтинг
		 * @param string $template имя шаблона (для tpl)
		 * @param null|int $elementId идентификатор страницы, за которую голосуют
		 * @param null|int $bid оценка
		 * @return mixed
		 */
		public function setElementRating($template = 'default', $elementId = null, $bid = null) {
			if (!$template) {
				$template = getRequest('param0');
			}

			if (!$elementId) {
				$elementId = (int) getRequest('param1');
			}

			if (!$bid) {
				$bid = (int) getRequest('param2');
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($elementId);

			if (Service::Registry()->get('//modules/vote/is_private') && !Service::Auth()->isAuthorized()) {
				return vote::renderTemplate(
					'vote/rate/' . $template,
					'rate_permitted',
					['@permitted' => 'permitted'],
					$elementId
				);
			}

			if (!$element) {
				return vote::renderTemplate(
					'vote/rate/' . $template,
					'rate_not_found',
					[],
					$elementId
				);
			}

			if ($this->module->getIsRated($elementId)) {
				return vote::renderTemplate(
					'vote/rate/' . $template,
					'rate_rated',
					$this->buildBlockArray($element),
					$elementId
				);
			}

			$rateVoters = (int) $element->getValue('rate_voters');
			$rateSum = (int) $element->getValue('rate_sum');
			$rateSum += $this->module->calculateBid($bid);
			$rateVoters++;

			$element->setValue('rate_voters', $rateVoters);
			$element->setValue('rate_sum', $rateSum);
			$element->commit();
			$umiHierarchy->unloadElement($elementId);

			$this->module->setIsRated($elementId);

			return vote::renderTemplate(
				'vote/rate/' . $template,
				'rate_ok',
				$this->buildBlockArray($element),
				$elementId
			);
		}

		/**
		 * Возвращает рейтинг страницы заданной страницы
		 * @param string $template имя шаблона (для tpl)
		 * @param null|int $elementId идентификатор страницы
		 * @return mixed
		 */
		public function getElementRating($template = 'default', $elementId = null) {
			if (!$template) {
				$template = getRequest('param0');
			}

			if (!$elementId) {
				$elementId = (int) getRequest('param1');
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($elementId);

			if (!$element) {
				return vote::renderTemplate(
					'vote/rate/' . $template,
					'rate_not_found',
					[],
					$elementId
				);
			}

			$blockArray = $this->buildBlockArray($element);
			$blockArray['is_rated'] = $this->module->getIsRated($elementId);
			$umiRegistry = Service::Registry();

			if ($umiRegistry->get('//modules/vote/is_graded')) {
				list($ratedTpl, $ratingTpl) = vote::loadTemplates(
					'vote/rate/' . $template,
					'rate_rated_graded',
					'rate_rating_graded'
				);
			} else {
				list($ratedTpl, $ratingTpl) = vote::loadTemplates(
					'vote/rate/' . $template,
					'rate_rated',
					'rate_rating'
				);
			}

			if ($umiRegistry->get('//modules/vote/is_private')) {
				$blockArray['@permitted'] = 'permitted';
				if (!Service::Auth()->isAuthorized()) {
					return vote::renderTemplate(
						'vote/rate/' . $template,
						'rate_rating_permitted',
						$blockArray,
						$elementId
					);
				}
			}

			if ($blockArray['is_rated']) {
				return vote::parseTemplate($ratedTpl, $blockArray, $elementId);
			}

			return vote::parseTemplate($ratingTpl, $blockArray, $elementId);
		}

		/**
		 * Формирует данные опроса
		 * @param iUmiHierarchyElement $element опрос
		 * @return array
		 */
		private function buildBlockArray(iUmiHierarchyElement $element) {
			/** @var iUmiHierarchyElement $element */
			$rateVoters = (int) $element->getValue('rate_voters');
			$rateSum = (int) $element->getValue('rate_sum');
			$rate = $this->module->calculateRate($rateSum, $rateVoters);
			$umiRegistry = Service::Registry();

			return [
				'@is_graded' => $umiRegistry->get('//modules/vote/is_graded') ? '1' : '0',
				'request_id' => getRequest('requestId'),
				'element_id' => $element->getId(),
				'rate_sum' => $rateSum,
				'rate_voters' => $rateVoters,
				'rate' => (float) $rate,
				'ceil_rate' => round($rate),
			];
		}

		/**
		 * Загружает в память объекты ответов на опрос
		 * @param array $answersIds идентификаторы ответов на опрос
		 * @return bool
		 * @throws selectorException
		 */
		private function loadAnswers($answersIds) {
			if (!$answersIds) {
				return false;
			}

			if (!is_array($answersIds)) {
				$answersIds = [$answersIds];
			}

			if (umiCount($answersIds) == 0) {
				return false;
			}

			$answers = new selector('objects');
			$answers->where('id')->equals($answersIds);
			$answers->option('no-length')->value(true);
			$answers->result();
			return true;
		}
	}
