<?php
/**
 * @namespace
 */
namespace Yandex\SiteSearchPinger;

use Yandex\Common\AbstractServiceClient;
use Yandex\Common\Exception\InvalidArgumentException;
use Yandex\SiteSearchPinger\Exception\InvalidUrlException;
use Yandex\SiteSearchPinger\Exception\SiteSearchPingerException;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * SiteSearchPinger
 *
 * @category Yandex
 * @package  SiteSearchPinger
 *
 * @property string $key
 * @property string $login
 * @property string $searchId
 * @property array $invalidUrls
 *
 * @author   Anton Shevchuk
 * @created  06.08.13 17:30
 */
class SiteSearchPinger extends AbstractServiceClient
{
    /**
     * @var string
     */
    protected $host = "http://site.yandex.ru";

    /**
     * @var string
     */
    protected $path = "ping.xml";

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $login;

    /**
     * @var string
     */
    protected $searchId;

    /**
     * Connection Errors
     */
    const ERROR_ILLEGAL_PARAM_VALUE = 'ILLEGAL_PARAM_VALUE';
    const ERROR_ILLEGAL_VALUE_TYPE = 'ILLEGAL_VALUE_TYPE';
    const ERROR_NO_SUCH_USER_IN_PASSPORT = 'NO_SUCH_USER_IN_PASSPORT';
    const ERROR_SEARCH_NOT_OWNED_BY_USER = 'SEARCH_NOT_OWNED_BY_USER';
    const ERROR_TOO_DELAYED_PUBLISH = 'TOO_DELAYED_PUBLISH';
    const ERROR_USER_NOT_PERMITTED = 'USER_NOT_PERMITTED';

    /**
     * URL Errors
     */
    const INVALID_MALFORMED_URLS = 'MALFORMED_URLS';
    const INVALID_NOT_CONFIRMED_IN_WMC = 'NOT_CONFIRMED_IN_WMC';
    const INVALID_OUT_OF_SEARCH_AREA = 'OUT_OF_SEARCH_AREA';

    /**
     * @var array
     */
    protected $invalid = array(
        self::INVALID_MALFORMED_URLS => "Invalid URL format",
        self::INVALID_NOT_CONFIRMED_IN_WMC => "Invalid site URL. Site is not confirmed on http://webmaster.yandex.ru/",
        self::INVALID_OUT_OF_SEARCH_AREA => "Invalid site URL. Site is not under your search area",
    );

    /**
     * @var array
     */
    protected $invalidUrls = array();

    /**
     * set search key
     *
     * @param $value
     * @return self
     */
    public function setKey($value)
    {
        $this->key = $value;
        return $this;
    }

    /**
     * set search login
     *
     * @param $value
     * @return self
     */
    public function setLogin($value)
    {
        $this->login = $value;
        return $this;
    }

    /**
     * set search id
     *
     * @param $value
     * @return self
     */
    public function setSearchId($value)
    {
        $this->searchId = $value;
        return $this;
    }

    /**
     * get invalid Urls from request
     *
     * @return array
     */
    public function getInvalidUrls()
    {
        return $this->invalidUrls;
    }

    /**
     * ping
     *
     * @param string|array $urls
     * @param integer $publishDate seconds from now to publish urls
     *
     * @throws Exception\SiteSearchPingerException
     * @throws Exception\InvalidUrlException
     * @throws \Yandex\Common\Exception\InvalidArgumentException
     * @return boolean
     */
    public function ping($urls, $publishDate = 0)
    {
        $this->checkSettings();

        $urls = (array)$urls;

        try {
            $response = $this->doRequest($urls, $publishDate);
        } catch (ClientErrorResponseException $e) {
            $xml = $e->getResponse()->xml();

            if (isset($xml->error) && isset($xml->error->message)) {
                $errorMessage = (string) $xml->error->message;
                if (isset($xml->error->param) && isset($xml->error->value)) {
                    $errorMessage .= " (".$xml->error->param." is ".$xml->error->value.")";
                }
                throw new InvalidArgumentException($errorMessage);
            }
            return false;
        }

        if (!$xml = $response->xml()) {
            throw new SiteSearchPingerException("Wrong server response format");
        } elseif ($xml->getName() == 'empty-param') {
            // workaround for invalid request, with empty `urls`
            throw new InvalidUrlException("URL param is required");
        }

        // retrieve count of valid urls
        $addedCount = 0;
        if (isset($xml->added) && isset($xml->added['count'])) {
            $addedCount = $xml->added['count'];
        }

        // check invalid urls and fill errors stack
        $this->invalidUrls = array();
        if (isset($xml->invalid)) {
            foreach ($xml->invalid as $invalid) {
                foreach ($invalid as $url) {
                    $this->invalidUrls[(string)$url] = $this->invalid[(string) $invalid['reason']];
                }
            }
        }

        return $addedCount;
    }


    /**
     * doCheckOptions
     *
     * @return boolean
     */
    protected function doCheckSettings()
    {
        return $this->key && $this->login && $this->searchId;
    }

    /**
     * @param array $urls
     * @param integer $publishDate
     * @return \Guzzle\Http\Message\Response
     */
    protected function doRequest($urls, $publishDate)
    {
        $client = new Client($this->host);
        $client->setDefaultOption('headers', array('Y-SDK' => 'Pinger'));
        $client->setDefaultOption(
            'query',
            array(
                'key' => $this->key,
                'login' => $this->login,
                'search_id' => $this->searchId
            )
        );

        /**
         * @var \Guzzle\Http\Message\EntityEnclosingRequest $request
         */
        $request = $client->post($this->path);
        $request->setProtocolVersion('1.0');
        $request->setPostField('urls', join("\n", $urls));
        $request->setPostField('publishdate', $publishDate);
        $request->setHeader('User-Agent', $this->getUserAgent());
        return $request->send();
    }
}
