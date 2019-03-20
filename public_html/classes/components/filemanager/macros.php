<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class FileManagerMacros {

		/** @var filemanager $module */
		public $module;

		/**
		 * Возвращает список страниц со скачиваемыми файлами из заданного раздела
		 * @param bool|int|string $element_id идентификатор или адрес корневого раздела
		 * @param string $template имя шаблона (для tpl шаблонизатора)
		 * @param bool|int $per_page количество выводимых элементов на страницу в рамках пагинации
		 * @param bool $ignore_paging игнорировать пагинацию
		 * @param int $depth уровень вложенности, на котором искать страницы со скачиваемыми файлами
		 * @return mixed
		 * @throws selectorException
		 */
		public function list_files(
			$element_id = false,
			$template = 'default',
			$per_page = false,
			$ignore_paging = false,
			$depth = 1
		) {
			if (!$template) {
				$template = 'default';
			}

			$depth = (int) $depth;

			if (!$depth) {
				$depth = 1;
			}

			list(
				$template_block,
				$template_line
				) = filemanager::loadTemplates(
				'filemanager/' . $template,
				'list_files',
				'list_files_row'
			);

			$block_arr = [];

			$element_id = $this->module->analyzeRequiredPath($element_id);

			if (!$per_page) {
				$per_page = $this->module->per_page;
			}

			$curr_page = (int) getRequest('p');

			if ($ignore_paging) {
				$curr_page = 0;
			}

			$sel = new selector('pages');
			$sel->types('object-type')->name('filemanager', 'shared_file');
			$sel->where('hierarchy')->page($element_id)->childs($depth);
			$sel->option('load-all-props')->value(true);
			$sel->limit($curr_page * $per_page, $per_page);

			$result = $sel->result();
			$total = $sel->length();

			$lines = [];
			$umiLinksHelper = umiLinksHelper::getInstance();
			/** @var iUmiHierarchyElement $element */
			foreach ($result as $element) {
				$line_arr = [];

				$next_element_id = $element->getId();

				$line_arr['attribute:id'] = $element->getId();
				$line_arr['attribute:name'] = $element->getName();
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($element);
				$line_arr['attribute:downloads-count'] = $element->getValue('downloads_counter');
				$line_arr['xlink:download-link'] = $this->module->pre_lang . '/filemanager/download/' . $next_element_id;
				$line_arr['xlink:href'] = 'upage://' . $next_element_id;
				$line_arr['node:desc'] = $element->getValue('content');

				filemanager::pushEditable('filemanager', 'shared_file', $next_element_id);
				$lines[] = filemanager::parseTemplate($template_line, $line_arr, $next_element_id);
			}

			$block_arr['nodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['per_page'] = $per_page;
			$block_arr['total'] = $total;

			return filemanager::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает информацию о странице со скачиваемым файлом
		 * @param string $template имя шаблона (для tpl шаблонизатора)
		 * @param bool|int|string $element_path идентификатор или адрес страницы
		 * @return mixed
		 */
		public function shared_file($template = 'default', $element_path = false) {
			if (!$template) {
				$template = 'default';
			}
			list(
				$s_download_file,
				$s_broken_file,
				$s_upload_file
				) = filemanager::loadTemplates(
				'filemanager/' . $template,
				'shared_file',
				'broken_file',
				'upload_file'
			);

			$element_id = $this->module->analyzeRequiredPath($element_path);
			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($element_id);

			$block_arr = [];
			$template_block = $s_broken_file;
			if ($element) {
				$permissionsCollection = permissionsCollection::getInstance();
				$auth = Service::Auth();
				$iUserId = $auth->getUserId();
				list($bAllowRead, $bAllowWrite) = $permissionsCollection->isAllowedObject($iUserId, $element_id);
				$block_arr['upload_file'] = '';
				if ($bAllowWrite) {
					$block_arr['upload_file'] = $s_upload_file;

					if (umiCount($_FILES)) {
						$oUploadedFile = umiFile::upload('shared_files', 'upload', './files/');
						if ($oUploadedFile instanceof umiFile) {
							$element->setValue('fs_file', $oUploadedFile);
							$element->commit();
						}
					}
				}

				$block_arr['id'] = $element_id;
				$block_arr['descr'] = ($descr = $element->getValue('descr')) ? $descr : $element->getValue('content');
				$block_arr['alt_name'] = $element->getAltName();
				$block_arr['link'] = $umiHierarchy->getPathById($element_id);
				$block_arr['download_link'] = '';
				$block_arr['file_name'] = '';
				$block_arr['file_size'] = 0;

				$o_file = $element->getValue('fs_file');

				if ($o_file instanceof umiFile) {
					if (!$o_file->getIsBroken()) {
						$template_block = $s_download_file;
						$block_arr['download_link'] = $this->module->pre_lang . '/filemanager/download/' . $element_id;
						$block_arr['file_name'] = $o_file->getFileName();
						$block_arr['file_size'] = round($o_file->getSize() / 1024, 2);
					}
				}
			} else {
				/** @var users $userModule */
				$cmsController = cmsController::getInstance();
				$userModule = $cmsController->getModule('users');
				return $userModule->auth();
			}

			filemanager::pushEditable('filemanager', 'shared_file', $element_id);
			return filemanager::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Инициирует скачивание файла и инкрементирует счетчик
		 * страницы со скачиваемым файлом
		 * @return mixed
		 */
		public function download() {
			$element_id = getRequest('param0');
			$element = umiHierarchy::getInstance()->getElement($element_id);

			define('DISABLE_STATIC_CACHE', 1);

			if ($element instanceof iUmiHierarchyElement) {
				$o_file = $element->getValue('fs_file');

				if ($o_file instanceof umiFile && !$o_file->getIsBroken()) {
					$i_downloads_counter = (int) $element->getValue('downloads_counter');
					$element->setValue('downloads_counter', ++$i_downloads_counter);
					$element->commit();
					$o_file->download();
				}
			}

			/** @var users $userModule */
			$cmsController = cmsController::getInstance();
			$userModule = $cmsController->getModule('users');
			return $userModule->auth();
		}
	}

