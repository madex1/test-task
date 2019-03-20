<?php
namespace Yandex\Market;

use Yandex\Common\AbstractServiceClient;
use Guzzle\Service\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Yandex\Common\Exception\ForbiddenException;

/**
 * Class MarketClient
 *
 * @category Yandex
 * @package Market
 *
 * @author   Alexander Khaylo <naxel@land.ru>
 * @created  04.11.13 12:48
 */
class MarketClient extends AbstractServiceClient
{

    /**
     * Order is being processed
     */
    const ORDER_STATUS_PROCESSING = 'PROCESSING';

    /**
     * Order submitted to the delivery
     */
    const ORDER_STATUS_DELIVERY = 'DELIVERY';

    /**
     *  Order delivered to the point of self-delivery
     */
    const ORDER_STATUS_PICKUP = 'PICKUP';

    /**
     * The order is received by the buyer
     */
    const ORDER_STATUS_DELIVERED = 'DELIVERED';

    /**
     * Order canceled.
     */
    const ORDER_STATUS_CANCELLED = 'CANCELLED';

    //Sub-statuses for status CANCELLED
    // - the buyer is not finalized the reserved order on time
    const ORDER_SUBSTATUS_RESERVATION_EXPIRED = 'RESERVATION_EXPIRED';
    // - the buyer did not pay for the order ( for the type of payment PREPAID)
    const ORDER_SUBSTATUS_USER_NOT_PAID = 'USER_NOT_PAID';
    // - failed to communicate with the buyer
    const ORDER_SUBSTATUS_USER_UNREACHABLE = 'USER_UNREACHABLE';
    // - buyer canceled the order for cause
    const ORDER_SUBSTATUS_USER_CHANGED_MIND = 'USER_CHANGED_MIND';
    // - the buyer is not satisfied with the terms of delivery
    const ORDER_SUBSTATUS_USER_REFUSED_DELIVERY = 'USER_REFUSED_DELIVERY';
    // - the buyer did not fit the goods
    const ORDER_SUBSTATUS_USER_REFUSED_PRODUCT = 'USER_REFUSED_PRODUCT';
    // - shop can not fulfill the order
    const ORDER_SUBSTATUS_SHOP_FAILED = 'SHOP_FAILED';
    // - the buyer is not satisfied with the quality of the goods
    const ORDER_SUBSTATUS_USER_REFUSED_QUALITY = 'USER_REFUSED_QUALITY';
    // - buyer changes the composition of the order
    const ORDER_SUBSTATUS_REPLACING_ORDER = 'REPLACING_ORDER';
    //- store does not process orders on time
    const ORDER_SUBSTATUS_PROCESSING_EXPIRED = 'PROCESSING_EXPIRED';


    /**
     * Requested version of API
     * @var string
     */
    private $version = 'v2';


    /**
     * Application id
     *
     * @var string
     */
    protected $clientId;


    /**
     * User login
     *
     * @var string
     */
    protected $login;


    /**
     * Campaign Id
     *
     * @var string
     */
    protected $campaignId;


    /**
     * API domain
     *
     * @var string
     */
    protected $serviceDomain = 'api.partner.market.yandex.ru';


    /**
     * Get url to service resource with parameters
     *
     * @param string $resource
     * @see http://api.yandex.ru/market/partner/doc/dg/concepts/method-call.xml
     * @return string
     */
    public function getServiceUrl($resource = '')
    {
        return $this->serviceScheme . '://' . $this->serviceDomain . '/'
        . $this->version . '/' . $resource;
    }


