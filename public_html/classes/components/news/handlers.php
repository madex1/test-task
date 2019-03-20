<?php

	/** Класс обработчиков событий */
	class NewsHandlers {

		/** @var news $module */
		public $module;

		/**
		 * Обработчик события срабатывания системного крона.
		 * Активирует новости с подходящей датой начала активности.
		 * @param iUmiEventPoint $event событие срабатывания системного крона
		 * @throws selectorException
		 */
		public function cronActivateNews(iUmiEventPoint $event) {
			$pages = new selector('pages');
			$pages->types('hierarchy-type')->name('news', 'item');
			$pages->where('is_active')->notequals(true);
			$pages->where('begin_time')->eqless(time());
			$pages->option('no-length')->value(true);

			if (!$pages->first()) {
				return;
			}

			/** @var iUmiHierarchyElement $page */
			foreach ($pages as $page) {
				$page->setIsActive();
				$page->commit();
			}
		}

		/**
		 * Обработчик события срабатывания системного крона.
		 * Импортирует все RSS-фиды.
		 * @param iUmiEventPoint $event событие срабатывания системного крона
		 * @return boolean
		 */
		public function feedsImportListener(iUmiEventPoint $event) {
			$counter = &$event->getRef('counter');
			$buffer = &$event->getRef('buffer');
			$counter++;

			try {
				/** @var NewsFeeds $newsFeeds */
				$newsFeeds = $this->module->getImplementedInstance('NewsFeeds');
			} catch (coreException $e) {
				return false;
			}

			$buffer[__METHOD__] = $newsFeeds->import_feeds();
			return true;
		}

		/**
		 * Ставит у созданной новости в поле "Дата публикации" текущую дату,
		 * если поле было пустым.
		 *
		 * @param iUmiEventPoint $event событие
		 */
		public function setNewsItemPublishTime(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return;
			}

			$newsItem = $event->getRef('element');
			if (!$newsItem instanceof iUmiHierarchyElement) {
				return;
			}

			$this->ensurePublishTime($newsItem);
		}

		/**
		 * Ставит у созданной через EIP новости в поле "Дата публикации" текущую дату,
		 * если поле было пустым.
		 *
		 * @param iUmiEventPoint $event событие
		 */
		public function eipSetNewsItemPublishTime(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return;
			}

			$newsItem = umiHierarchy::getInstance()
				->getElement($event->getParam('elementId'));
			if (!$newsItem instanceof iUmiHierarchyElement) {
				return;
			}

			$this->ensurePublishTime($newsItem);
		}

		/**
		 * Ставит у новости в поле "Дата публикации" текущую дату,
		 * если поле было пустым.
		 *
		 * @param iUmiHierarchyElement $newsItem новость
		 */
		private function ensurePublishTime(iUmiHierarchyElement $newsItem) {
			if (!$newsItem->getValue('publish_time')) {
				$newsItem->setValue('publish_time', time());
				$newsItem->commit();
			}
		}
	}
