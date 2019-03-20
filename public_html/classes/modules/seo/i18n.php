<?php

$i18n = Array(
	"header-seo-seo"			=> "Анализ позиций",
	"header-seo-megaindex"		=> "Настройки системы MegaIndex",
	"perms-seo-seo" 			=> "SEO-функции",

	"header-seo-config"			=> "Настройки SEO",
	"header-seo-links"			=> "Анализ ссылок",
	"label-seo-domain"			=> "Домен",
	"option-seo-title"			=> "Префикс для TITLE",
	"option-seo-default-title" => "TITLE (по умолчанию)",
	"option-seo-keywords"		=> "Keywords (по умолчанию)",
	"option-seo-description"	=> "Description (по умолчанию)",
	"header-seo-domains"		=> "SEO настройки доменов",

	"label-site-address"		=> "Адрес сайта",
	"label-site-analysis"		=> "Анализ сайта",
	"label-button"				=> "Проверить",
	"label-repeat"				=> "Повторить",
	"label-results"				=> "Результаты",

	"label-query"				=> "Запрос",
	"label-yandex"				=> "Яндекс",
	"label-google"				=> "Google",
	"label-count"				=> "Запросов в месяц",
	"label-wordstat"			=> "Wordstat",
	"label-price"				=> "Стоимость",

	"label-link-from"			=> "На какой странице расположена ссылка",
	"label-link-to"				=> "На какую страницу ведет ссылка",
	"label-tic-from"			=> "тИЦ донора",
	"label-tic-to"				=> "тИЦ акцептора",
	"label-link-anchor"			=> "Анкор ссылки",

	"label-seo-noindex"			=> "Сайт %s отсутствует в базе MegaIndex. Пожалуйста зарегистрируйтесь и добавьте сайт на индексацию.",

	"option-megaindex-login"	=> "Логин в системе MegaIndex",
	"option-megaindex-password"	=> "Пароль в системе MegaIndex",

	"error-invalid_answer"		=> "Система Megaindex не отвечает. Пожалуйста, попробуйте повторить попытку позже.",
	"error-authorization-failed"=> "Неверный логин или пароль.",
    "error"                     => "Ошибка: ",
    "error-data"                => "Ошибка: Некорректные данные",


	"header-seo-webmaster"		=> 'Яндекс.Вебмастер',
	"header-seo-yandex"			=> 'Настройки Яндекс.Вебмастер',

	"footer-webmaster-text"		=> 'Основано на данных ',
	"footer-webmaster-link"		=> 'Яндекс.Вебмастер',

	'label-error-no-token'		=> '<div style="margin-bottom:20px;">Для работы с Яндекс.Вебмастер необходимо получить авторизационный токен и сохранить его в <a href="%s/admin/seo/yandex/">настройках модуля</a>.
	<br /> Не беспокойтесь, это совсем не сложно и займёт пару минут.</div><a class="gettoken" href="https://oauth.yandex.ru/authorize?response_type=code&amp;client_id=47fc30ca18e045cdb75f17c9779cfc36" target="_blank">Получить код</a>',
	'label-error-service-down'	=> 'Сервис Яндекс.Вебмастер временно недоступен.',
	'label-error-host-is-a-mirror-of'	=> 'Хост является зеркалом для %s',
	'label-error-host-is-not-responding'	=> 'Хост %s не отвечает',

	'label-error-no-curl'		=> '<div style="margin-bottom:20px;">К сожалению, работа Яндекс.Вебмастер на данном сервере невозможна, так как <b>отсутствует библиотека cURL</b>.<br />
	Для решения данной проблемы необходимо обратиться в техническую поддержку хостинга или к системному администратору сервера.</div>',

	'option-token'			=> 'Ваш текущий токен: ',
	'option-code'			=> 'Введите код подтверждения',
	'link-code'				=> 'Получить код',
	'webmaster-wrong-code'	=> 'Введен неверный код подтверждения',

	'option-webmaster-general'		=> 'Общая информация',

	'js-webmaster-errors-header'	=> 'Произошли следующие ошибки: ',

	'js-webmaster-label-addhost'	=> 'Для получения информации по этому сайту необходимо добавить его в Яндекс.Вебмастер и <br /> подтвердить право на управление этим сайтом',
	'js-webmaster-link-addhost'		=> 'Добавить и подтвердить право на управление сайтом',

	'js-webmaster-label-verfyhost'	=> 'Для получения информации по этому сайту необходимо подтвердить право на управление этим сайтом',
	'js-webmaster-link-verifyhost'	=> 'Подтвердить право на управление сайтом',

	'js-webmaster-link-excluded'	=> 'Исключенные из индекса страницы',
	'js-webmaster-link-indexed'		=> 'Проиндексированные страницы',
	'js-webmaster-link-tops'		=> 'Популярные запросы',
	'js-webmaster-link-links'		=> 'Внешние ссылки на сайт',

	'js-webmaster-label-sitename'				=> 'Сайт',
	'js-webmaster-label-crawling'				=> 'Индексация',
	'js-webmaster-label-virused'				=> 'Вирусы',
	'js-webmaster-label-last-access'			=> 'Последняя проверка',
	'js-webmaster-label-tcy'					=> 'тИЦ',
	'js-webmaster-label-url-count'				=> 'Загружено роботом',
	'js-webmaster-label-url-errors'				=> 'Исключено из индекса',
	'js-webmaster-label-index-count'			=> 'Проиндексированно',
	'js-webmaster-label-internal-links-count'	=> 'Внутренних ссылок',
	'js-webmaster-label-links-count'			=> 'Внешних ссылок',

	'js-webmaster-index-label'			=> 'Проиндексировано за последнюю неделю',
	'js-webmaster-index-total-label'	=> 'Всего проиндексировано : ',
	'js-webmaster-index-nothing-label'	=> 'За последнюю неделю Яндекс не добавил ни одной страницы',
	'js-webmaster-links-label'			=> 'Найдено ссылок на сайт за последнюю неделю',
	'js-webmaster-links-total-label'	=> 'Всего ссылок найдено : ',
	'js-webmaster-links-nothing-label'	=> 'За последнюю неделю Яндекс не добавил ни одной ссылки на сайт',

	'js-webmaster-label-tops-query'		=> 'Поисковый запрос',
	'js-webmaster-label-tops-shows'		=> 'Показов',
	'js-webmaster-label-tops-clicks'	=> 'Кликов',
	'js-webmaster-label-tops-position'	=> 'Позиция',

	'js-webmaster-verification-state-IN_PROGRESS'			=> 'Производится проверка.',
	'js-webmaster-verification-state-NEVER_VERIFIED'		=> 'Права никогда не подтверждались.',
	'js-webmaster-verification-state-VERIFICATION_FAILED'	=> 'Ошибка при попытке подтверждения прав.',
	'js-webmaster-verification-state-VERIFIED'				=> 'Права подтверждены.',
	'js-webmaster-verification-state-WAITING'				=> 'Ожидание в очереди на подтверждение.',

	'js-webmaster-crawling-state-INDEXED'		=> 'Сайт проиндексирован',
	'js-webmaster-crawling-state-NOT_INDEXED'	=> 'Сайт не проиндексирован',
	'js-webmaster-crawling-state-WAITING'		=> 'Сайт ожидает индексирования',

	'js-webmaster-excluded-code-label' => 'Причина исключения страниц',
	'js-webmaster-excluded-code-400' => 'HTTP-статус: Неверный запрос (400)',
	'js-webmaster-excluded-code-401' => 'HTTP-статус: Неавторизованный запрос (401)',
	'js-webmaster-excluded-code-402' => 'HTTP-статус: Необходима оплата за запрос (402)',
	'js-webmaster-excluded-code-403' => 'HTTP-статус: Доступ к ресурсу запрещён (403)',
	'js-webmaster-excluded-code-404' => 'HTTP-статус: Ресурс не найден (404)',
	'js-webmaster-excluded-code-405' => 'HTTP-статус: Метод неприменим (405)',
	'js-webmaster-excluded-code-406' => 'HTTP-статус: Недопустимый тип ресурса (406)',
	'js-webmaster-excluded-code-407' => 'HTTP-статус: Требуется идентификация прокси, файервола (407)',
	'js-webmaster-excluded-code-408' => 'HTTP-статус: Время запроса истекло (408)',
	'js-webmaster-excluded-code-409' => 'HTTP-статус: Конфликт (409)',
	'js-webmaster-excluded-code-410' => 'HTTP-статус: Ресурс недоступен (410)',
	'js-webmaster-excluded-code-411' => 'HTTP-статус: Требуется длина (411)',
	'js-webmaster-excluded-code-412' => 'HTTP-статус: Сбой при обработке предварительного условия (412)',
	'js-webmaster-excluded-code-413' => 'HTTP-статус: Тело запроса превышает допустимый размер (413)',
	'js-webmaster-excluded-code-414' => 'HTTP-статус: Недопустимая длина URI запроса (414)',
	'js-webmaster-excluded-code-415' => 'HTTP-статус: Неподдерживаемый MIME тип (415)',
	'js-webmaster-excluded-code-416' => 'HTTP-статус: Диапазон не может быть обработан (416)',
	'js-webmaster-excluded-code-417' => 'HTTP-статус: Сбой при ожидании (417)',
	'js-webmaster-excluded-code-422' => 'HTTP-статус: Необрабатываемый элемент (422)',
	'js-webmaster-excluded-code-423' => 'HTTP-статус: Заблокировано (423)',
	'js-webmaster-excluded-code-424' => 'HTTP-статус: Неверная зависимость (424)',
	'js-webmaster-excluded-code-426' => 'HTTP-статус: Требуется обновление (426)',

	'js-webmaster-excluded-code-500' => 'HTTP-статус: Внутренняя ошибка сервера (500)',
	'js-webmaster-excluded-code-501' => 'HTTP-статус: Метод не поддерживается (501)',
	'js-webmaster-excluded-code-502' => 'HTTP-статус: Ошибка межсетевого шлюза (502)',
	'js-webmaster-excluded-code-503' => 'HTTP-статус: Служба не доступна (503)',
	'js-webmaster-excluded-code-504' => 'HTTP-статус: Время прохождения через межсетевой шлюз истекло (504)',
	'js-webmaster-excluded-code-505' => 'HTTP-статус: Версия НТТР не поддерживается (505)',
	'js-webmaster-excluded-code-507' => 'HTTP-статус: Недостаточно места (507)',
	'js-webmaster-excluded-code-510' => 'HTTP-статус: Отсутствуют расширения (510)',

	'js-webmaster-excluded-code-1001' => 'Обрыв соединения',
	'js-webmaster-excluded-code-1002' => 'Слишком большой документ',
	'js-webmaster-excluded-code-1003' => 'Документ запрещен в файле robots.txt',
	'js-webmaster-excluded-code-1004' => 'Адрес документа не соответствует стандарту HTTP',
	'js-webmaster-excluded-code-1005' => 'Формат документа не поддерживается',
	'js-webmaster-excluded-code-1006' => 'Ошибка DNS',
	'js-webmaster-excluded-code-1007' => 'Статус-код HTTP не соответствует стандарту',
	'js-webmaster-excluded-code-1008' => 'Неверный HTTP-заголовок',
	'js-webmaster-excluded-code-1010' => 'Не удалось соединиться с веб-сервером',
	'js-webmaster-excluded-code-1013' => 'Неверная длина сообщения',
	'js-webmaster-excluded-code-1014' => 'Неверная кодировка',
	'js-webmaster-excluded-code-1019' => 'Передано неверное количество данных',
	'js-webmaster-excluded-code-1020' => 'Длина HTTP-заголовка превышает предел',
	'js-webmaster-excluded-code-1021' => 'Длина адреса (URL) превышает предел',

	'js-webmaster-excluded-code-2004' => 'Документ содержит мета-тег refresh',
	'js-webmaster-excluded-code-2005' => 'Документ содержит мета-тег noindex',
	'js-webmaster-excluded-code-2006' => 'Кодировка документа не распознана',
	'js-webmaster-excluded-code-2007' => 'Документ является логом сервера',
	'js-webmaster-excluded-code-2010' => 'Неверный формат документа',
	'js-webmaster-excluded-code-2011' => 'Кодировка не распознана',
	'js-webmaster-excluded-code-2012' => 'Язык не поддерживается',
	'js-webmaster-excluded-code-2014' => 'Документ не содержит текста',
	'js-webmaster-excluded-code-2016' => 'Слишком много ссылок',
	'js-webmaster-excluded-code-2020' => 'Ошибка распаковывания',
	'js-webmaster-excluded-code-2024' => 'Документ имеет размер 0 байт',
	'js-webmaster-excluded-code-2025' => 'Документ является неканоническим',

	'js-webmaster-excluded-count-label' => 'Количество страниц',

	'js-webmaster-excluded-severity-label'					=> 'Тип ошибки',
	'js-webmaster-excluded-severity-SITE_ERROR'				=> 'Ошибка сайта.',
	'js-webmaster-excluded-severity-UNSUPPORTED_BY_ROBOT'	=> 'Не поддерживается роботом.',
	'js-webmaster-excluded-severity-DISALLOWED_BY_USER'		=> 'Запрещено пользователем.',
	'js-webmaster-excluded-severity-OK'						=> 'Нет ошибки.',

	'js-webmaster-excluded-total-label' => 'Всего исключеных страниц : ',

	// Яндекс.Острова
	'header-seo-islands'				=> 'Яндекс.Острова',
	'header-seo-island_edit'			=> 'Редактирование Яндекс.Острова',
	'header-seo-island_get'				=> 'Получить Яндекс.Остров',
	'label-add-island'					=> 'Добавить',
	'label-create-island-xml'			=> 'Создать',
	'object-new-seo-island'				=> 'Новый Яндекс.Остров',

	'label-island-user-fields-group'	=> "Используемые поля",

	'js-island-edit-add_field'		=> "Добавить поле",
	'js-island-edit-title'			=> 'Название',
	'js-island-edit-name'			=> 'Идентификатор',
	'js-island-edit-type'			=> 'Тип',
	'js-island-edit-restriction'	=> 'Формат значения',
	'js-island-edit-guide'			=> 'Справочник',
	'js-island-edit-visible'		=> 'Показывать в острове',
	'js-island-save-edit-field'		=> 'Сохранить',
	'js-island-confirm-cancel'		=> 'Отменить',
	'js-island-edit-new_field'		=> 'Новое поле',
	'js-seo-island'					=> 'Создание файла острова',
	'js-seo-island-getlink'			=> 'Получить ссылку на результат',
	'js-seo-island-getfile'			=> 'Скачать файл острова',
	'js-seo-edit-confirm_title'		=> "Подтверждение удаления",
	'js-seo-edit-confirm_text'		=> "Если вы уверены, нажмите \"Удалить\" (действие необратимо).",
	'js-seo-edit-saving'			=> "Сохранение",
	'js-island-edit-edit'			=> 'Редактировать поле',
	'js-island-edit-remove'			=> 'Удалить поле',

	'js-island-change-symlink-warning'	=> 'Будут загружены поля доминантного типа. Поля старого доминантного типа будут удалены. Страница будет перезагружена.',
);

?>
