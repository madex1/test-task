<?php
/**
 * Example of usage Yandex\SiteSearchPinger package
 *
 * @author   Anton Shevchuk
 * @created  07.08.13 10:32
 */

use Yandex\SiteSearchPinger\SiteSearchPinger;
use Yandex\SiteSearchPinger\Exception\SiteSearchPingerException;
use Yandex\Common\Exception\Exception;

?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Yandex.SDK: Pinger Demo</title>

    <link rel="stylesheet" href="//yandex.st/bootstrap/3.0.0/css/bootstrap.min.css">
    <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
    <link rel="stylesheet" href="/examples/Disk/css/style.css">

</head>
<body>

<div class="container">

    <div class="col-md-8">
        <?php
        try {

            $settings = require_once '../settings.php';
            $pinger = new SiteSearchPinger();

            if (!isset($settings["pinger"]["key"]) || !$settings["pinger"]["key"]) {
                throw new SiteSearchPingerException('Empty pinger key');
            }
            if (!isset($settings["pinger"]["login"]) || !$settings["pinger"]["login"]) {
                throw new SiteSearchPingerException('Empty pinger key');
            }
            if (!isset($settings["pinger"]["searchId"]) || !$settings["pinger"]["searchId"]) {
                throw new SiteSearchPingerException('Empty pinger key');
            }

            $pinger->key = $settings["pinger"]["key"];
            $pinger->login = $settings["pinger"]["login"];
            $pinger->searchId = $settings["pinger"]["searchId"];

            $url = array(
                "http://anton.shevchuk.name/php/php-development-environment-under-macos/",
                "http://anton.shevchuk.name/php/php-framework-bluz-update/",
                "http://ya.ru",
                "http://yandex.ru",
                "yaru",
                "yarus",
            );

            $added = $pinger->ping($url);

            echo "OK. " . $added . " from " . sizeof($url) . " urls was added to queue<br/>";

            if (sizeof($pinger->invalidUrls)) {
                echo "Invalid Urls:" . "<br/>";
                foreach ($pinger->invalidUrls as $url => $reason) {
                    echo $url . " - " . $reason . "<br/>";
                }
            }
        } catch (SiteSearchPingerException $e) {
            echo "Site Search Pinger Exception:<br/>";
            echo nl2br($e->getMessage());
        } catch (Exception $e) {
            echo "Yandex SDK Exception:<br/>";
            echo nl2br($e->getMessage());
        } catch (\Exception $e) {
            echo get_class($e) . "<br/>";
            echo nl2br($e->getMessage());
        }
        ?>
    </div>
</div>
</body>
</html>

