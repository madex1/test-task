<?php

	/**
	 * Обработчики событий создания пользователя. При создании пользователя ему назначается цвет заметок,
	 * см. /classes/modules/tickets/__events.php.
	 */
	new umiEventListener('users_registrate', 'tickets', 'onRegisterUserFillTicketsColor');
	new umiEventListener('systemCreateObject', 'tickets', 'onCreateUserFillTicketsColor');
