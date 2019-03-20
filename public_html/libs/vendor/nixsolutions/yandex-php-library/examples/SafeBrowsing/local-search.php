<?php
/**
 * Example of usage Yandex\SafeBrowsing package
 *
 * @author   Alexander Khaylo <naxel@land.ru>
 * @created  07.02.14 13:57
 */

use Yandex\SafeBrowsing\SafeBrowsingClient;
use Yandex\SafeBrowsing\SafeBrowsingException;
use Yandex\Common\Exception\Exception;

ini_set('memory_limit', '256M');

/**
 * @param string $url
 * @param string $key
 * @return bool
 */
function localSearchUrl($url, $key)
{
    $safeBrowsing = new SafeBrowsingClient($key);

    //Creating hashes by url
    $hashes = $safeBrowsing->getHashesByUrl($url);
    $localDbFile = 'hosts_prefixes.json';

    if (!is_file($localDbFile)) {
        exit('File "' . $localDbFile . '" not found');
    }

    $data = file_get_contents($localDbFile);
    $localHashPrefixes = json_decode($data, true);

    foreach ($hashes as $hash) {
        foreach ($localHashPrefixes as $shavar) {
            foreach ($shavar as $chunkNum => $chunk) {
                foreach ($chunk as $hashPrefix) {
                    if ($hash['prefix'] === $hashPrefix) {
                        //Found prefix in local DB
                        echo '<div class="alert alert-info">
                        Префикс хеша найден в локальной БД. Ищем в списке опасных сайтов...
                        </div>';
                        //Check full hash
                        if ($safeBrowsing->searchUrl($url)) {
                            return true;
                        }
                    }
                }
            }
        }
    }
    return false;
}

?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Yandex.SDK: Safe Browsing Demo</title>

    <link rel="stylesheet" href="//yandex.st/bootstrap/3.0.0/css/bootstrap.min.css">
    <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
    <link rel="stylesheet" href="/examples/Disk/css/style.css">
    <style>
        .btn {
            padding: 6px 12px;
        }
    </style>

</head>
<body>

<div class="container">
    <h3>Поиск префикса хеша сайта в локальной БД</h3>
    <?php
    try {

        $settings = require_once '../settings.php';

        if (!isset($settings["safebrowsing"]["key"]) || !$settings["safebrowsing"]["key"]) {
            throw new SafeBrowsingException('Empty Safe Browsing key');
        }

        if (isset($_GET['url']) && $_GET['url']) {
            $url = $_GET['url'];

            $key = $settings["safebrowsing"]["key"];

            $safeBrowsing = new SafeBrowsingClient($key);

            /**
             * Using "gethash" request
             */
            //If exist local DB of prefixes
            if (localSearchUrl($url, $key)) {
                ?>
                <div class="alert alert-danger">Найден полный хеш для "<?= htmlentities($url) ?>" в списке опасных сайтов</div>
            <?php
            } else {
                ?>
                <div class="alert alert-success"><?= htmlentities($url) ?> - не найден в списке опасных сайтов</div>
            <?php
            }
        }
        ?>

        <form method="get">
            <div class="input-group">
                <input name="url" placeholder="URL" type="text" class="form-control">
                      <span class="input-group-btn">
                          <input class="btn btn-primary" type="submit" value="Проверить URL"/>
                      </span>
            </div>
        </form>
        <p>
            Пример: http://www.wmconvirus.narod.ru/
        </p>
        <div>
            Также можно посмотреть примеры:
            <ul>
                <li>
                    <a href="index.php">Проверить адреса</a>
                </li>
                <li>
                    <a href="lookup.php">Lookup API и Check Adult API</a>
                </li>
                <li>
                    <a href="save-prefixes-db.php">Сохранение базы префиксов хешей вредоносных сайтов (начнется
                        автоматически)</a>
                </li>
                <li>
                    <a href="update-prefixes-db.php">Обновить локальную базу префиксов хешей вредоносных сайтов (начнется
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
