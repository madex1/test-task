<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на оформление заказа */
		'purchasing' => [
			'price',
			'stores',
			'basketaddlink',
			'basketaddfastlink',
			'fastpurchase',
			'applypricecurrency',
			'order',
			'basket',
			'cart',
			'yandex_market',
			'purchase',
			'gateway',
			'callback',
			'receipt',
			'removedeliveryaddress',
			'currencyselector',
			'getcustomerinfo',
			'selectcurrency',
			'discountinfo',
			'getpurchaselink',
			'getinvoicelink',
			'getinvoice',
			'getapishippointsbyproviderandcity',
			'getorderdeliveryaddress',
			'getapishipdeliveryoptions',
			'getapishipdeliverypointstypes',
			'getapishipprovidertariffbyid',
			'getapishipdeliverypointbyid',
			'resetapishipcredentials',
			'getordertax',
			'getorderitemtax',
			'getorderdeliverytax',
			/** Оформление заказа в 1 шаг */
			'purchasing_one_step',
			'customerdeliverylist',
			'personalinfo',
			'paymentslist',
			'saveinfo',
			/** Оформление заказа в 1 клик */
			'createform',
			'getoneclickorder',
			'validoneclickinfo',
		],
		/** Права на личный кабинет покупателя */
		'personal' => [
			'personal',
			'orderslist',
		],
		/** Права на сравнение товаров */
		'compare' => [
			'getcomparelist',
			'getcomparelink',
			'addtocompare',
			'removefromcompare',
			'resetcomparelist',
			'jsonaddtocomparelist',
			'jsonremovefromcompare',
			'jsonresetcomparelist',
			'getcompareelements',
		],
		/** Права на администрирование модуля */
		'control' => [
			'activity',
			'orders',
			'orderslist',
			'order_edit',
			'order_printable',
			'currency',
			'currency_add',
			'currency_edit',
			'delivery',
			'delivery_add',
			'delivery_edit',
			'discounts',
			'discount_add',
			'discount_edit',
			'getmodificators',
			'getrules',
			'payment',
			'payment_add',
			'payment_edit',
			'stores',
			'store_add',
			'store_edit',
			'stats',
			'realpayments',
			'setdaterange',
			'getdaterange',
			'getmostpopularproduct',
			'statrun',
			'order.edit',
			'currency.edit',
			'updatecurrencies',
			'delivery.edit',
			'discount.edit',
			'payment.edit',
			'store.edit',
			'getapishipchosenproviderssettings',
			'refreshapishipordersstatuses',
			'getapishiporders',
			'cancelapishiporder',
			'saveapishipuser',
			'getapishiplabel',
			'getapishipwaybill',
			'getapishippointsbyproviderandcity',
			'getapishipdeliveryoptions',
			'sendapishiporderrequest',
			'getapishipproviderdeliverytypes',
			'getapishipproviderpickuptypes',
			'getapishipprovidertariffs',
			'getapishipprovidersettings',
			'setapishipprovidersettings',
			'getapishipsupporteddeliverytypes',
			'getapishipsupportedpickuptypes',
			'getapishipallproviders',
			'connecttoapishipprovider',
			'getapishipdeliverypointbyid',
			'getapishipprovidertariffbyid',
			'getapishipdeliverypointstypes',
			'getapishippointsbyprovider',
			'getapishipdatasetconfiguration',
			'flushdefaultstoreattributes',
			'isordersenttoapiship',
			'updateapishipproviderconnection',
			'sendapishipupdateorderrequest'
		],
		/** Права на редактирование адресов доставки через административную панель */
		'delivery_address_edit' => [
			'delivery_address_edit',
			'delivery_address.edit'
		],
		/** Права на редактирование юридических лиц через административную панель */
		'legal_person_edit' => [
			'legalpersonedit',
			'legal_person.edit'
		],
		/** Права на редактирование товарных наименований заказа через административную панель */
		'order_item_edit' => [
			'orderitemedit',
			'order_item.edit'
		],
		/** Права на редактирование незарегистрированных покупателей через административную панель */
		'customer_edit' => [
			'customeredit',
			'customer.edit'
		],
		/** Права на редактирование данных заказа "в 1 клик" через административную панель */
		'one_click_order_edit' => [
			'oneclickorderdataedit'
		],
		/** Права на оформление и изменение заказа от имени покупателя */
		'order_editing' => [
			'editorderasuser',
			'actasuser'
		],
		/** Права на api для мобильного приложения */
		'mobile_application_get_data' => [
			'getorderstatuses',
			'getordersbystatus',
			'getorder',
			'setorder',
			'addtoken',
			'removetoken'
		],
		/** Права на работу с настройками */
		'config' => [
			'config',
			'deliverysettings',
			'paymentsettings',
			'mail_config',
			'yandex_market_config'
		],
		/** Права на удаление заказов, способов доставки и оплаты, скидок, складов и валют */
		'delete' => [
			'del',
		]
	];

