<?php
/**
 * Example of usage Yandex\SafeBrowsing package
 *
 * @author   Alexander Khaylo <naxel@land.ru>
 * @created  07.02.14 14:00
 */

use Yandex\SafeBrowsing\SafeBrowsingClient;
use Yandex\SafeBrowsing\SafeBrowsingException;
use Yandex\Common\Exception\Exception;

?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Yandex.SDK: Safe Browsing Demo</title>

    <link rel="stylesheet" href="//yandex.st/bootstrap/3.0.0/css/bootstrap.min.css">
    <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
    <link rel="stylesheet" href="/examples/Disk/css/style.css">
</head>
<body>

<div class="container">
    <h3>Сохранение базы префиксов хешей вредоносных сайтов</h3>
    <?php
    try {

        $settings = require_once '../settings.php';

        if (!isset($settings["safebrowsing"]["key"]) || !$settings["safebrowsing"]["key"]) {
            throw new SafeBrowsingException('Empty Safe Browsing key');
        }

        $key = $settings["safebrowsing"]["key"];

        $safeBrowsing = new SafeBrowsingClient($key);

        //Get all shavars from Yandex Safe Browsing
        /**
         * Using "list" request
         */
        $shavarsList = $safeBrowsing->getShavarsList();?>

        <p>Списки опасных сайтов:</p>
        <ul>
            <?php
            foreach ($shavarsList as $shavar) {
                ?>
                <li><?= $shavar ?></li>
            <?php } ?>
        </ul>
        <?php
        $safeBrowsing->setMalwareShavars($shavarsList);

        /**
         * Using "downloads" request
         */
        $malwaresData = $safeBrowsing->getMalwaresData();
        $newPrefixes = array();
        $removedPrefixes = array();

        foreach ($malwaresData as $shavarName => $types) {

            if (isset($types['added'])) {
                $newPrefixes[$shavarName] = $types['added'];
                file_put_contents('hosts_prefixes_' . $shavarName . '.json', json_encode($newPrefixes[$shavarName]));
            }
            if (isset($types['removed'])) {
                $removedPrefixes[$shavarName] = $types['removed'];
            }
        }

        $localDbFile = 'hosts_prefixes_all.json';
        file_put_contents($localDbFile, json_encode($newPrefixes));

        ?>
        <div class="alert alert-success">Сохранены префиксы хешей в "<?= $localDbFile ?>"</div>
        <div>
            Также можно посмотреть примеры:
            <ul>
                <li>
                    <a href="index.php">Проверить адреса</a>
                </li>
                <li>
                    <a href="local-search.php">Поиск префикса хеша сайта в локальной БД</a>
                </li>
                <li>
                    <a href="lookup.php">Lookup API и Check Adult API</a>
                </li>
                <li>
                    <a href="save-prefixes-db.php">Сохранение базы префиксов хешей вредоносных сайтов (начнется
                        автоматически)</a>
                </li>
            </ul>
        </div>
    <?php

    } catch (SafeBrowsingException $e) {
        echo "Safe Browsing Exception:<br/>";
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

</body>
</html>
