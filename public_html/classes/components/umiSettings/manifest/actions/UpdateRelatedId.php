<?php

	namespace UmiCms\Manifest\umiSettings;

	/**
	 * Команда обновления связанных идентификаторов (домена и языка) настроек
	 * @package UmiCms\Manifest\umiSettings
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
			$settingsList = $this->getSettingsList();

			foreach ($settingsList as $settings) {
				$this->processSettings($settings);
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
		 * Возвращает идентификатор источника импорта настроек или null
		 * @param \iUmiObject $settings
		 * @return int|null
		 */
		private function getSettingsSourceId(\iUmiObject $settings) {
			return $this->getImportRelation()
				->getSourceIdByObjectId($settings->getId());
		}

		/**
		 * Возвращает список настроек сайтов
		 * @return \iUmiObject[]
		 */
		private function getSettingsList() {
			$queryBuilder = new \selector('objects');
			$queryBuilder->types('hierarchy-type')->name('umiSettings', 'settings');
			return $queryBuilder->result();
		}

		/**
		 * Обрабатывает настройки сайта
		 * @param \iUmiObject $settings
		 * @return $this
		 */
		private function processSettings(\iUmiObject $settings) {
			$sourceId = $this->getSettingsSourceId($settings);

			if ($sourceId === null) {
				return $this;
			}

			return $this->updateDomainId($settings, $sourceId)
				->updateLanguageId($settings, $sourceId);
		}

		/**
		 * Обновляет идентификатор домена
		 * @param \iUmiObject $settings
		 * @param int $sourceId идентификатор источника импорта настроек
		 * @return $this
		 */
		private function updateDomainId(\iUmiObject $settings, $sourceId) {
			$oldDomainId = $settings->getValue('domain_id');
			$newDomainId = $this->getImportRelation()
				->getNewDomainIdRelation($sourceId, $oldDomainId);

			if (!is_numeric($newDomainId)) {
				return $this;
			}

			$settings->setValue('domain_id', $newDomainId);
			$settings->commit();

			return $this;
		}

		/**
		 * Обновляет идентификатор языка
		 * @param \iUmiObject $settings
		 * @param int $sourceId идентификатор источника импорта настроек или null
		 * @return $this
		 */
		private function updateLanguageId(\iUmiObject $settings, $sourceId) {
			$oldLanguageId = $settings->getValue('lang_id');
			$newLanguageId = $this->getImportRelation()
				->getNewLangIdRelation($sourceId, $oldLanguageId);

			if (!is_numeric($newLanguageId)) {
				return $this;
			}

			$settings->setValue('lang_id', $newLanguageId);
			$settings->commit();

			return $this;
		}
	}
