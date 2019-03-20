<?php

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class AppointmentMacros {

		/** @var appointment|AppointmentMacros $module */
		public $module;

		/**
		 * Алиас @see AppointmentMacros::employeesList()
		 * @param string $template
		 * @param int $limit
		 * @param null $selectedId
		 * @return mixed
		 * @throws Exception
		 */
		public function employees($template = 'default', $limit = 25, $selectedId = null) {
			return $this->module->employeesList($template, $limit, $selectedId);
		}

		/**
		 * Алиас @see AppointmentMacros::employeesListByServiceId()
		 * @param string $template
		 * @param $serviceId
		 * @param int $limit
		 * @param null $selectedId
		 * @return mixed
		 * @throws Exception
		 */
		public function employeesByServiceId($template = 'default', $serviceId, $limit = 25, $selectedId = null) {
			return $this->module->employeesListByServiceId($template, $serviceId, $limit, $selectedId);
		}

		/**
		 * Алиас @see AppointmentMacros::servicesList()
		 * @param string $template
		 * @param int $limit
		 * @param null $selectedId
		 * @return mixed
		 * @throws Exception
		 */
		public function services($template = 'default', $limit = 25, $selectedId = null) {
			return $this->module->servicesList($template, $limit, $selectedId);
		}

		/**
		 * Алиас @see AppointmentMacros::statusesList()
		 * @param string $template
		 * @param null $selectedCode
		 * @return mixed
		 */
		public function statuses($template = 'default', $selectedCode = null) {
			return $this->module->statusesList($template, $selectedCode);
		}

		/**
		 * Алиас @see AppointmentMacros::serviceGroupsList()
		 * @param string $template
		 * @param int $limit
		 * @param null $selectedId
		 * @return mixed
		 * @throws Exception
		 */
		public function serviceGroups($template = 'default', $limit = 25, $selectedId = null) {
			return $this->module->serviceGroupsList($template, $limit, $selectedId);
		}

		/**
		 * Алиас @see AppointmentMacros::employeeSchedulesList()
		 * @param string $template
		 * @param $employeeId
		 * @return mixed
		 * @throws publicAdminException
		 */
		public function employeeSchedules($template = 'default', $employeeId) {
			return $this->module->employeeSchedulesList($template, $employeeId);
		}

		/**
		 * Алиас @see AppointmentMacros::employeeServicesIdsList()
		 * @param string $template
		 * @param $employeeId
		 * @return mixed
		 * @throws publicAdminException
		 */
		public function employeeServicesIds($template = 'default', $employeeId) {
			return $this->module->employeeServicesIdsList($template, $employeeId);
		}

		/** Выводит в буффер полные данные о сервисе записи на прием для построения виджета */
		public function getAppointmentsData() {
			$params = [];
			$module = $this->module;
			$groups = $module->getServiceGroups();

			foreach ($groups as $group) {
				$params['groups'][$group->getId()] = $group->getName();
			}

			$timeReplacePattern = $module->timePregReplacePattern;
			$services = $module->getServices();

			foreach ($services as $service) {
				$categoryName = $params['groups'][$service->getGroupId()];

				$params['service'][$service->getId()] = [
					'id' => $service->getId(),
					'name' => $service->getName(),
					'time' => preg_replace($timeReplacePattern, '', $service->getTime()),
					'cost' => $service->getPrice(),
					'group_id' => $service->getGroupId(),
				];

				$params['scats'][$categoryName][] = $service->getId();
			}

			$employees = $module->getEmployees();

			foreach ($employees as $employee) {
				$params['personal'][$employee->getId()] = [
					'id' => $employee->getId(),
					'name' => $employee->getName(),
					'photo' => $employee->getPhoto(),
					'description' => $employee->getDescription(),
				];
			}

			$employeesServices = $module->getEmployeesServices();

			foreach ($employeesServices as $employeeService) {
				$employeeId = $employeeService->getEmployeeId();
				$serviceId = $employeeService->getServiceId();

				$params['personal'][$employeeId]['service'][] = $serviceId;
				$params['service'][$serviceId]['personal'][] = $employeeId;
			}

			$employeesSchedules = $module->getEmployeesSchedules();

			foreach ($employeesSchedules as $employeeSchedule) {
				$timeStart = preg_replace($timeReplacePattern, '', $employeeSchedule->getTimeStart());
				$timeEnd = preg_replace($timeReplacePattern, '', $employeeSchedule->getTimeEnd());
				$time = $timeStart . '-' . $timeEnd;
				$params['personal'][$employeeSchedule->getEmployeeId()]['days'][$employeeSchedule->getDayNumber()] = $time;
			}

			$defaultSchedule = $module->getDefaultSchedule();

			if (is_array($defaultSchedule) && umiCount($defaultSchedule) > 0) {
				$params['default']['days'] = $defaultSchedule;
			}

			$bookedOrders = $module->getBookedOrders();
			$entry = [];

			foreach ($bookedOrders as $order) {
				$orderProps = [
					'time' => preg_replace($timeReplacePattern, '', $order->getTime()),
					'status' => $order->getStatusId(),
					'service' => $order->getServiceId(),
					'personal' => $order->getEmployeeId()
				];

				$timeStamp = $order->getDate();
				$orderDate = new umiDate($timeStamp);
				$orderDate = $orderDate->getFormattedDate('d.m.Y');

				$entry['personal'][$order->getEmployeeId()][$orderDate][] = $orderProps;
				$employeesCount = umiCount($params['service'][$order->getServiceId()]['personal']);
				$counter = 0;

				foreach ($params['service'][$order->getServiceId()]['personal'] as $employeeId) {
					if (isset($entry['personal'][$employeeId][$orderDate])) {
						$counter++;
					}
				}

				if ($employeesCount == $counter) {
					$entry['service'][$order->getServiceId()][$orderDate][] = $orderProps;
					$entry['complete_booked'][$orderDate][] = $orderProps;
				}
			}

			$params['entry'] = $entry;
			$module->printJson($params);
		}

		/**
		 * Воводит буффер сообщение об ошибке
		 * @param string $userMessage собщение для пользователя
		 * @param null|mixed $debugMessage служебная информация
		 */
		public function printError($userMessage, $debugMessage = null) {
			$result = [
				'error' => true,
				'text' => $userMessage,
				'more' => $debugMessage
			];

			$this->module->printJson($result);
		}

		/**
		 * Создает заявку на запись
		 * @throws Exception
		 */
		public function postAppointment() {
			/** @var appointment|AppointmentMacros $module */
			$module = $this->module;
			$date = getRequest('date');
			$time = getRequest('time');

			$timestamp = strtotime($date);
			$entryName = $date . ' ' . $time;

			$email = getRequest('email');
			$phone = getRequest('phone');
			$name = getRequest('name');
			$comment = getRequest('commentary');

			if (!empty($name)) {
				$entryName = $name;
			}

			if (!umiMail::checkEmail($email)) {
				$this->printError(getLabel('error-incorrect-email', 'appointment'));
			}

			if (empty($email) && empty($phone)) {
				$this->printError(getLabel('error-email-and-phone-empty', 'appointment'));
			}

			$email = empty($email) ? null : $email;
			$phone = empty($phone) ? null : $phone;
			$fullService = getRequest('full_service');
			$fullPersonal = getRequest('full_personal');
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentOrdersCollection $ordersCollection */
			$ordersCollection = $serviceContainer->get('AppointmentOrders');
			$collectionMap = $ordersCollection->getMap();

			$statusId = $collectionMap->get('ORDER_STATUS_NOT_CONFIRMED');
			$serviceId = $fullService['id'];
			$employeeId = $fullPersonal['id'];

			if ($employeeId === '*') {
				try {
					$employeeId = $module->getRandomEmployeeIdByServiceId($serviceId);
				} catch (Exception $e) {
					$employeeId = null;
				}
			}

			try {
				$orderData = [
					$collectionMap->get('SERVICE_ID_FIELD_NAME') => $serviceId,
					$collectionMap->get('EMPLOYEE_ID_FIELD_NAME') => $employeeId,
					$collectionMap->get('ORDER_DATE_FIELD_NAME') => new umiDate(),
					$collectionMap->get('DATE_FIELD_NAME') => new umiDate($timestamp),
					$collectionMap->get('TIME_FIELD_NAME') => $time . $this->module->defaultSeconds,
					$collectionMap->get('PHONE_FIELD_NAME') => $phone,
					$collectionMap->get('EMAIL_FIELD_NAME') => $email,
					$collectionMap->get('NAME_FIELD_NAME') => $entryName,
					$collectionMap->get('COMMENT_FIELD_NAME') => $comment,
					$collectionMap->get('STATUS_ID_FIELD_NAME') => $statusId
				];

				$order = $ordersCollection->create($orderData);
			} catch (Exception $e) {
				$order = null;
				$this->printError(getLabel('error-incorrect-data-given', 'appointment'), $e->getMessage());
			}

			$result = [
				'error' => false,
				'text' => getLabel('appointments-success-entry', 'appointment')
			];

			$event = new umiEventPoint('addAppointmentOrder');
			$event->setParam('order', $order);
			appointment::setEventPoint($event);

			$module->printJson($result);
		}

		/**
		 * Возвращает список сотрудник
		 * @param string $template имя шаблона (для tpl)
		 * @param int $limit ограничение на количество выводимых сотрудников
		 * @param null|int $selectedId идентификатор выбранного сотрудника (он будет помечен в списке)
		 * @return mixed
		 * @throws Exception
		 */
		public function employeesList($template = 'default', $limit = 25, $selectedId = null) {
			list($employeesTemplate, $employeeTemplate, $emptyTemplate) = appointment::loadTemplates(
				'appointment/' . $template,
				'employees_block',
				'employee_line',
				'employees_empty'
			);

			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesCollection $employeesCollection */
			$employeesCollection = $serviceContainer->get('AppointmentEmployees');
			$collectionMap = $employeesCollection->getMap();

			$params = [];
			$params[$collectionMap->get('LIMIT_KEY')] = is_numeric($limit) ? $limit : 25;
			$params[$collectionMap->get('OFFSET_KEY')] = getRequest('p') * $limit;

			$employees = $this->module->getEmployees($params);

			if (umiCount($employees) == 0) {
				return appointment::parseTemplate($emptyTemplate, $employees);
			}

			$total = $employeesCollection->count([]);
			$items = [];
			$result = [];

			/** @var AppointmentEmployee $employee */
			foreach ($employees as $employee) {
				$item = [];
				$item['attribute:id'] = $employee->getId();
				$item['attribute:name'] = $employee->getName();
				$item['attribute:photo'] = $employee->getPhoto();
				$item['attribute:description'] = $employee->getDescription();

				if ($employee->getId() == $selectedId) {
					$item['attribute:selected'] = 'selected';
				}

				$items[] = appointment::parseTemplate($employeeTemplate, $item);
			}

			$result['subnodes:items'] = $items;
			$result['total'] = $total;
			$result['selected'] = $selectedId;

			return appointment::parseTemplate($employeesTemplate, $result);
		}

		/**
		 * Возвращает список сотрудников, оказывающих заданную услугу
		 * @param string $template имя шаблона (для tpl)
		 * @param int $serviceId идентификатор услуги
		 * @param int $limit ограничение на количество выводимых сотрудников
		 * @param null $selectedId идентификатор выбранного сотрудника (он будет помечен в списке)
		 * @return mixed
		 * @throws Exception
		 */
		public function employeesListByServiceId($template = 'default', $serviceId, $limit = 25, $selectedId = null) {
			list($employeesTemplate, $employeeTemplate, $emptyTemplate) = appointment::loadTemplates(
				'appointment/' . $template,
				'employees_block',
				'employee_line',
				'employees_empty'
			);

			if (!is_numeric($serviceId)) {
				throw new publicAdminException(getLabel('error-service-not-found', 'appointment'));
			}

			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesServicesCollection $employeesServicesCollection */
			$employeesServicesCollection = $serviceContainer->get('AppointmentEmployeesServices');
			$collectionMap = $employeesServicesCollection->getMap();
			$limit = is_numeric($limit) ? $limit : 25;

			$params = [];
			$params[$collectionMap->get('SERVICE_ID_FIELD_NAME')] = $serviceId;
			$params[$collectionMap->get('LIMIT_KEY')] = $limit;
			$params[$collectionMap->get('OFFSET_KEY')] = ((int) getRequest('p')) * $limit;

			$employeesWithService = $this->module->getEmployeesServices($params);
			$employeesIds = [];

			/** @var AppointmentEmployeeService $employeeWithService */
			foreach ($employeesWithService as $employeeWithService) {
				$employeesIds[] = $employeeWithService->getEmployeeId();
			}

			if (umiCount($employeesIds) == 0) {
				return appointment::parseTemplate($emptyTemplate, $employeesIds);
			}

			$total = $employeesServicesCollection->count([]);

			$params = [];
			$params[$collectionMap->get('ID_FIELD_NAME')] = $employeesIds;

			$employees = $this->module->getEmployees($params);

			$items = [];
			$result = [];

			/** @var AppointmentEmployee $employee */
			foreach ($employees as $employee) {
				$item = [];
				$item['attribute:id'] = $employee->getId();
				$item['attribute:name'] = $employee->getName();
				$item['attribute:photo'] = $employee->getPhoto();
				$item['attribute:description'] = $employee->getDescription();

				if ($employee->getId() == $selectedId) {
					$item['attribute:selected'] = 'selected';
				}

				$items[] = appointment::parseTemplate($employeeTemplate, $item);
			}

			$result['subnodes:items'] = $items;
			$result['total'] = $total;
			$result['selected'] = $selectedId;

			return appointment::parseTemplate($employeesTemplate, $result);
		}

		/**
		 * Возвращает список услуг
		 * @param string $template имя шаблона (для tpl)
		 * @param int $limit ограничение на количество выводимых услуг
		 * @param null|int $selectedId идентификатор выбранной услуги (она будет помечен в списке)
		 * @return mixed
		 * @throws Exception
		 */
		public function servicesList($template = 'default', $limit = 25, $selectedId = null) {
			list($servicesTemplate, $serviceTemplate, $emptyTemplate) = appointment::loadTemplates(
				'appointment/' . $template,
				'services_block',
				'service_line',
				'service_empty'
			);

			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $servicesCollection */
			$servicesCollection = $serviceContainer->get('AppointmentServices');
			$collectionMap = $servicesCollection->getMap();
			$limit = is_numeric($limit) ? $limit : 25;

			$params = [];
			$params[$collectionMap->get('LIMIT_KEY')] = $limit;
			$params[$collectionMap->get('OFFSET_KEY')] = ((int) getRequest('p')) * $limit;

			$services = $this->module->getServices($params);

			if (umiCount($services) == 0) {
				return appointment::parseTemplate($emptyTemplate, $services);
			}

			$total = $servicesCollection->count([]);
			$items = [];
			$result = [];
			$timePregReplacePattern = $this->module->timePregReplacePattern;
			$groupsNames = $this->module->getServicesGroupsNames();

			/** @var AppointmentService $service */
			foreach ($services as $service) {
				$item = [];
				$item['attribute:id'] = $service->getId();
				$item['attribute:name'] = $service->getName();
				$item['attribute:price'] = $service->getPrice();
				$item['attribute:time'] = preg_replace($timePregReplacePattern, '', $service->getTime());

				if (isset($groupsNames[$service->getGroupId()])) {
					$item['attribute:group'] = $groupsNames[$service->getGroupId()];
				}

				if ($service->getId() == $selectedId) {
					$item['attribute:selected'] = 'selected';
				}

				$items[] = appointment::parseTemplate($serviceTemplate, $item);
			}

			$result['subnodes:items'] = $items;
			$result['total'] = $total;
			$result['selected'] = $selectedId;

			return appointment::parseTemplate($servicesTemplate, $result);
		}

		/**
		 * Возвращает список статусов заявок на запись
		 * @param string $template имя шаблона (для tpl)
		 * @param null|int $selectedCode код выбранного статуса (он будет помечен в списке)
		 * @return mixed
		 */
		public function statusesList($template = 'default', $selectedCode = null) {
			list($block, $status) = appointment::loadTemplates(
				'appointment/' . $template,
				'statuses_block',
				'status_line'
			);

			$statuses = $this->module->getStatuses();
			$total = umiCount($statuses);
			$items = [];
			$result = [];

			foreach ($statuses as $statusCode => $statusName) {
				$item = [];
				$item['attribute:code'] = $statusCode;
				$item['attribute:name'] = getLabel($statusName);

				if ($statusCode == $selectedCode) {
					$item['attribute:selected'] = 'selected';
				}

				$items[] = appointment::parseTemplate($status, $item);
			}

			$result['subnodes:items'] = $items;
			$result['total'] = $total;
			$result['selected'] = $selectedCode;

			return appointment::parseTemplate($block, $result);
		}

		/**
		 * Возвращает список групп услуг
		 * @param string $template имя шаблона (для tpl)
		 * @param int $limit ограничение на количество выводимых групп
		 * @param null $selectedId идентификатор выбранной группы услуг (она будет помечена в списке)
		 * @return mixed
		 * @throws Exception
		 */
		public function serviceGroupsList($template = 'default', $limit = 25, $selectedId = null) {
			list($serviceGroupsTemplate, $serviceGroupTemplate, $emptyTemplate) = appointment::loadTemplates(
				'appointment/' . $template,
				'services_groups_block',
				'services_group_line',
				'services_groups_empty'
			);

			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServiceGroupsCollection $serviceGroupsCollection */
			$serviceGroupsCollection = $serviceContainer->get('AppointmentServiceGroups');
			$collectionMap = $serviceGroupsCollection->getMap();

			$limit = is_numeric($limit) ? $limit : 25;
			$params = [];
			$params[$collectionMap->get('LIMIT_KEY')] = $limit;
			$params[$collectionMap->get('OFFSET_KEY')] = ((int) getRequest('p')) * $limit;

			$servicesGroups = $this->module->getServiceGroups($params);

			if (umiCount($servicesGroups) == 0) {
				return appointment::parseTemplate($emptyTemplate, $servicesGroups);
			}

			$total = $serviceGroupsCollection->count([]);
			$items = [];
			$result = [];

			/** @var AppointmentServiceGroup $serviceGroup */
			foreach ($servicesGroups as $serviceGroup) {
				$item = [];
				$item['attribute:id'] = $serviceGroup->getId();
				$item['attribute:name'] = $serviceGroup->getName();

				if ($serviceGroup->getId() == $selectedId) {
					$item['attribute:selected'] = 'selected';
				}

				$items[] = appointment::parseTemplate($serviceGroupTemplate, $item);
			}

			$result['subnodes:items'] = $items;
			$result['total'] = $total;
			$result['selected'] = $selectedId;

			return appointment::parseTemplate($serviceGroupsTemplate, $result);
		}

		/**
		 * Возвращает список рабочих дней с часами работы сотрудника
		 * @param string $template имя шаблона (для tpl)
		 * @param int $employeeId идентификатор сотрудника
		 * @return mixed
		 * @throws publicAdminException
		 */
		public function employeeSchedulesList($template = 'default', $employeeId) {
			list($schedulesTemplate, $scheduleTemplate, $emptyTemplate) = appointment::loadTemplates(
				'appointment/' . $template,
				'employee_schedules_block',
				'employee_schedule_line',
				'employee_schedules_empty'
			);

			if (!is_numeric($employeeId)) {
				throw new publicAdminException(getLabel('error-employee-not-found', 'appointment'));
			}

			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesSchedulesCollection $employeesSchedulesCollection */
			$employeesSchedulesCollection = $serviceContainer->get('AppointmentEmployeesSchedules');
			$collectionMap = $employeesSchedulesCollection->getMap();

			$params = [];
			$params[$collectionMap->get('EMPLOYEE_ID_FIELD_NAME')] = $employeeId;
			$employeeSchedules = $this->module->getEmployeesSchedules($params);
			$total = umiCount($employeeSchedules);

			if ($total == 0) {
				return appointment::parseTemplate($emptyTemplate, $employeeSchedules);
			}

			$items = [];
			$result = [];
			$timePregReplacePattern = $this->module->timePregReplacePattern;

			/** @var AppointmentEmployeeSchedule $employeeSchedule */
			foreach ($employeeSchedules as $employeeSchedule) {
				$item = [];
				$item['attribute:id'] = $employeeSchedule->getId();
				$item['attribute:number'] = $employeeSchedule->getDayNumber();
				$item['attribute:name'] = getLabel('label-day-' . $employeeSchedule->getDayNumber(), 'appointment');
				$item['attribute:time_start'] = preg_replace($timePregReplacePattern, '', $employeeSchedule->getTimeStart());
				$item['attribute:time_end'] = preg_replace($timePregReplacePattern, '', $employeeSchedule->getTimeEnd());
				$items[] = appointment::parseTemplate($scheduleTemplate, $item);
			}

			$result['subnodes:items'] = $items;
			$result['total'] = $total;
			$result['employee_id'] = $employeeId;
			return appointment::parseTemplate($schedulesTemplate, $result);
		}

		/**
		 * Возвращает список возможных значений для времени начала или конца работы сотрудника
		 * @param string $template имя шаблона (для tpl)
		 * @param null|string $selectedTime выбранное значение (будет помечено в списке)
		 * @return mixed
		 */
		public function getScheduleWorkTimes($template = 'default', $selectedTime = null) {
			list($workTimesTemplate, $workTimeTemplate) = appointment::loadTemplates(
				'appointment/' . $template,
				'work_times_block',
				'work_time_line'
			);

			$items = [];
			$result = [];
			$hoursCounter = 0;

			for ($counter = 0; $counter < 48; $counter++) {
				$isOddCounter = ($counter % 2) == 1;
				$minutes = $isOddCounter ? '30' : '00';
				$hoursCounter = (mb_strlen($hoursCounter) == 1) ? '0' . $hoursCounter : (string) $hoursCounter;

				$item = [];
				$item['attribute:number'] = $counter + 1;
				$item['attribute:value'] = $hoursCounter . ':' . $minutes;

				if ($selectedTime == $item['attribute:value']) {
					$item['attribute:selected'] = 'selected';
				}

				$items[] = appointment::parseTemplate($workTimeTemplate, $item);

				if ($isOddCounter) {
					$hoursCounter++;
				}
			}

			$result['subnodes:items'] = $items;
			return appointment::parseTemplate($workTimesTemplate, $result);
		}

		/**
		 * Возвращает список идентификатор услуг, которые умеет оказывать заданные сотрудник
		 * @param string $template имя шаблона (для tpl)
		 * @param int $employeeId идентификатор сотрудника
		 * @return mixed
		 * @throws publicAdminException
		 */
		public function employeeServicesIdsList($template = 'default', $employeeId) {
			list($servicesIdsTemplate, $serviceIdTemplate, $emptyTemplate) = appointment::loadTemplates(
				'appointment/' . $template,
				'services_ids_block',
				'service_id_line',
				'services_ids_empty'
			);

			if (!is_numeric($employeeId)) {
				throw new publicAdminException(getLabel('error-employee-not-found', 'appointment'));
			}

			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesServicesCollection $employeesServicesCollection */
			$employeesServicesCollection = $serviceContainer->get('AppointmentEmployeesServices');
			$collectionMap = $employeesServicesCollection->getMap();

			$params = [];
			$params[$collectionMap->get('EMPLOYEE_ID_FIELD_NAME')] = $employeeId;
			$employeesServicesIds = $this->module->getEmployeesServices($params);
			$total = umiCount($employeesServicesIds);

			if ($total == 0) {
				return appointment::parseTemplate($emptyTemplate, $employeesServicesIds);
			}

			$items = [];
			$result = [];
			/** @var AppointmentEmployeeService $employeeServiceId */
			foreach ($employeesServicesIds as $employeeServiceId) {
				$item = [];
				$item['attribute:id'] = $employeeServiceId->getId();
				$item['attribute:service_id'] = $employeeServiceId->getServiceId();
				$items[] = appointment::parseTemplate($serviceIdTemplate, $item);
			}

			$result['subnodes:items'] = $items;
			$result['total'] = $total;
			$result['employee_id'] = $employeeId;
			return appointment::parseTemplate($servicesIdsTemplate, $result);
		}
	}

