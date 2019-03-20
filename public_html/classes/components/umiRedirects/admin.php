<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class UmiRedirectsAdmin {

		use baseModuleAdmin;

		/** @var umiRedirects $module */
		public $module;

		/**
		 * Возвращает список редиректов
		 * @return bool
		 * @throws Exception
		 */
		public function lists() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = (int) getRequest('per_page_limit');
			$limit = ($limit === 0) ? 25 : $limit;
			$currentPage = (int) getRequest('p');
			$offset = $currentPage * $limit;

			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$redirectsMap = $umiRedirectsCollection->getMap();
			$result = [];
			$total = 0;

			try {
				$queryParams = [
					$redirectsMap->get('OFFSET_KEY') => $offset,
					$redirectsMap->get('LIMIT_KEY') => $limit,
					$redirectsMap->get('COUNT_KEY') => true,
					$redirectsMap->get('LIKE_MODE_KEY') => [],
					$redirectsMap->get('COMPARE_MODE_KEY') => []
				];

				$filtersKey = 'fields_filter';
				$filters = (isset($_REQUEST[$filtersKey]) && is_array($_REQUEST[$filtersKey])) ? $_REQUEST[$filtersKey] : [];

				$fieldNames = [
					$redirectsMap->get('TARGET_FIELD_NAME'),
					$redirectsMap->get('SOURCE_FIELD_NAME'),
					$redirectsMap->get('STATUS_FIELD_NAME'),
					$redirectsMap->get('MADE_BY_USER_FIELD_NAME')
				];

				foreach ($filters as $fieldName => $fieldInfo) {
					if (!in_array($fieldName, $fieldNames)) {
						continue;
					}

					foreach ($fieldInfo as $mode => $fieldValue) {
						if ($fieldValue === null || $fieldValue === '') {
							continue 2;
						}

						if ($mode == 'like') {
							$queryParams[$redirectsMap->get('LIKE_MODE_KEY')][$fieldName] = true;
						} elseif (in_array($mode, ['ge', 'le', 'gt', 'lt', 'eq', 'ne'])) {
							$queryParams[$redirectsMap->get('COMPARE_MODE_KEY')][$fieldName] = $mode;
						}

						$queryParams[$fieldName] = $fieldValue;
					}
				}

				$ordersKey = 'order_filter';
				$orders = (isset($_REQUEST[$ordersKey]) && is_array($_REQUEST[$ordersKey])) ? $_REQUEST[$ordersKey] : [];

				if (umiCount($orders) > 0) {
					$queryParams[$redirectsMap->get('ORDER_KEY')] = $orders;
				}

				$redirects = $umiRedirectsCollection->export($queryParams);

				$result['data'] = $redirects;
				$total = $umiRedirectsCollection->count([
					$redirectsMap->get('CALCULATE_ONLY_KEY') => true,
				]);
			} catch (Exception $e) {
				$result['data']['error'] = $e->getMessage();
			}

			$result['data']['offset'] = $offset;
			$result['data']['per_page_limit'] = $limit;
			$result['data']['total'] = $total;

			Service::Response()
				->printJson($result);
		}

		/**
		 * Возвращает данные для создания формы добавления редиректа.
		 * Если передан $_REQUEST['param0'] = do,
		 * то добавляет редирект и перенаправляет на страницу, где можно отредактировать
		 * редирект
		 * @throws Exception
		 */
		public function add() {
			$this->setHeaderLabel('header-umiRedirects-add-redirect');
			$requestData = isset($_REQUEST['data']['new']) ? $_REQUEST['data']['new'] : [];

			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$redirectsMap = $umiRedirectsCollection->getMap();
			$source = $redirectsMap->get('SOURCE_FIELD_NAME');
			$target = $redirectsMap->get('TARGET_FIELD_NAME');
			$status = $redirectsMap->get('STATUS_FIELD_NAME');
			$madeByUser = $redirectsMap->get('MADE_BY_USER_FIELD_NAME');

			$formData = [
				$source => isset($requestData[$source]) ? $this->trim($requestData[$source]) : null,
				$target => isset($requestData[$target]) ? $this->trim($requestData[$target]) : null,
				$status => isset($requestData[$status]) ? (int) $requestData[$status] : null,
				$madeByUser => isset($requestData[$madeByUser]) ? (bool) $requestData[$madeByUser] : null,
				'field_name_prefix' => 'data[new]'
			];

			if ($this->isSaveMode()) {
				$madeByUserValue = isset($requestData[$madeByUser]) ? (bool) $requestData[$madeByUser] : false;
				$formData[$madeByUser] = $madeByUserValue;
				$this->module->validateRedirectParams($formData);

				/** @var umiRedirect $redirect */
				$redirect = $umiRedirectsCollection->create($formData);

				switch (getRequest('save-mode')) {
					case getLabel('label-save-add-exit'): {
						$this->chooseRedirect();
						break;
					}
					case getLabel('label-save-add'): {
						$this->module->redirect($this->module->pre_lang . '/admin/umiRedirects/edit/' . $redirect->getId());
						break;
					}
				}
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$this->setData($formData);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования редиректа.
		 * Если передан $_REQUEST['param1'] = do,
		 * то сохраняет изменения редиректа и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws publicAdminException
		 */
		public function edit() {
			$redirectId = (string) getRequest('param0');
			$this->setHeaderLabel('header-umiRedirects-edit-redirect');
			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$redirectsMap = $umiRedirectsCollection->getMap();
			$id = $redirectsMap->get('ID_FIELD_NAME');
			$source = $redirectsMap->get('SOURCE_FIELD_NAME');
			$target = $redirectsMap->get('TARGET_FIELD_NAME');
			$status = $redirectsMap->get('STATUS_FIELD_NAME');
			$madeByUser = $redirectsMap->get('MADE_BY_USER_FIELD_NAME');

			$redirects = $umiRedirectsCollection->get(
				[
					$id => $redirectId
				]
			);

			if (umiCount($redirects) == 0) {
				throw new publicAdminException(getLabel('error-redirect-not-found'));
			}

			/** @var umiRedirect $redirect */
			$redirect = array_shift($redirects);
			$requestData = isset($_REQUEST['data'][$redirectId]) ? $_REQUEST['data'][$redirectId] : [];

			$formData = [
				$source => isset($requestData[$source]) ? $this->trim($requestData[$source]) : $redirect->getSource(),
				$target => isset($requestData[$target]) ? $this->trim($requestData[$target]) : $redirect->getTarget(),
				$status => isset($requestData[$status]) ? (int) $requestData[$status] : $redirect->getStatus(),
				$madeByUser => isset($requestData[$madeByUser]) ? (bool) $requestData[$madeByUser] : $redirect->isMadeByUser()
			];

			if ($this->isSaveMode('param1')) {
				$madeByUserValue = isset($requestData[$madeByUser]) ? (bool) $requestData[$madeByUser] : false;
				$formData[$madeByUser] = $madeByUserValue;

				if ($formData[$source] != $this->trim($redirect->getSource())) {
					$this->module->validateRedirectParams($formData);
				} else {
					$this->module->checkCircles($formData);
				}

				$redirect->import($formData);
				$redirect->commit();
				$this->chooseRedirect();
			}

			$formData['field_name_prefix'] = 'data[' . $redirectId . ']';
			$formData[$id] = $redirectId;

			$this->setDataType('form');
			$this->setActionType('modify');
			$this->setData($formData);
			$this->doData();
		}

		/** Удаляет редиректы */
		public function del() {
			$redirects = getRequest('element');

			if (!is_array($redirects)) {
				$redirects = [$redirects];
			}

			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$idName = $umiRedirectsCollection->getMap()->get('ID_FIELD_NAME');

			$redirectIds = [];

			foreach ($redirects as $redirect) {
				$redirectIds[] = $redirect[$idName];
			}

			$result = [];

			try {
				$umiRedirectsCollection->delete(
					[
						$idName => $redirectIds
					]
				);
				$result['data']['success'] = true;
			} catch (Exception $e) {
				$result['data']['error'] = $e->getMessage();
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData($result);
			$this->doData();
		}

		/**
		 * Сохраняет изменения поля редиректа
		 * @throws Exception
		 */
		public function saveValue() {
			$redirectId = (string) getRequest('param0');
			$fieldKey = getRequest('field');
			$fieldValue = getRequest('value');

			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$redirectsMap = $umiRedirectsCollection->getMap();
			$result = [];

			$sourceField = $redirectsMap->get('SOURCE_FIELD_NAME');
			$targetField = $redirectsMap->get('TARGET_FIELD_NAME');

			try {
				$redirects = $umiRedirectsCollection->get(
					[
						$redirectsMap->get('ID_FIELD_NAME') => $redirectId
					]
				);

				if (umiCount($redirects) == 0) {
					throw new coreException(getLabel('error-redirect-not-found'));
				}

				/** @var umiRedirect $redirect */
				$redirect = array_shift($redirects);

				if (in_array($fieldKey, [$sourceField, $targetField])) {
					$fieldValue = $this->trim($fieldValue);
				}

				$needUniquenessCheck =
					($fieldKey == $sourceField) &&
					$fieldValue != $this->trim($redirect->getSource());

				$candidateData = [
					$sourceField => $fieldValue,
					$targetField => $redirect->getTarget()
				];

				if ($needUniquenessCheck) {
					$this->module->validateRedirectParams($candidateData);
				} else {
					$this->module->checkCircles($candidateData);
				}

				$redirect->setValue($fieldKey, $fieldValue);
				$redirect->commit();

				$result['data']['success'] = true;
			} catch (Exception $e) {
				$result['data']['success'] = $e->getMessage();
			}

			Service::Response()
				->printJson($result);
		}

		/**
		 * Возвращает настройки модуля "Редиректы".
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет переданные настройки.
		 */
		public function config() {
			$config = mainConfiguration::getInstance();

			$params = [
				'config' => [
					'boolean:allow-redirects-watch' => null
				]
			];

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$config->set('seo', 'watch-redirects-history', $params['config']['boolean:allow-redirects-watch']);
				$config->save();
				$this->chooseRedirect();
			}

			$params['config']['boolean:allow-redirects-watch'] = $config->get('seo', 'watch-redirects-history');

			$this->setConfigResult($params);
		}

		/** Удаляет все редиректы в системе */
		public function removeAllRedirects() {
			Service::Redirects()->deleteAll();
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
						'module' => 'umiRedirects',
						'type' => 'load',
						'name' => 'lists'
					],
					[
						'title' => getLabel('js-permissions-edit'),
						'module' => 'umiRedirects',
						'type' => 'edit',
						'name' => 'edit'
					],
					[
						'title' => getLabel('js-confirm-unrecoverable-yes'),
						'module' => 'umiRedirects',
						'type' => 'delete',
						'name' => 'del'
					],
					[
						'title' => getLabel('js-confirm-unrecoverable-yes'),
						'module' => 'umiRedirects',
						'type' => 'saveField',
						'name' => 'saveValue'
					]
				],
				'default' => 'source[400px]|target[400px]|status[250px]|made_by_user[250px]',
				'fields' => [
					[
						'name' => 'source',
						'title' => getLabel('label-source-field'),
						'type' => 'string',
					],
					[
						'name' => 'target',
						'title' => getLabel('label-target-field'),
						'type' => 'string',
					],
					[
						'name' => 'status',
						'title' => getLabel('label-status-field'),
						'type' => 'relation',
						'multiple' => 'false',
						'options' => '301,302,303,307'
					],
					[
						'name' => 'made_by_user',
						'title' => getLabel('label-made-by-user-field'),
						'type' => 'bool',
					]
				]
			];
		}

		/** Возвращает конфиг модуля в формате JSON для табличного контрола */
		public function flushDataConfig() {
			$this->module->printJson($this->getDatasetConfiguration());
		}

		/**
		 * Убирает лишние слэши из редиректа
		 * @param string $redirect источник или назначение редиректа
		 * @return string
		 */
		private function trim($redirect) {
			if ($redirect === '/') {
				return $redirect;
			}
			return trim($redirect, '/');
		}

	}
