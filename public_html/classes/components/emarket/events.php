<?php

	/**
	 * Обработчики изменения заказа в UMI.CMS:
	 *
	 * 1) Отправляют уведомления по e-mail менеджерам и клиентам;
	 * 2) Отправляют push уведомления в приложение UMI.Manager;
	 * 3) Ведут начисление, списание и учет бонусов;
	 * 4) Обновляют статистику модуля;
	 * 5) Изменяют поля в заказе
	 */
	$handler = new umiEventListener('systemModifyPropertyValue', 'emarket', 'onModifyProperty');
	$handler->setIsCritical(true);
	$handler = new umiEventListener('systemModifyObject', 'emarket', 'onModifyObject');
	$handler->setIsCritical(true);
	$handler = new umiEventListener('order-status-changed', 'emarket', 'onStatusChanged');
	$handler->setIsCritical(true);
	new umiEventListener('order-payment-status-changed', 'emarket', 'onPaymentStatusChanged');
	new umiEventListener('order-delivery-status-changed', 'emarket', 'onDeliveryStatusChanged');

	/** Следит за тем, чтобы только у одного склада стоял флаг "Основной" */
	new umiEventListener('systemModifyPropertyValue', 'emarket', 'onStorePropChange');

	/** Удаляет объекты, связанные с заказом при его удалении */
	new umiEventListener('systemDeleteObject', 'emarket', 'onOrderDeleteCleanRelations');

	/** Обновляют заказы в Яндекс.Маркет после изменения заказа в UMI.CMS */
	$onModifyObjectUpdateYandexMarket = new umiEventListener('systemModifyObject', 'emarket', 'changedOrder');
	$onModifyObjectUpdateYandexMarket->setIsCritical(true);
	$onModifyPropertyUpdateYandexMarket = new umiEventListener(
		'systemModifyPropertyValue',
		'emarket',
		'changedOrderEntity'
	);
	$onModifyPropertyUpdateYandexMarket->setIsCritical(true);

	/** Синхронизирует курсы валют с цб по срабатыванию системного крона */
	new umiEventListener('cron', 'emarket', 'onCronSyncCurrency');

	/**
	 * Удаляют "просроченные" объекты незавершенных заказов, незарегистрированных
	 * покупателей и т.д. по срабатыванию системного крона
	 */
	$handler = new umiEventListener('cron', 'emarket', 'onCronCheckExpiredCustomers');
	$handler->setPriority(0);
	$handler = new umiEventListener('cron', 'emarket', 'onCronCheckExpiredOrders');
	$handler->setPriority(0);
	$handler = new umiEventListener('cron', 'emarket', 'onCronCheckExpiredCustomersOneClick');
	$handler->setPriority(0);

