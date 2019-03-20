<?php

use UmiCms\Service;

/** Класс функционала административной панели */
	class AppointmentAdmin {

		use baseModuleAdmin;

		/** @var [] опции на вкладке настроек уведомлений */
		private $configOptions = [
			'notifications' => [
				'newRecordAdmin' => 'new-record-admin-notify',
				'newRecordUser' => 'new-record-user-notify',
				'statusChangedUser' => 'record-status-changed-user-notify',
			],
		];

		/** @var appointment $module */
		public $module;

		/**
		 * Возвращает заявки на запись
		 * @return bool|void
		 */
		public function orders() {
			$this->setTypes();

			if ($this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$serviceName = 'AppointmentOrders';
			$fieldsKeys = $this->module->getEntityFieldsKeys($serviceName);
			$servicesNames = $this->module->getServicesNames();
			$employeesNames = $this->module->getEmployeesNames();
			$orderStatusesNames = $this->module->getStatuses();
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentOrdersCollection $ordersCollection */
			$ordersCollection = $serviceContainer->get('AppointmentOrders');
			$collectionMap = $ordersCollection->getMap();
			$statusIdKey = $collectionMap->get('STATUS_ID_FIELD_NAME');
			$employeeIdKey = $collectionMap->get('EMPLOYEE_ID_FIELD_NAME');
			$serviceIdKey = $collectionMap->get('SERVICE_ID_FIELD_NAME');
			$dateKey = $collectionMap->get('DATE_FIELD_NAME');
			$timeKey = $collectionMap->get('TIME_FIELD_NAME');
			$filtersKey = 'fields_filter';

			$filtersKeys = (isset($_REQUEST[$filtersKey]) && is_array($_REQUEST[$filtersKey]))
				? array_keys($_REQUEST[$filtersKey])
				: [];

			foreach ($filtersKeys as $fieldKey) {
				if ($fieldKey == $statusIdKey) {
					foreach ($_REQUEST[$filtersKey][$fieldKey] as $key => $value) {
						$statusId = array_search($value, $orderStatusesNames);

						if ($statusId !== false) {
							$_REQUEST[$filtersKey][$fieldKey][$key] = $statusId;
						}
					}
				}

				if ($fieldKey == $employeeIdKey) {
					foreach ($_REQUEST[$filtersKey][$fieldKey] as $key => $value) {
						$employeeId = array_search($value, $employeesNames);

						if ($employeeId !== false) {
							$_REQUEST[$filtersKey][$fieldKey][$key] = $employeeId;
						}
					}
				}

				if ($fieldKey == $serviceIdKey) {
					foreach ($_REQUEST[$filtersKey][$fieldKey] as $key => $value) {
						$serviceId = array_search($value, $servicesNames);

						if ($serviceId !== false) {
							$_REQUEST[$filtersKey][$fieldKey][$key] = $serviceId;
						}
					}
				}

				if ($fieldKey == $dateKey) {
					foreach ($_REQUEST[$filtersKey][$fieldKey] as $key => $value) {
						$date = DateTime::createFromFormat('d.m.Y', $value);
						$timestamp = false;

						if ($date instanceof DateTime) {
							$date->setTime(0, 0);
							$timestamp = $date->getTimestamp();
						}

						if ($timestamp !== false) {
							$_REQUEST[$filtersKey][$fieldKey][$key] = $timestamp;
						}
					}
				}
			}

			$entities = $this->getEntities($serviceName, $fieldsKeys);

			foreach ($entities['data'] as &$entity) {
				if (!is_array($entity)) {
					continue;
				}

				if (isset($entity[$statusIdKey])) {
					$statusId = $entity[$statusIdKey];

					if (isset($orderStatusesNames[$statusId])) {
						$entity[$statusIdKey] = $orderStatusesNames[$statusId];
					} else {
						$entity[$statusIdKey] = getLabel('error-status-not-found', 'appointment');
					}
				}

				if (isset($entity[$employeeIdKey])) {
					$employeeId = $entity[$employeeIdKey];

					if (isset($employeesNames[$employeeId])) {
						$entity[$employeeIdKey] = $employeesNames[$employeeId];
					} else {
						$entity[$employeeIdKey] = getLabel('error-employee-not-found', 'appointment');
					}
				}

				if (isset($entity[$serviceIdKey])) {
					$serviceId = $entity[$serviceIdKey];

					if (isset($servicesNames[$serviceId])) {
						$entity[$serviceIdKey] = $servicesNames[$serviceId];
					} else {
						$entity[$serviceIdKey] = getLabel('error-service-not-found', 'appointment');
					}
				}

				if (isset($entity[$dateKey])) {
					$timeStamp = $entity[$dateKey];
					$date = new umiDate($timeStamp);
					$date = $date->getFormattedDate('d.m.Y');
					$entity[$dateKey] = $date;
				}

				if (isset($entity[$timeKey])) {
					$timeReplacePattern = $this->module->timePregReplacePattern;
					$entity[$timeKey] = preg_replace($timeReplacePattern, '', $entity[$timeKey]);
				}
			}

			$this->module->printJson($entities);
		}

		/**
		 * Возвращает данные для построения формы редактирования заявки на запись.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения.
		 * @throws publicAdminException
		 */
		public function editOrder() {
			$orderId = (string) getRequest('param0');
			$mode = (string) getRequest('param1');
			$serviceName = 'AppointmentOrders';
			$exitMethod = 'orders';
			try {
				$this->edit($orderId, $mode, $serviceName, '', '', $exitMethod);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/** Сохраняет изменение поля заявки на запись и выводит результат в буффер */
		public function saveOrderField() {
			$entityId = (string) getRequest('param0');
			$fieldName = getRequest('field');
			$fieldValue = getRequest('value');
			$serviceName = 'AppointmentOrders';
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentOrdersCollection $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();

			switch ($fieldName) {
				case $collectionMap->get('STATUS_ID_FIELD_NAME') : {
					$statusesNames = $this->module->getStatuses();
					$statusesNames = array_flip($statusesNames);
					$fieldValue = isset($statusesNames[$fieldValue]) ? $statusesNames[$fieldValue] : null;
					break;
				}

				case $collectionMap->get('EMPLOYEE_ID_FIELD_NAME') : {
					$employeesNames = $this->module->getEmployeesNames();
					$employeesNames = array_flip($employeesNames);
					$fieldValue = isset($employeesNames[$fieldValue]) ? $employeesNames[$fieldValue] : null;
					break;
				}

				case $collectionMap->get('DATE_FIELD_NAME') : {
					$date = DateTime::createFromFormat('d.m.Y', $fieldValue);
					$timestamp = time();

					if ($date instanceof DateTime) {
						$date->setTime(0, 0);
						$timestamp = $date->getTimestamp();
					}

					$fieldValue = new umiDate($timestamp);
					break;
				}

				case $collectionMap->get('TIME_FIELD_NAME') : {
					$fieldValue .= $this->module->defaultSeconds;
					break;
				}
			}

			$result = $this->saveField($entityId, $fieldName, $fieldValue, $serviceName);
			$this->module->printJson($result);
		}

		/** Удаляет заявки на запись */
		public function deleteOrder() {
			$this->deleteEntities('AppointmentOrders');
		}

		/**
		 * Вспомогательный метод для удаления сущностей
		 * @param string $serviceName название сервиса
		 */
		protected function deleteEntities($serviceName) {
			$entities = getRequest('element');
			$ids = [];

			if (is_array($entities)) {
				$serviceContainer = ServiceContainerFactory::create();
				/** @var iUmiCollection|iUmiConstantMapInjector $servicesCollection */
				$servicesCollection = $serviceContainer->get($serviceName);
				$serviceMap = $servicesCollection->getMap();
				$idName = $serviceMap->get('ID_FIELD_NAME');

				foreach ($entities as $entity) {
					$ids[] = $entity[$idName];
				}
			} else {
				$ids = [$entities];
			}

			$this->delete($ids, $serviceName);
		}

		/**
		 * Возвращает группы услуг
		 * @return bool|void
		 */
		public function serviceGroups() {
			$this->setTypes();

			if ($this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$serviceName = 'AppointmentServiceGroups';
			$fieldsKeys = $this->module->getEntityFieldsKeys($serviceName);
			$entities = $this->getEntities($serviceName, $fieldsKeys);

			$this->module->printJson($entities);
		}

		/**
		 * Возвращает данные для построения формы создания группы услуг.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do, то добавляет группу.
		 */
		public function addServiceGroup() {
			$mode = (string) getRequest('param0');
			$serviceName = 'AppointmentServiceGroups';
			$editMethod = 'editServiceGroup';
			$validateMethod = 'validateServiceGroup';
			try {
				$this->add($mode, $serviceName, $editMethod, $validateMethod);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Возвращает данные для построения формы редактирования группы услуг.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения группы.
		 * @throws publicAdminException
		 */
		public function editServiceGroup() {
			$orderId = (string) getRequest('param0');
			$mode = (string) getRequest('param1');
			$serviceName = 'AppointmentServiceGroups';
			$validateMethod = 'validateServiceGroup';
			$exitMethod = 'services';
			try {
				$this->edit($orderId, $mode, $serviceName, $validateMethod, '', $exitMethod);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/** Перенаправляет на форму редактирования сущности вкладки "Услуги" */
		public function editServiceEntity() {
			$type = (string) getRequest('type');
			$id = (int) getRequest('param0');

			switch ($type) {
				case 'AppointmentServiceGroup' : {
					$method = 'editServiceGroup';
					break;
				}
				case 'AppointmentService' : {
					$method = 'editService';
					break;
				}
				default : {
					throw new publicAdminException('Wrong type given');
				}
			}

			$this->module->redirect('/admin/appointment/' . $method . '/' . $id);
		}

		/**
		 * Сохраняет изменение поля группы услуг и выводит результат в буффер
		 * @throws publicAdminException
		 */
		public function saveGroupField() {
			$entityId = (string) getRequest('param0');
			$fieldName = getRequest('field');
			$fieldValue = getRequest('value');
			$serviceName = 'AppointmentServiceGroups';
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServiceGroupsCollection $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();

			switch ($fieldName) {
				case $collectionMap->get('NAME_FIELD_NAME') : {
					$this->validateServiceGroup(
						[
							$fieldName => $fieldValue
						]
					);
					break;
				}
			}

			$result = $this->saveField($entityId, $fieldName, $fieldValue, $serviceName);
			$this->module->printJson($result);
		}

		/** Удаляет группы услуг */
		public function deleteServiceGroups() {
			$this->deleteEntities('AppointmentServiceGroups');
		}

		/**
		 * Возвращает услуги и группы услуг
		 * @return bool|void
		 */
		public function services() {
			$this->setTypes();

			if ($this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$serviceGroupId = null;

			if (isset($_REQUEST['rel']) && is_array($_REQUEST['rel']) && umiCount($_REQUEST['rel'])) {
				$serviceGroupId = array_shift($_REQUEST['rel']);
			}

			$filtersKey = 'fields_filter';
			$serviceGroupTaken = !($serviceGroupId === null || $serviceGroupId === '0');

			if (!$serviceGroupTaken && !isset($_REQUEST[$filtersKey]) && !isset($_REQUEST['order_filter'])) {
				return $this->serviceGroups();
			}

			$serviceName = 'AppointmentServices';
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();
			$fieldsKeys = $this->module->getEntityFieldsKeys($serviceName);
			$servicesGroupsNames = $this->module->getServicesGroupsNames();
			$serviceGroupIdKey = $collectionMap->get('GROUP_ID_FIELD_NAME');

			if ($serviceGroupTaken) {
				$_REQUEST[$filtersKey][$serviceGroupIdKey]['eq'] = $serviceGroupId;
			}

			$timeKey = $collectionMap->get('TIME_FIELD_NAME');
			$filtersKeys = (isset($_REQUEST[$filtersKey]) && is_array($_REQUEST[$filtersKey]))
				? array_keys($_REQUEST[$filtersKey])
				: [];

			foreach ($filtersKeys as $fieldKey) {
				if ($fieldKey == $serviceGroupIdKey) {
					foreach ($_REQUEST[$filtersKey][$fieldKey] as $key => $value) {
						$serviceGroupId = array_search($value, $servicesGroupsNames);

						if ($serviceGroupId !== false) {
							$_REQUEST[$filtersKey][$fieldKey][$key] = $serviceGroupId;
						}
					}
				}
			}

			$entities = $this->getEntities($serviceName, $fieldsKeys);

			foreach ($entities['data'] as &$entity) {
				if (!is_array($entity)) {
					continue;
				}

				if (isset($entity[$serviceGroupIdKey])) {
					$serviceGroupId = $entity[$serviceGroupIdKey];

					if (isset($servicesGroupsNames[$serviceGroupId])) {
						$entity[$serviceGroupIdKey] = $servicesGroupsNames[$serviceGroupId];
					} else {
						$entity[$serviceGroupIdKey] = getLabel('error-service-group-not-found', 'appointment');
					}
				}

				if (isset($entity[$timeKey])) {
					$timeReplacePattern = $this->module->timePregReplacePattern;
					$entity[$timeKey] = preg_replace($timeReplacePattern, '', $entity[$timeKey]);
				}
			}

			$this->module->printJson($entities);
		}

		/** Сохраняет значение поля сущности в табличном контроле вкладки "Услуги" */
		public function saveServiceEntityField() {
			$type = (string) getRequest('type');

			switch ($type) {
				case 'AppointmentServiceGroup' : {
					$this->saveGroupField();
					break;
				}
				case 'AppointmentService' : {
					$this->saveServiceField();
					break;
				}
			}
		}

		/**
		 * Возвращает данные для построения формы создания услуги.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do, то создает услугу.
		 */
		public function addService() {
			$mode = isset($_REQUEST['param1']) ? getRequest('param1') : getRequest('param0');
			$serviceName = 'AppointmentServices';
			$editMethod = 'editService';
			$validateMethod = 'validateService';
			try {
				$this->add($mode, $serviceName, $editMethod, $validateMethod);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Возвращает данные для построения формы редактирования услуги.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения услуги.
		 * @throws publicAdminException
		 */
		public function editService() {
			$orderId = (string) getRequest('param0');
			$mode = (string) getRequest('param1');
			$serviceName = 'AppointmentServices';
			$validateMethod = 'validateService';
			$exitMethod = 'services';
			try {
				$this->edit($orderId, $mode, $serviceName, $validateMethod, '', $exitMethod);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Сохраняет изменения поля услуги и выводит результат в буффер
		 * @throws publicAdminException
		 */
		public function saveServiceField() {
			$entityId = (string) getRequest('param0');
			$fieldName = getRequest('field');
			$fieldValue = getRequest('value');
			$serviceName = 'AppointmentServices';
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();

			switch ($fieldName) {
				case $collectionMap->get('GROUP_ID_FIELD_NAME') : {
					$groupsNames = $this->module->getServicesGroupsNames();
					$groupsNames = array_flip($groupsNames);
					$fieldValue = isset($groupsNames[$fieldValue]) ? $groupsNames[$fieldValue] : null;
					break;
				}
				case $collectionMap->get('NAME_FIELD_NAME') : {
					$this->validateService(
						[
							$fieldName => $fieldValue
						]
					);
					break;
				}
				case $collectionMap->get('TIME_FIELD_NAME') : {
					$fieldValue = $fieldValue . $this->module->defaultSeconds;
				}
			}

			$result = $this->saveField($entityId, $fieldName, $fieldValue, $serviceName);
			$this->module->printJson($result);
		}

		/** Изменяет группу у заданных услуг и выводит результат в буффер */
		public function changeServiceGroup() {
			try {
				$mode = (string) getRequest('mode');

				if ($mode !== 'child') {
					throw new publicAdminException('Cannot change group: wrong mode given');
				}

				$serviceGroupData = (array) getRequest('rel');

				if (!isset($serviceGroupData['id']) || !isset($serviceGroupData['type'])) {
					throw new publicAdminException('Cannot change group: wrong group data given');
				}

				$serviceContainer = ServiceContainerFactory::create();
				/** @var iUmiCollection|iUmiConstantMapInjector $serviceGroups */
				$serviceGroups = $serviceContainer->get('AppointmentServiceGroups');

				if ($serviceGroupData['type'] !== $serviceGroups->getCollectionItemClass()) {
					throw new publicAdminException('Cannot change group: incorrect group type given');
				}

				/** @var iUmiCollection|iUmiConstantMapInjector $servicesCollection */
				$servicesCollection = $serviceContainer->get('AppointmentServices');
				$servicesForEditing = (array) getRequest('selected_list');

				foreach ($servicesForEditing as $serviceForEditing) {
					if (!isset($serviceForEditing['id']) || !isset($serviceForEditing['type'])) {
						throw new publicAdminException('Cannot change group: wrong service data given');
					}

					if ($serviceForEditing['type'] !== $servicesCollection->getCollectionItemClass()) {
						throw new publicAdminException('Cannot change group: incorrect service type given');
					}

					$services = $servicesCollection->get([
						$servicesCollection->getMap()->get('ID_FIELD_NAME') => $serviceForEditing['id']
					]);

					/** @var iUmiCollectionItem|iAppointmentService $service */
					$service = array_shift($services);

					if (!$service instanceof iAppointmentService) {
						throw new publicAdminException('Cannot change group: incorrect service id given');
					}

					$service->setGroupId($serviceGroupData['id']);
					$service->commit();
				}

				$result['data']['success'] = true;
			} catch (Exception $e) {
				$result['data']['error'] = $e->getMessage();
			}

			$this->module->printJson($result);
		}

		/** Удаляет услуги */
		public function deleteServices() {
			$this->deleteEntities('AppointmentServices');
		}

		/** Удаляет сущности в табличном контроле вкладки "Услуги" */
		public function deleteServiceEntities() {
			$entities = getRequest('element');

			if (!is_array($entities)) {
				$entities = [$entities];
			}

			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $servicesCollection */
			$servicesCollection = $serviceContainer->get('AppointmentServices');
			$serviceMap = $servicesCollection->getMap();
			$id = $serviceMap->get('ID_FIELD_NAME');
			$type = $serviceMap->get('ENTITY_TYPE_KEY');

			$servicesIds = [];

			foreach ($entities as $entity) {
				if (!isset($entity[$id]) || !isset($entity[$type])) {
					continue;
				}

				if ($entity[$type] == $servicesCollection->getCollectionItemClass()) {
					$servicesIds[] = $entity[$id];
				}
			}

			$this->delete($servicesIds, $servicesCollection->getServiceName());

			/** @var AppointmentServiceGroupsCollection $groupsCollection */
			$groupsCollection = $serviceContainer->get('AppointmentServiceGroups');
			$serviceMap = $groupsCollection->getMap();
			$id = $serviceMap->get('ID_FIELD_NAME');
			$type = $serviceMap->get('ENTITY_TYPE_KEY');

			$serviceGroupsIds = [];

			foreach ($entities as $entity) {
				if (!isset($entity[$id]) || !isset($entity[$type])) {
					continue;
				}

				if ($entity[$type] == $groupsCollection->getCollectionItemClass()) {
					$serviceGroupsIds[] = $entity[$id];
				}
			}

			$this->delete($serviceGroupsIds, $groupsCollection->getServiceName());
		}

		/**
		 * Возвращает сотрудников
		 * @return bool|void
		 */
		public function employees() {
			$this->setTypes();

			if ($this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$serviceName = 'AppointmentEmployees';
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesCollection $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();
			$fieldsKeys = [
				$collectionMap->get('NAME_FIELD_NAME'),
				$collectionMap->get('IMAGE_FIELD_TYPE'),
				$collectionMap->get('DESCRIPTION_FIELD_NAME')
			];

			$entities = $this->getEntities($serviceName, $fieldsKeys);
			$this->module->printJson($entities);
		}

		/**
		 * Возвращает данные для построения формы создания сотрудника.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do, то создает сотрудника.
		 */
		public function addEmployee() {
			$mode = (string) getRequest('param0');
			$serviceName = 'AppointmentEmployees';
			$editMethod = 'editEmployee';
			$validateMethod = 'validateEmployee';
			try {
				$this->add($mode, $serviceName, $editMethod, $validateMethod);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Возвращает данные для построения формы редактирования сотрудника.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения сотрудника.
		 * @throws publicAdminException
		 */
		public function editEmployee() {
			$orderId = (string) getRequest('param0');
			$mode = (string) getRequest('param1');
			$serviceName = 'AppointmentEmployees';
			$validateMethod = 'validateEmployee';
			$afterSaveMethod = 'saveEmployeeRelatedEntities';
			$exitMethod = 'employees';
			try {
				$this->edit($orderId, $mode, $serviceName, $validateMethod, $afterSaveMethod, $exitMethod);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/** Сохраняет изменение поля сотрудника и выводит результат в буффер */
		public function saveEmployeeField() {
			$entityId = (string) getRequest('param0');
			$fieldName = getRequest('field');
			$fieldValue = getRequest('value');
			$serviceName = 'AppointmentEmployees';
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesCollection $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();

			switch ($fieldName) {
				case $collectionMap->get('PHOTO_FIELD_NAME') : {
					$fieldValue = new umiImageFile('.' . $fieldValue);
					break;
				}
			}

			$result = $this->saveField($entityId, $fieldName, $fieldValue, $serviceName);
			$this->module->printJson($result);
		}

		/** Удаляет сотрудников */
		public function deleteEmployees() {
			$this->deleteEntities('AppointmentEmployees');
		}

		/**
		 * Возвращает страницы с данными для виджета записи на прием
		 * @return bool|void
		 * @throws coreException
		 * @throws selectorException
		 */
		public function pages() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode() && $this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('appointment', 'page');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->setDataRangeByPerPage($limit, $currentPage);
			$this->doData();

			return true;
		}

		/**
		 * Возвращает данные для построения формы создания страницы.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то создает страницу.
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function addPage() {
			$type = 'page';
			$inputData = [
				'type' => $type,
				'parent' => null,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => [
					'page'
				]
			];

			if ($this->isSaveMode('param2')) {
				$this->saveAddedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('create');

			$data = $this->prepareData($inputData, 'page');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для построения формы редактрования страницы.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения страницы.
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function editPage() {
			$element = $this->expectElement('param0', true);
			$inputData = [
				'element' => $element,
				'allowed-element-types' => [
					'page'
				]
			];

			if ($this->isSaveMode('param1')) {
				$this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($inputData, 'page');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Изменяет активность страниц
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['page']);
		}

		/**
		 * Удаляет страницы
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$pagesIds = getRequest('element');

			if (!is_array($pagesIds)) {
				$pagesIds = [$pagesIds];
			}

			foreach ($pagesIds as $pagesId) {
				$page = $this->expectElement($pagesId, false, true);

				$params = [
					'element' => $page,
					'allowed-element-types' => [
						'page',
						'appointment'
					]
				];

				$this->deleteElement($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($pagesIds, 'pages');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает рабочие часы сервиса.
		 * Если передан ключевой параметр $_REQUEST['param0'] = 'do',
		 * то сохраняет изменения рабочих часов.
		 * @throws coreException
		 */
		public function serviceWorkingTime() {
			$config = [];
			$umiRegistry = Service::Registry();
			$languageConstantTemplate = 'label-day-%d';
			$fromQueryTemplate = '//modules/appointment/work-time-%d-from';
			$toQueryTemplate = '//modules/appointment/work-time-%d-to';
			$timeValidatePattern = $this->module->timeValidatePattern;
			$schedule = [];

			for ($dayNumber = 0; $dayNumber < 7; $dayNumber++) {
				$timeFrom = (string) $umiRegistry->get(sprintf($fromQueryTemplate, $dayNumber));
				$timeFrom = preg_match($timeValidatePattern, $timeFrom) ? $timeFrom : null;

				$timeTo = (string) $umiRegistry->get(sprintf($toQueryTemplate, $dayNumber));
				$timeTo = preg_match($timeValidatePattern, $timeTo) ? $timeTo : null;

				$schedule[$dayNumber] = [
					'number' => $dayNumber,
					'name' => getLabel(sprintf($languageConstantTemplate, $dayNumber), 'appointment'),
					'from' => $timeFrom,
					'to' => $timeTo,
				];
			}

			$config['appointment-working-time'] = $schedule;

			if ($this->isSaveMode()) {
				$schedules = isset($_REQUEST['data']['schedules']) ? $_REQUEST['data']['schedules'] : [];

				foreach ($schedules as $dayNumber => $formData) {
					$fromTime = null;

					if (isset($formData['from']) && is_string($formData['from']) &&
						preg_match($timeValidatePattern, $formData['from'])) {
						$fromTime = $formData['from'];
					}

					$toTime = null;

					if (isset($formData['to']) && is_string($formData['to']) && preg_match($timeValidatePattern, $formData['to'])) {
						$toTime = $formData['to'];
					}

					$umiRegistry->set(sprintf($fromQueryTemplate, $dayNumber), $fromTime);
					$umiRegistry->set(sprintf($toQueryTemplate, $dayNumber), $toTime);
				}

				$this->chooseRedirect();
			}

			$this->setConfigResult($config);
		}

		/**
		 * Вкладка настроек шаблонов уведомлений модуля
		 * @throws Exception
		 * @throws coreException
		 */
		public function config() {
			$optionsList = $this->configOptions;

			$config = [
				'appointment-notify-options' => [
					"boolean:{$optionsList['notifications']['newRecordAdmin']}" => null,
					"boolean:{$optionsList['notifications']['newRecordUser']}" => null,
					"boolean:{$optionsList['notifications']['statusChangedUser']}" => null
				],
			];

			$umiRegistry = Service::Registry();

			if ($this->isSaveMode()) {
				$userValues = $this->expectParams($config);

				$umiRegistry->set(
					"//modules/appointment/{$optionsList['notifications']['newRecordAdmin']}",
					$userValues['appointment-notify-options']["boolean:{$optionsList['notifications']['newRecordAdmin']}"]
				);

				$umiRegistry->set(
					"//modules/appointment/{$optionsList['notifications']['newRecordUser']}",
					$userValues['appointment-notify-options']["boolean:{$optionsList['notifications']['newRecordUser']}"]
				);

				$umiRegistry->set(
					"//modules/appointment/{$optionsList['notifications']['statusChangedUser']}",
					$userValues['appointment-notify-options']["boolean:{$optionsList['notifications']['statusChangedUser']}"]
				);

				$this->chooseRedirect();
			}

			$params = [
				'appointment-notify-options' => [
					"boolean:{$optionsList['notifications']['newRecordAdmin']}" => $umiRegistry->get(
						"//modules/appointment/{$optionsList['notifications']['newRecordAdmin']}"
					),
					"boolean:{$optionsList['notifications']['newRecordUser']}" => $umiRegistry->get(
						"//modules/appointment/{$optionsList['notifications']['newRecordUser']}"
					),
					"boolean:{$optionsList['notifications']['statusChangedUser']}" => $umiRegistry->get(
						"//modules/appointment/{$optionsList['notifications']['statusChangedUser']}"
					),
				],
			];

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает данные конфигурации административного интерфейса
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$param = ($param === null) ? 'page' : $param;
			switch ($param) {
				case 'page' : {
					return $this->getPageConfig();
				}
			}
		}

		/** Возвращает конфиг вкладки "Услуги" в формате JSON для табличного контрола */
		public function flushServiceDataConfig() {
			$this->module->printJson($this->getServiceConfig());
		}

		/** Возвращает конфиг вкладки "Заявки на запись" в формате JSON для табличного контрола */
		public function flushOrderDataConfig() {
			$this->module->printJson($this->getOrderConfig());
		}

		/** Возвращает конфиг вкладки "Сотрудники" в формате JSON для табличного контрола */
		public function flushEmployeeDataConfig() {
			$this->module->printJson($this->getEmployeeConfig());
		}

		/**
		 * Возвращает настройки для табличного контрола на вкладке "Заявки на запись"
		 * @return array
		 */
		protected function getOrderConfig() {
			$statusesNames = $this->module->getStatuses();
			$servicesNames = $this->module->getServicesNames();
			$employeesNames = $this->module->getEmployeesNames();

			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'appointment',
						'type' => 'load',
						'name' => 'orders'
					],
					[
						'title' => getLabel('js-permissions-edit'),
						'module' => 'appointment',
						'type' => 'edit',
						'name' => 'editOrder'
					],
					[
						'title' => getLabel('js-confirm-unrecoverable-yes'),
						'module' => 'appointment',
						'type' => 'delete',
						'name' => 'deleteOrder'
					],
					[
						'title' => '',
						'module' => 'appointment',
						'type' => 'saveField',
						'name' => 'saveOrderField'
					]
				],
				'default' => 'status_id[150px]|date[140px]|time[110px]|name[200px]|phone[150px]|email[150px]|service_id[220px]|employee_id[220px]',
				'fields' => [
					[
						'name' => 'status_id',
						'title' => getLabel('label-field-order-status', 'appointment'),
						'type' => 'relation',
						'multiple' => 'false',
						'options' => implode(',', $statusesNames),
						'show_edit_page_link' => 'false'
					],
					[
						'name' => 'name',
						'title' => getLabel('label-field-customer-name', 'appointment'),
						'type' => 'string',
						'editable' => 'false'
					],
					[
						'name' => 'phone',
						'title' => getLabel('label-field-customer-phone', 'appointment'),
						'type' => 'string',
						'editable' => 'false'
					],
					[
						'name' => 'email',
						'title' => getLabel('label-field-customer-email', 'appointment'),
						'type' => 'string',
						'editable' => 'false'
					],
					[
						'name' => 'date',
						'title' => getLabel('label-field-order-date', 'appointment'),
						'type' => 'date'
					],
					[
						'name' => 'time',
						'title' => getLabel('label-field-order-time', 'appointment'),
						'type' => 'time'
					],
					[
						'name' => 'comment',
						'title' => getLabel('label-field-order-comment', 'appointment'),
						'type' => 'string',
						'editable' => 'false'
					],
					[
						'name' => 'service_id',
						'title' => getLabel('label-field-order-service', 'appointment'),
						'type' => 'relation',
						'multiple' => 'false',
						'options' => implode(',', $servicesNames),
						'editable' => 'false'
					],
					[
						'name' => 'employee_id',
						'title' => getLabel('label-field-order-employee', 'appointment'),
						'type' => 'relation',
						'multiple' => 'false',
						'options' => implode(',', $employeesNames),
						'editable' => 'false'
					]
				]
			];
		}

		/**
		 * Возвращает настройки для табличного контрола на вкладке "Услуги"
		 * @return array
		 */
		protected function getServiceConfig() {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'appointment',
						'type' => 'load',
						'name' => 'services'
					],
					[
						'title' => getLabel('js-permissions-edit'),
						'module' => 'appointment',
						'type' => 'edit',
						'name' => 'editServiceEntity'
					],
					[
						'title' => getLabel('js-confirm-unrecoverable-yes'),
						'module' => 'appointment',
						'type' => 'delete',
						'name' => 'deleteServiceEntities'
					],
					[
						'title' => getLabel('smc-move'),
						'module' => 'appointment',
						'type' => 'move',
						'name' => 'changeServiceGroup'
					],
					[
						'title' => '',
						'module' => 'appointment',
						'type' => 'saveField',
						'name' => 'saveServiceEntityField'
					]
				],
				'default' => 'name[250]|time[250px]|price[150px]',
				'fields' => [
					[
						'name' => 'name',
						'title' => getLabel('label-field-service-name', 'appointment'),
						'type' => 'string',
					],
					[
						'name' => 'time',
						'title' => getLabel('label-field-service-time', 'appointment'),
						'type' => 'time',
					],
					[
						'name' => 'price',
						'title' => getLabel('label-field-service-price', 'appointment'),
						'type' => 'number',
					],
				]
			];
		}

		/**
		 * Возвращает настройки для табличного контрола на вкладке "Сотрудники"
		 * @return array
		 */
		protected function getEmployeeConfig() {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'appointment',
						'type' => 'load',
						'name' => 'employees'
					],
					[
						'title' => getLabel('js-permissions-edit'),
						'module' => 'appointment',
						'type' => 'edit',
						'name' => 'editEmployee'
					],
					[
						'title' => getLabel('js-confirm-unrecoverable-yes'),
						'module' => 'appointment',
						'type' => 'delete',
						'name' => 'deleteEmployees'
					],
					[
						'title' => getLabel('js-confirm-unrecoverable-yes'),
						'module' => 'appointment',
						'type' => 'saveField',
						'name' => 'saveEmployeeField'
					]
				],
				'default' => 'name[250px]|photo[150px]|description[350px]',
				'fields' => [
					[
						'name' => 'name',
						'title' => getLabel('label-field-employee-name', 'appointment'),
						'type' => 'string',
					],
					[
						'name' => 'photo',
						'title' => getLabel('label-field-employee-photo', 'appointment'),
						'type' => 'image',
						'filterable' => 'false'
					],
					[
						'name' => 'description',
						'title' => getLabel('label-field-employee-description', 'appointment'),
						'type' => 'string',
					]
				]
			];
		}

		/**
		 * Возвращает настройки для табличного контрола на вкладке "Страницы с записью"
		 * @return array
		 * @throws coreException
		 */
		protected function getPageConfig() {
			$umiObjectsTypes = umiObjectTypesCollection::getInstance();
			$typeId = $umiObjectsTypes->getTypeIdByGUID('appointment-pages');
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'appointment',
						'#__name' => 'pages'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'appointment',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'appointment',
						'#__name' => 'activity',
						'aliases' => 'tree_set_activity,activity'
					],
				],
				'types' => [
					[
						'common' => 'true',
						'id' => $typeId
					]
				],
				'stoplist' => [
					'locktime',
					'lockuser',
					'rate_voters',
					'rate_sum'
				],
				'default' => 'name[400px]'
			];
		}

		/**
		 * Запускает изменение сущностей, связанных с сотрудником
		 * @param AppointmentEmployee $employee сотрудник
		 */
		protected function saveEmployeeRelatedEntities(AppointmentEmployee $employee) {
			$this->saveEmployeeSchedules($employee);
			$this->saveEmployeeServices($employee);
		}

		/**
		 * Сохраняет список услуг, которые оказывает сотрудник
		 * @param AppointmentEmployee $employee сотрудник
		 * @throws Exception
		 */
		protected function saveEmployeeServices(AppointmentEmployee $employee) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesServicesCollection $collection */
			$collection = $serviceContainer->get('AppointmentEmployeesServices');
			$collectionMap = $collection->getMap();

			$collection->delete(
				[
					$collectionMap->get('EMPLOYEE_ID_FIELD_NAME') => $employee->getId()
				]
			);

			$services = isset($_REQUEST['data']['services']) ? $_REQUEST['data']['services'] : [];

			foreach ($services as $serviceId) {
				$employeesServiceData = [
					$collectionMap->get('EMPLOYEE_ID_FIELD_NAME') => $employee->getId(),
					$collectionMap->get('SERVICE_ID_FIELD_NAME') => $serviceId
				];

				$collection->create($employeesServiceData);
			}
		}

		/**
		 * Сохраняет рабочие часы сотрудника
		 * @param AppointmentEmployee $employee сотрудник
		 * @throws Exception
		 */
		protected function saveEmployeeSchedules(AppointmentEmployee $employee) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesSchedulesCollection $collection */
			$collection = $serviceContainer->get('AppointmentEmployeesSchedules');
			$collectionMap = $collection->getMap();

			$collection->delete(
				[
					$collectionMap->get('EMPLOYEE_ID_FIELD_NAME') => $employee->getId()
				]
			);

			$schedules = isset($_REQUEST['data']['schedules']) ? $_REQUEST['data']['schedules'] : [];
			$timeValidatePattern = $this->module->timeValidatePattern;

			foreach ($schedules as $dayNumber => $schedule) {
				if (!isset($schedule['from']) || !is_string($schedule['from']) ||
					!preg_match($timeValidatePattern, $schedule['from'])) {
					continue;
				}

				if (!isset($schedule['to']) || !is_string($schedule['to']) || !preg_match($timeValidatePattern, $schedule['to'])) {
					continue;
				}

				$employeeScheduleData = [
					$collectionMap->get('EMPLOYEE_ID_FIELD_NAME') => $employee->getId(),
					$collectionMap->get('DAY_NUMBER_FIELD_NAME') => $dayNumber,
					$collectionMap->get('TIME_START_FIELD_NAME') => $schedule['from'] . $this->module->defaultSeconds,
					$collectionMap->get('TIME_END_FIELD_NAME') => $schedule['to'] . $this->module->defaultSeconds,
				];

				$collection->create($employeeScheduleData);
			}
		}

		protected function validateService(array $formData, AppointmentService $service = null) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $collection */
			$collection = $serviceContainer->get('AppointmentServices');
			$nameKey = $collection->getMap()->get('NAME_FIELD_NAME');
			$newName = isset($formData[$nameKey]) ? $formData[$nameKey] : null;

			if ($service !== null && $service->getName() == $newName) {
				return true;
			}

			$params = [
				$nameKey => $newName,
				$collection->getMap()->get('CALCULATE_ONLY_KEY') => true
			];

			if ($collection->count($params) > 0) {
				throw new publicAdminException(getLabel('error-service-with-name-exist', 'appointment'));
			}
		}

		/**
		 * Валидирует данные формы, которые требуется применить к группе услуг
		 * @param array $formData данные формы
		 * @param AppointmentServiceGroup $group группа услуг
		 * @return bool
		 * @throws Exception
		 * @throws publicAdminException
		 */
		protected function validateServiceGroup(array $formData, AppointmentServiceGroup $group = null) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServiceGroupsCollection $collection */
			$collection = $serviceContainer->get('AppointmentServiceGroups');
			$nameKey = $collection->getMap()->get('NAME_FIELD_NAME');
			$newName = isset($formData[$nameKey]) ? $formData[$nameKey] : null;

			if ($group !== null && $group->getName() == $newName) {
				return true;
			}

			$params = [
				$nameKey => $newName,
				$collection->getMap()->get('CALCULATE_ONLY_KEY') => true
			];

			if ($collection->count($params) > 0) {
				throw new publicAdminException(getLabel('error-service-group-with-name-exist', 'appointment'));
			}
		}

		/**
		 * Валидирует данные формы, которые требуется применить к сотруднику
		 * @param array $formData данные формы
		 * @param AppointmentEmployee $employee сотрудник
		 * @return bool
		 * @throws Exception
		 * @throws publicAdminException
		 */
		protected function validateEmployee(array $formData, AppointmentEmployee $employee = null) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesCollection $collection */
			$collection = $serviceContainer->get('AppointmentEmployees');
			$nameKey = $collection->getMap()->get('NAME_FIELD_NAME');
			$newName = isset($formData[$nameKey]) ? $formData[$nameKey] : null;

			if ($employee !== null && $employee->getName() == $newName) {
				return true;
			}

			$params = [
				$nameKey => $newName,
				$collection->getMap()->get('CALCULATE_ONLY_KEY') => true
			];

			if ($collection->count($params) > 0) {
				throw new publicAdminException(getLabel('error-employee-with-name-exist', 'appointment'));
			}
		}

		/**
		 * Возвращает данные для создания формы добавления сущности модуля.
		 * Если $mode = 'dо', то добавляет сущность.
		 * @param string $mode режим работы
		 * @param string $serviceName имя сервиса, который отвечает за работу с сущностью
		 * @param string $editMethod метод, которым редактируется сущность
		 * @param string $validateMethod метод, которым валидируется сущность
		 * @throws Exception
		 */
		protected function add($mode, $serviceName, $editMethod, $validateMethod = '') {
			$requestData = isset($_REQUEST['data']['new']) ? $_REQUEST['data']['new'] : [];
			$formData = [];

			foreach ($this->module->getEntityFieldsKeys($serviceName) as $fieldKey) {
				$formData[$fieldKey] = isset($requestData[$fieldKey]) ? $requestData[$fieldKey] : null;

				if ($fieldKey == 'time' && is_string($formData[$fieldKey])) {
					$formData[$fieldKey] = $formData[$fieldKey] .= $this->module->defaultSeconds;
				}

				if ($fieldKey == 'photo' && is_string($formData[$fieldKey])) {
					$formData[$fieldKey] = new umiImageFile($formData[$fieldKey]);
				}
			}

			if ($mode == 'do') {
				if (is_callable([$this, $validateMethod])) {
					$this->$validateMethod($formData);
				}

				$serviceContainer = ServiceContainerFactory::create();
				/** @var iUmiCollection $collection */
				$collection = $serviceContainer->get($serviceName);
				/** @var iUmiCollectionItem $entity */
				$entity = $collection->create($formData);

				switch (getRequest('save-mode')) {
					case getLabel('label-save-add-exit'): {
						$this->chooseRedirect();
						break;
					}
					case getLabel('label-save-add'): {
						$this->module->redirect(
							$this->module->pre_lang . '/admin/appointment/' . $editMethod . '/' . $entity->getId()
						);
						break;
					}
				}
			} elseif (is_numeric($mode)) {
				$formData['rel'] = $mode;
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$this->setData($formData);
			$this->doData();
		}

		/**
		 * Удаляет сущности модуля
		 * @param array $entitiesIds список идентификаторов сущностей
		 * @param string $serviceName имя сервиса, который отвечает за работу с сущностями
		 * @throws Exception
		 */
		protected function delete(array $entitiesIds, $serviceName) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var iUmiCollection|iUmiConstantMapInjector $collection */
			$collection = $serviceContainer->get($serviceName);
			$result = [];

			try {
				$collection->delete(
					[
						$collection->getMap()->get('ID_FIELD_NAME') => $entitiesIds
					]
				);
				$result['data']['success'] = true;
			} catch (Exception $e) {
				$result['data']['success'] = $e->getMessage();
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData($result);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования сущности модуля.
		 * Если $mode = 'dо', то сохраняет изменения сущности.
		 * @param int $entityId идентификатор сущности
		 * @param string $mode режим работы
		 * @param string $serviceName имя сервиса, который отвечает за работу с сущностями
		 * @param string $validateMethod метод, которым валидируется сущность
		 * @param string $afterSaveMethod метод, который необходимо вызвать после сохранения сущности
		 * @param string $exitMethod метод, на который нужно перенаправлять пользовать, если он нажал "Сохранить и выйти"
		 * @throws Exception
		 * @throws publicAdminException
		 */
		protected function edit($entityId, $mode, $serviceName, $validateMethod = '', $afterSaveMethod = '', $exitMethod = null) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var iUmiCollection|iUmiConstantMapInjector $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();
			$idFieldKey = $collectionMap->get('ID_FIELD_NAME');

			$orders = $collection->get(
				[
					$idFieldKey => $entityId
				]
			);

			if (umiCount($orders) == 0) {
				throw new publicAdminException(getLabel('error-order-not-found', 'appointment'));
			}

			/** @var iUmiCollectionItem $entity */
			$entity = array_shift($orders);
			$requestData = isset($_REQUEST['data'][$entityId]) ? $_REQUEST['data'][$entityId] : [];
			$timeFieldName = $collectionMap->get('TIME_FIELD_NAME');
			$formData = [];

			foreach ($this->module->getEntityFieldsKeys($serviceName) as $fieldKey) {
				$defaultValue = null;
				switch ($fieldKey) {
					case $collectionMap->get('DATE_FIELD_NAME') : {
						$defaultValue = new umiDate($entity->getValue($fieldKey));

						if (isset($requestData[$fieldKey])) {
							$date = DateTime::createFromFormat($this->module->dateFormat, $requestData[$fieldKey]);
							$timestamp = time();

							if ($date instanceof DateTime) {
								$date->setTime(0, 0);
								$timestamp = $date->getTimestamp();
							}

							$requestData[$fieldKey] = new umiDate($timestamp);
						}

						break;
					}
					case $timeFieldName : {
						$timeReplacePattern = $this->module->timePregReplacePattern;
						$defaultValue = preg_replace($timeReplacePattern, '', $entity->getValue($fieldKey));
						break;
					}
					case $collectionMap->get('PHOTO_FIELD_NAME') : {
						$defaultValue = new umiImageFile($entity->getValue($fieldKey));

						if (isset($requestData[$fieldKey])) {
							$requestData[$fieldKey] = new umiImageFile($requestData[$fieldKey]);
						}
						break;
					}
					default : {
						$defaultValue = $entity->getValue($fieldKey);
					}
				}

				$formData[$fieldKey] = isset($requestData[$fieldKey]) ? $requestData[$fieldKey] : $defaultValue;
			}

			if ($mode == 'do') {
				if (is_callable([$this, $validateMethod])) {
					$this->$validateMethod($formData, $entity);
				}

				if (isset($formData[$timeFieldName])) {
					$formData[$timeFieldName] .= $this->module->defaultSeconds;
				}

				$entity->import($formData);
				$isUpdate = $entity->isUpdated();
				$event = new umiEventPoint('modifyEntity' . $serviceName);
				$event->setParam('entity', $entity);
				$event->setParam('data', $formData);

				if ($isUpdate) {
					$event->setMode('before');
					appointment::setEventPoint($event);
				}

				$entity->commit();

				if ($isUpdate) {
					$event->setMode('after');
					appointment::setEventPoint($event);
				}

				if (is_callable([$this, $afterSaveMethod])) {
					$this->$afterSaveMethod($entity);
				}

				if ($exitMethod !== null && getRequest('save-mode') == getLabel('label-save-exit')) {
					$this->module->redirect($this->module->pre_lang . '/admin/appointment/' . $exitMethod);
				}

				$this->chooseRedirect();
			}

			$formData[$idFieldKey] = $entityId;
			$this->setDataType('form');
			$this->setActionType('modify');
			$this->setData($formData);
			$this->doData();
		}

		/**
		 * Сохраняет изменения поля сущности
		 * @param int $entityId идентификатор сущности
		 * @param string $fieldName идентификатор поля
		 * @param mixed $fieldValue значение поля
		 * @param string $serviceName имя сервиса, который отвечает за работу с сущностями
		 * @return array
		 * @throws Exception
		 */
		protected function saveField($entityId, $fieldName, $fieldValue, $serviceName) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var iUmiCollection|iUmiConstantMapInjector $collection */
			$collection = $serviceContainer->get($serviceName);
			$idFieldKey = $collection->getMap()->get('ID_FIELD_NAME');

			try {
				$entities = $collection->get(
					[
						$idFieldKey => $entityId
					]
				);

				if (umiCount($entities) == 0) {
					throw new Exception(getLabel('error-entity-not-found', 'appointment'));
				}

				/** @var iUmiCollectionItem $entity */
				$entity = array_shift($entities);
				$entity->setValue($fieldName, $fieldValue);
				$isUpdate = $entity->isUpdated();

				$event = new umiEventPoint('modifyEntity' . $serviceName);
				$event->setParam('entity', $entity);
				$event->setParam('data',
					[
						$fieldName => $fieldValue
					]
				);

				if ($isUpdate) {
					$event->setMode('before');
					appointment::setEventPoint($event);
				}

				$entity->commit();

				if ($isUpdate) {
					$event->setMode('after');
					appointment::setEventPoint($event);
				}

				$entity->commit();

				$result['data']['success'] = true;
			} catch (Exception $e) {
				$result['data']['error'] = $e->getMessage();
			}

			return $result;
		}

		/**
		 * Устанавливает настройки вкладки административной панели
		 * @param string $dataType тип данных
		 * @param string $actionType тип действия
		 */
		protected function setTypes($dataType = 'list', $actionType = 'view') {
			$this->setDataType($dataType);
			$this->setActionType($actionType);
		}

		/**
		 * Алиас baseModuleAdmin::getEntitiesForTable()
		 * @param $serviceName
		 * @param array $fieldNames
		 * @return array
		 * @throws Exception
		 */
		protected function getEntities($serviceName, array $fieldNames) {
			return $this->getEntitiesForTable($serviceName, $fieldNames);
		}
	}

