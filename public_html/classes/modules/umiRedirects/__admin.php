<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	abstract class __umiRedirects extends baseModuleAdmin {

		/** @const string FILTERS_REQUEST_KEY ключ данных формы с параметрами фильтрации */
		const FILTERS_REQUEST_KEY = 'fields_filter';
		/** @const string ORDERS_REQUEST_KEY ключ данных формы с параметрами сортировки */
		const ORDERS_REQUEST_KEY = 'order_filter';
		/** @const string LIMIT_REQUEST_KEY ключ данных формы с ограничение на количество редиректов */
		const LIMIT_REQUEST_KEY = 'per_page_limit';
		/** @const string PAGE_NUM_REQUEST_KEY ключ данных формы с номером текущей страницы */
		const PAGE_NUM_REQUEST_KEY = 'p';
		/** @const string DATA_RESULT_KEY корневой ключ в данных ответа */
		const DATA_RESULT_KEY = 'data';
		/** @const string ERROR_RESULT_KEY ключ ошибки в данных ответа */
		const ERROR_RESULT_KEY = 'error';
		/** @const string SUCCESS_RESULT_KEY ключ успешной операции в данных ответа */
		const SUCCESS_RESULT_KEY = 'success';
		/** @const string OFFSET_RESULT_KEY ключ смещения в данных ответа */
		const OFFSET_RESULT_KEY = 'offset';
		/** @const string TOTAL_RESULT_KEY ключ общего количества в данных ответа */
		const TOTAL_RESULT_KEY = 'total';
		/** @const string LIMIT_RESULT_KEY ключ ограничения на количество в данных ответа */
		const LIMIT_RESULT_KEY = 'limit';
		/** @const string FORM_FIELD_NAME_PREFIX ключ с префиксом имен полей формы в данных ответа */
		const FORM_FIELD_NAME_PREFIX = 'field_name_prefix';

		/**
		 * Возвращает список редиректов
		 * @return bool
		 * @throws Exception
		 */
		public function lists() {
			/** @var def_module|umiRedirects|__umiRedirects $this */
			$this->setDataType("list");
			$this->setActionType("view");

			if ($this->ifNotJsonMode()) {
				return $this->doData();
			}

			$limit = (int) getRequest(self::LIMIT_REQUEST_KEY);
			$limit = ($limit === 0) ? 25 : $limit;
			$currentPage = (int) getRequest(self::PAGE_NUM_REQUEST_KEY);
			$offset = $currentPage * $limit;

			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$collectionMap = $umiRedirectsCollection->getMap();
			$result = [];
			$total = 0;

			try {
				$queryParams = [
					$collectionMap->get('OFFSET_KEY') => $offset,
					$collectionMap->get('LIMIT_KEY') => $limit,
					$collectionMap->get('COUNT_KEY') => true
				];

				$filtersKey = self::FILTERS_REQUEST_KEY;
				$filtersKeys = (isset($_REQUEST[$filtersKey]) && is_array($_REQUEST[$filtersKey])) ? $_REQUEST[$filtersKey] : [];

				$fieldsKeys = [
					$collectionMap->get('TARGET_FIELD_NAME'),
					$collectionMap->get('SOURCE_FIELD_NAME'),
					$collectionMap->get('STATUS_FIELD_NAME'),
					$collectionMap->get('MADE_BY_USER_FIELD_NAME')
				];

				foreach ($filtersKeys as $fieldKey => $fieldInfo) {
					if (!in_array($fieldKey, $fieldsKeys)) {
						continue;
					}

					foreach ($fieldInfo as $mode => $value) {
						if ($value === null || $value === '') {
							continue 2;
						}
					}

					$queryParams[$fieldKey] = $fieldInfo;
				}

				$ordersKey = self::ORDERS_REQUEST_KEY;
				$orders = (isset($_REQUEST[$ordersKey]) && is_array($_REQUEST[$ordersKey])) ? $_REQUEST[$ordersKey] : [];

				if (count($orders) > 0) {
					$queryParams[$collectionMap->get('ORDER_KEY')] = $orders;
				}

				$redirects = $umiRedirectsCollection->export($queryParams);
				$result[self::DATA_RESULT_KEY] = $redirects;
				$total = $umiRedirectsCollection->count([]);
			} catch (Exception $e) {
				$result[self::DATA_RESULT_KEY][self::ERROR_RESULT_KEY] = $e->getMessage();
			}

			$result[self::DATA_RESULT_KEY][self::OFFSET_RESULT_KEY] = $offset;
			$result[self::DATA_RESULT_KEY][self::LIMIT_REQUEST_KEY] = $limit;
			$result[self::DATA_RESULT_KEY][self::TOTAL_RESULT_KEY] = $total;
			/** @var HTTPOutputBuffer $buffer */
			$buffer = Service::Response()
				->getHttpBuffer();
			$buffer->calltime();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$buffer->push(json_encode($result));
			$buffer->end();
		}

		/**
		 * Возвращает данные для создания формы добавления редиректа.
		 * Если передан $_REQUEST['param0'] = do,
		 * то добавляет редирект и перенаправляет на страницу, где можно отредактировать
		 * редирект
		 * @throws Exception
		 */
		public function add() {
			/** @var def_module|umiRedirects|__umiRedirects $this */
			$mode = (string) getRequest("param0");
			$this->setHeaderLabel("header-umiRedirects-add-redirect");

			$requestData = isset($_REQUEST['data']['new']) ? $_REQUEST['data']['new'] : [];
			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$collectionMap = $umiRedirectsCollection->getMap();
			$source = $collectionMap->get('SOURCE_FIELD_NAME');
			$target = $collectionMap->get('TARGET_FIELD_NAME');
			$status = $collectionMap->get('STATUS_FIELD_NAME');
			$madeByUser = $collectionMap->get('MADE_BY_USER_FIELD_NAME');

			$formData = [
				$source => (isset($requestData[$source])) ? $requestData[$source] : null,
				$target => (isset($requestData[$target])) ? $requestData[$target] : null,
				$status => (isset($requestData[$status])) ? (int) $requestData[$status] : null,
				$madeByUser => (isset($requestData[$madeByUser])) ? (bool) $requestData[$status] : null,
				self::FORM_FIELD_NAME_PREFIX => 'data[new]'
			];

			if ($mode == "do") {
				$madeByUserValue = (isset($requestData[$madeByUser])) ? (bool) $requestData[$madeByUser] : false;
				$formData[$madeByUser] = $madeByUserValue;

				$this->validateRedirectParams($formData);
				/** @var umiRedirect $redirect */
				$redirect = $umiRedirectsCollection->create($formData);

				switch (getRequest('save-mode')) {
					case getLabel('label-save-add-exit'): {
						$this->chooseRedirect();
						break;
					}
					case getLabel('label-save-add'): {
						$this->redirect($this->pre_lang . '/admin/umiRedirects/edit/' . $redirect->getId());
						break;
					}
				}
			}

			$this->setDataType("form");
			$this->setActionType("create");
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
			/** @var def_module|umiRedirects|__umiRedirects $this */
			$redirectId = (string) getRequest("param0");
			$mode = (string) getRequest("param1");
			$this->setHeaderLabel("header-umiRedirects-edit-redirect");
			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$collectionMap = $umiRedirectsCollection->getMap();

			$id = $collectionMap->get('ID_FIELD_NAME');
			$source = $collectionMap->get('SOURCE_FIELD_NAME');
			$target = $collectionMap->get('TARGET_FIELD_NAME');
			$status = $collectionMap->get('STATUS_FIELD_NAME');
			$madeByUser = $collectionMap->get('MADE_BY_USER_FIELD_NAME');

			$redirects = $umiRedirectsCollection->get(
				[
					$id => $redirectId
				]
			);

			if (count($redirects) == 0) {
				throw new publicAdminException(getLabel('error-redirect-not-found'));
			}

			/** @var umiRedirect $redirect */
			$redirect = array_shift($redirects);
			$requestData = isset($_REQUEST['data'][$redirectId]) ? $_REQUEST['data'][$redirectId] : [];

			$formData = [
				$source => (isset($requestData[$source])) ? $requestData[$source] : $redirect->getSource(),
				$target => (isset($requestData[$target])) ? $requestData[$target] : $redirect->getTarget(),
				$status => (isset($requestData[$status])) ? (int) $requestData[$status] : $redirect->getStatus(),
				$madeByUser => (isset($requestData[$madeByUser])) ? (bool) $requestData[$madeByUser] : $redirect->isMadeByUser()
			];

			if ($mode == "do") {
				$madeByUserValue = (isset($requestData[$madeByUser])) ? (bool) $requestData[$madeByUser] : false;
				$formData[$madeByUser] = $madeByUserValue;

				if ($requestData[$source] != $redirect->getSource()) {
					$this->validateRedirectParams($formData);
				} else {
					$this->checkCircles($formData);
				}

				$redirect->import($formData);
				$redirect->commit();
				$this->chooseRedirect();
			}

			$formData[self::FORM_FIELD_NAME_PREFIX] = 'data[' . $redirectId . ']';
			$formData[$id] = $redirectId;

			$this->setDataType("form");
			$this->setActionType("modify");
			$this->setData($formData);
			$this->doData();
		}

		/** Удаляет редиректы */
		public function del() {
			/** @var def_module|umiRedirects|__umiRedirects $this */
			$redirectsIds = getRequest('element');

			if (!is_array($redirectsIds)) {
				$redirectsIds = [$redirectsIds];
			}

			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$result = [];

			try {
				$umiRedirectsCollection->delete(
					[
						$umiRedirectsCollection->getMap()->get('ID_FIELD_NAME') => $redirectsIds
					]
				);
				$result[self::DATA_RESULT_KEY][self::SUCCESS_RESULT_KEY] = true;
			} catch (Exception $e) {
				$result[self::DATA_RESULT_KEY][self::ERROR_RESULT_KEY] = $e->getMessage();
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$this->setData($result);
			$this->doData();
		}

		/**
		 * Сохраняет изменения поля редиректа
		 * @throws Exception
		 */
		public function saveValue() {
			/** @var def_module|umiRedirects|__umiRedirects $this */
			$redirectId = (string) getRequest("param0");
			$fieldKey = getRequest('field');
			$fieldValue = getRequest('value');

			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$redirectsMap = $umiRedirectsCollection->getMap();
			$result = [];

			try {
				$redirects = $umiRedirectsCollection->get(
						[
								$redirectsMap->get('ID_FIELD_NAME') => $redirectId
						]
				);

				if (count($redirects) == 0) {
					throw new Exception(getLabel('error-redirect-not-found'));
				}

				/** @var umiRedirect $redirect */
				$redirect = array_shift($redirects);
				$redirect->setValue($fieldKey, $fieldValue);

				if ($fieldKey == $redirectsMap->get('SOURCE_FIELD_NAME')) {
					$this->validateRedirectParams($redirect->export());
				} else {
					$this->checkCircles($redirect->export());
				}

				$redirect->commit();

				$result[self::DATA_RESULT_KEY][self::SUCCESS_RESULT_KEY] = true;
			} catch (Exception $e) {
				$result[self::DATA_RESULT_KEY][self::ERROR_RESULT_KEY] = $e->getMessage();
			}

			/** @var HTTPOutputBuffer $buffer */
			$buffer = $umiRedirectsCollection->getBuffer();
			$buffer->calltime();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$buffer->push(json_encode($result));
			$buffer->end();
		}

		/**
		 * Возвращает настройки модуля "Редиректы".
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет переданные настройки.
		 * @return void
		 */
		public function config() {
			$config = mainConfiguration::getInstance();

			$params = [
					'config' => [
							"boolean:allow-redirects-watch" => null
					]
			];

			$mode = getRequest('param0');

			if ($mode == 'do') {
				$params = $this->expectParams($params);
				$config->set('seo', 'watch-redirects-history', $params['config']['boolean:allow-redirects-watch']);
				$config->save();
				$this->chooseRedirect();
			}

			$params['config']['boolean:allow-redirects-watch'] = $config->get('seo', 'watch-redirects-history');

			$data = $this->prepareData($params, 'settings');
			$this->setDataType('settings');
			$this->setActionType('modify');
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
						'title'   => getLabel('smc-load'),
						'forload' => true,
						'module'  =>'umiRedirects',
						'type'    => 'load',
						'name' =>'lists'
					],
					[
						'title'   => getLabel('js-permissions-edit'),
						'module'  => 'umiRedirects',
						'type'    => 'edit',
						'name' => 'edit'
					],
					[
						'title'   => getLabel('js-confirm-unrecoverable-yes'),
						'module'  => 'umiRedirects',
						'type'    => 'delete',
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
			\UmiCms\Service::Response()
				->printJson($this->getDatasetConfiguration());
		}

		/** Удаляет все редиректы в системе */
		public function removeAllRedirects() {
			Service::Redirects()->deleteAll();
		}
	}
