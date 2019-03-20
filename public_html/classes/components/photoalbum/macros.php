<?php

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class PhotoAlbumMacros {

		/** @var photoalbum|PhotoAlbumMacros $module */
		public $module;

		/**
		 * Возвращает список фотоальбомов
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $limit ограничение на количество выводимых элементов
		 * @param bool $ignore_paging игнорировать пагинацию
		 * @param bool|int $parentElementId идентификатор родителькой страницы,
		 * среди дочерних страниц которой необходимо искать фотоальбомы
		 * @param string $order тип сортировки
		 * @return mixed
		 * @throws ErrorException
		 * @throws coreException
		 * @throws selectorException
		 */
		public function albums(
			$template = 'default',
			$limit = false,
			$ignore_paging = false,
			$parentElementId = false,
			$order = 'asc'
		) {
			list($template_block, $template_block_empty, $template_line) = photoalbum::loadTemplates(
				'photoalbum/' . $template,
				'albums_list_block',
				'albums_list_block_empty',
				'albums_list_block_line'
			);

			$block_arr = [];
			$curr_page = (int) getRequest('p');

			if ($ignore_paging) {
				$curr_page = 0;
			}

			$offset = $limit * $curr_page;

			$sel = new selector('pages');
			$sel->types('object-type')->name('photoalbum', 'album');

			$parentElementId = (int) $this->module->analyzeRequiredPath($parentElementId);

			if ($parentElementId) {
				$sel->where('hierarchy')->page($parentElementId);
			}

			if (in_array($order, ['asc', 'desc', 'rand'])) {
				$sel->order('ord')->$order();
			}

			$sel->option('load-all-props')->value(true);
			$sel->limit($offset, $limit);
			$result = $sel->result();
			$total = $sel->length();

			$lines = [];

			if ($total == 0) {
				return photoalbum::parseTemplate($template_block_empty, $block_arr);
			}

			$umiLinksHelper = umiLinksHelper::getInstance();

			/** @var iUmiHierarchyElement $element */
			foreach ($result as $element) {
				$line_arr = [];

				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$element_id = $element->getId();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($element);
				$line_arr['xlink:href'] = 'upage://' . $element_id;
				$line_arr['node:name'] = $element->getName();

				photoalbum::pushEditable('photoalbum', 'album', $element_id);
				$lines[] = photoalbum::parseTemplate($template_line, $line_arr, $element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $limit;
			return photoalbum::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает фотографии заданного фотоальбома
		 * @param bool|int|string $path идентификатор или адрес фотоальбома
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $limit ограничение на количество выводимых элементов
		 * @param bool $ignore_paging игнорировать пагинацию
		 * @return mixed
		 * @throws ErrorException
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public function album($path = false, $template = 'default', $limit = false, $ignore_paging = false) {
			$curr_page = (int) getRequest('p');
			$per_page = $limit ?: $this->module->per_page;

			$element_id = $this->module->analyzeRequiredPath($path);

			if ($element_id === false && $path != KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			list($template_block, $template_block_empty, $template_line) = photoalbum::loadTemplates(
				'photoalbum/' . $template,
				'album_block',
				'album_block_empty',
				'album_block_line'
			);

			$photos = new selector('pages');
			$photos->types('hierarchy-type')->name('photoalbum', 'photo');

			if ($path != KEYWORD_GRAB_ALL) {
				$photos->where('hierarchy')->page($element_id);
			}

			$photos->option('load-all-props')->value(true);
			$this->module->setAlbumListLimit($photos, $curr_page, $per_page, $ignore_paging);
			$result = $photos->result();
			$total = $photos->length();

			$block_arr = [];
			$block_arr['id'] = $block_arr['void:album_id'] = $element_id;

			if ($total == 0) {
				return photoalbum::parseTemplate($template_block_empty, $block_arr, $element_id);
			}

			$lines = [];
			$umiLinksHelper = umiLinksHelper::getInstance();

			foreach ($result as $photo) {
				$line_arr = [];

				if (!$photo instanceof iUmiHierarchyElement) {
					continue;
				}

				$photo_element_id = $photo->getId();
				$line_arr['attribute:id'] = $photo_element_id;
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($photo);
				$line_arr['xlink:href'] = 'upage://' . $photo_element_id;
				$line_arr['node:name'] = $photo->getName();

				photoalbum::pushEditable('photoalbum', 'photo', $photo_element_id);
				$lines[] = photoalbum::parseTemplate($template_line, $line_arr, $photo_element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['link'] = umiHierarchy::getInstance()->getPathById($element_id);
			return photoalbum::parseTemplate($template_block, $block_arr, $element_id);
		}

		/**
		 * Устанавливает лимит списка альбомов
		 * @param selector $selector выборка альбомов
		 * @param int $currentPage номер текущей страницы
		 * @param int $perPage элементов на страницу
		 * @param bool $ignorePaging игнорировать пагинацию
		 * @throws selectorException
		 */
		public function setAlbumListLimit(selector $selector, $currentPage, $perPage, $ignorePaging) {
			if ($ignorePaging) {
				$currentPage = 0;
			}

			$selector->limit($currentPage * $perPage, $perPage);
		}

		/**
		 * Возвращает информации о фотографии
		 * @param bool|int|string $element_id идентификатор или адрес фотографии
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws ErrorException
		 * @throws coreException
		 * @throws publicException
		 */
		public function photo($element_id = false, $template = 'default') {
			$hierarchy = umiHierarchy::getInstance();
			list($template_block) = photoalbum::loadTemplates(
				'photoalbum/' . $template,
				'photo_block'
			);

			$element_id = $this->module->analyzeRequiredPath($element_id);
			$element = $hierarchy->getElement($element_id);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $element_id));
			}

			photoalbum::pushEditable('photoalbum', 'photo', $element_id);

			return photoalbum::parseTemplate($template_block, [
				'id' => $element->getId(),
				'name' => $element->getName()
			], $element_id);
		}
	}

