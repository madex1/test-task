<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\Russianpost;

	/** Определяет идентификатор упаковки для видов отправлений "Посылка" */
	class PackIdProvider implements iPackIdProvider {

		/** @var int Идентификатор упаковки XL */
		const XL_ID = 40;

		/** @var int Идентификатор упаковки L */
		const L_ID = 30;

		/** @var int Идентификатор упаковки M */
		const M_ID = 20;

		/** @var int Идентификатор упаковки S */
		const S_ID = 10;

		/* Максимальные размерности разных типов упаковок в сантиметрах */

		const MAX_L_LENGTH = 40;

		const MAX_M_LENGTH = 30;

		const MAX_S_LENGTH = 26;

		const MAX_L_WIDTH = 27;

		const MAX_M_WIDTH = 20;

		const MAX_S_WIDTH = 17;

		const MAX_L_HEIGHT = 18;

		const MAX_M_HEIGHT = 15;

		const MAX_S_HEIGHT = 8;

		/** @inheritdoc */
		public function getPackId(\order $order) {
			list($length, $width, $height) = $this->getDimensionsInDescendingOrder($order);

			if ($length >= self::MAX_L_LENGTH || $width >= self::MAX_L_WIDTH || $height >= self::MAX_L_HEIGHT) {
				return self::XL_ID;
			}

			if ($length >= self::MAX_M_LENGTH || $width >= self::MAX_M_WIDTH || $height >= self::MAX_M_HEIGHT) {
				return self::L_ID;
			}

			if ($length >= self::MAX_S_LENGTH || $width >= self::MAX_S_WIDTH || $height >= self::MAX_S_HEIGHT) {
				return self::M_ID;
			}

			return self::S_ID;
		}

		/**
		 * Возвращает размерности заказа в сантиметрах в нисходящем порядке
		 * @param \order $order Заказ
		 * @return array
		 */
		private function getDimensionsInDescendingOrder(\order $order) {
			$length = $order->getTotalLength();
			$width = $order->getTotalWidth();
			$height = $order->getTotalHeight();

			if ($height > $length) {
				list($length, $height) = [$height, $length];
			}
			if ($height > $width) {
				list($height, $width) = [$width, $height];
			}
			if ($width > $length) {
				list($length, $width) = [$width, $length];
			}

			return [$length, $width, $height];
		}
	}
