<?php
	/** Основной класс административной части модуля "Заметки" */
	abstract class __tickets extends baseModuleAdmin {

		/**
		 * Возвращает данные о заметках
		 * @throws coreException
		 * @throws selectorException
		 */
		public function tickets () {
			$this->setDataType("list");
			$this->setActionType("view");

			if($this->ifNotXmlMode()){
				return $this->doData();
			}

			$limit = getRequest('per_page_limit');
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$tickets = new selector('objects');
			$tickets->types('object-type')->name('content', 'ticket');
			$tickets->limit($offset, $limit);

			selectorHelper::detectFilters($tickets);

			if(isset($_REQUEST['order_filter']['name'])) {
				$_REQUEST['order_filter']['message'] = $_REQUEST['order_filter']['name'];
				unset($_REQUEST['order_filter']['name']);
			}

			$data = $this->prepareData($tickets->result, "objects");

			$this->setData($data, $tickets->length);
			$this->setDataRangeByPerPage($limit, $currentPage);
			return $this->doData();
		}

		/**
		 * Удаляет заметку
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 */
		public function delTicket() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('ticket')
				);

				$this->deleteObject($params);
				$deleteEventPoint = new umiEventPoint('deleteTicket');
				$deleteEventPoint->setMode('after');
				$deleteEventPoint->setParam('id', $objectId);
				$this->setEventPoint($deleteEventPoint);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		/**
		 * Возвращает данные конфигурации административного интерфейса
		 * @param string $param текущий метод модуля
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$loadMethod = 'tickets';
			$deleteMethod = 'delTicket';
			$activityMethod = 'none';
			$ticketType = umiObjectTypesCollection::getInstance()->getTypeByGUID('content-ticket');

			$result = array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'content', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'tickets', '#__name'=>$deleteMethod, 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'content', '#__name'=>$activityMethod, 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'move'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copyElementToSite'))
			);

			if ($ticketType instanceof iUmiObjectType) {
				$result['types'] = array(
					array('common' => 'true', 'id' => $ticketType->getId())
				);
			}

			$result['stoplist'] = array('x', 'y', 'width', 'height');
			$result['default'] = 'name[400px]|url[350px]|user_id[250px]|create_time[250px]';

			return $result;
		}

		/**
		 * Возвращает ссылку на редактирование объектов в административной панели
		 * @param int $objectId ID редактируемого объекта
		 * @param string|bool $type метод типа объекта
		 * @return bool
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return false;
		}
	};
?>
