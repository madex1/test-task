<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\iReceipt;

	/**
	 * Интерфейс фабрики серилизаторов для чеков по ФЗ-54
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param \iServiceContainer $serviceContainer контейнер сервисов
		 */
		public function __construct(\iServiceContainer $serviceContainer);

		/**
		 * Создает серилизатор
		 * @param string $name имя серилизиатора
		 * @return iReceipt
		 */
		public function create($name);
	}
