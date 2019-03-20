<?php

	/** Обработчик события создания заявки на запись */
	new umiEventListener('addAppointmentOrder', 'appointment', 'onCreateOrder');
	/** Обработчик события изменения заявки на запись */
	new umiEventListener('modifyEntityAppointmentOrders', 'appointment', 'onModifyOrder');

