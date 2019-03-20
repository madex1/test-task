<?php

namespace Yandex\Common;

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Yandex\Common\Exception\ResponseException;
use Yandex\Common\Version;

/**
 * Class AbstractServiceClient
 *
 * @package Yandex\Common
 *
 * @author   Eugene Zabolotniy <realbaziak@gmail.com>
 * @created  13.09.13 12:09
 */
abstract class AbstractServiceClient extends AbstractPackage
{
    /**
     * Request schemes constants
     */
    const HTTPS_SCHEME = 'https';
    const HTTP_SCHEME = 'http';

    /**
     * @var string
     */
    protected $serviceScheme = self::HTTPS_SCHEME;

    /**
     * @var string
     */
    protected $serviceDomain = '';

    /**
     * @var string
     */
    protected $servicePort = '';

    /**
     * @var string
     */
    protected $accessToken = '';

    /**
     * @var string
     */
    protected $sdkName = 'yandex-sdk-php';

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->sdkName . '/' . Version::$version;
    }

    /**
     * @param string $accessToken
     *
     * @return self
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $serviceDomain
     *
     * @return self
     */
    public function setServiceDomain($serviceDomain)
    {
        $this->serviceDomain = $serviceDomain;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceDomain()
    {
        return $this->serviceDomain;
    }

    /**
     * @param string $servicePort
     *
     * @return self
     */
    public function setServicePort($servicePort)
    {
        $this->servicePort = $servicePort;

        return $this;
    }

    /**
     * @return string
     */
    public function getServicePort()
    {
        return $this->servicePort;
    }

    /**
     * @param string $serviceScheme
     *
     * @return self
     */
    public function setServiceScheme($serviceScheme = self::HTTPS_SCHEME)
    {
        $this->serviceScheme = $serviceScheme;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceScheme()
    {
        return $this->serviceScheme;
    }

    /**
     * @param string $resource
     * @return string
     */
    public function getServiceUrl($resource = '')
    {
        return $this->serviceScheme . '://' . $this->serviceDomain . '/' . rawurlencode($resource);
    }

    /**
     * Check package configuration
     *
     * @return boolean
     */
    protected function doCheckSettings()
    {
        return true;
    }

    /**
     * Sends a request
     *
     * @param Request $request
     *
     * @throws \Yandex\OAuth\AuthRequestException
     * @throws \Exception|\Guzzle\Http\Exception\ClientErrorResponseException
     * @return Response
     *
     */
    protected function sendRequest(Request $request)
    {
        try {

            $request->setHeader('User-Agent', $this->getUserAgent());
            $response = $request->send();

        } catch (ClientErrorResponseException $ex) {

            $result = $request->getResponse()->json();

            if (is_array($result) && isset($result['error'])) {
                // handle a service error message
                throw new ResponseException($result);
                //throw new ResponseException('Service responsed with error code "' . $result['error'] . '"');
            }

            // unknown error
            throw $ex;
        }

        return $response;
    }
}
