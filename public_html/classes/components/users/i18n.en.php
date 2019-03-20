<?php

	/** Языковые константы для английской версии */
	$i18n = [
		'header-users-login' => 'Log in',
		'header-users-config' => 'Module settings',
		'header-users-del' => 'Delete',
		'option-def_group' => 'Default user group',
		'option-guest_id' => 'Guest user',
		'option-without_act' => 'Registrate without activation',
		'option-check_csrf_on_user_update' => 'Check CSRF-token when changing user settings',
		'option-pages_permissions_changing_enabled_on_add' => 'When you create a user need to install pages permissions',
		'option-pages_permissions_changing_enabled_on_edit' => 'When you change the user access rights need to change pages permissions',
		'option-require_current_password' => 'Check user password for user settings changes',
		'label-users' => 'Users',
		'label-email' => 'E-mail',
		'label-in-groups' => 'In groups',
		'permissions-module' => 'Module',
		'permissions-use-access' => 'Admin access permission',
		'permissions-other-access' => 'Other permissions',
		'label-groups' => 'Groups',
		'group-outgroup' => 'Outgroup users',
		'group-allusers' => 'All users',
		'header-users-groups_list' => 'Groups list',
		'header-users-edit' => 'Edit',
		'header-users-users_list' => 'Users list',
		'header-users-login_do' => 'Log in',
		'header-users-edit-users' => 'Edit users group',
		'header-users-add-users' => 'Add users group',
		'header-users-edit-user' => 'Edit user',
		'header-users-add-user' => 'Add user',
		'label-add-user' => 'Add user',
		'label-do-search' => 'Search',
		'label-search-user' => 'Search user',
		'label-add-group' => 'Add users group',
		'label-search-group' => 'Search users group',
		'label-no-groups-found' => 'No groups found',
		'label-no-users-found' => 'No users found',
		'header-users-add' => 'Add user',
		'label-user-groups-member' => 'In groups',
		'error-no-login' => "Login field can't be empty.",
		'error-no-password' => "Password field can't be empty",
		'perms-users-login' => 'Authorisation',
		'perms-users-registrate' => 'Registration',
		'perms-users-settings' => 'Edit settings',
		'perms-users-users_list' => 'Management of users',
		'perms-users-profile' => 'View profiles',
		'perms-users-config' => 'Permissions for working with settings',
		'perms-users-delete' => 'Permissions for deleting users and groups',
		'error-sv-group-delete' => 'You can\'t delete this group.',
		'error-sv-user-delete' => 'You can\'t delete this supervisor.',
		'error-guest-user-delete' => 'You can\'t delete this user because it is guest account.',
		'error-sv-user-activity' => 'You can\'t disable this supervisor.',
		'error-guest-user-activity' => 'You can\'t disable this user because it is guest account.',
		'js-smc-user_list' => ' ',
		'js-smc-users' => 'Users',
		'error-break-action-with-sv' => 'Can not perform action - not enough perms!',
		'header-users-activity' => 'Change activity',
		'error-delete-yourself' => 'You can\'t delete your own account.',
		'error-users-non-referer' => 'This action is not allowed to perform on the domain that is not registered in the system.',
		'field-target' => 'URL, which the user came to',
		'field-referer' => 'URL, which the user came from',
		'label-act-as-user' => 'Checkout as a user',
		'label-act-as-user-tip' => 'This link will take you to the site and will be able to act on behalf of the user',
		'js-del-object-title-short' => 'Removal',
		'js-del-shured' => 'Are you sure you want to remove an object? It will be permanently deleted.',
		'forget-password' => 'Forgot your password?',
		'enter-credentials' => 'Enter your login or e-mail',
		'forget-button' => 'Send password',
		'mail-verification-subject' => 'Password restore',
		'mail-password-subject' => 'New password',
		'label-repeat-password' => 'Repeat password',
		'login_do_try_again' => 'You entered incorrect login or password. Check your «Caps Lock» button and try again .',
		'mail-template-users-new-registration-admin-subject' => 'Subject',
		'mail-template-users-new-registration-admin-content' => 'Content',
		'mail-template-users-restore-password-subject' => 'Subject',
		'mail-template-users-restore-password-content' => 'Subject',
		'mail-template-users-registered-subject' => 'Subject',
		'mail-template-users-registered-content' => 'Content',
		'mail-template-users-registered-no-activation-subject' => 'Subject',
		'mail-template-users-registered-no-activation-content' => 'Content',
		'mail-template-users-new-password-subject' => 'Subject',
		'mail-template-users-new-password-content' => 'Content',
		'mail-template-variable-user_id' => 'User Id',
		'mail-template-variable-login' => 'Login',
		'mail-template-variable-domain' => 'Domain',
		'mail-template-variable-restore_link' => 'Password restore link',
		'mail-template-variable-email' => 'Email',
		'mail-template-variable-activate_link' => 'Activation link',
		'mail-template-variable-password' => 'Password',
		'mail-template-variable-fname' => 'First name',
		'mail-template-variable-lname' => 'Last name',
		'mail-template-variable-father_name' => 'Father name',
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
