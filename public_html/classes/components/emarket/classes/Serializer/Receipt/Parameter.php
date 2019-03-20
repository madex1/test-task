<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	/**
	 * Класс параметра передаваемого в чеке платежной системы
	 * Параметр содержит идентификаторы для внешних сервисов, для того, чтобы связывать
	 * идентификаторы ставок в UMI.CMS с идентификаторами ставок в интегрируемых системах.
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt
	 */
	class Parameter implements iParameter {

		/** @var \iUmiObject $dataObject объект данных параметра */
		protected $dataObject;

		/** @inheritdoc */
		public function __construct(\iUmiObject $dataObject) {
			$this->dataObject = $dataObject;
		}

		/** @inheritdoc */
		public function getId() {
			return $this->getDataObject()
				->getId();
		}

		/** @inheritdoc */
		public function getName() {
			return $this->getDataObject()
				->getName();
		}

		/** @inheritdoc */
		public function getYandexKassaId() {
			return (string) $this->getDataObject()
				->getValue(self::YANDEX_KASSA_ID_FIELD);
		}

		/** @inheritdoc */
		public function getRoboKassaId() {
			return (string) $this->getDataObject()
				->getValue(self::ROBO_KASSA_ID_FIELD);
		}

		/** @inheritdoc */
		public function getPayAnyWayId() {
			return (string) $this->getDataObject()
				->getValue(self::PAY_ANY_WAY_ID_FIELD);
		}

		/** @inheritdoc */
		public function getPayOnlineId() {
			return (string) $this->getDataObject()
				->getValue(self::PAY_ONLINE_ID_FIELD);
		}

		/** @inheritdoc */
		public function getSberbankId() {
			return (string) $this->getDataObject()
				->getValue(self::SBERBANK_ID_FIELD);
		}

		/**
		 * Возвращает объект данных параметра
		 * @return \iUmiObject
		 */
		public function getDataObject() {
			return $this->dataObject;
		}
	}