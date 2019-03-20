<?php
$settings = require_once '../settings.php';
use Yandex\Market\MarketClient;
use Yandex\Common\Exception\ForbiddenException;

//Is auth
if (isset($_COOKIE['yaAccessToken']) && isset($_COOKIE['yaClientId'])) {

    $market = new MarketClient($_COOKIE['yaAccessToken']);
    $market->setClientId($_COOKIE['yaClientId']);
    $market->setLogin($settings['global']['marketLogin']);
    $errorMessage = false;

    try {
        $campaigns = $market->getCampaigns();
    } catch (ForbiddenException $ex) {
        $errorMessage = $ex->getMessage();
        $errorMessage .= '<p>Возможно, у приложения нет прав на доступ к ресурсу. Попробуйте '
            . '<a href="/examples/OAuth/">авторизироваться</a> и повторить.</p>';

    } catch (Exception $ex) {
        $errorMessage = $ex->getMessage();
    }
}
?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Yandex.SDK: Market Demo</title>

    <link rel="stylesheet" href="//yandex.st/bootstrap/3.0.0/css/bootstrap.min.css">
    <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
    <link rel="stylesheet" href="/examples/Disk/css/style.css">

</head>
<body>


<div class="container">
    <?php
    if (!isset($_COOKIE['yaAccessToken']) || !isset($_COOKIE['yaClientId'])) {
        ?>
        <div class="alert alert-info">
            Для просмотра этой страници вам необходимо авторизироваться.
            <a id="goToAuth" href="/examples/OAuth/" class="alert-link">Перейти на страницу авторизации</a>.
        </div>
    <?php
    } else {

        if ($errorMessage) {
            ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php
        } else {
            ?>
            <div class="col-md-8">
            <h2>Кампании пользователя</h2>
            <h3>Запрос:</h3>
            <p>
                <a href="http://api.yandex.ru/market/partner/doc/dg/reference/get-campaigns.xml">
                    GET /campaigns
                </a>
            </p>

            <h3>Ответ:</h3>
            <?php
            echo '<pre>';
            print_r($campaigns);
            echo '</pre>';

            $params = array(
                'status' => null,
                'fromDate' => null,
                'toDate' => null,
                'pageSize' => 50,
                'page' => 1
            );
            $campaignId = $campaigns[0]['id'];
            $market->setCampaignId($campaignId);
            $orders = $market->getOrders($params);
            ?>
            <h2>Информация о запрашиваемых заказах</h2>
            <h3>Запрос:</h3>
            <p>
                <a href="http://api.yandex.ru/market/partner/doc/dg/reference/get-campaigns-id-orders.xml">
                    GET /campaigns/{campaignId}/orders
                </a>
            </p>

            <h3>Ответ:</h3>
            <pre>
        <?php print_r($orders); ?>
        </pre>
        <?php
        }
        ?>
        </div>
    <?php
    }
    ?>
</div>
<script src="http://yandex.st/jquery/2.0.3/jquery.min.js"></script>
<script src="http://yandex.st/jquery/cookie/1.0/jquery.cookie.min.js"></script>
<script>
    $(function () {
        $('#goToAuth').click(function (e) {
            $.cookie('back', location.href, { expires: 256, path: '/' });
        });
    });
</script>
</body>
</html>

