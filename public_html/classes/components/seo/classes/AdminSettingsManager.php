<?php

	namespace UmiCms\Classes\Components\Seo;

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Seo\Settings\Custom;

	/**
	 * Класс для управлением настройками SEO в административной панели
	 * @package UmiCms\Classes\Components\Seo
	 */
	class AdminSettingsManager {

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getParams() {
			return array_merge($this->getCommonParams(), $this->getCustomParams());
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setCustomParams($params) {
			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$customParams = $params[$domain->getDecodedHost()];

				$this->createCustomSettings($domainId)
					->setTitlePrefix($customParams["string:seo-title-$domainId"])
					->setDefaultTitle($customParams["string:seo-default-title-$domainId"])
					->setDefaultKeywords($customParams["string:seo-keywords-$domainId"])
					->setDefaultDescription($customParams["string:seo-description-$domainId"])
					->setCaseSensitive($customParams["boolean:seo-is-case-sensitive-$domainId"])
					->setCaseSensitiveStatus($customParams["select:seo-case-sensitive-status-$domainId"])
					->setProcessRepeatedSlashes($customParams["boolean:seo-is-process-slashes-$domainId"])
					->setProcessRepeatedSlashesStatus($customParams["select:seo-process-slashes-status-$domainId"])
					->setAddIdToDuplicateAltName($customParams["boolean:seo-add-id-to-alt-name-$domainId"]);
			}
		}

		/**
		 * Возвращает настройки, специфические для каждого сайта на текущей языковой версии
		 * @return array
		 * @throws \Exception
		 */
		private function getCustomParams() {
			$params = [];

			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$settings = $this->createCustomSettings($domainId);
				$host = $domain->getDecodedHost();
				$params[$host] = [
					'status:domain' => $host,
					"string:seo-title-$domainId" => $settings->getTitlePrefix(),
					"string:seo-default-title-$domainId" => $settings->getDefaultTitle(),
					"string:seo-keywords-$domainId" => $settings->getDefaultKeywords(),
					"string:seo-description-$domainId" => $settings->getDefaultDescription(),
					"boolean:seo-is-case-sensitive-$domainId" => $settings->isCaseSensitive(),
					"select:seo-case-sensitive-status-$domainId" => $this->getSensitiveUrlOptions($domainId),
					"boolean:seo-is-process-slashes-$domainId" => $settings->isProcessRepeatedSlashes(),
					"select:seo-process-slashes-status-$domainId" => $this->getProcessSlashesOptions($domainId),
					"boolean:seo-add-id-to-alt-name-$domainId" => $settings->isAddIdToDuplicateAltName(),
				];
			}

			return $params;
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки
		 * "Способ обработки URL с повторяющимися слешами"
		 * @param int $domainId
		 * @return array
		 * @throws \Exception
		 */
		private function getProcessSlashesOptions($domainId) {
			$settings = $this->createCustomSettings($domainId);
			return array_merge(
				$this->getSlashesStatusList(),
				['value' => $settings->getProcessRepeatedSlashesStatus()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки
		 * "Способ обработки регистрозависимого URL"
		 * @param int $domainId
		 * @return array
		 * @throws \Exception
		 */
		private function getSensitiveUrlOptions($domainId) {
			$settings = $this->createCustomSettings($domainId);
			return array_merge(
				$this->getSensitiveUrlStatusList(),
				['value' => $settings->getCaseSensitiveStatus()]
			);
		}

		/**
		 * Возвращает список статусов настройки
		 * "Способ обработки URL с повторяющимися слешами"
		 * @return array
		 */
		private function getSlashesStatusList() {
			return [
				'redirect' => getLabel('option-delete-slashes-and-redirect'),
				'not-found' => getLabel('option-redirect-to-not-found-page'),
			];
		}

		/**
		 * Возвращает список статусов настройки
		 * "Способ обработки URL с повторяющимися слешами"
		 * @return array
		 */
		private function getSensitiveUrlStatusList() {
			return [
				'redirect' => getLabel('option-redirect-to-similar-url'),
				'not-found' => getLabel('option-redirect-to-not-found-page'),
			];
		}


		/**
		 * Создает SEO настройки для домена
		 * @param int $domainId идентификатор домена
		 * @return Custom
		 */
		private function createCustomSettings($domainId) {
			/** @var Custom $settings */
			$settings = Service::SeoSettingsFactory()->createCustom($domainId);

			return $settings;
		}

		/**
		 * Возвращает общие настройки
		 * @return array
		 * @throws \Exception
		 */
		private function getCommonParams() {
			return [];
		}
	}