<?php

	namespace UmiCms\Manifest\Emarket;

	use UmiCms\Service;

	/** Команда обновления доменных идентификаторов у заказов на сайте */
	class UpdateOrderDomainIdsAction extends \Action {

		/** @inheritdoc */
		public function execute() {
			$orders = new \selector('objects');
			$orders->types('hierarchy-type')->name('emarket', 'order');
			$result = $orders->result();

			$currentDomainId = Service::DomainDetector()->detectId();
			$domainCollection = Service::DomainCollection();

			/** @var \iUmiObject $order */
			foreach ($result as $order) {
				if ($domainCollection->isExists($order->getValue('domain_id'))) {
					continue;
				}

				$order->setValue('domain_id', $currentDomainId);
				$order->commit();
			}
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}
	}
