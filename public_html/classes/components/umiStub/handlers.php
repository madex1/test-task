<?php

	/** Класс обработчиков событий */
	class UmiStubHandlers {

		/** @var umiStub $module*/
		public $module;

		/**
		 * Обработчик события редактирования поля ip-адреса через табличный контрол
		 * Запускает валидацию ip-адреса и домена
		 * @param iUmiEventPoint $e событие редактирования
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws publicAdminException
		 * @throws selectorException
		 * @throws wrongValueException
		 */
		public function onModifyIpAddress(iUmiEventPoint $e) {
			if ($e->getMode() !== 'before') {
				return;
			}

			$object = $e->getRef('entity');

			if ($object instanceof iUmiHierarchyElement) {
				/** @var iUmiObject */
				$object = $object->getObject();
			}

			$objectId = $object->getId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());
			$objectTypeMethod = $objectType->getMethod();
			$isWrongMethod = !in_array($objectTypeMethod, ['ip-whitelist', 'ip-blacklist']);

			if (!$objectType || $objectType->getModule() != 'umiStub' || $isWrongMethod) {
				return;
			}

			$newValue = &$e->getRef('newValue');
			$domainId = $object->getValue('domain_id');
			$name = $object->getName();

			switch ((string) $e->getParam('property')) {
				case 'name' : {
					$this->module->validateIpAddress($newValue, $domainId, $objectTypeMethod, $objectId);
					break;
				}

				case 'domain_id' : {
					$this->module->validateIpAddress($name, $newValue, $objectTypeMethod, $objectId);
					break;
				}

				default:
					return;
			}

			$this->module->errorThrow('xml');
		}
	}