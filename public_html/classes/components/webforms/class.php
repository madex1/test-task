<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Конструктор форм".
	 * Модуль управляет следующими сущностями:
	 * 1) Адреса;
	 * 2) Формы;
	 * 3) Шаблоны писем;
	 * 4) Сообщения форм;
	 * Модуль позволяет конструировать формы,
	 * назначать им адреса отправки, шаблоны писем
	 * и отправлять их.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_obratnaya_svyaz/
	 */
	class webforms extends def_module {

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('addresses', [
					'address_edit',
					'address_add'
				]);

				$commonTabs->add('forms', [
					'form_edit',
					'form_add'
				]);

				$commonTabs->add('templates', [
					'template_edit',
					'template_add'
				]);

				$commonTabs->add('messages', [
					'message'
				]);
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('WebformsAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('WebFormsCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('WebFormsMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('WebFormsCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылку на страницу, где можно отредактировать объект модуля
		 * @param int $objectId идентификатор объекта
		 * @param bool|string $type тип объекта
		 * @return string
		 * @throws coreException
		 */
		public function getObjectEditLink($objectId, $type = false) {
			$object = umiObjectsCollection::getInstance()->getObject($objectId);
			$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
			$oType = $umiObjectTypesCollection->getType($object->getTypeId());

			if ($oType->getParentId() == $umiObjectTypesCollection->getTypeIdByHierarchyTypeName('webforms', 'form')) {
				$type = 'message';
			}

			switch ($type) {
				case 'form' :
				case 'message' : {
					return $this->pre_lang . '/admin/webforms/message/' . $objectId . '/';
				}
				case 'template' : {
					return $this->pre_lang . '/admin/webforms/template_edit/' . $objectId . '/';
				}
				default : {
					return $this->pre_lang . '/admin/webforms/address_edit/' . $objectId . '/';
				}
			}
		}

		/**
		 * Возвращает адрес редактирования страницы и адрес добавления дочерней страницы
		 * @param int $element_id идентификатор страницы
		 * @return array
		 */
		public function getEditLink($element_id) {
			return [false, $this->pre_lang . '/admin/content/edit/' . $element_id . '/'];
		}

		/**
		 * @inheritdoc
		 * Применяет к сообщению шаблон письма
		 * @return array|string
		 * @throws Exception
		 */
		public function formatMessage($message, $bSplitLongMode = 0) {
			$args = func_get_args();
			$messageId = array_shift($args);
			if (!is_numeric($messageId)) {
				throw new Exception('Message id expected for format message');
			}

			$shouldUseAllFields = array_shift($args);
			$shouldUseAllFields = ($shouldUseAllFields === null) ? false : $shouldUseAllFields;

			$webformMessage = umiObjectsCollection::getInstance()->getObject($messageId);
			if (!$webformMessage instanceof iUmiObject) {
				throw new Exception('Incorrect message id given');
			}

			$templateQuery = new selector('objects');
			$templateQuery->types('object-type')->name('webforms', 'template');
			$templateQuery->where('form_id')->equals($webformMessage->getTypeId());
			$templateQuery->limit(0, 1);

			$template = $templateQuery->first();
			if ($template instanceof iUmiObject) {
				/** @var WebFormsMacros $this */
				return $this->formatMessageByTemplate($template, $webformMessage, $shouldUseAllFields);
			}

			/** @var WebFormsMacros $this */
			return $this->formatMessageByRawFieldValues($webformMessage, $shouldUseAllFields);
		}

		/**
		 * Возвращает идентификатор адреса,
		 * который содержит заданный адрес
		 * @param string $_sAddress адрес
		 * @return mixed
		 * @throws selectorException
		 */
		public function guessAddressId($_sAddress) {
			if (is_numeric($_sAddress)) {
				return $_sAddress;
			}

			$_sFind = str_replace([' ', ','], ['%', '%'], $_sAddress);

			$addresses = new selector('objects');
			$addresses->types('object-type')->name('webforms', 'address');
			$addresses->where('address_list')->ilike($_sFind);
			$addresses->option('no-length')->value(true);
			$addresses->option('load-all-props')->value(true);
			$addresses->option('return')->value('id');
			$addresses = $addresses->result();

			$addressesIds = [];
			foreach ($addresses as $addressId) {
				if (isset($addressId['id'])) {
					$addressesIds[] = $addressId['id'];
				}
			}

			return !empty($addressesIds) ? $addressesIds[0] : $_sAddress;
		}

		/**
		 * Административный метод.
		 * Возвращает формы, которые не привязаны к указанному шаблону.
		 * @param bool|int $currentTemplateId идентификатор шаблона письма
		 * @return array
		 * @throws coreException
		 */
		public function getUnbindedForms($currentTemplateId = false) {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$baseType = $typesCollection->getTypeIdByHierarchyTypeName('webforms', 'form');
			$forms = $typesCollection->getSubTypesList($baseType);

			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'template');
			$result = $sel->result();

			$exclude = [];
			$currentFormId = null;

			foreach ($result as $template) {
				if (!($template instanceof iUmiObject)) {
					continue;
				}

				$formId = $template->getValue('form_id');
				$exclude[] = $formId;

				if ($template->getId() == $currentTemplateId) {
					$currentFormId = $formId;
				}
			}

			$forms = array_diff($forms, $exclude);

			if (is_numeric($currentFormId) && !in_array($currentFormId, $forms)) {
				array_unshift($forms, $currentFormId);
			}

			$result = [];

			foreach ($forms as $id) {
				$itemArr = [];
				$itemArr['attribute:id'] = $id;
				$itemArr['node:name'] = $typesCollection->getType($id)->getName();
				$result[] = $itemArr;
			}

			return [
				'items' => [
					'nodes:item' => $result
				]
			];
		}

		/**
		 * Административный метод.
		 * Возвращает адреса для форм.
		 * @param bool|int $iFormId идентификатор выбранной формы
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function getAddresses($iFormId = false) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'address');
			$result = $sel->result();
			$aBlock = [];
			$aLines = [];

			/** @var iUmiObject $oObject */
			foreach ($result as $oObject) {
				$aLine = [];
				$aLine['attribute:id'] = $oObject->getId();

				if (in_array($iFormId, explode(',', $oObject->getValue('form_id')))) {
					$aLine['attribute:selected'] = 'selected';
				}

				$aLine['node:text'] = $oObject->getName();
				$aLines[] = self::parseTemplate('', $aLine);
			}

			$aBlock['attribute:input_name'] = 'data[address]';
			$aBlock['subnodes:items'] = $aLines;

			return self::parseTemplate('', $aBlock);
		}

		/**
		 * Административный метод.
		 * Возвращает список формы
		 * @param bool|int $form_id идентификатор выбранной формы
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function getForms($form_id = false) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$type_id = $objectTypes->getTypeIdByHierarchyTypeName('webforms', 'form');
			$sub_types = $objectTypes->getSubTypesList($type_id);
			$block_arr = [];
			$lines = [];

			foreach ($sub_types as $typeId) {
				$type = $objectTypes->getType($typeId);

				if ($type instanceof iUmiObjectType) {
					$line_arr = [];
					$line_arr['attribute:id'] = $typeId;

					if ($form_id == $typeId) {
						$line_arr['attribute:selected'] = 'selected';
					}

					$line_arr['node:text'] = $type->getName();
					$lines[] = self::parseTemplate('', $line_arr);
				}
			}

			$block_arr['subnodes:items'] = $lines;
			return self::parseTemplate('', $block_arr);
		}

		/**
		 * Алиас @see data::checkRequiredFields()
		 * @param int $typeId
		 * @return array|bool
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function checkRequiredFields($typeId) {
			/** @var data $dataModule */
			$dataModule = cmsController::getInstance()->getModule('data');
			if (!$dataModule) {
				throw new publicAdminException('Service unavailable');
			}

			return $dataModule->checkRequiredFields($typeId);
		}

		/**
		 * Алиас @see data::assembleErrorFields()
		 * @param array $errorFields
		 * @return string
		 * @throws publicAdminException
		 */
		public function assembleErrorFields($errorFields) {
			/** @var data $dataModule */
			$dataModule = cmsController::getInstance()->getModule('data');
			if (!$dataModule) {
				throw new publicAdminException('Service unavailable');
			}

			return $dataModule->assembleErrorFields($errorFields);
		}

		/**
		 * Клиентский метод.
		 * Проверяет существование объекта адреса по строковому адресу.
		 * Если адреса нет - кинет исключение errorPanicException
		 * @param string $address адрес
		 * @throws coreException
		 */
		public function checkAddressExistence($address) {
			$count = 0;

			if ($address) {
				$find = '%' . $address . '%';

				$sel = new selector('objects');
				$sel->types('object-type')->name('webforms', 'address');
				$sel->where('address_list')->like($find);
				$sel->option('return')->value('count');
				$count = $sel->result();
			}

			if (!$count) {
				/** @var webforms|WebFormsMacros $this */
				$this->reportError('%unknown_address%');
			}
		}

		/**
		 * Клиентский метод.
		 * Возвращает адрес отправки формы.
		 * @param string|int $id идентификатор адреса или его строковое представление
		 * @return Mixed
		 * @throws coreException
		 */
		public function guessAddressValue($id) {
			if (!is_numeric($id)) {
				$this->checkAddressExistence($id);
				return $id;
			}

			$address = umiObjectsCollection::getInstance()
				->getObject($id);

			if (!$address instanceof iUmiObject) {
				return $id;
			}

			return $address->getValue('address_list');
		}

		/**
		 * Клиентский метод.
		 * Возвращает имя адреса отправки формы
		 * @param string|int $id идентификатор адреса или его строковое представление
		 * @return bool|string
		 */
		public function guessAddressName($id) {
			if (!is_numeric($id)) {
				return $id;
			}

			$address = umiObjectsCollection::getInstance()
				->getObject($id);

			if (!$address instanceof iUmiObject) {
				return false;
			}

			return $address->getName();
		}

		/** @deprecated */
		public function getVariableNamesForMailTemplates() {
			return [];
		}
	}
