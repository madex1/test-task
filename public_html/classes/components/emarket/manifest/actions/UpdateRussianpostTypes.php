<?php

	namespace UmiCms\Manifest\Emarket;

	/** Команда обновления видов отправлений Почты России */
	class UpdateRussianpostTypesAction extends \Action {

		/** @inheritdoc */
		public function execute() {
			$this->removeLegacyFields();
			$this->changePostTypes();
		}

		/** Удаляет лишние поля из типа данных "Доставка Почтой России" */
		private function removeLegacyFields() {
			$russianPost = \umiObjectTypesCollection::getInstance()->getTypeByGUID('emarket-delivery-808');
			if (!$russianPost instanceof \iUmiObjectType) {
				return;
			}

			$group = $russianPost->getFieldsGroupByName('settings');
			if (!$group instanceof \iUmiFieldsGroup) {
				return;
			}

			$legacyFields = ['typepost', 'departure_city', 'setpostvalue'];
			foreach ($group->getFields() as $field) {
				if (in_array($field->getName(), $legacyFields)) {
					$group->detachField($field->getId());
				}
			}
		}

		/** Обновляет виды отправлений Почты России */
		private function changePostTypes() {
			$oldGuidList = [
				'f92878f18258dde6be466657afbe597f425f92bb',
				'c5f7a7eb8380a03c76ba26c22eb38118b6838b3b',
				'47126638f57bd9d0f3e583b2b68ba080f16dc3b2',
				'44213024959016ae285efe9dd256d1451c7a5194',
				'e280cf173f5e9d92679dfd30af723848ca911949',
				'412392b58bd5e166cc32ce7d021041bd843fe3f6',
				'0c8124b837910c7341cb9d226319a31eeaa03756',
			];
			$newGuidList = [
				'russianpost_ems_standart',
				'russianpost_ems_declared_value',
				'russianpost_registered_wrapper',
				'russianpost_registered_wrapper_first_class',
				'russianpost_wrapper_with_declared_value',
				'russianpost_wrapper_first_class_with_declared_value',
				'russianpost_parcel_with_declared_value',
			];
			$nameList = [
				'i18n::object-ems_standart',
				'i18n::object-ems_declared_value',
				'i18n::object-registered_wrapper',
				'i18n::object-registered_wrapper_first_class',
				'i18n::object-wrapper_with_declared_value',
				'i18n::object-wrapper_first_class_with_declared_value',
				'i18n::object-parcel_with_declared_value',
			];
			$identifierList = [7030, 7020, 3010, 16010, 3020, 16020, 27020];

			$umiObjects = \umiObjectsCollection::getInstance();

			for ($i = 0, $length = count($oldGuidList); $i < $length; $i++) {
				if ($umiObjects->getObjectIdByGUID($newGuidList[$i])) {
					continue;
				}

				$object = $umiObjects->getObjectByGUID($oldGuidList[$i]);

				if (!$object instanceof \iUmiObject) {
					continue;
				}

				$object->setGUID($newGuidList[$i]);
				$object->setName($nameList[$i]);
				$object->setValue('identifier', $identifierList[$i]);
				$object->commit();
			}
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}
	}
