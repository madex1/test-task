<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	abstract class __trash extends baseModuleAdmin {

		/**
		 * Возвращает список страниц, помещенных в корзину
		 * @throws coreException
		 */
		public function trash() {
			/** следующие два параметра нужно брать через getRequest() из-за манипулиции с сессией в этой функции */
			$limit = getRequest('per_page_limit');
			$pageNumber = getRequest('p');

			$request = Service::Request()
				->Get();
			$searchName = getFirstValue($request->get('search-all-text'));
			$domainId = getFirstValue($request->get('domain_id'));
			$languageId = getFirstValue($request->get('lang_id'));

			$totalRef = 0;
			$pageIdList = umiHierarchy::getInstance()
				->getDeletedList($totalRef, $limit, $pageNumber, $searchName, $domainId, $languageId);

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDataRange($limit, $pageNumber * $limit);
			$data = $this->prepareData($pageIdList, 'pages');
			$this->setData($data, $totalRef);
			$this->doData();
		}

		/**
		 * Восстанавливает страницы из корзины
		 * @throws expectElementException
		 */
		public function trash_restore() {
			$hierarchy = umiHierarchy::getInstance();

			foreach ($this->getElementIdList() as $id) {
				$hierarchy->restoreElement($id);
			}

			$this->redirectToModuleIfNeeds();
			$this->setData([]);
			$this->doData();
		}

		/**
		 * Окончательно удаляет выбранные страницы
		 * @throws expectElementException
		 */
		public function trash_del() {
			$hierarchy = umiHierarchy::getInstance();

			foreach ($this->getElementIdList() as $id) {
				$hierarchy->removeDeletedElement($id);
			}

			$this->redirectToModuleIfNeeds();
			$this->setData([]);
			$this->doData();
		}

		/**
		 * Окончательно удаляет 100 страниц.
		 * Используется для итерационной очистки корзины
		 */
		public function trash_empty() {
			$limit = 100;
			$deletedPagesCount = umiHierarchy::getInstance()
				->removeDeletedWithLimit($limit);

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');

			$data = [
				'attribute:complete' => (int) ($deletedPagesCount < $limit),
				'attribute:deleted' => $deletedPagesCount
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'trash',
						'#__name' => 'trash'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'trash',
						'#__name' => 'trash_del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-restore'),
						'module' => 'trash',
						'#__name' => 'trash_restore',
						'aliases' => 'restore_element'
					]
				],
				'default' => 'name[400px]'
			];
		}

		/**
		 * Возвращает список идентификаторов страниц
		 * @return array
		 * @throws expectElementException
		 */
		private function getElementIdList() {
			$elementIdList = $this->expectElementId('param0') ?: getRequest('element');
			return (array) $elementIdList;
		}

		/**
		 * Перенаправляет на главную страницу модуля, если это необходимо
		 * @throws expectElementException
		 */
		private function redirectToModuleIfNeeds() {
			if ($this->isNeedRedirect()) {
				$this->chooseRedirect($this->pre_lang . '/admin/trash/trash/');
			}
		}

		/**
		 * Определяет необходим ли редирект
		 * @return bool
		 * @throws expectElementException
		 */
		private function isNeedRedirect() {
			return !getRequest('element');
		}
	}