<?php

	use UmiCms\Classes\Components\UmiSliders\SlidersCollection;
	use UmiCms\Classes\Components\UmiSliders\SlidesCollection;
	use UmiCms\Service;

	/** Базовый класс модуля "Слайдеры" */
	class umiSliders extends def_module {

		/** @var SlidersCollection $slidersCollection */
		private $slidersCollection;

		/** @var SlidesCollection $slidesCollection */
		private $slidesCollection;

		/** Конструктор */
		public function __construct() {
			parent::__construct();
			$this->initProperties();

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$configTabs = $this->getConfigTabs();

			if ($configTabs instanceof iAdminModuleTabs) {
				$configTabs->add('config');
			}

			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('getSliders');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('UmiSlidersAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('UmiSlidersCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('UmiSlidersMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('UmiSlidersHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('UmiSlidersCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает коллекцию слайдов
		 * @return iUmiService|SlidesCollection
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getSlidesCollection() {
			if (!$this->slidesCollection instanceof SlidesCollection) {
				throw new RequiredPropertyHasNoValueException('You should set SlidesCollection first');
			}

			return $this->slidesCollection;
		}

		/**
		 * Возвращает коллекцию слайдеров
		 * @return iUmiService|SlidersCollection
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getSlidersCollection() {
			if (!$this->slidersCollection instanceof SlidersCollection) {
				throw new RequiredPropertyHasNoValueException('You should set SlidersCollection first');
			}

			return $this->slidersCollection;
		}

		/** Инициализирует свойства */
		protected function initProperties() {
			$serviceContainer = ServiceContainerFactory::create();

			$slidesCollection = $serviceContainer->get('SlidesCollection');
			$this->setSlidesCollection($slidesCollection);

			$slidersCollection = $serviceContainer->get('SlidersCollection');
			$this->setSlidersCollection($slidersCollection);
		}

		/**
		 * Устанавливает экземпляр коллекции слайдов и возвращает текущий объект
		 * @param SlidesCollection $slidesCollection экземпляр коллекции слайдов
		 * @return umiSliders
		 */
		private function setSlidesCollection(SlidesCollection $slidesCollection) {
			$this->slidesCollection = $slidesCollection;
			return $this;
		}

		/**
		 * Устанавливает экземпляр коллекции слайдеров и возвращает текущий объект
		 * @param SlidersCollection $slidersCollection экземпляр коллекции слайдеров
		 * @return umiSliders
		 */
		private function setSlidersCollection(SlidersCollection $slidersCollection) {
			$this->slidersCollection = $slidersCollection;
			return $this;
		}
	}