    /**
     * @param string $token access token
     */
    public function __construct($token = '')
    {
        $this->setAccessToken($token);
    }


    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }


    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }


    /**
     * @param string $campaignId
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
    }


    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }


    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }


    /**
     * Sends a request
     *
     * @param Request $request
     * @return Response
     * @throws ForbiddenException
     * @throws MarketRequestException
     */
    protected function sendRequest(Request $request)
    {
        try {

            $request->setHeader('User-Agent', $this->getUserAgent());
            $response = $request->send();

        } catch (ClientErrorResponseException $ex) {

            $result = $request->getResponse();
            $code = $result->getStatusCode();
            $message = $result->getReasonPhrase();

            if ($code === 403) {
                throw new ForbiddenException($message);
            }

            throw new MarketRequestException(
                'Service responded with error code: "' . $code . '" and message: "' . $message . '"'
            );
        }

        return $response;
    }


    /**
     * Get OAuth data for header request
     *
     * @see http://api.yandex.ru/market/partner/doc/dg/concepts/authorization.xml
     *
     * @return string
     */
    public function getOauthData()
    {
        return 'OAuth oauth_token=' . $this->getAccessToken() . ', oauth_client_id=' . $this->getClientId()
        . ', oauth_login=' . $this->getLogin();
    }


    /**
     * Get User Campaigns
     *
     * Returns the user to the list of campaigns Yandex.market.
     * The list coincides with the list of campaigns
     * that are displayed in the partner interface Yandex.Market on page "My shops."
     *
     * @see http://api.yandex.ru/market/partner/doc/dg/reference/get-campaigns.xml
     *
     * @return array
     */
    public function getCampaigns()
    {
        $resource = 'campaigns.json';
        $client = new Client($this->getServiceUrl($resource));
        $request = $client->createRequest('GET');
        $request->setProtocolVersion('1.1');
        $request->setHeader('Authorization', $this->getOauthData());
        $request->setHeader('Host', $this->getServiceDomain());
        $request->setHeader('Accept', '*/*');
        $response = $this->sendRequest($request)->json();
        return $response['campaigns'];
    }


    /**
     * Get information about orders by campaign id
     *
     * @param array $params
     *
     * Returns information on the requested orders.
     * Available filtering by date ordering and order status.
     * The maximum range of dates in a single request for a resource - 30 days.
     *
     * @see http://api.yandex.ru/market/partner/doc/dg/reference/get-campaigns-id-orders.xml
     *
     * @return array
     */
    public function getOrders($params = array())
    {
        $resource = 'campaigns/' . $this->campaignId . '/orders.json';
        $resource .= '?' . http_build_query($params);

        $client = new Client($this->getServiceUrl($resource));
        $request = $client->createRequest('GET');
        $request->setProtocolVersion('1.1');
        $request->setHeader('Authorization', $this->getOauthData());
        $request->setHeader('Host', $this->getServiceDomain());
        $request->setHeader('Accept', '*/*');
        $response = $this->sendRequest($request)->json();
        return $response;
    }


    /**
     * Get order info
     *
     * @param int $orderId
     * @return array
     *
     * @link http://api.yandex.ru/market/partner/doc/dg/reference/get-campaigns-id-orders-id.xml
     */
    public function getOrder($orderId)
    {
        $resource = 'campaigns/' . $this->campaignId . '/orders/' . $orderId . '.json';

        $client = new Client($this->getServiceUrl($resource));
        $request = $client->createRequest('GET');
        $request->setProtocolVersion('1.1');
        $request->setHeader('Authorization', $this->getOauthData());
        $request->setHeader('Host', $this->getServiceDomain());
        $request->setHeader('Accept', '*/*');
        $response = $this->sendRequest($request)->json();
        return $response;
    }


    /**
     * Send changed status to Yandex.Market
     *
     * @param int $orderId
     * @param string $status
     * @param null|string $subStatus
     * @return array
     *
     * @link http://api.yandex.ru/market/partner/doc/dg/reference/put-campaigns-id-orders-id-status.xml
     */
    public function setOrderStatus($orderId, $status, $subStatus = null)
    {
        $resource = 'campaigns/' . $this->campaignId . '/orders/' . $orderId . '/status.json';

        $data = array(
            "order" => array(
                "status" => $status
            )
        );
        if ($subStatus) {
            $data['order']['substatus'] = $subStatus;
        }

        $data = json_encode($data);
        $client = new Client($this->getServiceUrl($resource));
        $request = $client->createRequest('PUT', null, null, $data);
        $request->setProtocolVersion('1.1');
        $request->setHeader('Authorization', $this->getOauthData());
        $request->setHeader('Host', $this->getServiceDomain());
        $request->setHeader('Accept', '*/*');
        $request->setHeader('Content-type', 'application/json');

        $response = $this->sendRequest($request)->json();
        return $response;
    }


    /**
     * Update changed delivery parameters
     *
     * @param int $orderId
     * @param array $data
     * @return array
     *
     * Example:
     * PUT /v2/campaigns/10003/order/12345/delivery.json HTTP/1.1
     *
     * @link http://api.yandex.ru/market/partner/doc/dg/reference/put-campaigns-id-orders-id-delivery.xml
     */
    public function updateDelivery($orderId, $data)
    {
        $resource = 'campaigns/' . $this->campaignId . '/orders/' . $orderId . '/delivery.json';
        $data = json_encode($data);
        $client = new Client($this->getServiceUrl($resource));
        $request = $client->createRequest('PUT', null, null, $data);
        $request->setProtocolVersion('1.1');
        $request->setHeader('Authorization', $this->getOauthData());
        $request->setHeader('Host', $this->getServiceDomain());
        $request->setHeader('Accept', '*/*');
        $request->setHeader('Content-type', 'application/json');

        $response = $this->sendRequest($request)->json();
        return $response;
    }
}
