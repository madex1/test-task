<?php

	/** Языковые константы для русской версии */
	$i18n = [
		'header-users-login' => 'Авторизация',
		'header-users-config' => 'Настройки модуля',
		'header-users-del' => 'Удаление',
		'option-def_group' => 'Группа пользователей по умолчанию',
		'option-guest_id' => 'Пользователь-гость',
		'option-without_act' => 'Регистрация пользователей без активации',
		'option-check_csrf_on_user_update' => 'Проверять CSRF-токен при изменении настроек пользователя',
		'option-pages_permissions_changing_enabled_on_add' => 'Устанавливать права на страницы сайта при создании пользователя/группы',
		'option-pages_permissions_changing_enabled_on_edit' => 'Изменять права на страницы сайта при изменении прав доступа пользователя/группы',
		'option-require_current_password' => 'Запрашивать пароль для изменения настроек пользователя',
		'label-users' => 'Пользователи',
		'label-email' => 'E-mail',
		'label-in-groups' => 'Состоит в группах',
		'permissions-module' => 'Модуль',
		'permissions-use-access' => 'Права на использование',
		'permissions-other-access' => 'Прочие права',
		'label-groups' => 'Группы пользователей',
		'group-outgroup' => 'Пользователи вне групп',
		'group-allusers' => 'Все пользователи',
		'header-users-groups_list' => 'Группы пользователей',
		'header-users-edit' => 'Редактирование',
		'header-users-users_list' => 'Пользователи',
		'header-users-login_do' => 'Авторизация',
		'header-users-add-user' => 'Добавление пользователя',
		'header-users-add-users' => 'Добавление группы пользователей',
		'header-users-edit-user' => 'Редактирование пользователя',
		'header-users-edit-users' => 'Редактирование группы пользователей',
		'label-add-user' => 'Добавить пользователя',
		'label-do-search' => 'Искать',
		'label-search-user' => 'Поиск пользователя',
		'label-add-group' => 'Добавить группу пользователей',
		'label-search-group' => 'Поиск группы пользователей',
		'label-no-groups-found' => 'Не найдено ни одной группы пользователей',
		'label-no-users-found' => 'Ни один пользователь не найден',
		'header-users-add' => 'Добавление пользователя',
		'label-user-groups-member' => 'Входит в группы',
		'error-no-login' => 'Поле "Логин" не может быть пустым',
		'error-no-password' => 'Поле "Пароль" не может быть пустым',
		'perms-users-login' => 'Авторизация',
		'perms-users-registrate' => 'Регистрация',
		'perms-users-settings' => 'Редактирование настроек',
		'perms-users-users_list' => 'Управление пользователями',
		'perms-users-profile' => 'Просмотр профиля пользователей',
		'perms-users-config' => 'Права на работу с настройками',
		'perms-users-delete' => 'Права на удаление пользователей и групп',
		'error-sv-group-delete' => 'Нельзя удалить эту группу пользователей.',
		'error-sv-user-delete' => 'Нельзя удалить этого супервайзера.',
		'error-guest-user-delete' => 'Нельзя удалить пользователя, потому что это учетная запись для гостей.',
		'error-sv-user-activity' => 'Нельзя отключить этого супервайзера.',
		'error-guest-user-activity' => 'Нельзя отключить пользователя, потому что это учетная запись для гостей.',
		'js-smc-user_list' => ' ',
		'js-smc-users' => 'Пользователи',
		'error-break-action-with-sv' => 'Нельзя выполнить действие - недостаточно прав!',
		'header-users-activity' => 'Изменение активности',
		'error-delete-yourself' => 'Нельзя удалять собственную учетную запись.',
		'error-users-non-referer' => 'Данное действие запрещено выполнять на домене, который не зарегистрирован в системе.',
		'field-target' => 'Адрес, на который пришел пользователь',
		'field-referer' => 'Адрес, с которого пришел пользователь',
		'label-act-as-user' => 'Оформить заказ от имени пользователя',
		'label-act-as-user-tip' => 'По этой ссылке вы перейдете на сайт и сможете действовать от лица пользователя',
		'js-del-object-title-short' => 'Удаление',
		'js-del-shured' => 'Вы уверены, что хотите удалить объект? Он будет удален окончательно.',
		'forget-password' => 'Забыли пароль?',
		'enter-credentials' => 'Введите ваш логин или e-mail',
		'forget-button' => 'Выслать пароль',
		'mail-verification-subject' => 'Восстановление пароля',
		'mail-password-subject' => 'Новый пароль для сайта',
		'label-repeat-password' => 'Повторите пароль',
		'login_do_try_again' => 'Вы ввели неверный логин или пароль. Проверьте раскладку клавиатуры, не нажата ли клавиша «Caps Lock» и попробуйте ввести логин и пароль еще раз.',
		'mail-template-users-new-registration-admin-subject' => 'Тема письма',
		'mail-template-users-new-registration-admin-content' => 'Шаблон письма',
		'mail-template-users-restore-password-subject' => 'Тема письма',
		'mail-template-users-restore-password-content' => 'Шаблон письма',
		'mail-template-users-registered-subject' => 'Тема письма',
		'mail-template-users-registered-content' => 'Шаблон письма',
		'mail-template-users-registered-no-activation-subject' => 'Тема письма',
		'mail-template-users-registered-no-activation-content' => 'Шаблон письма',
		'mail-template-users-new-password-subject' => 'Тема письма',
		'mail-template-users-new-password-content' => 'Шаблон письма',
		'mail-template-variable-user_id' => 'Id пользователя',
		'mail-template-variable-login' => 'Логин',
		'mail-template-variable-domain' => 'Домен',
		'mail-template-variable-restore_link' => 'Ссылка восстановления пароля',
		'mail-template-variable-email' => 'Электронная почта',
		'mail-template-variable-activate_link' => 'Ссылка активации',
		'mail-template-variable-password' => 'Пароль',
		'mail-template-variable-fname' => 'Имя',
		'mail-template-variable-lname' => 'Фамилия',
		'mail-template-variable-father_name' => 'Отчество',
	];

	$i18n['mail-verification-body'] = <<<BODY


	<p>
		Здравствуйте!<br />
		Кто-то, возможно Вы, пытается восстановить пароль для пользователя "%login%" на сайте <a href="http://%domain%">%domain%</a>.
	</p>


	<p>
		Если это не Вы, просто проигнорируйте данное письмо.
	</p>

	<p>
		Если Вы действительно хотите восстановить пароль, кликните по этой ссылке:<br />
		<a href="%restore_link%">%restore_link%</a>
	</p>

	<p>
		С уважением,<br />
		<b>Администрация сайта <a href="http://%domain%">%domain%</a></b>
	</p>


BODY;

	$i18n['mail-password-body'] = <<<BODY


	<p>
		Здравствуйте!<br />

		Ваш новый пароль от сайта <a href="http://%domain%">%domain%</a>.
	</p>


	<p>
		Логин:	%login%<br />
		Пароль: %password%
	</p>

	<p>
		С уважением,<br />
		<b>Администрация сайта <a href="http://%domain%">%domain%</a></b>
	</p>

BODY;
