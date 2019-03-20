<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	use UmiCms\Service;
	use UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory\Injector;

	/**
	 * Class SlidesCollection Класс коллекции слайдов
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	class SlidesCollection implements
		iSlidesCollection,
		\iUmiDataBaseInjector,
		\iUmiService,
		\iUmiConstantMapInjector,
		\iClassConfigManager,
		\iUmiImageFileInjector {

		use \tUmiDataBaseInjector;
		use \tUmiService;
		use \tCommonCollection;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;
		use \tUmiImageFileInjector;
		use Injector;

		/** @var string $collectionItemClass класс элемента коллекции, с которым она работает */
		private $collectionItemClass = 'UmiCms\Classes\Components\UmiSliders\Slide';

		/** @var array $classConfig конфигурация класса */
		private static $classConfig = [
			'service' => 'Slides',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'comparable' => true,
					'used-in-creation' => false,
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'SLIDER_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TITLE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'IMAGE_FIELD_NAME',
					'type' => 'IMAGE_FIELD_TYPE',
					'required' => false,
					'comparable' => true
				],
				[
					'name' => 'TEXT_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'LINK_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'OPEN_IN_NEW_TAB_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'IS_ACTIVE_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'ORDER_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				]
			],
			'create-prepare-instancing-callback' => 'convertFilePathToUmiImage',
			'create-after-callback' => 'setImageFileHandlerToEntity',
			'get-after-callback' => 'setImageFileHandlerToEntity'
		];

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/**
		 * Экспортирует сущности для табличного контрола.
		 * @param array $params параметры выборки
		 * @return array
		 */
		public function exportForTableControl(array $params) {
			$exportedEntities = $this->export($params);

			$constantMap = $this->getMap();
			$type = $constantMap->get('ENTITY_TYPE_KEY');
			$itemClass = $this->getCollectionItemClass();

			foreach ($exportedEntities as &$exportedEntity) {
				$exportedEntity[$type] = $itemClass;
			}

			return $exportedEntities;
		}

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/**
		 * Возвращает порядковый номер последнего слайда
		 * @return int
		 */
		public function getMaximumSlidesOrder() {
			$connection = $this->getConnection();
			$constantsMap = $this->getMap();
			$orderFieldName = $connection->escape($constantsMap->get('ORDER_FIELD_NAME'));
			$tableName = $connection->escape($this->getTableName());

			$query = <<<SQL
SELECT max(`$orderFieldName`) FROM `$tableName`
SQL;

			$queryResult = $connection->queryResult($query);
			$queryResult = $queryResult->fetch();

			return (int) array_shift($queryResult);
		}

		/** @inheritdoc */
		public function updateRelatedId(array $properties, $sourceId) {

			if (isset($properties['slider_id'])) {
				$entityRelations = $this->getSourceIdBinderFactory()
					->create($sourceId);
				/** @var SlidersCollection $sliderCollection */
				$sliderCollection = Service::get('SlidersCollection');
				$table = $sliderCollection->getMap()
					->get('EXCHANGE_RELATION_TABLE_NAME');

				$oldSliderId = $properties['slider_id'];
				$newSliderId = $entityRelations->getInternalId($oldSliderId, $table);

				if ($newSliderId !== null) {
					$properties['slider_id'] = $newSliderId;
				}
			}

			return $properties;
		}
	}
