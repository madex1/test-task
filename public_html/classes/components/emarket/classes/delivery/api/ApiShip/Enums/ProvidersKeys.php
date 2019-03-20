<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Exceptions\UnsupportedProviderKeyException;
	use UmiCms\Classes\System\Enums\Enum;
	use UmiCms\Classes\System\Enums\EnumElementNotExistsException;

	/**
	 * Перечисление ключей (строковых идентификаторов) провайдеров (служб достави, сд)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums
	 */
	class ProvidersKeys extends Enum {

		/** @const string A1_KEY строковой идентификатор провайдера "А1" */
		const A1_KEY = 'a1';

		/** @const string B2CPL_KEY строковой идентификатор провайдера "B2Cpl" */
		const B2CPL_KEY = 'b2cpl';

		/** @const string BOXBERRY_KEY строковой идентификатор провайдера "Boxberry" */
		const BOXBERRY_KEY = 'boxberry';

		/** @const string CDEK_KEY строковой идентификатор провайдера "СДЕК" */
		const CDEK_KEY = 'cdek';

		/** @const string DPD_KEY строковой идентификатор провайдера "DPD" */
		const DPD_KEY = 'dpd';

		/** @const string HERMES_KEY строковой идентификатор провайдера "Hermes" */
		const HERMES_KEY = 'hermes';

		/** @const string IML_KEY строковой идентификатор провайдера "Iml" */
		const IML_KEY = 'iml';

		/** @const string MAXI_KEY строковой идентификатор провайдера "MaxiPost" */
		const MAXI_KEY = 'maxi';

		/** @const string PICKPOINT_KEY строковой идентификатор провайдера "PickPoint" */
		const PICKPOINT_KEY = 'pickpoint';

		/** @const string PONY_KEY строковой идентификатор провайдера "PonyExpress" */
		const PONY_KEY = 'pony';

		/** @const string SPSR_KEY строковой идентификатор провайдера "Spsr" */
		const SPSR_KEY = 'spsr';

		/** @const string DALLI_KEY строковой идентификатор провайдера "Dalli-Service" */
		const DALLI_KEY = 'dalli';

		/** @inheritdoc */
		public function __construct($currentValue = null) {
			try {
				if ($currentValue === null) {
					parent::__construct();
				} else {
					parent::__construct($currentValue);
				}
			} catch (EnumElementNotExistsException $e) {
				throw new UnsupportedProviderKeyException($currentValue);
			}
		}

		/** @inheritdoc */
		protected function getDefaultValue() {
			return self::CDEK_KEY;
		}
	}
