<?php

use UmiCms\Service;

/** Класс обработчиков событий */
	class DispatchesHandlers {

		/** @var dispatches $module */
		public $module;

		/**
		 * Обработчик события создания объекта.
		 * Валидирует почтовый ящик подписчика.
		 * В случае неудачи - подписчик удаляется.
		 * @param iUmiEventPoint $e событие создания объекта
		 * @throws coreException
		 * @throws selectorException
		 */
		public function onCreateObject(iUmiEventPoint $e) {
			/** @var iUmiObject $object */
			$object = $e->getRef('object');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/plain');

			if ($objectType->getModule() != 'dispatches' || $objectType->getMethod() != 'subscriber' || $e->getMode() != 'before') {
				return;
			}

			$umiObjectsCollection = umiObjectsCollection::getInstance();

			if (!umiMail::checkEmail(getRequest('name'))) {
				$umiObjectsCollection->delObject($object->getId());
				$this->module->errorRegisterFailPage($this->module->pre_lang . '/admin/dispatches/add/subscriber/');
				$this->module->errorNewMessage(getLabel('error-email-wrong-format'));
				return;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id($objectType->getId());
			$sel->where('name')->equals((string) getRequest('name'));
			$sel->limit(1, 1);

			if ($sel->first()) {
				$umiObjectsCollection->delObject($sel->first()->id);
				$this->module->errorRegisterFailPage($this->module->pre_lang . '/admin/dispatches/add/subscriber/');
				$this->module->errorNewMessage(getLabel('error-subscriber-exists'));
			}
		}

		/**
		 * Обработчик события сохранения изменений объекта.
		 * Валидирует почтовый ящик подписчика.
		 * @param iUmiEventPoint $e событие сохранения изменений объекта
		 * @throws coreException
		 */
		public function onModifyObject(iUmiEventPoint $e) {
			/** @var iUmiObject $object */
			$object = $e->getRef('object');
			$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
			$objectType = $umiObjectTypesCollection->getType($object->getTypeId());

			if ($e->getMode() != 'before' || $objectType->getModule() != 'dispatches' || $objectType->getMethod() != 'subscriber') {
				return;
			}

			$subscriberHierarchyTypeId = umiHierarchyTypesCollection::getInstance()
				->getTypeByName('dispatches', 'subscriber')
				->getId();
			$subscriberTypeId = $umiObjectTypesCollection->getTypeIdByHierarchyTypeId($subscriberHierarchyTypeId);

			if ($subscriberTypeId == $object->getTypeId()) {
				if (!umiMail::checkEmail(getRequest('name'))) {
					$this->module->errorNewMessage(getLabel('error-email-wrong-format'));
				}
			}
		}

		/**
		 * Обработчик событие изменения значения поля сущности через eip.
		 * Валидирует почтовый ящик подписчика.
		 * @param iUmiEventPoint $e событетия изменения значения поля
		 * @throws coreException
		 */
		public function onPropertyChanged(iUmiEventPoint $e) {
			/** @var iUmiObject|iUmiHierarchyElement $object */
			$object = $e->getRef('entity');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			if ($e->getMode() != 'before' || $objectType->getModule() != 'dispatches' || $objectType->getMethod() != 'subscriber') {
				return;
			}

			$newValue = &$e->getRef('newValue');

			switch ((string) $e->getParam('property')) {
				case 'name' : {
					if (!umiMail::checkEmail($newValue)) {
						$this->module->errorAddErrors('error-email-wrong-format');
						$newValue = false;
					}
					break;
				}
				default: {
					return;
				}
			}

			$this->module->errorThrow('xml');
		}

		/**
		 * Обработчик события сохранения изменений объекта.
		 * Меняет рассылку, в которую будут выгружаться новые темы форума
		 * @param iUmiEventPoint $event событие сохранения изменений объекта
		 */
		public function changeLoadForumOptionModify(iUmiEventPoint $event) {
			static $oldValue;
			$mode = $event->getMode();
			/** @var iUmiObject $object */
			$object = $event->getRef('object');

			if (!$this->module->isDispatch($object)) {
				return;
			}

			if ($mode === 'before') {
				$oldValue = $object->getValue('load_from_forum');
			}

			if ($mode === 'after') {
				$newValue = $object->getValue('load_from_forum');

				if ($oldValue !== $newValue && $newValue) {
					$this->module->changeLoadFromForumDispatch($object);
				}
			}
		}

		/**
		 * Обработчик событие изменения значения поля сущности через быстрое редактирование
		 * в табличном контроле.
		 * Меняет рассылку, в которую будут выгружаться новые темы форума
		 * @param iUmiEventPoint $event событие изменения значение поля
		 */
		public function changeLoadForumOptionQuickEdit(iUmiEventPoint $event) {
			$mode = $event->getMode();
			/** @var iUmiObject $object */
			$object = $event->getRef('entity');

			if (!$this->module->isDispatch($object)) {
				return;
			}

			if ($mode === 'after') {
				$oldValue = $event->getParam('oldValue');
				$newValue = $event->getParam('newValue');
				$propertyName = $event->getParam('property');

				if ($propertyName === 'load_from_forum' && $oldValue !== $newValue && $newValue) {
					$this->module->changeLoadFromForumDispatch($object);
				}
			}
		}

		/**
		 * Запускает автоматическую отправку рассылок по запуску системного крона
		 * @param iUmiEventPoint $event событие запуска системного крона
		 * @throws selectorException
		 */
		public function onAutosendDispathes(iUmiEventPoint $event) {
			$objects = umiObjectsCollection::getInstance();

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'dispatch');
			$dispatches = $sel->result();

			@set_time_limit(0);

			/** @var iUmiObject $dispatch */
			foreach ($dispatches as $dispatch) {
				$disp_last_release = $dispatch->getValue('disp_last_release');

				if ($disp_last_release instanceof umiDate && $disp_last_release->timestamp > time() - 3600) {
					continue;
				}

				$need_dispatch = false;
				$arr_days = $dispatch->getValue('days');

				if (is_array($arr_days)) {
					foreach ($arr_days as $i_day) {
						$day = $objects->getObject($i_day);

						if ($day->getValue('number') == (int) date('N')) {
							$need_dispatch = true;
							break;
						}
					}
				}

				if (!$need_dispatch) {
					continue;
				}

				$need_dispatch = false;
				$arr_hours = $dispatch->getValue('hours');

				if (is_array($arr_hours)) {
					foreach ($arr_hours as $i_hour) {
						$hour = $objects->getObject($i_hour);

						if ($hour->getValue('number') == (int) date('H')) {
							$need_dispatch = true;
							break;
						}
					}
				}

				if (!$need_dispatch) {
					continue;
				}

				$this->module->fill_release($dispatch->getId(), true);
				$this->module->release_send_full($dispatch->getId());
			}
		}
	}

