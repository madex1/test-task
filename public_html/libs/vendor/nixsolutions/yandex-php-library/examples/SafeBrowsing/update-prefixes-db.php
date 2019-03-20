<?php
/**
 * Example of usage Yandex\SafeBrowsing package
 *
 * @author   Alexander Khaylo <naxel@land.ru>
 * @created  07.02.14 14:00
 */
ini_set('memory_limit', '256M');
$settings = require_once '../settings.php';
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
    <h3>Обновление локальной базы префиксов хешей вредоносных сайтов</h3>
    <?php
    try {

        $settings = require_once '../settings.php';

        if (!isset($settings["safebrowsing"]["key"]) || !$settings["safebrowsing"]["key"]) {
            throw new SafeBrowsingException('Empty Safe Browsing key');
        }

        $key = $settings["safebrowsing"]["key"];

        $safeBrowsing = new SafeBrowsingClient($key);
        $localDbFile = 'hosts_prefixes.json';

        if (!is_file($localDbFile)) {
            exit('File "' . $localDbFile . '" not found');
        }

        $data = file_get_contents($localDbFile);
        $localHashPrefixes = json_decode($data, true);

        /**
         * Example:
         */
        //$savedChunks['ydx-malware-shavar'] = array(
        //    'added' => array(
        //        'min' => 1,
        //        'max' => 30000
        //    ),
        //    'removed' => array(
        //        'min' => 1,
        //        'max' => 30000
        //    )
        //
        //);
        $savedChunks = array();

        foreach ($localHashPrefixes as $shavar => $shavarData) {

            $minChunkNum = false;
            $maxChunkNum = false;
            foreach ($shavarData as $chunkNum => $chunk) {
                if (!$maxChunkNum && !$minChunkNum) {
                    $minChunkNum = $chunkNum;
                    $maxChunkNum = $chunkNum;
                } elseif ($chunkNum > $maxChunkNum) {
                    $maxChunkNum = $chunkNum;
                } elseif ($chunkNum < $minChunkNum) {
                    $minChunkNum = $chunkNum;
                }
            }

            if ($minChunkNum && $maxChunkNum) {
                $savedChunks[$shavar]['added'] = array(
                    'min' => $minChunkNum,
                    'max' => $maxChunkNum
                );
            }
        }

        /**
         * Using "downloads" request
         */
        $malwaresData = $safeBrowsing->getMalwaresData($savedChunks);

        if (is_string($malwaresData) && $malwaresData === 'pleasereset') {
            ?>
            <div class="alert alert-info">Нужно сбросить БД</div>
        <?php
        } else {
            $newPrefixes = array();
            $removedPrefixes = array();
            $newChunks = 0;
            $removedChunks = 0;
            foreach ($malwaresData as $shavarName => $types) {

                //Need add new malwares hash prefixes
                if (isset($types['added'])) {
                    foreach ($types['added'] as $chunkNum => $chunkData) {
                        if (!isset($localHashPrefixes[$shavarName][$chunkNum])) {
                            $localHashPrefixes[$shavarName][$chunkNum] = $chunkData;
                            $newChunks++;
                        }
                    }
                }

                //Need remove chunks
                if (isset($types['removed'])) {
                    foreach ($types['removed'] as $chunkNum => $chunkData) {
                        if (isset($localHashPrefixes[$shavarName][$chunkNum])) {
                            unset($localHashPrefixes[$shavarName][$chunkNum]);
                            $removedChunks++;
                        }
                    }
                }

                //Need remove chunks range
                if (isset($types['delete_added_ranges'])) {
                    foreach ($types['delete_added_ranges'] as $range) {
                        for ($i = $range['min']; $i <= $range['max']; $i++) {
                            if (isset($localHashPrefixes[$shavarName][$i])) {
                                //Remove chunk
                                unset($localHashPrefixes[$shavarName][$i]);
                            }
                        }
                    }
                }

            }
            ?>
            <div class="alert alert-info">Новых кусков: <?= $newChunks ?></div>
            <div class="alert alert-info">Кусков, в которых содержаться более не опасные
                сайты: <?= $removedChunks ?></div>
            <?php
            file_put_contents('hosts_prefixes.json', json_encode($localHashPrefixes));
            ?>
            <div class="alert alert-success">Локальная БД обновлена успешно.</div>
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
                        <a href="update-prefixes-db.php">Обновить локальную базу префиксов хешей вредоносных сайтов
                            (начнется автоматически)</a>
                    </li>
                </ul>
            </div>
        <?php
        }

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
