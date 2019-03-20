<?php

	use UmiCms\Service;

	/**
	 * Вспомогательный класс для механизма формирования выборок "Selector"
	 * Основное назначение:
	 * 1) Принимать данные из $_REQUEST и добавлять фильтр или сортировку в Selector
	 * 2) Запускать быстрый импорт и экспорт в формате csv
	 * Класс, в основном, применяется в табличном контроле
	 */
	class selectorHelper {

		/**
		 * Применяет к selector все возможные фильтры
		 * @param selector $sel
		 * @throws selectorException
		 */
		public static function detectFilters(selector $sel) {

			if ($sel->__get('mode') == 'pages') {
				self::detectDomainFilter($sel);
				self::detectLanguageFilter($sel);
			}

			self::detectHierarchyTypeFilter($sel);
			self::detectHierarchyFilters($sel);
			self::detectWhereFilters($sel);
			self::detectOrderFilters($sel);
			self::checkSyncParams($sel);
		}

		/**
		 * Применяет к selector фильтр по домену.
		 * Фильтр по домену устанавливается через id домена в $_REQUEST['domain_id'] или $_REQUEST['domain_id'][].
		 * @param selector $sel
		 */
		public static function detectDomainFilter(selector $sel) {
			$domainIdList = (array) getRequest('domain_id');

			foreach ($domainIdList as $domainId) {
				$sel->where('domain')->equals($domainId);
			}
		}

		/**
		 * Применяет к selector фильтр по языку.
		 * Фильтр по языку устанавливается через id языка в $_REQUEST['lang_id'] или $_REQUEST['lang_id'][].
		 * @param selector $sel
		 */
		public static function detectLanguageFilter(selector $sel) {
			$languageIdList = (array) getRequest('lang_id');

			foreach ($languageIdList as $languageId) {
				$sel->where('lang')->equals($languageId);
			}
		}

		/**
		 * Применяет к selector фильтр по иерархическому типу.
		 * Фильтр по иерархическому типу устанавливается через umiHierarchyType::$name . '_' . umiHierarchyType::$ext в
		 * $_REQUEST['hierarchy_types'] или $_REQUEST['hierarchy_types'][].
		 * @param selector $sel
		 */
		public static function detectHierarchyTypeFilter(selector $sel) {
			if (isset($_REQUEST['hierarchy_types'])) {
				$hierarchyTypes = (array) $_REQUEST['hierarchy_types'];
				foreach ($hierarchyTypes as $hierarchyType) {
					$hierarchyType = explode('-', $hierarchyType);

					if (umiCount($hierarchyType) == 2) {
						$sel->types('hierarchy-type')->name($hierarchyType[0], $hierarchyType[1]);
					}
				}
			}
		}

		/**
		 * Запускает быстрый импорт и экспорт в формате csv.
		 * Экспорт запускается при передаче параметра $_REQUEST['export'].
		 * Импорт запускается при передаче параметра $_REQUEST['import'].
		 * В $_REQUEST['encoding'] можно передать название кодировки.
		 * @param selector $sel выборка
		 */
		public static function checkSyncParams(selector $sel) {

			$quickExchange = Service::QuickExchange();
			$quickExchange->setEncoding(getRequest('encoding'));

			if (getRequest('export')) {
				if (getRequest('download')) {
					$quickExchange->download();
				} else {
					$quickExchange->export($sel);
				}
			}

			if (getRequest('import')) {
				if (getRequest('upload')) {
					$quickExchange->upload();
				} else {
					$quickExchange->import($sel);
				}
			}
		}

		/**
		 * Применяет к selector фильтры по иерархии.
		 * Варианты использования:
		 * 1) $_REQUEST['rel'] = 12;
		 * Ищем страницы, дочерние странице с ид 12 на один уровень вложенности
		 * 2) $_REQUEST['rel'][] = 12;
		 *    $_REQUEST['rel'][] = 13
		 * Ищем страницы, дочерние страницам с ид 12 и 13 на один уровень вложенности
		 * 3) $_REQUEST['rel'][] = null или $_REQUEST['rel'][] = 0;
		 * Включает опцию 'exclude-nested'
		 * @param selector $sel
		 */
		public static function detectHierarchyFilters(selector $sel) {
			$relationIdList = (array) getRequest('rel');
			$relationIdListCount = count($relationIdList);

			if ($relationIdListCount == 0 && $sel->__get('mode') == 'pages') {
				$sel->option('exclude-nested', true);
			}

			$hierarchyLevel = getRequest('hierarchy-level') ?: 1;

			foreach ($relationIdList as $relationId) {
				try {
					if ($relationId || $relationId === '0') {
						$sel->where('hierarchy')->page($relationId)->level($hierarchyLevel);
					}
					if ($relationId === '0' && $relationIdListCount === 1) {
						$sel->option('exclude-nested', true);
					}
				} catch (selectorException $e) {
					//nothing
				}
			}
		}

		/**
		 * @todo refactor + implement umiSelection filters
		 * Применяет к selector фильтры по значению полей объекта
		 * Варианты использования:
		 * 1) $_REQUEST['search-all-text'] = 'test';
		 * фильтр по всем полям объекта в режиме 'like',
		 * если тип не содержит полей - будет произведен фильтр по umiObject::$name
		 * 2) $_REQUEST['fields_filter']['price'][0] = 10;
		 *    $_REQUEST['fields_filter']['price'][1] = 100;
		 * фильтр по полю в режиме 'between'
		 * 3) $_REQUEST['fields_filter']['h1']['eq'] = 'test';
		 * фильтр по полю в режиме 'equals'
		 * 4) $_REQUEST['fields_filter']['h1']['ne'] = 'test';
		 * фильтр по полю в режиме 'notequals'
		 * 5) $_REQUEST['fields_filter']['h1']['like'] = 'test';
		 * фильтр по полю в режиме 'like'
		 * 6) $_REQUEST['fields_filter']['counter']['gt'] = 10;
		 * фильтр по полю в режиме 'more'
		 * 7) $_REQUEST['fields_filter']['counter']['lt'] = 100;
		 * фильтр по полю в режиме 'less'
		 * 8) $_REQUEST['fields_filter']['header_pic']['eq'] = 1;
		 * фильтр по полю типа "Изображение", "Файл", "Видео" и "Флеш-ролик" в режиме 'isnotnull'
		 * 9) $_REQUEST['fields_filter']['header_pic']['eq'] = -1;
		 * фильтр по полю типа "Изображение", "Файл", "Видео" и "Флеш-ролик" в режиме 'isnull'
		 * 10) $_REQUEST['fields_filter']['h1'] = 'test'
		 * фильтр по полю в режиме 'equals'
		 * 11) $_REQUEST['fields_filter']['register_date']['eq'] = 01.08.2016;
		 * фильтр по полю типа "Дата" в режиме between с первой секунды указаного дня до последней
		 * @param selector $sel
		 * @throws selectorException
		 */
		public static function detectWhereFilters(selector $sel) {
			static $methods = [
				'eq' => 'equals',
				'ne' => 'notequals',
				'like' => 'like',
				'gt' => 'more',
				'lt' => 'less'
			];

			$searchAllText = (array) getRequest('search-all-text');
			$searchAllText = array_filter($searchAllText, function ($str) {
				return $str !== '';
			});

			if (count($searchAllText) > 1) {
				$sel->option('or-mode')->field('*');
			}

			$objectTypeIds = [];
			$umiTypesHelper = umiTypesHelper::getInstance();

			if (count($sel->__get('types')) === 1) {
				$objectTypeIds = $umiTypesHelper->getFieldsByObjectTypeIds($sel->__get('types')[0]->objectTypeIds);
			}

			if (count($sel->__get('types')) === 1 && $sel->__get('types')[0]->objectTypeIds !== null &&
				umiCount($objectTypeIds) === 0) {
				foreach ($searchAllText as $searchString) {
					$sel->where('name')->like('%' . $searchString . '%');
				}
			} else {
				foreach ($searchAllText as $searchString) {
					try {
						$sel->where('*')->like('%' . $searchString . '%');
					} catch (selectorException $e) {
						//nothing
					}
				}
			}

			$umiFieldsTypes = umiFieldTypesCollection::getInstance();
			$filters = (array) getRequest('fields_filter');
			foreach ($filters as $fieldName => $info) {
				if (is_array($info)) {
					if (isset($info[0]) && isset($info[1])) {
						try {
							$sel->where($fieldName)->between($info[0], $info[1]);
						} catch (selectorException $e) {
							//nothing
						}
					}
					foreach ($info as $i => $v) {
						if (isset($methods[$i])) {
							if (is_array($v) && $methods[$i] == 'like') {
								$sel->option('or-mode')->field($fieldName);
								foreach ($v as $item) {
									try {
										$item .= '%';
										$sel->where($fieldName)->{$methods[$i]}($item);
									} catch (selectorException $e) {
										//nothing
									}
								}
							} else {
								try {
									$fieldId = $sel->searchField($fieldName, true);
									$fieldsTypeId = $umiTypesHelper->getFieldTypeIdByFieldId($fieldId);
									$fieldsTypeName = null;

									if (is_numeric($fieldsTypeId)) {
										$fieldType = $umiFieldsTypes->getFieldType($fieldsTypeId);

										if ($fieldType instanceof iUmiFieldType) {
											$fieldsTypeName = $fieldType->getDataType();
										}
									}

									switch (true) {
										case $methods[$i] == 'like': {
											$v = '%' . $v . '%';
											break;
										}
										case $methods[$i] == 'equals' && ($v == '1' || $v == '-1' || $v == '0'): {
											if (!preg_match('/(file)/', $fieldsTypeName)) {
												break;
											}

											$method = ($v > 0) ? 'isnotnull' : 'isnull';
											$sel->where($fieldName)->$method(true);
											$v = '';
											break;
										}
										case $fieldsTypeName == 'date' && $methods[$i] == 'equals' : {
											if (!preg_match('/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/', $v)) {
												break;
											}

											$dateFrom = DateTime::createFromFormat('d.m.Y', $v);
											$dateFrom->setTime(0, 0);

											$dateTo = DateTime::createFromFormat('d.m.Y', $v);
											$dateTo->setTime(24, 0);
											$sel->where($fieldName)->between(
												$dateFrom->getTimestamp(),
												$dateTo->getTimestamp()
											);

											$v = '';
											break;
										}
									}
									if ($v !== '') {
										$sel->where($fieldName)->{$methods[$i]}($v);
									}
								} catch (selectorException $e) {
									//nothing
								}
							}
						}
					}
				} else {
					try {
						if ($info !== '') {
							$sel->where($fieldName)->equals($info);
						}
					} catch (selectorException $e) {
						//nothing
					}
				}
			}
		}

		/**
		 * Определяет запрошена ли фильтрация
		 * @return bool
		 */
		public static function isFilterRequested() {
			return !isEmptyArray((array) getRequest('fields_filter'));
		}

		/**
		 * Применяет к selector сортировку
		 * Варианты использования:
		 * 1) $_REQUEST['order_filter']['price'] = 'desc';
		 * сортировка по убыванию
		 * 2) $_REQUEST['order_filter']['price'] = 'asc';
		 * сортировка по возрастанию
		 * @param selector $sel
		 */
		public static function detectOrderFilters(selector $sel) {
			$orders = (array) getRequest('order_filter');

			foreach ($orders as $fieldName => $direction) {
				$func = (mb_strtolower($direction) == 'desc') ? 'desc' : 'asc';

				try {
					$sel->order($fieldName)->$func();
				} catch (selectorException $e) {
					//nothing
				}
			}
		}
	}
