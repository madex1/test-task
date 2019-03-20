<?php

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class SeoMacros implements iModulePart {

		use tModulePart;

		/**
		 * Возвращает ссылку на каноническую страницу для заданной страницы.
		 * Для обычной страницы канонической ссылкой является ссылка на нее, для виртуальной копии -
		 * ссылка на оригинальную страницу.
		 * @param string $templateName имя шаблона (для tpl)
		 * @param int $pageId идентификатор страницы
		 * @return array
		 *
		 * [
		 *        '@link' => string|null
		 * ]
		 */
		public function getRelCanonical($templateName = 'default', $pageId) {
			$umiPages = umiHierarchy::getInstance();
			$page = $umiPages
				->getElement($pageId);

			$originalPage = null;

			if ($page instanceof iUmiHierarchyElement) {
				switch (true) {
					case $page->isOriginal() : {
						$originalPage = $page;
						break;
					}
					default : {
						$originalPage = $umiPages->getOriginalPage($page->getObjectId());
					}
				}
			}

			list($templateBlock, $templateBlockEmpty) = seo::loadTemplates(
				'seo/' . $templateName,
				'template_block',
				'template_block_empty'
			);

			$originalPageLink = null;

			if ($originalPage instanceof iUmiHierarchyElement) {
				$oldForceAbsolutePath = $umiPages->forceAbsolutePath();
				$originalPageLink = $umiPages->getPathById($originalPage->getId());
				$umiPages->forceAbsolutePath($oldForceAbsolutePath);
			}

			$template = ($originalPageLink === null) ? $templateBlockEmpty : $templateBlock;
			$result = [
				'@link' => $originalPageLink
			];

			return seo::parseTemplate($template, $result);
		}
	}
