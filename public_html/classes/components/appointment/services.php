<?php

	$parameters = [
		'AppointmentServicesCollection' => 'AppointmentServices',
		'AppointmentServiceGroupsCollection' => 'AppointmentServiceGroups',
		'AppointmentEmployeesCollection' => 'AppointmentEmployees',
		'AppointmentEmployeesServicesCollection' => 'AppointmentEmployeesServices',
		'AppointmentEmployeesSchedulesCollection' => 'AppointmentEmployeesSchedules',
		'AppointmentOrdersCollection' => 'AppointmentOrders'
	];

	$rules = [
		'AppointmentServices' => [
			'class' => 'AppointmentServicesCollection',
			'arguments' => [
				new ParameterReference('AppointmentServicesCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('appointmentServicesConstantMap')
					]
				],
				[
					'method' => 'setSourceIdBinderFactory',
					'arguments' => [
						new ServiceReference('ImportEntitySourceIdBinderFactory')
					]
				]
			]
		],

		'AppointmentServiceGroups' => [
			'class' => 'AppointmentServiceGroupsCollection',
			'arguments' => [
				new ParameterReference('AppointmentServiceGroupsCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('appointmentServiceGroupsConstantMap')
					]
				]
			]
		],

		'AppointmentEmployees' => [
			'class' => 'AppointmentEmployeesCollection',
			'arguments' => [
				new ParameterReference('AppointmentEmployeesCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('appointmentEmployeesConstantMap')
					]
				],
				[
					'method' => 'setImageFileHandler',
					'arguments' => [
						new ParameterReference('imageFileHandler')
					]
				]
			]
		],

		'AppointmentEmployeesServices' => [
			'class' => 'AppointmentEmployeesServicesCollection',
			'arguments' => [
				new ParameterReference('AppointmentEmployeesServicesCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('appointmentEmployeesServicesConstantMap')
					]
				],
				[
					'method' => 'setSourceIdBinderFactory',
					'arguments' => [
						new ServiceReference('ImportEntitySourceIdBinderFactory')
					]
				]
			]
		],

		'AppointmentEmployeesSchedules' => [
			'class' => 'AppointmentEmployeesSchedulesCollection',
			'arguments' => [
				new ParameterReference('AppointmentEmployeesSchedulesCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('appointmentEmployeesSchedulesConstantMap')
					]
				],
				[
					'method' => 'setSourceIdBinderFactory',
					'arguments' => [
						new ServiceReference('ImportEntitySourceIdBinderFactory')
					]
				]
			]
		],

		'AppointmentOrders' => [
			'class' => 'AppointmentOrdersCollection',
			'arguments' => [
				new ParameterReference('AppointmentOrdersCollection'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('appointmentOrdersConstantMap')
					]
				]
			]
		]
	];
