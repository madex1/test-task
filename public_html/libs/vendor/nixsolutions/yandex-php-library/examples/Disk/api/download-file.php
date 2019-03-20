<?php
/**
 * Example of usage Yandex\Disk package
 *
 * @author   Alexander Mitsura
 * @created  15.10.13 10:37
 */

$settings = require_once '../../settings.php';

use Yandex\OAuth\OAuthClient;

$client = new OAuthClient($settings['global']['clientId']);

if (isset($_COOKIE['yaAccessToken'])) {

    $file = $_GET['file'];

    $client->setAccessToken($_COOKIE['yaAccessToken']);

    // XXX: how it should be (using user access token)
    //$diskClient = new \Yandex\Disk\DiskClient($client->getAccessToken());

    // XXX: how it is now (using magic access token)
    $diskClient = new \Yandex\Disk\DiskClient($client->getAccessToken());

    $diskClient->setServiceScheme(\Yandex\Disk\DiskClient::HTTPS_SCHEME);

    $file = $diskClient->downloadFile($file);
    header('Content-Description: File Transfer');
    header('Connection: Keep-Alive');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-type: ' . $file['headers']['Last-Modified']);
    header('Etag: ' . $file['headers']['Etag']);
    header('Date: ' . $file['headers']['Date']);
    header('Content-Type: ' . $file['headers']['Content-Type']);
    header('Content-Length: ' . $file['headers']['Content-Length']);
    header('Content-Disposition: ' . $file['headers']['Content-Disposition']);
    header('Accept-Ranges: ' . $file['headers']['Accept-Ranges']);

    echo $file['body'];
}