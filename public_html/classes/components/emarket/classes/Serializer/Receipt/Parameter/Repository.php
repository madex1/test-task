<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter;

	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Абстрактный класс репозитория параметра чека платежной системы
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter
	 */
	abstract class Repository implements iRepository {

		/** @var iFactory $parameterFactory фабрика валют */
		protected $parameterFactory;

		/** @var SelectorFactory $selectorFactory фабрика ставок налога на добавленную стоимость (НДС) */
		protected $selectorFactory;

		/** @inheritdoc */
		public function __construct(iFactory $parameterFactory, SelectorFactory $selectorFactory) {
			$this->parameterFactory = $parameterFactory;
			$this->selectorFactory = $selectorFactory;
		}

		/**
		 * @inheritdoc
		 * @throws \selectorException
		 */
		public function load($id) {
			$selector = $this->getSelector();
			$selector->where('id')->equals($id);

			return $this->createParameterBySelector($selector);
		}

		/**
		 * @inheritdoc
		 * @throws \selectorException
		 */
		public function loadByGuid($guid) {
			$selector = $this->getSelector();
			$selector->where('guid')->equals($guid);

			return $this->createParameterBySelector($selector);
		}

		/**
		 * Создает объект параметра по селектору
		 * @param \selector $selector
		 * @return \UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter|null
		 * @throws \selectorException
		 */
		protected function createParameterBySelector(\selector $selector) {
			$selector->limit(0, 1);

			$dataObjectList = $selector->result();

			if (!isset($dataObjectList[0]) || !$dataObjectList[0] instanceof \iUmiObject) {
				return null;
			}

			$dataObject = array_shift($dataObjectList);

			return $this->getParameterFactory()
				->create($dataObject);
		}

		/**
		 * Возвращает фабрику параметра чека
		 * @return iFactory
		 */
		protected function getParameterFactory() {
			return $this->parameterFactory;
		}

		/**
		 * Возвращает фабрику селекторов
		 * @return SelectorFactory
		 */
		protected function getSelectorFactory() {
			return $this->selectorFactory;
		}

		/**
		 * Возвращает селектор
		 * @return \selector
		 * @throws \selectorException
		 */
		protected function getSelector() {
			$typeGuid = $this->getParameterFactory()
				->getTypeGuid();

			$selector = $this->getSelectorFactory()
				->createObjectTypeGuid($typeGuid);
			$selector->option('ignore-children-types', true);
			$selector->option('no-length', true);
			$selector->option('load-all-props', true);
			return $selector;
		}
	}