<?php
$settings = require_once '../settings.php';
session_start();
?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Yandex.SDK: OAuth Demo Callback</title>

    <link rel="stylesheet" href="//yandex.st/bootstrap/3.0.0/css/bootstrap.min.css">
    <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
    <link rel="stylesheet" href="/examples/Disk/css/style.css">

</head>
<body>


<div class="container">
    <div class="col-md-8">
        <p>
            <a href="index.php">&lt;Назад</a>
        </p>

        <?php
        use Yandex\OAuth\OAuthClient;

        $client = new OAuthClient($settings['global']['clientId'], $settings['global']['clientSecret']);

        if (isset($_COOKIE['yaAccessToken'])) {

            $client->setAccessToken($_COOKIE['yaAccessToken']);
            echo '<p>PHP: Access token from cookies is: ' . htmlentities($client->getAccessToken()) . '</p>';
        }

        /**
         * There are two ways to get an access token from Yandex OAuth API.
         * First one is to get in browser after # symbol. It's handled above with JS and PHP.
         * Second one is to use an intermediate code. It's handled below with PHP.
         *
         * This file implements both cases because the only one callback url can be set for an application.
         *
         */

        if (isset($_REQUEST['code'])) {

            try {
                $client->requestAccessToken($_REQUEST['code']);
            } catch (\Yandex\OAuth\AuthRequestException $ex) {
                echo $ex->getMessage();
            }

            if ($client->getAccessToken()) {
                echo "<p>PHP: Access token from server is " . $client->getAccessToken() . '</p>';
                setcookie('yaAccessToken', $client->getAccessToken(), 0, '/');
                setcookie('yaClientId', $settings['global']['clientId'], 0, '/');
            }

        } elseif (isset($_GET['error'])) {

            echo '<p>PHP: Server redirected with error "' . htmlentities($_GET['error']) . '"';

            if (isset($_GET['error_description'])) {
                echo ' and message "' . htmlentities($_GET['error_description']) . '"';
            }
            echo '</p>';
        }

        if ($client->getAccessToken() && isset($_SESSION['back'])) {

            $back = $_SESSION['back'];
            $_SESSION['back'] = null;
            header('Location: ' . $back);
        } elseif ($client->getAccessToken() && isset($_COOKIE['back'])) {
            $back = $_COOKIE['back'];
            $_COOKIE['back'] = null;
            header('Location: ' . $back);
        }

        ?>
        <p style="font-size: 20px;" id="info"></p>
    </div>
</div>
<script src="http://yandex.st/jquery/2.0.3/jquery.min.js"></script>
<script src="http://yandex.st/jquery/cookie/1.0/jquery.cookie.min.js"></script>
<script>

    // handle access token, set it to cookies and close the window
    var result = /access_token=([0-9a-f]+)/.exec(document.location.hash);

    if (result != null) {
        var accessToken = result[1];

        $.cookie('yaAccessToken', accessToken, { expires: 256, path: '/' });
        $.cookie('yaClientId', '<?=$settings['global']['clientId']?>', { expires: 256, path: '/' });

        if (null !== opener) {
            opener.yaAuthCallback(accessToken);
            window.close();
        } else {
            document.getElementById('info').innerHTML = "JS: Access token is " + accessToken
                + ". Refreshing page in 5 seconds...";
            setInterval(function () {
                window.location.href = window.location.search;
            }, 5000);
        }
    }

</script>
</body>
</html>

