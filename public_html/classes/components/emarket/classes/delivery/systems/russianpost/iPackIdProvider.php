<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\Russianpost;

	/** Определяет идентификатор упаковки для видов отправлений "Посылка" */
	interface iPackIdProvider {

		/**
		 * Возвращает идентификатор упаковки для видов отправлений "Посылка"
		 * @link https://www.livemaster.ru/topic/2422199-tipy-i-razmery-pochtovyh-korobok-novogo-obraztsa-s-m-l-xl
		 * @param \order $order Заказ
		 * @return int
		 * @throws \privateException
		 */
		public function getPackId(\order $order);
	}
