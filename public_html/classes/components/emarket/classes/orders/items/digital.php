<?php

	/**
	 * Класс товарного наименования заказа типа "Цифровой".
	 * Добавляет возможность применять к товарному наименованию опции.
	 *
	 * Чтобы применить определенный тип товарного наименования,
	 * нужно произвести соответствующие настройки в config.ini,
	 * см. метод orderItem::getItemTypeId().
	 */
	class digitalOrderItem extends orderItem {

		/** @var bool $isDigital является ли товар цифровым */
		protected $isDigital = true;

		/**
		 * Возвращает контент письма, через которое будет отгружен цифровой товар
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function getDeliveryMail($template = 'delivery') {
			/** @var iUmiHierarchyElement $element */
			$element = $this->getRelatedProduct();

			list($template) = emarket::loadTemplatesForMail(
				'emarket/mail/' . $template,
				'digital_item'
			);

			return emarket::parseTemplateForMail($template, [], $element->getId());
		}
	}

