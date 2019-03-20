<?php

	/** Класс, содержащий обработчики событий */
	class CommentsHandlers {

		/** @var comments $module */
		public $module;

		/**
		 * Обработчик события добавления комментария с клиентской части.
		 * Запускает проверку комментария на предмет содержания спама.
		 * @param iUmiEventPoint $event событие добавления комментария
		 */
		public function onCommentPost(iUmiEventPoint $event) {
			if ($event->getMode() != 'after') {
				return;
			}

			$commentId = $event->getParam('message_id');
			antiSpamHelper::checkForSpam($commentId);
		}
	}
