<?php
	namespace UmiCms\Classes\Components\Catalog\Trade\Offer\Price\Type;

	use UmiCms\Service;
	use UmiCms\System\Orm\Entity\iCollection;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Trade\Offer\Price\Type\iMapper;
	use UmiCms\System\Trade\Offer\Price\Type\iCollection as iPriceTypeCollection;

	/**
	 * Класс административного функционала типов цен торговых предложений
	 * @package UmiCms\Classes\Components\Catalog\Trade\Offer\Price\Type
	 */
	class Admin implements \iModulePart {

		use \tModulePart;

		/** @var \CatalogAdmin|null $admin базовый класс административной панели модуля */
		private $admin;

		/**
		 * Конструктор
		 * @param \catalog $module экземпляр модуля "Каталог"
		 * @throws \coreException
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function __construct(\catalog $module) {
			if (!$module->isClassImplemented($module::ADMIN_CLASS)) {
				throw new \coreException(
					getLabel('label-module-admin-not-implemented', $this->getModuleName())
				);
			}

			/** @var \CatalogAdmin $admin */
			$admin = $module->getImplementedInstance($module::ADMIN_CLASS);
			$this->setAdmin($admin);
		}

		/** Выводит в буфер список типов цен торговых предожений товара с учетом фильтрации, сортировки и пагинации */
		public function tradeOfferPriceTypes() {
			$admin = $this->getAdmin();

			if (!Service::Request()->isJson()) {
				$admin->setDataSetDirectCallMessage();
				return;
			}

			try {
				$collection = Service::TradeOfferPriceTypeFacade()->getAll();
				$collection = $this->filterCollection($collection);
				$filteredCollectionCount = $collection->getCount();
				$collection = $this->sortCollection($collection);
				$collection = $admin->sliceEntityCollection($collection);
				$result = $this->preparePriceTypeList($collection, $filteredCollectionCount);
			} catch (\Exception $exception) {
				$result = [
					'error' => $exception->getMessage(),
					'offset' => 0,
					'per_page_limit' => 0,
					'total' => 0
				];
			}

			$admin->printEntityTableControlResult($result);
		}

		/** Добавляет новый тип цены */
		public function addTradeOfferPriceType() {
			try {
				$attributeList = [
					iMapper::TITLE => getLabel('label-new-trade-offer-price-type-name'),
					iMapper::NAME => sprintf('new_type_%d', rand()),
				];
				Service::TradeOfferPriceTypeFacade()
					->create($attributeList);
				$status = [
					'success' => true
				];
			} catch (\Exception $exception) {
				$status = [
					'error' => $exception->getMessage()
				];
			}

			$this->getAdmin()->printEntityTableControlResult($status);
		}

		/**
		 * Удаляет список типов цен торговых предложений
		 * @param array $priceTypeIdList список идентификаторов типов цен
		 */
		public function deleteTradeOfferPriceTypeList(array $priceTypeIdList = []) {
			$priceTypeIdList = $priceTypeIdList ?: $this->getPriceTypeIdList();

			try {
				Service::TradeOfferPriceTypeFacade()
					->deleteList($priceTypeIdList);
				$status = [
					'success' => true
				];
			} catch (\Exception $exception) {
				$status = [
					'error' => $exception->getMessage()
				];
			}

			$this->getAdmin()->printEntityTableControlResult($status);
		}

		/**
		 * Сохраняет значение атрибута типа цены торгового предложения
		 * @param int|null $id идентификатор типа цены
		 * @param string|null $field имя атрибута
		 * @param mixed $value значение атрибута
		 */
		public function saveTradeOfferPriceTypeField($id = null, $field = null, $value = null) {
			$admin = $this->getAdmin();
			$id = $id ?: $admin->getNumberedParameter(0);

			try {
				$tradeOfferPriceTypeFacade = Service::TradeOfferPriceTypeFacade();
				$priceType = $tradeOfferPriceTypeFacade->get($id);

				if (!$priceType instanceof iType) {
					throw new \ExpectTradeOfferException('Trade offer price type id expected');
				}

				$post = Service::Request()->Post();
				$field = $field ?: $post->get('field');
				$value = $value ?: $post->get('value');

				if ($field === iMapper::IS_DEFAULT) {
					$tradeOfferPriceTypeFacade->setDefault($priceType);
					$status = [
						'refresh' => true
					];
				} else {
					$tradeOfferPriceTypeFacade->importToEntity($priceType, [$field => $value]);
					$tradeOfferPriceTypeFacade->save($priceType);
					$status = [
						'success' => true
					];
				}
			}  catch (\Exception $exception) {
				$status = [
					'error' => $exception->getMessage()
				];
			}

			$admin->printEntityTableControlResult($status);
		}

		/**
		 * Выводит конфигурацию табличного контрола типов цен торговых предложений
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function flushTradeOfferPriceTypeListConfig() {
			$this->getModule()
				->printJson($this->getTradeOfferPriceTypeListConfig());
		}

		/**
		 * Фильтрует коллекцию типов цен торговых предложений
		 * @param iPriceTypeCollection|iCollection $collection коллекция
		 * @param array|null $filter параметры фильтрации
		 * @example
		 *
		 * [
		 *		'title' => [
		 * 			'eq' => 'Foo'
		 * 		],
		 * 		'name' => [
		 * 			'like' => 'Bar'
		 * 		]
		 * ]
		 *
		 * @return iPriceTypeCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		private function filterCollection(iPriceTypeCollection $collection, array $filter = null) {
			$admin = $this->getAdmin();
			$filter = $filter ?: $admin->getFilterValues();

			if (!$filter || $collection->getCount() === 0) {
				return $collection;
			}

			return $collection->filter($filter);
		}

		/**
		 * Сортирует коллекцию типов цен торговых предложений
		 * @param iPriceTypeCollection|iCollection $collection коллекция
		 * @param array|null $sort параметры сортировки
		 * @example
		 *
		 * [
		 *		'title' => 'asc',
		 *		'name' => 'desc',
		 * ]
		 *
		 * @return iPriceTypeCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		private function sortCollection(iPriceTypeCollection $collection, array $sort = null) {
			$admin = $this->getAdmin();
			$sort = $sort ?: $admin->getSortValues();

			if ($collection->getCount() === 0) {
				return $collection;
			}

			if (!$sort) {
				$sort = $this->getIdSort();
			}

			return $collection->sort($sort);
		}

		/**
		 * Возвращает карту сортировки по идентификатору типов цен торговых предложений
		 * @return array
		 */
		private function getIdSort() {
			return [iMapper::ID => iCollection::SORT_TYPE_ASC];
		}

		/**
		 * Формирует массив типов цен торговых предложений
		 * @param iPriceTypeCollection $collection коллекция типов цен
		 * @param int $totalCount длина отфильтрованной коллекции
		 * @return array
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		private function preparePriceTypeList(iPriceTypeCollection $collection, $totalCount) {
			$priceTypeRowList = $collection->map();
			$priceTypeRowList = array_values($priceTypeRowList);
			return $this->getAdmin()->appendPageNavigation($priceTypeRowList, $totalCount);
		}

		/**
		 * Возвращает конфигурацию табличного контрола типов цен торговых предложений
		 * @return array
		 */
		private function getTradeOfferPriceTypeListConfig() {
			return [
				'methods' => $this->getMethodsConfig(),
				'default' => $this->getDefaultVisibleColumnsConfig(),
				'fields' => $this->getFieldsConfig()
			];
		}

		/**
		 * Возвращает конфигурация методов табличного контрола типов цен торговых предложений
		 * @return array
		 */
		private function getMethodsConfig() {
			return [
				[
					'title' => getLabel('smc-load'),
					'module' => 'catalog',
					'type' => 'load',
					'name' => 'tradeOfferPriceTypes'
				],
				[
					'title' => getLabel('js-confirm-unrecoverable-yes'),
					'module' => 'catalog',
					'type' => 'delete',
					'name' => 'deleteTradeOfferPriceTypeList'
				],
				[
					'title' => getLabel('js-confirm-unrecoverable-yes'),
					'module' => 'catalog',
					'type' => 'saveField',
					'name' => 'saveTradeOfferPriceTypeField'
				]
			];
		}

		/**
		 * Возвращает перечень колонок табличного контрола, видимых по умолчанию
		 * @return string
		 */
		private function getDefaultVisibleColumnsConfig() {
			$columnList = [
				'title[350px]',
				'is_default[150px]'
			];
			return implode('|', $columnList);
		}

		/**
		 * Возвращает конфигурация полей табличного контрола типов цен торговых предложений
		 * @return array
		 */
		private function getFieldsConfig() {
			return [
				[
					'name' => 'title',
					'title' => getLabel('label-trade-offer-price-type-title'),
					'type' => 'string',
					'show_edit_page_link' => 'false'
				],
				[
					'name' => 'name',
					'title' => getLabel('label-trade-offer-price-type-name'),
					'type' => 'string'
				],
				[
					'name' => 'is_default',
					'title' => getLabel('label-trade-offer-price-type-is-default'),
					'type' => 'bool'
				]
			];
		}

		/**
		 * Возвращает список идентификаторов типов цен торговых предложений, над которым нужно произвести операцию
		 * @return array
		 */
		private function getPriceTypeIdList() {
			return Service::Request()->Post()->get('price_type_id_list');
		}

		/**
		 * Возвращает экземпяр базового класса административной панели модуля
		 * @return \CatalogAdmin
		 */
		private function getAdmin() {
			return $this->admin;
		}

		/**
		 * Устанавливает экземпяр базового класса административной панели модуля
		 * @param \CatalogAdmin $admin экземпяр базового класса административной панели модуля
		 * @return $this
		 */
		private function setAdmin(\CatalogAdmin $admin) {
			$this->admin = $admin;
			return $this;
		}
	}