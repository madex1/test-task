<?php

	namespace UmiCms\Manifest\WebForms;

	/**
	 * Команда обновления связанных идентификаторов (формы для адреса)
	 * @package UmiCms\Manifest\WebForms
	 */
	class UpdateRelatedIdAction extends \Action {

		/** @var \umiImportRelations $importRelation экземпляр класс связей идентификаторов импортируемых сущностей */
		private $importRelation;

		/** @inheritdoc */
		public function __construct($name, array $params = []) {
			parent::__construct($name, $params);
			$this->importRelation = \umiImportRelations::getInstance();
		}

		/**
		 * @see \iAction
		 * @return $this
		 */
		public function execute() {
			$addressList = $this->getAddressList();

			foreach ($addressList as $address) {
				$this->processAddress($address);
			}

			return $this;
		}

		/**
		 * @see \iAction
		 * @return $this
		 */
		public function rollback() {
			return $this;
		}

		/**
		 * Возвращает экземпляр класс связей идентификаторов импортируемых сущностей
		 * @return \umiImportRelations
		 */
		private function getImportRelation() {
			return $this->importRelation;
		}

		/**
		 * Возвращает список адресов для форм обратной связи
		 * @return \iUmiObject[]
		 */
		private function getAddressList() {
			$queryBuilder = new \selector('objects');
			$queryBuilder->types('hierarchy-type')->name('webforms', 'address');
			return $queryBuilder->result();
		}

		/**
		 * Обрабатывает адрес формы обратной связи
		 * @param \iUmiObject $address
		 * @return $this
		 */
		private function processAddress(\iUmiObject $address) {
			$sourceId = $this->getAddressSourceId($address);

			if ($sourceId === null) {
				return $this;
			}

			return $this->updateFormId($address, $sourceId);
		}

		/**
		 * Возвращает идентификатор источника импорта адреса или null
		 * @param \iUmiObject $address
		 * @return int|null
		 */
		private function getAddressSourceId(\iUmiObject $address) {
			return $this->getImportRelation()
				->getSourceIdByObjectId($address->getId());
		}

		/**
		 * Обновляет идентификаторы формы у адреса
		 * @param \iUmiObject $address
		 * @param int $sourceId идентификатор источника импорта адреса
		 * @return $this
		 */
		private function updateFormId(\iUmiObject $address, $sourceId) {
			$oldFormIdList = (string) $address->getValue('form_id');
			$oldFormIdList = explode(',', $oldFormIdList);

			if (empty($oldFormIdList)) {
				return $this;
			}

			$newFormIdList = [];
			$importRelation = $this->getImportRelation();

			foreach ($oldFormIdList as $oldFormId) {
				$newFormId = $importRelation->getNewTypeIdRelation($sourceId, $oldFormId);

				if (!is_numeric($newFormId)) {
					$newFormIdList[] = $oldFormId;
					continue;
				}

				$newFormIdList[] = $newFormId;
			}

			$newFormIdList = array_unique($newFormIdList);
			$newFormIdList = implode(',', $newFormIdList);

			$address->setValue('form_id', $newFormIdList);
			$address->commit();

			return $this;
		}
	}

