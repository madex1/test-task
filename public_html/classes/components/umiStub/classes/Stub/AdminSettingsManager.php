<?php

	namespace UmiCms\Classes\Components\Stub;

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Stub\Settings\Common;
	use UmiCms\Classes\System\Utils\Stub\Settings\Custom;

	/**
	 * Класс для управления настройками доступа к сайту в административной панели
	 * @package UmiCms\Classes\Components\Stub
	 */
	class AdminSettingsManager implements iAdminSettingsManager {

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
		public function setCommonParams($params) {
			/** @var Common $settings */
			$settings = Service::StubSettingsFactory()
				->createCommon();

			$settings->setIpStub($params['boolean:enable-stub'])
				->setDisableRobotIndex($params['boolean:disable-robot-index'])
				->setStubContent($params['wysiwyg:stub-content'])
				->setUseBlackList($params['boolean:use-blacklist']);
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setCustomParams($params) {
			foreach ($this->getDomainList() as $domain) {
				$domainId = $domain->getId();
				$customParams = $params["stub-$domainId"];

				$settings = Service::StubSettingsFactory()
					->createCustom($domainId);
				/** @var Custom $settings */
				$settings->setShouldUseCustomSettings($customParams["boolean:use-custom-settings-$domainId"])
					->setIpStub($customParams["boolean:enable-stub-$domainId"])
					->setDisableRobotIndex($customParams["boolean:disable-robot-index-$domainId"])
					->setStubContent($customParams["wysiwyg:stub-content-$domainId"])
					->setUseBlackList($customParams["boolean:use-blacklist-$domainId"]);
			}
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 */
		public function getWhiteList() {
			return array_merge($this->getCommonWhiteList(), $this->getCustomWhiteList());
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 */
		public function getBlackList() {
			return array_merge($this->getCommonBlackList(), $this->getCustomBlackList());
		}

		/**
		 * Возвращает общие настройки
		 * @return array
		 * @throws \Exception
		 */
		private function getCommonParams() {
			/** @var Common $settings */
			$settings = Service::StubSettingsFactory()->createCommon();

			return [
				'stub' => [
					'boolean:enable-stub' => $settings->isIpStub(),
					'boolean:use-blacklist' => $settings->isUseBlackList(),
					'boolean:disable-robot-index' => $settings->isDisableRobotIndex(),
					'wysiwyg:stub-content' => $settings->getStubContent()
				],
			];
		}

		/**
		 * Возвращает настройки, специфические для каждого сайта на текущей языковой версии
		 * @return array
		 * @throws \Exception
		 */
		private function getCustomParams() {
			$params = [];

			foreach ($this->getDomainList() as $domain) {
				$domainId = $domain->getId();
				/** @var Custom $settings */
				$settings = Service::StubSettingsFactory()->createCustom($domainId);
				$params["stub-$domainId"] = [
					'status:domain' => $domain->getHost(),
					"boolean:use-custom-settings-$domainId" => $settings->shouldUseCustomSettings(),
					"boolean:enable-stub-$domainId" => $settings->isIpStub(),
					"boolean:use-blacklist-$domainId" => $settings->isUseBlackList(),
					"boolean:disable-robot-index-$domainId" => $settings->isDisableRobotIndex(),
					"wysiwyg:stub-content-$domainId" => $settings->getStubContent()
				];
			}

			return $params;
		}

		/**
		 * Возвращает значения белого списка, общие для сайтов
		 * @return array
		 * @throws \coreException
		 * @throws \Exception
		 */
		private function getCommonWhiteList() {
			/** @var Common $settings */
			$settings = Service::StubSettingsFactory()->createCommon();

			return [
				'whiteList' => [
					'relation:ip-whitelist' => $this->getNodeList($settings->getWhiteList(), 'ip-whitelist'),
				],
			];
		}

		/**
		 * Возвращает значения белого списка для отдельных сайтов
		 * @return array
		 * @throws \coreException
		 * @throws \Exception
		 */
		private function getCustomWhiteList() {
			$params = [];

			foreach ($this->getDomainList() as $domain) {
				$domainId = $domain->getId();
				/** @var Custom $settings */
				$settings = Service::StubSettingsFactory()->createCustom($domainId);
				$params["whiteList-$domainId"] = [
					'status:domain' => $domain->getHost(),
					"relation:ip-whitelist-$domainId" => $this->getNodeList(
						$settings->getWhiteList(),
						'ip-whitelist',
						$domainId
					)
				];
			}

			return $params;
		}

		/**
		 * Возвращает значения черного списка, общие для сайтов
		 * @return array
		 * @throws \coreException
		 * @throws \Exception
		 */
		private function getCommonBlackList() {
			/** @var Common $settings */
			$settings = Service::StubSettingsFactory()->createCommon();

			return [
				'blackList' => [
					'relation:ip-blacklist' => $this->getNodeList(
						$settings->getBlackList(),
						'ip-blacklist'
					),
				],
			];
		}

		/**
		 * Возвращает значения черного списка для отдельных сайтов
		 * @return array
		 * @throws \coreException
		 * @throws \Exception
		 */
		private function getCustomBlackList() {
			$params = [];

			foreach ($this->getDomainList() as $domain) {
				$domainId = $domain->getId();
				/** @var Custom $settings */
				$settings = Service::StubSettingsFactory()->createCustom($domainId);
				$params["blackList-$domainId"] = [
					'status:domain' => $domain->getHost(),
					"relation:ip-blacklist-$domainId" => $this->getNodeList(
						$settings->getBlackList(),
						'ip-blacklist',
						$domainId
					)
				];
			}

			return $params;
		}

		/**
		 * Возвращает информацию о справочнике
		 * @param array $list данные справочника
		 * @param string $guid гуид типа
		 * @param string $domainId
		 * @return array
		 * @throws \coreException
		 */
		private function getNodeList($list, $guid, $domainId = '') {
			$nodeList = [];

			foreach ($list as $key => $item) {
				$nodeList[] = [
					'@id' => $key,
					'node:items' => $item,
				];
			}

			return [
				'attribute:guide_id' => $this->getGuideId($guid),
				'attribute:domain_id' => $domainId,
				'nodes:item' => $nodeList
			];
		}

		/**
		 * Возвращает идентификатор справочника по его гуиду
		 * @param string $guid
		 * @return int
		 * @throws \coreException
		 */
		private function getGuideId($guid) {
			return \umiObjectTypesCollection::getInstance()
				->getTypeIdByGUID($guid);
		}

		/**
		 * Возвращает список доменов
		 * @return \iDomain[]
		 */
		private function getDomainList() {
			return Service::DomainCollection()->getList();
		}
	}