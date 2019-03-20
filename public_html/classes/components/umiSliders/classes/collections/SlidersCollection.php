<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Class SlidersCollection Класс коллекции слайдеров
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	class SlidersCollection implements
		iSlidersCollection,
		\iUmiDataBaseInjector,
		\iUmiService,
		\iUmiConstantMapInjector,
		\iClassConfigManager,
		\iUmiDomainsInjector {

		use \tUmiDataBaseInjector;
		use \tUmiService;
		use \tCommonCollection;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;
		use \tUmiDomainsInjector;

		/** @var string $collectionItemClass класс элемента коллекции, с которым она работает */
		private $collectionItemClass = 'UmiCms\Classes\Components\UmiSliders\Slider';

		/** @var array $classConfig конфигурация класса */
		private static $classConfig = [
			'service' => 'Sliders',
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
					'name' => 'DOMAIN_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'LANGUAGE_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'SLIDING_SPEED_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'SLIDING_DELAY_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'SLIDING_LOOP_ENABLE_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'SLIDES_RANDOM_ORDER_ENABLE_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false,
				],
				[
					'name' => 'SLIDES_COUNT_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => false
				],
				[
					'name' => 'CUSTOM_ID_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => false
				]
			],
			'create-after-callback' => 'setUmiDomainsInjectorToEntity'
		];

		/** @var iSlidesCollection $slidesCollection коллекция слайдов */
		private $slidesCollection;

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

			$sliderConstantMap = $this->getMap();
			$count = $sliderConstantMap->get('CHILDREN_COUNT_KEY');
			$type = $sliderConstantMap->get('ENTITY_TYPE_KEY');
			$id = $sliderConstantMap->get('ID_FIELD_NAME');
			$itemClass = $this->getCollectionItemClass();

			$slidesCollection = $this->getSlidesCollection();
			$slidesConstantMap = $slidesCollection->getMap();
			$sliderIdKey = $slidesConstantMap->get('SLIDER_ID_FIELD_NAME');
			$calculate = $slidesConstantMap->get('CALCULATE_ONLY_KEY');

			foreach ($exportedEntities as &$exportedEntity) {
				$exportedEntity[$type] = $itemClass;
				$exportedEntity[$count] = $slidesCollection->count([
					$sliderIdKey => $exportedEntity[$id],
					$calculate => true,
				]);
			}

			return $exportedEntities;
		}

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/** @inheritdoc */
		public function getByName($name) {
			return $this->getBy($this->getMap()->get('NAME_FIELD_NAME'), $name);
		}

		/** @inheritdoc */
		public function getByCustomId($customId) {
			return $this->getBy($this->getMap()->get('CUSTOM_ID_FIELD_NAME'), $customId);
		}

		/** @inheritdoc */
		public function getByCustomIdDomainAndLanguage($customId, $domainId, $languageId) {
			$constants = $this->getMap();
			$query = [
				$constants->get('CUSTOM_ID_FIELD_NAME') => $customId,
				$constants->get('DOMAIN_ID_FIELD_NAME') => $domainId,
				$constants->get('LANGUAGE_ID_FIELD_NAME') => $languageId
			];
			$sliderList = $this->get($query);

			if (isEmptyArray($sliderList)) {
				return null;
			}

			return array_shift($sliderList);
		}

		/** @inheritdoc */
		public function getSliderIdListByDomainIdAndLanguageId($domainId = null, $languageId = null) {
			if ($domainId === null && $languageId === null) {
				throw new \wrongParamException('language id and domain id cannot be empty both');
			}

			$query = [];
			$constants = $this->getMap();

			if ($domainId !== null) {
				$query[$constants->get('DOMAIN_ID_FIELD_NAME')] = $domainId;
			}

			if ($languageId !== null) {
				$query[$constants->get('LANGUAGE_ID_FIELD_NAME')] = $languageId;
			}

			return array_map(function (iSlider $slider) {
				return $slider->getId();
			}, $this->get($query));
		}

		/** @inheritdoc */
		public function setSlidesCollection(iSlidesCollection $collection) {
			$this->slidesCollection = $collection;
			return $this;
		}

		/** @inheritdoc */
		public function updateRelatedId(array $properties, $sourceId) {
			$entityRelations = \umiImportRelations::getInstance();

			if (isset($properties['domain_id'])) {
				$oldDomainId = $properties['domain_id'];
				$newDomainId = $entityRelations->getNewDomainIdRelation($sourceId, $oldDomainId);

				if (is_numeric($newDomainId)) {
					$properties['domain_id'] = $newDomainId;
				}
			}

			if (isset($properties['language_id'])) {
				$oldLanguageId = $properties['language_id'];
				$newLanguageId = $entityRelations->getNewLangIdRelation($sourceId, $oldLanguageId);

				if (is_numeric($newLanguageId)) {
					$properties['language_id'] = $newLanguageId;
				}
			}

			return $properties;
		}

		/**
		 * Возвращает коллекция слайдов
		 * @return iSlidesCollection|\iUmiConstantMapInjector
		 */
		private function getSlidesCollection() {
			return $this->slidesCollection;
		}
	}
