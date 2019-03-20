Яндекс SDK PHP
==============

[![Build Status](https://secure.travis-ci.org/nixsolutions/yandex-sdk-php.png?branch=master)](https://travis-ci.org/nixsolutions/yandex-sdk-php)
[![Latest Stable Version](https://poser.pugx.org/nixsolutions/yandex-sdk-php/v/stable.png)](https://packagist.org/packages/nixsolutions/yandex-sdk-php)
[![Total Downloads](https://poser.pugx.org/nixsolutions/yandex-sdk-php/downloads.png)](https://packagist.org/packages/nixsolutions/yandex-sdk-php)

## Установка

### composer

Установка с использованием менеджера пакетов [Composer](http://getcomposer.org):

```bash
$ curl -s https://getcomposer.org/installer | php
```

Теперь вносим изменения в ваш `composer.json`:

```yaml
{
    "require": {
        "nixsolutions/yandex-sdk-php": "dev-master"
    }
}
```

### phar-архив

Работа с [phar архивом](http://php.net/manual/en/book.phar.php):

1. Скачиваем по [ссылке](http://yadi.sk/d/26YmC3hRByBd7) phar-файл или bz2-архив с ним, последней или конкретной версии.
2. Сохраняем в папку с проектом.
3. Используем!

Пример подключения и работа с SDK из phar-архива:
```php
<?php
//Подключаем autoload.php из phar-архива
require_once 'phar://yandex-sdk_master.phar/vendor/autoload.php';

use Yandex\Disk\DiskClient;

$disk = new DiskClient();
//Устанавливаем полученный токен
$disk->setAccessToken(TOKEN);

//Получаем список файлов из директории
$files = $disk->directoryContents();
```

## Использование

* [Yandex Site Search Pinger](https://github.com/nixsolutions/yandex-sdk-php/wiki/Yandex-Site-Search-Pinger)
* [Yandex Safe Browsing](https://github.com/nixsolutions/yandex-sdk-php/wiki/Yandex-Safe-Browsing)

## Лицензия

Пакет `yandex-sdk-php` распространяется под лицензией MIT (текст лицензии вы найдёте в файле
[LICENSE](https://raw.github.com/nixsolutions/yandex-sdk-php/master/LICENSE)), данная лицензия
распространяется на код данной библиотеки и только на неё, использование сервисов Яндекс регулируются
документами, которые вы сможете найти на странице [Правовые документы](http://legal.yandex.ru/)
