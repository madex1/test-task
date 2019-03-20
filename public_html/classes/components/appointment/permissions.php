<?php

	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на запись */
		'enroll' => [
			'getappointmentsdata',
			'postappointment',
			'employees',
			'services',
			'statuses',
			'servicegroups',
			'employeeschedules',
			'employeeservicesids',
			'employeesbyserviceid',
			'getdefaultschedule',
			'page'
		],
		/** Права на администрирование модуля */
		'manage' => [
			'pages',
			'addPage',
			'editPage',
			'activity',
			'getdatasetconfiguration',
			'page.edit',
			'publish',
			'servicegroups',
			'services',
			'employees',
			'orders',
			'employeeslist',
			'serviceslist',
			'statuseslist',
			'servicegroupslist',
			'employeeslistByserviceid',
			'saveorderfield',
			'editservice',
			'addservice',
			'servicegroups',
			'saveservicefield',
			'editservicegroup',
			'addservicegroup',
			'savegroupfield',
			'addemployee',
			'editemployee',
			'employeescheduleslist',
			'getscheduleworktimes',
			'employeeservicesidslist',
			'serviceworkingtime',
			'editserviceentity',
			'saveserviceentityfield',
			'changeservicegroup',
			'flushservicedataconfig',
			'flushorderdataconfig',
			'flushemployeedataconfig',
		],
		/** Права на работу с настройками */
		'config' => [
			'config'
		],
		/** Права на удаление заявок, услуг, групп услуг, сотрудников и страниц с записью */
		'delete' => [
			'deleteorder',
			'deleteservicegroups',
			'deleteservices',
			'deleteserviceentities',
			'deleteemployees',
			'del'
		]
	];

