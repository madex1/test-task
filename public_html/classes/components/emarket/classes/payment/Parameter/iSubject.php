<?php

	namespace UmiCms\Classes\Components\Emarket\Payment;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter;

	/**
	 * @inheritdoc
	 * @package UmiCms\Classes\Components\Emarket\Payment
	 */
	interface iSubject extends iParameter {

		/** @const string TYPE_GUID гуид типа */
		const TYPE_GUID = 'payment_subject';
	}
