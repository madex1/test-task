<?php
	namespace UmiCms\Classes\Components\Catalog\Trade\Offer;

	use UmiCms\Service;
	use UmiCms\System\Trade\iStock;
	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Entity\iMapper;
	use UmiCms\System\Orm\Entity\iCollection;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Trade\Offer\iCharacteristic;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;
	use UmiCms\System\Orm\Entity\iCollection as iAbstractCollection;
	use UmiCms\System\Trade\Offer\Price\iCollection as iPriceCollection;
	use UmiCms\System\Trade\Stock\Balance\iCollection as iStockBalanceCollection;
	use UmiCms\System\Trade\Offer\Characteristic\iCollection as iCharacteristicCollection;
	use UmiCms\System\Trade\Offer\Price\Type\iCollection as iPriceTypeCollection;

	/**
	 * Класс административного функционала торговых предложений
	 * @package UmiCms\Classes\Components\Catalog\Offer
	 */
	class Admin implements \iModulePart {

		use \tModulePart;

		/** @var \CatalogAdmin|null $admin базовый класс административной панели модуля */
		private $admin;

		/** @var string PRICE_TYPE_PREFIX префикс имени типа цены */
		const PRICE_TYPE_PREFIX  = 'price_type_';

		/** @var string STOCK_ID_PREFIX префикс имени идентификатор склада */
		const STOCK_ID_PREFIX  = 'stock_id_';

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

		/**
		 * Выводит в буфер список торговых предожений товара с учетом фильтрации, сортировки и пагинации
		 * @param int|null $pageId идентификатор страницы товара
		 * @param string|null $fieldName имя поля объекта товара со списком торговых предложений
		 */
		public function getProductOfferList($pageId = null, $fieldName = null) {
			$admin = $this->getAdmin();

			if (!Service::Request()->isJson()) {
				$admin->setDataSetDirectCallMessage();
				return;
			}

			$pageId = $pageId ?: $admin->getNumberedParameter(0);
			$fieldName = $fieldName ?: $admin->getNumberedParameter(1);

			try {
				$page = \umiHierarchy::getInstance()
					->getElement($pageId);

				if (!$page instanceof \iUmiHierarchyElement) {
					throw new \expectElementException('Catalog object page id expected');
				}

				$collection = $this->getOfferCollection($page, $fieldName);
				$collection = $this->filterOfferCollection($collection);
				$filteredCollectionCount = $collection->getCount();
				$collection = $this->sortOfferCollection($collection);
				$collection = $admin->sliceEntityCollection($collection);
				$result = $this->prepareOfferList($collection, $filteredCollectionCount, $page->getObjectTypeId());
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

		/**
		 * Сохраняет изменения поля торгового предложения и выводит результат в буфер
		 * @param int|null $offerId идентификатор торгового предложения
		 * @param string|null $field имя поля
		 * @param mixed|null $value значение поля
		 */
		public function saveTradeOfferField($offerId = null, $field = null, $value = null) {
			$admin = $this->getAdmin();
			$offerId = $offerId ?: $admin->getNumberedParameter(0);

			try {
				$tradeOfferFacade = Service::TradeOfferFacade();
				$offer = $tradeOfferFacade->get($offerId);

				if (!$offer instanceof iOffer) {
					throw new \ExpectTradeOfferException('Trade offer id expected');
				}

				$post = Service::Request()->Post();
				$field = $field ?: $post->get('field');
				$value = $value ?: $post->get('value');
				/** @var iMapper $mapper */
				$mapper = Service::get('TradeOfferMapper');

				switch (true) {
					case $mapper->isExistsAttribute($field) : {
						$tradeOfferFacade->importToEntity($offer, [$field => $value]);
						break;
					}
					case $this->isPriceTypeName($field) : {
						$this->saveOfferPriceValue($offerId, $this->getPriceType($field), $value);
						break;
					}
					case $this->isStockIdName($field) : {
						$this->saveOfferStockBalanceValue($offerId, $this->getStock($field), $value);
						break;
					}
					default : {
						$this->setCharacteristicValue($offer, $field, $value);
					}
				}

				$tradeOfferFacade->save($offer);

				$status = [
					'success' => true
				];
			}  catch (\Exception $exception) {
				$status = [
					'error' => $exception->getMessage()
				];
			}

			$admin->printEntityTableControlResult($status);
		}

		/**
		 * Изменяет положение торгового предложения в списке
		 * @param int|null $dropId идентификатор предложения, относительно которого происходит изменение
		 * @param array|null $dragIdList список идентификаторов предложений, которые меняют положение
		 * @param string|null $mode режиме изменения положения (до/после)
		 */
		public function changeTradeOfferOrder($dropId = null, array $dragIdList = null, $mode = null) {
			$dropId = $dropId ?: $this->getDropTargetOfferId();
			$dragIdList = $dragIdList ?: $this->getDragOfferIdList();
			$mode = $mode ?: $this->getDragAndDropMode();

			try {
				$tradeOfferFacade = Service::TradeOfferFacade();
				$targetOffer = $tradeOfferFacade->get($dropId);

				if (!$targetOffer instanceof iOffer) {
					throw new \ExpectTradeOfferException('Drop target trade offer id expected');
				}

				$dragOfferCollection = $tradeOfferFacade->getCollectionByIdList($dragIdList);
				$tradeOfferFacade->moveCollectionByMode($dragOfferCollection, $targetOffer, $mode);

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
		 * Удаляет список торговых предложений
		 * @param array $offerIdList список идентификаторов торговых предложений, которые требуется удалить
		 * @throws \Exception
		 */
		public function deleteTradeOfferList(array $offerIdList = []) {
			$offerIdList = $offerIdList ?: $this->getOfferIdList();

			try {
				Service::TradeOfferFacade()->deleteList($offerIdList);
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
		 * Создает торговое предложение товара
		 * @param int|null $productObjectId идентификатор объекта товара
		 * @param string|null $fieldName имя поля объекта товара со списком торговых предложений
		 * @throws \Exception
		 */
		public function addTradeOffer($productObjectId = null, $fieldName = null) {
			$productObjectId = $productObjectId ?: $this->getProductObjectId();

			try {
				$product = \umiObjectsCollection::getInstance()
					->getObject($productObjectId);

				if (!$product instanceof \iUmiObject) {
					throw new \expectObjectException('Product object id expected');
				}

				$tradeOffer = Service::TradeOfferFacade()
					->createForProduct($product);
				$fieldName = $fieldName ?: $this->getFieldName();
				$tradeOfferIdList = $product->getValue($fieldName);
				$tradeOfferIdList[] = $tradeOffer->getId();
				$product->setValue($fieldName, $tradeOfferIdList);
				$product->commit();

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
		 * Копирует торговое предложение товара
		 * @param int|null $sourceOfferId идентификатор копируемого торгового предложения
		 * @param int|null $productObjectId идентификатор объекта товара
		 * @param string|null $fieldName имя поля объекта товара со списком торговых предложений
		 * @throws \Exception
		 */
		public function copyTradeOffer($sourceOfferId = null, $productObjectId = null, $fieldName = null) {
			$sourceOfferId = $sourceOfferId ?: $this->getOfferIdForCopying();

			try {
				$facade = Service::TradeOfferFacade();
				$tradeOffer = $facade->get($sourceOfferId);

				if (!$tradeOffer instanceof iOffer) {
					throw new \ExpectTradeOfferException('Trade offer id expected');
				}

				$copy = $facade->copy($tradeOffer);

				$productObjectId = $productObjectId ?: $this->getProductObjectId();
				$productObject = \umiObjectsCollection::getInstance()
					->getObject($productObjectId);

				if (!$productObject instanceof \iUmiObject) {
					throw new \expectObjectException('Product object id expected');
				}

				$fieldName = $fieldName ?: $this->getFieldName();
				$tradeOfferIdList = $productObject->getValue($fieldName);
				$tradeOfferIdList[] = $copy->getId();
				$productObject->setValue($fieldName, $tradeOfferIdList);
				$productObject->commit();

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
		 * Меняет статус активности списка торговых предложений на заданное значение
		 * @param array $offerIdList список идентификаторов торговых предложений
		 * @param bool|null $activityStatus значение активности
		 */
		public function changeTradeOfferListActivity(array $offerIdList = [], $activityStatus = null) {
			$offerIdList = $offerIdList ?: $this->getOfferIdList();
			$activityStatus = ($activityStatus !== null) ? $activityStatus : (bool) $this->getActivityStatus();

			try {
				$tradeOfferFacade = Service::TradeOfferFacade();
				$offerCollection = $tradeOfferFacade->getCollectionByIdList($offerIdList);

				/** @var iOffer $offer */
				foreach ($offerCollection as $offer) {
					$offer->setActive($activityStatus);
					$tradeOfferFacade->save($offer);
				}

				$status = [
					'success' => true
				];
			} catch (\Exception $exception) {
				$status = [
					'error' => $activityStatus
				];
			}

			$this->getAdmin()->printEntityTableControlResult($status);
		}

		/**
		 * Выводит конфигурацию табличного контрола торговых предложений
		 * @param int|null $pageId идентификатор страницы товара
		 * @param string|null $fieldName имя поля объекта товара со списком торговых предложений
		 * @throws \RequiredPropertyHasNoValueException
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \ExpectFieldGroupException
		 * @throws \expectElementException
		 * @throws \expectObjectTypeException
		 * @throws \ReflectionException
		 */
		public function flushTradeOfferListConfig($pageId = null, $fieldName = null) {
			$admin = $this->getAdmin();
			$pageId = $pageId ?: $admin->getNumberedParameter(0);
			$fieldName = $fieldName ?: $admin->getNumberedParameter(1);
			$this->getModule()
				->printJson($this->getTradeOfferListConfig($pageId, $fieldName));
		}

		/**
		 * Возвращает конфигурацию табличного контрола торговых предложений
		 * @param int $pageId идентификатор страницы товара
		 * @param int $fieldName имя поля объекта товара со списком торговых предложений
		 * @return array
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \ExpectFieldGroupException
		 * @throws \expectElementException
		 * @throws \expectObjectTypeException
		 * @throws \ReflectionException
		 * @throws \ExpectFieldGroupException
		 */
		private function getTradeOfferListConfig($pageId, $fieldName) {
			return [
				'methods' => $this->getTradeOfferListMethodsConfig($pageId, $fieldName),
				'default' => $this->getDefaultVisibleColumnsConfig(),
				'fields' => $this->getTradeOfferFieldsConfig($pageId)
			];
		}

		/**
		 * Возвращает конфигурация методов табличного контрола торговых предложений
		 * @param int $pageId идентификатор страницы товара
		 * @param int $fieldName имя поля объекта товара со списком торговых предложений
		 * @return array
		 */
		private function getTradeOfferListMethodsConfig($pageId, $fieldName) {
			return [
				[
					'title' => getLabel('smc-load'),
					'module' => 'catalog',
					'type' => 'load',
					'name' => sprintf('getProductOfferList/%d/%s', $pageId, $fieldName)
				],
				[
					'title' => getLabel('js-confirm-unrecoverable-yes'),
					'module' => 'catalog',
					'type' => 'delete',
					'name' => 'deleteTradeOfferList'
				],
				[
					'title' => getLabel('js-confirm-unrecoverable-yes'),
					'module' => 'catalog',
					'type' => 'saveField',
					'name' => 'saveTradeOfferField'
				],
				[
					'title' => getLabel('smc-move'),
					'module' => 'catalog',
					'type' => 'move',
					'name' => 'changeTradeOfferOrder'
				],
			];
		}

		/**
		 * Возвращает перечень колонок табличного контрола, видимых по умолчанию
		 * @return string
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		private function getDefaultVisibleColumnsConfig() {
			$columnList = [
				'name[350px]',
				'is_active[150px]',
				'vendor_code[150px]',
				'trade_offer_image[250px]'
			];

			foreach ($this->getOfferPriceFieldList() as $priceConfig) {
				$columnList[] = sprintf('%s[250px]', $priceConfig['name']);
			}

			$columnList[] = 'total_count[200px]';

			return implode('|', $columnList);
		}

		/**
		 * Возвращает конфигурация полей табличного контрола торговых предложений
		 * @param int $pageId идентификатор страницы товара
		 * @return array
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \ExpectFieldGroupException
		 * @throws \expectElementException
		 * @throws \expectObjectTypeException
		 * @throws \ReflectionException
		 * @throws \ExpectFieldGroupException
		 */
		private function getTradeOfferFieldsConfig($pageId) {
			return array_merge(
				$this->getOfferFieldList(),
				$this->getOfferPriceFieldList(),
				$this->getOfferStockBalanceList(),
				$this->getOfferCharacteristicList($pageId)
			);
		}

		/**
		 * Возвращает список полей торгового предложения
		 * @return array
		 */
		private function getOfferFieldList() {
			return [
				[
					'name' => 'name',
					'title' => getLabel('label-trade-offer-name'),
					'type' => 'string',
					'show_edit_page_link' => 'false'
				],
				[
					'name' => 'vendor_code',
					'title' => getLabel('label-trade-offer-vendor-code'),
					'type' => 'string'
				],
				[
					'name' => 'is_active',
					'title' => getLabel('label-trade-offer-is-active'),
					'type' => 'bool'
				],
				[
					'name' => 'bar_code',
					'title' => getLabel('label-trade-offer-bar-code'),
					'type' => 'string'
				],
				[
					'name' => 'total_count',
					'title' => getLabel('label-trade-offer-total-count'),
					'type' => 'integer'
				],
				[
					'name' => 'weight',
					'title' => getLabel('label-trade-offer-weight'),
					'type' => 'integer'
				],
				[
					'name' => 'width',
					'title' => getLabel('label-trade-offer-width'),
					'type' => 'integer'
				],
				[
					'name' => 'length',
					'title' => getLabel('label-trade-offer-length'),
					'type' => 'integer'
				],
				[
					'name' => 'height',
					'title' => getLabel('label-trade-offer-height'),
					'type' => 'integer'
				]
			];
		}

		/**
		 * Возвращает список цен торгового предложения
		 * @return array
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		private function getOfferPriceFieldList() {
			$priceFieldList = [];

			foreach ($this->getOfferPriceTypeCollection() as $type) {
				$priceFieldList[] = [
					'name' => $this->getPriceTypeName($type),
					'title' => $type->getPriceTitle(),
					'type' => $type->getPriceViewType()
				];
			}

			return $priceFieldList;
		}

		/**
		 * Возвращает список возможных складских остатков
		 * @return array
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		private function getOfferStockBalanceList() {
			$stockList = [];

			foreach ($this->getStockList() as $stock) {
				$stockList[] = [
					'name' => $this->getStockIdName($stock),
					'title' => $stock->getBalanceTitle(),
					'type' => $stock->getBalanceViewType()
				];
			}

			return $stockList;
		}

		/**
		 * Возвращает список характеристик торгового предложения
		 * @param int $pageId идентификатор товара, которому принадлежат предложения
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \expectElementException
		 * @throws \expectObjectTypeException
		 */
		private function getOfferCharacteristicList($pageId) {
			$page = Service::Hierarchy()
				->getElement($pageId);

			if (!$page instanceof \iUmiHierarchyElement) {
				throw new \expectElementException('Product page id expected');
			}

			$characteristicCollection = Service::TradeOfferCharacteristicFacade()
				->getCollectionByType($page->getObjectTypeId());
			$characteristicList = [];

			/** @var iCharacteristic $characteristic */
			foreach ($characteristicCollection as $characteristic) {
				$characteristicList[] = [
					'name' => $characteristic->getName(),
					'title' => $characteristic->getTitle(),
					'multiple' => $characteristic->isMultiple(),
					'type' => $characteristic->getViewType(),
					'guide_id' => $characteristic->getGuideId()
				];
			}

			return $characteristicList;
		}

		/**
		 * Возвращает список складов
		 * @return iStock[]
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		private function getStockList() {
			return Service::TradeStockFacade()->getList();
		}

		/**
		 * Сохраняет значение цены торгового предложения
		 * @param int $offerId идентификатор торгового предложения
		 * @param iType $priceType тип цены
		 * @param float $value значение
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \wrongParamException
		 * @throws \privateException
		 */
		private function saveOfferPriceValue($offerId, iType $priceType, $value) {
			$priceFacade = Service::TradeOfferPriceFacade();
			$priceTypeId = $priceType->getId();
			$price = $priceFacade->getCollectionByOffer($offerId)
				->copy()
				->filterByType($priceTypeId)
				->getFirst();
			$price = $price ?: $priceFacade->createByOfferAndType($offerId, $priceTypeId);
			$value = (float) $value;
			$price->setValue($value);
			$priceFacade->save($price);
			return $this;
		}

		/**
		 * Сохраняет значение складского остатка торгового предложения
		 * @param int $offerId идентификатор торгового предложения
		 * @param iStock $stock склад
		 * @param int $value значение
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		private function saveOfferStockBalanceValue($offerId, iStock $stock, $value) {
			$stockBalanceFacade = Service::TradeStockBalanceFacade();
			$stockId = $stock->getId();
			$stockBalance = $stockBalanceFacade->getCollectionByOffer($offerId)
				->copy()
				->filterByStock($stockId)
				->getFirst();
			$stockBalance = $stockBalance ?: $stockBalanceFacade->createByOfferAndStock($offerId, $stockId);
			$value = (int) $value;
			$stockBalance->setValue($value);
			$stockBalanceFacade->save($stockBalance);
			return $this;
		}

		/**
		 * Устанавливает значение характеристики торгового предложения
		 * @param iOffer $offer торговое предложение
		 * @param string $field имя характеристики
		 * @param mixed $value значение характеристики
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ExpectFieldException
		 * @throws \coreException
		 * @throws \databaseException
		 * @throws \expectObjectException
		 */
		private function setCharacteristicValue(iOffer $offer, $field, $value) {
			$characteristic = Service::TradeOfferCharacteristicFacade()
				->createByOfferAndFieldName($offer, $field);

			switch ($characteristic->getFieldType()) {
				case 'date' : {
					$value = Service::DateFactory()
						->createByDateString($value);
					break;
				}
				case 'file' : {
					$value = Service::FileFactory()
						->createSecure($value);
					break;
				}
				case 'img_file' : {
					$value = Service::ImageFactory()
						->create($value);
					break;
				}
			}

			if ($value instanceof \iUmiFile && !$value->isExists()) {
				throw new \ErrorException(getLabel('label-error-trade-offer-broken-path'));
			}

			$characteristic->setValue($value);
			return $this;
		}

		/**
		 * Возвращает коллекцию торговых предложений товара
		 * @param \iUmiHierarchyElement $page страница товара
		 * @param string $fieldName имя поля объекта товара со списком торговых предложений
		 * @return iOfferCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		private function getOfferCollection(\iUmiHierarchyElement $page, $fieldName) {
			$offerListId = (array) $page->getValue($fieldName);
			return Service::TradeOfferFacade()
				->getCollectionByIdList($offerListId);
		}

		/**
		 * Фильтрует коллекцию торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @param array|null $offerFilter параметры фильтрации
		 * @example
		 *
		 * [
		 *		'vendor_code' => [
		 * 			'eq' => 'Foo'
		 * 		],
		 * 		'name' => [
		 * 			'like' => 'Bar'
		 * 		]
		 * ]
		 *
		 * @return iOfferCollection
		 * @throws \ExpectTradeStockException
		 * @throws \ExpectTradeOfferPriceTypeException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 * @throws \ExpectFieldGroupException
		 * @throws \coreException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		private function filterOfferCollection(iOfferCollection $offerCollection, array $offerFilter = null) {
			$admin = $this->getAdmin();
			$offerFilter = $offerFilter ?: $admin->getFilterValues();

			if (!$offerFilter || $offerCollection->getCount() === 0) {
				return $offerCollection;
			}

			$offerFilter = $this->appendPriceFilter($offerFilter, $offerCollection);
			$offerFilter = $this->appendStockBalanceFilter($offerFilter, $offerCollection);
			$offerFilter = $this->appendCharacteristicFilter($offerFilter, $offerCollection);

			return $offerCollection->filter($offerFilter);
		}

		/**
		 * Применяет параметры фильтра списка предложений по цене
		 * @param array $offerFilter параметры фильтра торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return array
		 * @throws \ExpectTradeOfferPriceTypeException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		private function appendPriceFilter(array $offerFilter, iOfferCollection $offerCollection) {
			$offerPriceFilter = $this->pullOfferPriceConditions($offerFilter);

			if (isEmptyArray($offerPriceFilter)) {
				return $offerFilter;
			}

			$priceFilter = $this->buildPriceFilter($offerPriceFilter);
			$priceFacade = Service::TradeOfferPriceFacade();
			$offerIdList = $priceFacade->getCollectionByOfferCollection($offerCollection)
				->filterByList($priceFilter)
				->extractOfferId();
			return $this->appendOfferFilterByValueList($offerFilter, 'id', $offerIdList);
		}

		/**
		 * Извлекает параметры фильтра или сортировки по цене
		 * @param array $offerConditions параметры фильтра или сортировки торговых предложений
		 * @return array
		 */
		private function pullOfferPriceConditions(array &$offerConditions) {
			return $this->pullConditions($offerConditions, [$this, 'isPriceTypeName']);
		}

		/**
		 * Формирует параметры фильтра списка цен
		 * @param array $offerPriceFilter параметры фильтра торговых предложений по цене
		 * @return array
		 * @throws \ExpectTradeOfferPriceTypeException
		 */
		private function buildPriceFilter(array $offerPriceFilter) {
			$priceFilter = [];

			foreach ($offerPriceFilter as $typeName => $condition) {
				$priceFilter[] = [
					'type_id' => [
						iAbstractCollection::COMPARE_TYPE_EQUALS => $this->getPriceType($typeName)->getId()
					],
					'value' => $condition
				];
			}

			return $priceFilter;
		}

		/**
		 * Добавляет фильтр по заданному списку значений параметра торговых предложений
		 * @param array $offerFilter параметры фильтра списка торговых предложений
		 * @param string $name имя добавляемого параметра
		 * @param array $valueList список значений добавляемого параметра
		 * @return array
		 */
		private function appendOfferFilterByValueList(array $offerFilter, $name, array $valueList) {

			if (isset($offerFilter[$name][iAbstractCollection::COMPARE_TYPE_IN_LIST])) {
				$existIdList = $offerFilter[$name][iAbstractCollection::COMPARE_TYPE_IN_LIST];
				$valueList = array_intersect($existIdList, $valueList);
			}

			$offerFilter[$name] = [
				iAbstractCollection::COMPARE_TYPE_IN_LIST => $valueList
			];

			return $offerFilter;
		}

		/**
		 * Применяет параметры фильтра списка предложений по складскому остатку
		 * @param array $offerFilter параметры фильтра торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectTradeStockException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		private function appendStockBalanceFilter(array $offerFilter, iOfferCollection $offerCollection) {
			$offerStockBalanceFilter = $this->pullOfferStockBalanceConditions($offerFilter);

			if (isEmptyArray($offerStockBalanceFilter)) {
				return $offerFilter;
			}

			$stockBalanceFilter = $this->buildStockBalanceFilter($offerStockBalanceFilter);
			$stockBalanceFacade = Service::TradeStockBalanceFacade();
			$offerIdList = $stockBalanceFacade->getCollectionByOfferCollection($offerCollection)
				->filterByList($stockBalanceFilter)
				->extractOfferId();

			return $this->appendOfferFilterByValueList($offerFilter, 'id', $offerIdList);
		}

		/**
		 * Извлекает параметры фильтра или сортировки по складским остаткам
		 * @param array $offerConditions параметры фильтра или сортировки торговых предложений
		 * @return array
		 */
		private function pullOfferStockBalanceConditions(array &$offerConditions) {
			return $this->pullConditions($offerConditions, [$this, 'isStockIdName']);
		}

		/**
		 * Извлекает параметры фильтра или сортировки с помощью функция валидации имени параметра
		 * @param array $offerConditions параметры фильтра или сортировки торговых предложений
		 * @param callable $callback функция валидации имени параметра
		 * @return array
		 */
		private function pullConditions(array &$offerConditions, callable $callback) {
			$conditions = [];

			foreach ($offerConditions as $fieldName => $condition) {

				if (!$callback($fieldName)) {
					continue;
				}

				$conditions[$fieldName] = $condition;
				unset($offerConditions[$fieldName]);
			}

			return $conditions;
		}

		/**
		 * Формирует параметры фильтра списка цен
		 * @param array $offerStockBalanceFilter параметры фильтра торговых предложений по складским остаткам
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectTradeStockException
		 */
		private function buildStockBalanceFilter(array $offerStockBalanceFilter) {
			$priceFilter = [];

			foreach ($offerStockBalanceFilter as $stockIdName => $condition) {
				$priceFilter[] = [
					'stock_id' => [
						iAbstractCollection::COMPARE_TYPE_EQUALS => $this->getStock($stockIdName)->getId()
					],
					'value' => $condition
				];
			}

			return $priceFilter;
		}

		/**
		 * Применяет параметры фильтра списка предложений по характеристикам
		 * @param array $offerFilter параметры фильтра торговых предложений
		 * @param iOfferCollection $offerCollection параметры фильтра торговых предложений
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		private function appendCharacteristicFilter(array $offerFilter, iOfferCollection $offerCollection) {
			$offerCharacteristicFilter = $this->pullOfferCharacteristicConditions($offerFilter);

			if (isEmptyArray($offerCharacteristicFilter)) {
				return $offerFilter;
			}

			$typeId = $offerCollection->getFirst()->getTypeId();
			$characteristicFilter = $this->buildCharacteristicFilter($offerCharacteristicFilter, $typeId);
			$characteristicFacade = Service::TradeOfferCharacteristicFacade();
			$dataObjectIdList = [];
			$characteristicCollection = $characteristicFacade->getCollectionByOfferCollection($offerCollection);

			foreach ($characteristicFilter as $filter) {
				$nextDataObjectIdList = $characteristicCollection->filter($filter)
					->extractDataObjectId();

				if (isEmptyArray($dataObjectIdList)) {
					$dataObjectIdList = $nextDataObjectIdList;
				} else {
					$dataObjectIdList = array_intersect($dataObjectIdList, $nextDataObjectIdList);
				}
			}

			return $this->appendOfferFilterByValueList($offerFilter, 'data_object_id', $dataObjectIdList);
		}

		/**
		 * Извлекает параметры фильтра или сортировки по характеристикам
		 * @param array $offerConditions параметры фильтра или сортировки торговых предложений
		 * @return array
		 */
		private function pullOfferCharacteristicConditions(array &$offerConditions) {
			return $this->pullConditions($offerConditions, [$this, 'isNotOfferAttribute']);
		}

		/**
		 * Формирует параметры фильтра списка характеристик
		 * @param array $offerCharacteristicFilter параметры фильтра торговых предложений по характеристикам
		 * @param int $typeId идентификатор типа предложения
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \expectObjectTypeException
		 */
		private function buildCharacteristicFilter(array $offerCharacteristicFilter, $typeId) {
			$characteristicFilter = [];
			$characteristicFacade = Service::TradeOfferCharacteristicFacade();
			$characteristicCollection = $characteristicFacade->getCollectionByType($typeId);

			foreach ($offerCharacteristicFilter as $fieldName => $condition) {
				$characteristic = $characteristicCollection
					->copy()
					->filterByField($fieldName)
					->getFirst();

				if (!$characteristic instanceof iCharacteristic) {
					continue;
				}

				$filter = [
					'name' => [
						iAbstractCollection::COMPARE_TYPE_EQUALS => $fieldName
					]
				];

				$valueFilter = $condition;

				if (contains($characteristic->getFieldType(), 'file') || $characteristic->getFieldType() === 'boolean') {
					$isNotNull = (bool) array_shift($condition);
					$compareType = $isNotNull ? iAbstractCollection::COMPARE_TYPE_NOT_EQUALS : iAbstractCollection::COMPARE_TYPE_EQUALS;
					$valueFilter = [$compareType => null];
				}

				if ($characteristic->getFieldType() === 'relation') {
					$valueFilter = [iAbstractCollection::COMPARE_TYPE_LIKE => array_shift($condition)];
				}

				$filter['value'] = $valueFilter;
				$characteristicFilter[] = $filter;
			}

			return $characteristicFilter;
		}

		/**
		 * Сортирует коллекцию торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @param array|null $offerSort параметры сортировки
		 * @example
		 *
		 * [
		 *		'vendor_code' => 'asc',
		 *		'name' => 'desc',
		 * ]
		 *
		 * @return iOfferCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \ExpectTradeOfferPriceTypeException
		 * @throws \ExpectTradeStockException
		 * @throws \databaseException
		 * @throws \ExpectFieldGroupException
		 * @throws \coreException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		private function sortOfferCollection(iOfferCollection $offerCollection, array $offerSort = null) {
			$admin = $this->getAdmin();
			$offerSort = $offerSort ?: $admin->getSortValues();

			if ($offerCollection->getCount() === 0) {
				return $offerCollection;
			}

			if (!$offerSort) {
				$offerSort = $this->getOrderIndexSort();
			} else {
				$offerSort = $this->appendPriceSort($offerSort, $offerCollection);
				$offerSort = $this->appendStockBalanceSort($offerSort, $offerCollection);
				$offerSort = $this->appendCharacteristicSort($offerSort, $offerCollection);
			}

			return $offerCollection->sort($offerSort);
		}

		/**
		 * Возвращает карту сортировки по индексу сортировки торговых предложений
		 * @return array
		 */
		private function getOrderIndexSort() {
			return ['order' => iAbstractCollection::SORT_TYPE_ASC];
		}

		/**
		 * Применяет сортировку по цене к списку торговых предложений
		 * @param array $offerSort параметры сортировки списка торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectTradeOfferPriceTypeException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		private function appendPriceSort(array $offerSort, iOfferCollection $offerCollection) {
			$offerPriceSort = $this->pullOfferPriceConditions($offerSort);

			if (isEmptyArray($offerPriceSort)) {
				return $offerSort;
			}

			$priceSort = $this->buildPriceSort($offerPriceSort);
			$priceCollection = Service::TradeOfferPriceFacade()
				->getCollectionByOfferCollection($offerCollection);

			foreach ($priceSort as $typeId => $sortMap) {
				$priceCollection = $priceCollection->copy()
					->filterByType($typeId)
					->sort($sortMap);
				$offerCollection->sortByPriceCollection($priceCollection);
			}

			return $offerSort;
		}

		/**
		 * Формирует параметры сортировки списка цен
		 * @param array $offerPriceSort параметры сортировки списка торговых предложений по цене
		 * @return array
		 * @throws \ExpectTradeOfferPriceTypeException
		 */
		private function buildPriceSort(array $offerPriceSort) {
			$priceSort = [];

			foreach ($offerPriceSort as $typeName => $condition) {
				$typeId = $this->getPriceType($typeName)->getId();
				$priceSort[$typeId] = [
					'value' => $condition
				];
			}

			return $priceSort;
		}

		/**
		 * Применяет сортировку по складскому остатку к списку торговых предложений
		 * @param array $offerSort параметры сортировки списка торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectTradeStockException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		private function appendStockBalanceSort(array $offerSort, iOfferCollection $offerCollection) {
			$offerStockBalanceSort = $this->pullOfferStockBalanceConditions($offerSort);

			if (isEmptyArray($offerStockBalanceSort)) {
				return $offerSort;
			}

			$stockBalanceSort = $this->buildStockBalanceSort($offerStockBalanceSort);
			$stockBalanceCollection = Service::TradeStockBalanceFacade()
				->getCollectionByOfferCollection($offerCollection);

			foreach ($stockBalanceSort as $stockId => $sortMap) {
				$stockBalanceCollection = $stockBalanceCollection->copy()
					->filterByStock($stockId)
					->sort($sortMap);
				$offerCollection->sortByStockBalanceCollection($stockBalanceCollection);
			}

			return $offerSort;
		}

		/**
		 * Формирует параметры сортировки списка складских остатков
		 * @param array $offerStockBalanceSort параметры сортировки списка торговых предложений по складским остаткам
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectTradeStockException
		 */
		private function buildStockBalanceSort(array $offerStockBalanceSort) {
			$stockBalanceSort = [];

			foreach ($offerStockBalanceSort as $stockIdName => $condition) {
				$stockId = $this->getStock($stockIdName)->getId();
				$stockBalanceSort[$stockId] = [
					'value' => $condition
				];
			}

			return $stockBalanceSort;
		}

		/**
		 * Применяет сортировку по характеристике к списку торговых предложений
		 * @param array $offerSort параметры сортировки списка торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		private function appendCharacteristicSort(array $offerSort, iOfferCollection $offerCollection) {
			$offerCharacteristicSort = $this->pullOfferCharacteristicConditions($offerSort);

			if (isEmptyArray($offerCharacteristicSort)) {
				return $offerSort;
			}

			$offerCharacteristicSort = $this->buildCharacteristicSort($offerCharacteristicSort);
			$characteristicCollection = Service::TradeOfferCharacteristicFacade()
				->getCollectionByOfferCollection($offerCollection);

			foreach ($offerCharacteristicSort as $name => $sortMap) {
				$characteristicCollection = $characteristicCollection->copy()
					->filterByField($name)
					->sort($sortMap);
				$offerCollection->sortByCharacteristicCollection($characteristicCollection);
			}

			return $offerSort;
		}

		/**
		 * Формирует параметры сортировки списка характеристик
		 * @param array $offerCharacteristicSort параметры сортировки списка торговых предложений по характеристике
		 * @return array
		 */
		private function buildCharacteristicSort(array $offerCharacteristicSort) {
			$characteristicSort = [];

			foreach ($offerCharacteristicSort as $name => $sortType) {
				$characteristicSort[$name] = [
					'value' => $sortType
				];
			}

			return $characteristicSort;
		}

		/**
		 * Формирует массив торговых предложений
		 * @param iOfferCollection|iCollection $offerCollection результирующая коллекция списка торговых предложений
		 * @param int $totalCount длина отфильтрованной коллекции торговых предложений
		 * @param int $typeId идентификатор типа данных торгового предложения
		 * @return array
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \privateException
		 * @throws \selectorException
		 * @throws \wrongParamException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 * @throws \ExpectFieldGroupException
		 */
		private function prepareOfferList(iOfferCollection $offerCollection, $totalCount, $typeId) {
			$offerRowList = $offerCollection->map();

			$priceCollection = Service::TradeOfferPriceFacade()
				->getCollectionByOfferCollection($offerCollection);
			$offerRowList = $this->appendPriceListToOfferRowList($priceCollection, $offerRowList);

			$stockBalanceCollection = Service::TradeStockBalanceFacade()
				->getCollectionByOfferCollection($offerCollection);
			$offerRowList = $this->appendStockBalanceListToOfferRowList($stockBalanceCollection, $offerRowList);

			$characteristicCollection = Service::TradeOfferCharacteristicFacade()
				->getCollectionByOfferCollection($offerCollection);
			$offerRowList = $this->appendCharacteristicListToOfferRowList($characteristicCollection, $offerRowList, $typeId);

			$offerRowList = array_values($offerRowList);
			return $this->getAdmin()->appendPageNavigation($offerRowList, $totalCount);
		}

		/**
		 * Добавляет значения цен в список атрибутов торговых предложений
		 * @param iPriceCollection $priceCollection коллекция цен
		 * @param array $offerRowList список атрибутов торговых предложений
		 * @return array
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \privateException
		 * @throws \selectorException
		 * @throws \wrongParamException
		 * @throws \ReflectionException
		 */
		private function appendPriceListToOfferRowList(iPriceCollection $priceCollection, array $offerRowList) {
			$priceFacade = Service::TradeOfferPriceFacade();
			$offerPriceTypeCollection = $this->getOfferPriceTypeCollection();

			foreach ($offerRowList as $index => $offerRow) {
				$offerId = $offerRow['id'];
				$offerPriceCollection = $priceCollection->copy()
					->filterByOffer($offerId);

				foreach ($offerPriceTypeCollection as $type) {
					$typeId = $type->getId();
					$price = $offerPriceCollection->copy()
						->filterByType($typeId)
						->getFirst();
					$price = $price ?: $priceFacade->createByOfferAndType($offerId, $typeId);
					$offerRowList[$index][$this->getPriceTypeName($type)] = $price->getValue();
				}
			}

			return $offerRowList;
		}

		/**
		 * Добавляет значения складских остатков в список атрибутов торговых предложений
		 * @param iStockBalanceCollection $stockBalanceCollection коллекция складских остатков
		 * @param array $offerRowList список атрибутов торговых предложений
		 * @return array
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		private function appendStockBalanceListToOfferRowList(iStockBalanceCollection $stockBalanceCollection, array $offerRowList) {
			$stockBalanceFacade = Service::TradeStockBalanceFacade();
			$stockList = $this->getStockList();

			foreach ($offerRowList as $index => $offerRow) {
				$offerId = $offerRow['id'];
				$offerStockBalanceCollection = $stockBalanceCollection->copy()
					->filterByOffer($offerId);

				foreach ($stockList as $stock) {
					$stockId = $stock->getId();
					$stockBalance = $offerStockBalanceCollection->copy()
						->filterByStock($stockId)
						->getFirst();
					$stockBalance = $stockBalance ?: $stockBalanceFacade->createByOfferAndStock($offerId, $stockId);
					$offerRowList[$index][$this->getStockIdName($stock)] = $stockBalance->getValue();
				}
			}

			return $offerRowList;
		}

		/**
		 * Добавляет значения характеристик в список атрибутов торговых предложений
		 * @param iCharacteristicCollection $characteristicCollection коллекция характеристик
		 * @param array $offerRowList список атрибутов торговых предложений
		 * @param int $typeId идентификатор типа данных торгового предложения
		 * @return array
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		private function appendCharacteristicListToOfferRowList(iCharacteristicCollection $characteristicCollection, array $offerRowList, $typeId) {
			$tradeOfferFacade = Service::TradeOfferFacade();
			$characteristicFacade = Service::TradeOfferCharacteristicFacade();
			$typeCharacteristicCollection = $characteristicFacade->getCollectionByType($typeId);

			foreach ($offerRowList as $index => $offerRow) {
				$offerId = $offerRow['id'];
				$dataObjectId = $offerRow['data_object_id'];
				$offerCharacteristicCollection = $characteristicCollection->copy()
					->filterByDataObject($dataObjectId);

				foreach ($typeCharacteristicCollection as $typeCharacteristic) {
					$typeCharacteristicId = $typeCharacteristic->getId();
					$offerCharacteristic = $offerCharacteristicCollection->get($typeCharacteristicId);

					if (!$offerCharacteristic) {
						$offer = $tradeOfferFacade->get($offerId);
						$offerCharacteristic = $characteristicFacade->createByOfferAndField($offer, $typeCharacteristic->getField());
						$tradeOfferFacade->save($offer);
					}

					$offerRowList[$index][$offerCharacteristic->getName()] = $offerCharacteristic->getValue();
				}
			}

			return $offerRowList;
		}

		/**
		 * Возвращает идентификатор торгового предложения, относительно которого происходит перетаскивание
		 * @return int|null
		 */
		private function getDropTargetOfferId() {
			$id = Service::Request()->Post()->get('rel') ?: [];
			return isset($id['id']) ? (int) $id['id'] : null;
		}

		/**
		 * Возвращает список идентификаторов торговых предложений, которые перетаскивают
		 * @return int[]
		 */
		private function getDragOfferIdList() {
			$selectedList = Service::Request()->Post()->get('selected_list') ?: [];
			$idList = [];

			foreach ($selectedList as $id) {
				if (!isset($id['id'])) {
					continue;
				}

				$idList[] = (int) $id['id'];
			}

			return $idList;
		}

		/**
		 * Возвращает режим перемещения торговых предложений
		 * @return null|string
		 */
		private function getDragAndDropMode() {
			return $this->getAdmin()->getDragMode();
		}

		/**
		 * Возвращает имя типа цены
		 * @param iType $type тип цены
		 * @return string
		 */
		private function getPriceTypeName(iType $type) {
			return sprintf('%s%s', self::PRICE_TYPE_PREFIX, $type->getName());
		}

		/**
		 * Возвращает имя иденитификатор склада
		 * @param iStock $stock склад
		 * @return string
		 */
		private function getStockIdName(iStock $stock) {
			return sprintf('%s%d', self::STOCK_ID_PREFIX, $stock->getId());
		}

		/**
		 * Определяет является ли имя поля именем типа цены
		 * @param string $name проверяемое имя
		 * @return bool
		 */
		private function isPriceTypeName($name) {
			return contains($name, self::PRICE_TYPE_PREFIX);
		}

		/**
		 * Определяет является ли имя поля именен складского остатка
		 * @param string $name проверяемое имя
		 * @return bool
		 */
		private function isStockIdName($name) {
			return contains($name, self::STOCK_ID_PREFIX);
		}

		/**
		 * Определяет, что поле не является атрибутом торгового предложения
		 * @param string $name
		 * @return bool
		 * @throws \Exception
		 */
		private function isNotOfferAttribute($name) {
			/** @var iMapper $mapper */
			$mapper = Service::get('TradeOfferMapper');
			return $mapper->isExistsAttribute($name) === false;
		}

		/**
		 * Возвращает группу цены
		 * @param string $priceName имя типа цены
		 * @return null|iType
		 * @throws \ExpectTradeOfferPriceTypeException
		 */
		private function getPriceType($priceName) {
			$typeName = str_replace(self::PRICE_TYPE_PREFIX, '', $priceName);
			$type = Service::TradeOfferPriceTypeFacade()
				->getByName($typeName);

			if (!$type instanceof iType) {
				throw new \ExpectTradeOfferPriceTypeException('Trade offer price type name expected');
			}

			return $type;
		}

		/**
		 * Возвращает склад
		 * @param string $stockIdName имя идентификатор склада
		 * @return iStock
		 * @throws \ErrorException
		 * @throws \ExpectTradeStockException
		 */
		private function getStock($stockIdName) {
			$id = str_replace(self::STOCK_ID_PREFIX, '', $stockIdName);
			$stock = Service::TradeStockFacade()
				->get($id);

			if (!$stock instanceof iStock) {
				throw new \ExpectTradeStockException('Trade offer stock id name expected');
			}

			return $stock;
		}

		/**
		 * Возвращает коллекцию типов цен торговых предложений
		 * @return iPriceTypeCollection|iCollection
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		private function getOfferPriceTypeCollection() {
			return Service::TradeOfferPriceTypeFacade()
				->getAll();
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

		/**
		 * Возвращает идентификатор объекта товара
		 * @return mixed
		 */
		private function getProductObjectId() {
			return Service::Request()->Post()->get('object_id');
		}

		/**
		 * Возвращает имя поля объекта товара со списком торговых предложений
		 * @return mixed
		 */
		private function getFieldName() {
			return Service::Request()->Post()->get('field_name');
		}

		/**
		 * Возвращает список идентификаторов торговых предложений, над которым нужно произвести операцию
		 * @return array
		 */
		private function getOfferIdList() {
			return Service::Request()->Post()->get('offer_id_list');
		}

		/**
		 * Возвращает идентификатор копируемого торгового предложения
		 * @return mixed
		 */
		private function getOfferIdForCopying() {
			return Service::Request()->Post()->get('offer_id');
		}

		/**
		 * Возращает статус активности
		 * @return mixed
		 */
		private function getActivityStatus() {
			return Service::Request()->Post()->get('is_active');
		}
	}