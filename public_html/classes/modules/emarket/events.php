<?php
	new umiEventListener('cron', 'emarket', 'onCronSyncCurrency');
	// Notification listeners
	new umiEventListener('systemModifyPropertyValue', 'emarket', 'onModifyProperty');
	new umiEventListener('systemModifyObject', 'emarket', 'onModifyObject');
	new umiEventListener('order-status-changed', 'emarket', 'onStatusChanged');
	new umiEventListener('order-payment-status-changed', 'emarket', 'onPaymentStatusChanged');
	new umiEventListener('order-delivery-status-changed', 'emarket', 'onDeliveryStatusChanged');
	// Reservation listeners
	new umiEventListener('systemModifyPropertyValue', 'emarket', 'onOrderPropChange');
	
	new umiEventListener('systemModifyPropertyValue', 'emarket', 'onStorePropChange');
	
	new umiEventListener('systemDeleteObject', 'emarket', 'onOrderDeleteUnreserve');
	new umiEventListener('systemDeleteObject', 'emarket', 'onOrderDeleteCleanRelations');

	// Yandex.Market
	preg_match("/[1-9]+.[0-9]+.[0-9]+/", PHP_VERSION, $matches);
	$yandexMarketAvailable = version_compare($matches[0], '5.3.0', '>');
	if ($yandexMarketAvailable) {
		new umiEventListener('systemModifyObject', 'emarket', 'changedOrder');
		new umiEventListener('systemModifyPropertyValue', 'emarket', 'changedOrderEntity');
	}

	new umiEventListener('cron', 'emarket', 'onCronCheckExpiredCustomers');
	new umiEventListener('cron', 'emarket', 'onCronCheckExpiredOrders');
	new umiEventListener('cron', 'emarket', 'onCronCheckExpiredCustomersOneClick');
?>
