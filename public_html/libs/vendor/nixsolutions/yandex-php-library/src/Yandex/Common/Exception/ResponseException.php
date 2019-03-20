<?php
/**
 * @namespace
 */
namespace Yandex\Common\Exception;

/**
 * Response Exception
 *
 * @category Yandex
 * @package  Common
 *
 * @author   Alexander Khaylo <naxel@land.ru>
 * @created  21.11.13 17:30
 */
class ResponseException extends \Exception
{
    public function __construct($message = "", $code = 0, $previous = null)
    {
        if (is_array($message) && isset($message['error']) && isset($message['message'])) {

            switch ($message['error']) {
                case 'MissedRequiredArguments':
                    throw new MissedArgumentException($message['message']);
                case 'AssistantProfileNotFound':
                    throw new ProfileNotFoundException($message['message']);
                default:
                    throw new Exception($message['message']);
            }
        }
    }
}
