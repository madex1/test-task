<?php
	/**
	 * Наименование в заказе для цифровых товаров
	 *
	 * Class digitalOrderItem
	 */
	class digitalOrderItem extends orderItem {
		/** @var bool $isDigital Товар цифровой */
		protected $isDigital = true;

		/**
		 * Сформировать сообщение для доставки товара
		 *
		 * @param string $template TPL шаблон
		 *
		 * @return mixed
		 */
		public function getDeliveryMail($template = "delivery") {
			$element = $this->itemElement[0];
			list($template) = def_module::loadTemplatesForMail("emarket/mail/" . $template, "digital_item");
			return def_module::parseTemplateForMail($template, array(), $element->getId() );
		}
	};
?>