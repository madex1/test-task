<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\iReceipt;

	/**
	 * Класс фабрики серилизаторов для чеков по ФЗ-54
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt
	 */
	class Factory implements iFactory {

		/** @var \iServiceContainer $serviceContainer контейнер сервисов */
		private $serviceContainer;

		/** @inheritdoc */
		public function __construct(\iServiceContainer $serviceContainer) {
			$this->serviceContainer = $serviceContainer;
		}

		/**
		 * @inheritdoc
		 * @throws \wrongParamException
		 */
		public function create($name) {
			if (!is_string($name) || $name === '') {
				throw new \wrongParamException('Wrong receipt serializer name given');
			}

			$serviceName = $this->getServiceName($name);

			try {
				$service = $this->getServiceContainer()
					->get($serviceName);
			} catch (\Exception $exception) {
				throw new \wrongParamException(sprintf('Receipt serializer "%s" not found', $name));
			}

			if (!$service instanceof iReceipt) {
				throw new \wrongParamException(sprintf('Receipt serializer "%s" must implement iReceipt', $name));
			}

			return $service;
		}

		/**
		 * Возвращает сервис серилизатора по его имени
		 * @param string $name имя серилизатора
		 * @return string
		 */
		private function getServiceName($name) {
			return sprintf('ReceiptSerializer%s', $name);
		}

		/**
		 * Возвращает контейнер сервисов
		 * @return \iServiceContainer
		 */
		private function getServiceContainer() {
			return $this->serviceContainer;
		}
	}
