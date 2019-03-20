<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class UmiSettingsMacros implements iModulePart {

		use tModulePart;

		/**
		 * Возвращает идентификатор настроек по кастомному идентификатору настроек (поле "Идентификатор")
		 * @param string $customId кастомный идентификатор настроек
		 * @param bool|int $domainId идентификатор домена, к которому относятся настройки
		 * @param bool|int $languageId идентификатор языка, к которому относятся настройки
		 * @return int
		 * @throws publicException
		 */
		public function getIdByCustomId($customId, $domainId = false, $languageId = false) {
			if (!is_string($customId) || empty($customId)) {
				$message = getLabel('label-error-wrong-settings-custom-id', $this->getModuleName());
				throw new publicException($message);
			}

			$domainId = is_numeric($domainId) ? $domainId : $this->getDefaultDomainId();
			$languageId = is_numeric($languageId) ? $languageId : $this->getDefaultLanguageId();

			$queryBuilder = $this->getDefaultQueryBuilder($domainId, $languageId);
			$queryBuilder->where('custom_id')->equals($customId);

			if ($queryBuilder->length() == 0) {
				$message = getLabel('label-error-settings-not-found-by-custom-id', $this->getModuleName(), $customId);
				throw new publicException($message);
			}

			$result = $queryBuilder->result();
			$settingsData = array_shift($result);
			return (int) $settingsData['id'];
		}

		/**
		 * Алиас UmiSettingsMacros::getId()
		 * @param string $name
		 * @param bool|int $domainId
		 * @param bool|int $languageId
		 * @return int
		 * @throws publicException
		 */
		public function getIdByName($name, $domainId = false, $languageId = false) {
			return $this->getId($name, $domainId, $languageId);
		}

		/**
		 * Возвращает идентификатор настроек по имени настроек
		 * @param string $name название настроек
		 * @param bool|int $domainId идентификатор домена, к которому относятся настройки
		 * @param bool|int $languageId идентификатор языка, к которому относятся настройки
		 * @return int
		 * @throws publicException
		 */
		public function getId($name, $domainId = false, $languageId = false) {
			if (!is_string($name) || empty($name)) {
				$message = getLabel('label-error-wrong-settings-name', $this->getModuleName());
				throw new publicException($message);
			}

			$domainId = is_numeric($domainId) ? $domainId : $this->getDefaultDomainId();
			$languageId = is_numeric($languageId) ? $languageId : $this->getDefaultLanguageId();

			$queryBuilder = $this->getDefaultQueryBuilder($domainId, $languageId);
			$queryBuilder->where('name')->equals($name);

			if ($queryBuilder->length() == 0) {
				$message = getLabel('label-error-settings-not-found-by-name', $this->getModuleName(), $name);
				throw new publicException($message);
			}

			$result = $queryBuilder->result();
			$settingsData = array_shift($result);
			return (int) $settingsData['id'];
		}

		/**
		 * Возвращает идентификатор языка по умолчанию
		 * @return int
		 */
		protected function getDefaultLanguageId() {
			return Service::LanguageDetector()->detectId();
		}

		/**
		 * Возвращает идентификатор домена по умолчанию
		 * @return int
		 */
		protected function getDefaultDomainId() {
			return Service::DomainDetector()->detectId();
		}

		/**
		 * Возвращает строителя выборок по умолчанию для получение идентификатор настроек
		 * @param int $domainId идентификатор домена
		 * @param int $languageId идентификатор языка
		 * @return selector
		 */
		protected function getDefaultQueryBuilder($domainId, $languageId) {
			$queryBuilder = new selector('objects');
			$queryBuilder->types('object-type')->guid(umiSettings::ROOT_TYPE_GUID);
			$queryBuilder->where('domain_id')->equals($domainId);
			$queryBuilder->where('lang_id')->equals($languageId);
			$queryBuilder->option('return')->value('id');
			$queryBuilder->limit(0, 1);
			return $queryBuilder;
		}
	}
