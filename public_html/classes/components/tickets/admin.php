<?php

	/** Класс функционала административной панели */
	class TicketsAdmin {

		use baseModuleAdmin;

		/** @var tickets $module */
		public $module;

		/**
		 * Возвращает данные о заметках
		 * @throws coreException
		 * @throws selectorException
		 */
		public function tickets() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$tickets = new selector('objects');
			$tickets->types('object-type')->name('content', 'ticket');
			$tickets->limit($offset, $limit);

			selectorHelper::detectFilters($tickets);

			if (isset($_REQUEST['order_filter']['name'])) {
				$_REQUEST['order_filter']['message'] = $_REQUEST['order_filter']['name'];
				unset($_REQUEST['order_filter']['name']);
			}

			$data = $this->prepareData($tickets->result(), 'objects');

			$this->setData($data, $tickets->length());
			$this->setDataRangeByPerPage($limit, $currentPage);
			$this->doData();
		}

		/**
		 * Удаляет заметку
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 */
		public function delTicket() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = [$objects];
			}

			foreach ($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = [
					'object' => $object,
					'allowed-element-types' => ['ticket']
				];

				$this->deleteObject($params);
				$deleteEventPoint = new umiEventPoint('deleteTicket');
				$deleteEventPoint->setMode('after');
				$deleteEventPoint->setParam('id', $objectId);
				tickets::setEventPoint($deleteEventPoint);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные конфигурации административного интерфейса
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$loadMethod = 'tickets';
			$deleteMethod = 'delTicket';
			$activityMethod = 'none';
			$ticketType = umiObjectTypesCollection::getInstance()->getTypeByGUID('content-ticket');

			$result = [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'content',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'tickets',
						'#__name' => $deleteMethod,
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'content',
						'#__name' => $activityMethod,
						'aliases' => 'tree_set_activity,activity'
					],
					[
						'title' => getLabel('smc-copy'),
						'module' => 'content',
						'#__name' => 'tree_copy_element'
					],
					[
						'title' => getLabel('smc-move'),
						'module' => 'content',
						'#__name' => 'move'
					],
					[
						'title' => getLabel('smc-change-template'),
						'module' => 'content',
						'#__name' => 'change_template'
					],
					[
						'title' => getLabel('smc-change-lang'),
						'module' => 'content',
						'#__name' => 'copyElementToSite'
					]
				]
			];

			/** @var iUmiObjectType $ticketType */
			if ($ticketType instanceof iUmiObjectType) {
				$result['types'] = [
					['common' => 'true', 'id' => $ticketType->getId()]
				];
			}

			$result['stoplist'] = ['x', 'y', 'width', 'height'];
			$result['default'] = 'name[400px]|url[350px]|user_id[250px]|create_time[250px]';

			return $result;
		}
	}

