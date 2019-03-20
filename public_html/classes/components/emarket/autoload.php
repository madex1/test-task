<?php

	$classes = [
		'discount' => [
			__DIR__ . '/classes/discounts/discount.php'
		],

		'discountModificator' => [
			__DIR__ . '/classes/discounts/discountModificator.php'
		],

		'itemDiscountRule' => [
			__DIR__ . '/classes/discounts/iItemDiscountRule.php'
		],

		'orderDiscountRule' => [
			__DIR__ . '/classes/discounts/iOrderDiscountRule.php'
		],

		'discountRule' => [
			__DIR__ . '/classes/discounts/discountRule.php'
		],

		'order' => [
			__DIR__ . '/classes/orders/order.php'
		],

		'orderItem' => [
			__DIR__ . '/classes/orders/orderItem.php'
		],

		'iOrderNumber' => [
			__DIR__ . '/classes/orders/number/iOrderNumber.php'
		],

		'delivery' => [
			__DIR__ . '/classes/delivery/delivery.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\Russianpost\PackIdProvider' => [
			__DIR__ . '/classes/delivery/systems/russianpost/PackIdProvider.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\Russianpost\iPackIdProvider' => [
			__DIR__ . '/classes/delivery/systems/russianpost/iPackIdProvider.php'
		],

		'payment' => [
			__DIR__ . '/classes/payment/payment.php'
		],

		'customer' => [
			__DIR__ . '/classes/customer/customer.php'
		],

		'emarketTop' => [
			__DIR__ . '/classes/stat/emarketTop.php'
		],

		'UmiCms\Classes\Components\Emarket\Orders\Calculator' => [
			__DIR__ . '/classes/orders/Calculator.class.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestSender' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestSender.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iRequestSender' => [
			__DIR__ . '/classes/delivery/api/ApiShip/iRequestSender.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\ProvidersFactory' => [
			__DIR__ . '/classes/delivery/api/ApiShip/ProvidersFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\ProvidersSettings' => [
			__DIR__ . '/classes/delivery/api/ApiShip/ProvidersSettings.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Provider' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Provider.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\Collection' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Orders/Collection.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\iCollection' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Orders/iCollection.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\ConstantMap' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Orders/ConstantMap.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\CalculateDeliveryCost' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestData/CalculateDeliveryCost.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\SendOrder' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestData/SendOrder.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\ConnectProvider' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestData/ConnectProvider.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\iCalculateDeliveryCost' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestData/iCalculateDeliveryCost.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\iSendOrder' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestData/iSendOrder.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\iOrder' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/iOrder.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\iOrderCost' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/iOrderCost.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\iDeliveryAgent' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/iDeliveryAgent.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\iOrderItem' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/iOrderItem.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\iCity' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/iCity.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\iConnectProvider' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestData/iConnectProvider.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iProvidersFactory' => [
			__DIR__ . '/classes/delivery/api/ApiShip/iProvidersFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iProvider' => [
			__DIR__ . '/classes/delivery/api/ApiShip/iProvider.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataFactory' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iRequestDataFactory' => [
			__DIR__ . '/classes/delivery/api/ApiShip/iRequestDataFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\iEntity' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Orders/iEntity.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iProvidersSettings' => [
			__DIR__ . '/classes/delivery/api/ApiShip/iProvidersSettings.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\Order' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/Order.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\OrderCost' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/OrderCost.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\DeliveryAgent' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/DeliveryAgent.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\OrderItem' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/OrderItem.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\City' => [
			__DIR__ . '/classes/delivery/api/ApiShip/RequestDataParts/City.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\Entity' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Orders/Entity.php'
		],

		'ApiShipDelivery' => [
			__DIR__ . '/classes/delivery/systems/ApiShip.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\A1' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/A1.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\B2cpl' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/B2cpl.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Boxberry' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Boxberry.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Cdek' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Cdek.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Dalli' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Dalli.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Dpd' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Dpd.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Hermes' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Hermes.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Iml' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Iml.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Maxi' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Maxi.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Pickpoint' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Pickpoint.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Pony' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Pony.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers\Spsr' => [
			__DIR__ . '/classes/delivery/api/ApiShip/providers/Spsr.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\OrderStatuses' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Enums/OrderStatuses.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\DeliveryTypes' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Enums/DeliveryTypes.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\PickupTypes' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Enums/PickupTypes.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\PointOperations' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Enums/PointOperations.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\PointTypes' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Enums/PointTypes.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\ProvidersKeys' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Enums/ProvidersKeys.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\OrderStatusConverter' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Utils/OrderStatusConverter.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\iOrderStatusConverter' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Utils/iOrderStatusConverter.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Utils/ArgumentsValidator.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\iArgumentsValidator' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Utils/iArgumentsValidator.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Exceptions\UnsupportedProviderKeyException' => [
			__DIR__ . '/classes/delivery/api/ApiShip/Exceptions/UnsupportedProviderKeyException.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\Address\Address' => [
			__DIR__ . '/classes/delivery/Address/Address.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\Address\iAddress' => [
			__DIR__ . '/classes/delivery/Address/iAddress.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\Address\AddressFactory' => [
			__DIR__ . '/classes/delivery/Address/AddressFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Delivery\Address\iAddressFactory' => [
			__DIR__ . '/classes/delivery/Address/iAddressFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Kassa' => [
			__DIR__ . '/classes/payment/api/Yandex/Client/Kassa.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\iKassa' => [
			__DIR__ . '/classes/payment/api/Yandex/Client/iKassa.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Exception\Response\Incorrect' => [
			__DIR__ . '/classes/payment/api/Yandex/Client/Exception/Response/Incorrect.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Exception\Response\Error' => [
			__DIR__ . '/classes/payment/api/Yandex/Client/Exception/Response/Error.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\iVat' => [
			__DIR__ . '/classes/Tax/Rate/iVat.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat' => [
			__DIR__ . '/classes/Tax/Rate/Vat.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Facade' => [
			__DIR__ . '/classes/Tax/Rate/Vat/Facade.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFacade' => [
			__DIR__ . '/classes/Tax/Rate/Vat/iFacade.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Factory' => [
			__DIR__ . '/classes/Tax/Rate/Vat/Factory.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFactory' => [
			__DIR__ . '/classes/Tax/Rate/Vat/iFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Repository' => [
			__DIR__ . '/classes/Tax/Rate/Vat/Repository.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iRepository' => [
			__DIR__ . '/classes/Tax/Rate/Vat/iRepository.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\iSubject' => [
			__DIR__ . '/classes/payment/Parameter/iSubject.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Subject' => [
			__DIR__ . '/classes/payment/Parameter/Subject.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Subject\Facade' => [
			__DIR__ . '/classes/payment/Parameter/Subject/Facade.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Subject\iFacade' => [
			__DIR__ . '/classes/payment/Parameter/Subject/iFacade.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Subject\Factory' => [
			__DIR__ . '/classes/payment/Parameter/Subject/Factory.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Subject\iFactory' => [
			__DIR__ . '/classes/payment/Parameter/Subject/iFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Subject\Repository' => [
			__DIR__ . '/classes/payment/Parameter/Subject/Repository.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Subject\iRepository' => [
			__DIR__ . '/classes/payment/Parameter/Subject/iRepository.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\iMode' => [
			__DIR__ . '/classes/payment/Parameter/iMode.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Mode' => [
			__DIR__ . '/classes/payment/Parameter/Mode.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Mode\Facade' => [
			__DIR__ . '/classes/payment/Parameter/Mode/Facade.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Mode\iFacade' => [
			__DIR__ . '/classes/payment/Parameter/Mode/iFacade.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Mode\Factory' => [
			__DIR__ . '/classes/payment/Parameter/Mode/Factory.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Mode\iFactory' => [
			__DIR__ . '/classes/payment/Parameter/Mode/iFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Mode\Repository' => [
			__DIR__ . '/classes/payment/Parameter/Mode/Repository.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\Mode\iRepository' => [
			__DIR__ . '/classes/payment/Parameter/Mode/iRepository.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter' => [
			__DIR__ . '/classes/Serializer/Receipt/iParameter.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter' => [
			__DIR__ . '/classes/Serializer/Receipt/Parameter.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Facade' => [
			__DIR__ . '/classes/Serializer/Receipt/Parameter/Facade.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFacade' => [
			__DIR__ . '/classes/Serializer/Receipt/Parameter/iFacade.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Factory' => [
			__DIR__ . '/classes/Serializer/Receipt/Parameter/Factory.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFactory' => [
			__DIR__ . '/classes/Serializer/Receipt/Parameter/iFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Repository' => [
			__DIR__ . '/classes/Serializer/Receipt/Parameter/Repository.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iRepository' => [
			__DIR__ . '/classes/Serializer/Receipt/Parameter/iRepository.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt' => [
			__DIR__ . '/classes/Serializer/Receipt.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\iReceipt' => [
			__DIR__ . '/classes/Serializer/iReceipt.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Factory' => [
			__DIR__ . '/classes/Serializer/Receipt/Factory.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\iFactory' => [
			__DIR__ . '/classes/Serializer/Receipt/iFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayAnyWay' => [
			__DIR__ . '/classes/Serializer/Receipt/PayAnyWay.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\RoboKassa' => [
			__DIR__ . '/classes/Serializer/Receipt/RoboKassa.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\YandexKassa3' => [
			__DIR__ . '/classes/Serializer/Receipt/YandexKassa3.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\YandexKassa4' => [
			__DIR__ . '/classes/Serializer/Receipt/YandexKassa4.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayOnline' => [
			__DIR__ . '/classes/Serializer/Receipt/PayOnline.php'
		],

		'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Sberbank' => [
			__DIR__ . '/classes/Serializer/Receipt/Sberbank.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client\Exception\Response\Error' => [
			__DIR__ . '/classes/payment/api/PayOnline/Client/Exception/Response/Error.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client\Exception\Response\Incorrect' => [
			__DIR__ . '/classes/payment/api/PayOnline/Client/Exception/Response/Incorrect.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client\Fiscal' => [
			__DIR__ . '/classes/payment/api/PayOnline/Client/Fiscal.php'
		],

		'UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client\iFiscal' => [
			__DIR__ . '/classes/payment/api/PayOnline/Client/iFiscal.php'
		],

		'UmiCms\Classes\Components\Emarket\Orders\Items\Filter' => [
			__DIR__ . '/classes/orders/items/Filter.php'
		],

		'UmiCms\Classes\Components\Emarket\Orders\Items\iFilter' => [
			__DIR__ . '/classes/orders/items/iFilter.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\iCalculator' => [
			__DIR__ . '/classes/Tax/Rate/iCalculator.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Calculator' => [
			__DIR__ . '/classes/Tax/Rate/Calculator.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\iParser' => [
			__DIR__ . '/classes/Tax/Rate/iParser.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Parser' => [
			__DIR__ . '/classes/Tax/Rate/Parser.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Parser\iFactory' => [
			__DIR__ . '/classes/Tax/Rate/Parser/iFactory.php'
		],

		'UmiCms\Classes\Components\Emarket\Tax\Rate\Parser\Factory' => [
			__DIR__ . '/classes/Tax/Rate/Parser/Factory.php'
		],

	];
