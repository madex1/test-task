<?php

	use UmiCms\Service;

	/**
	 * Класс для работы с поисковой базой по сайту
	 * @todo refactoring
	 */
	class searchModel extends singleton implements iSingleton, iSearchModel {
		
		/** @const int MIN_WORD_LENGTH минимальная допустимая длинна слова для поиска */
		const MIN_WORD_LENGTH = 2;

		/** @inheritdoc */
		protected function __construct() {}

		/**
		 * @inheritdoc
		 * @return iSearchModel
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function index_all($limit = false, $lastId = 0) {
			$total = 0;
			$lastId = (int) $lastId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT id, updatetime FROM cms3_hierarchy
WHERE is_deleted = '0' AND is_active = '1' AND id > '{$lastId}' ORDER BY id
SQL;

			if (is_numeric($limit)) {
				$sql .= ' LIMIT ' . (int) $limit;
			}

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				list($element_id, $updatetime) = $row;
				++$total;

				$lastId = $element_id;

				if (!$this->elementIsReindexed($element_id, $updatetime)) {
					$this->index_item($element_id, true);
				}
			}

			$sql = 'SELECT COUNT(`rel_id`) FROM `cms3_search`';
			$count = $connection->queryResult($sql);
			$count->setFetchType(IQueryResult::FETCH_ROW);
			$current = false;

			if ($result->length() > 0) {
				$fetchResult = $count->fetch();
				$current = array_shift($fetchResult);
			}

			return [
				'current' => $current,
				'lastId' => $lastId
			];
		}

		/** @inheritdoc */
		public function index_item($elementId, $isManual = false) {
			if (defined('DISABLE_SEARCH_REINDEX') && !$isManual) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction("Indexing element #{$elementId}");

			try {
				$index_data = $this->parseItem($elementId);
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();

			return $index_data;
		}

		/** @inheritdoc */
		public function processPage(iUmiHierarchyElement $element) {
			if ($this->isAllowed($element)) {
				$this->index_item($element->getId());
			} else {
				$this->unindex_items($element->getId());
			}

			return $this;
		}

		/** @inheritdoc */
		public function processPageList(array $elementList) {
			array_map([$this, 'processPage'], $elementList);
			return $this;
		}

		/** @inheritdoc */
		public function parseItem($element_id) {
			$element_id = (int) $element_id;
			$element = umiHierarchy::getInstance()->getElement($element_id, true, true);

			if (!$element instanceof iUmiHierarchyElement || !$this->isAllowed($element)) {
				return false;
			}

			$index_fields = [];
			$type_id = $element->getObjectTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			$field_groups = $type->getFieldsGroupsList();

			foreach ($field_groups as $field_group_id => $field_group) {
				foreach ($field_group->getFields() as $field_id => $field) {
					$data_type = $field->getFieldType()->getDataType();

					if (!$field->getIsInSearch() || $data_type == 'optioned') {
						continue;
					}

					$field_name = $field->getName();
					$val = $element->getValue($field_name);

					if ($data_type) {
						if (is_array($val)) {
							if ($data_type == 'relation') {
								foreach ($val as $i => $v) {
									$item = selector::get('object')->id($v);

									if ($item) {
										$val[$i] = $item->getName();
										unset($item);
									}
								}
							}
							$val = implode(' ', $val);
						} else {
							if (is_object($val)) {
								continue;
							}

							if ($data_type == 'relation') {
								$item = selector::get('object')->id($val);

								if ($item) {
									$val = $item->getName();
								}
							}
						}
					}

					if ($val === null || !$val) {
						continue;
					}

					// kill macroses
					$val = preg_replace('/%([A-z_]*)%/m', '', $val);
					$val = preg_replace("/%([A-zЂ-пРђ-СЏ \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*)%/m", '', $val);

					$index_fields[$field_name] = $val;
				}
			}

			$index_image = $this->buildIndexImage($index_fields);
			$this->updateSearchIndex($element_id, $index_image);
		}

		/** @inheritdoc */
		public function buildIndexImage($indexFields) {
			$img = [];

			$weights = [
				'h1' => 5,
				'title' => 5,
				'meta_keywords' => 3,
				'meta_descriptions' => 3,
				'tags' => 3
			];

			foreach ($indexFields as $fieldName => $str) {
				$arr = $this->splitString($str);

				if (isset($weights[$fieldName])) {
					$weight = (int) $weights[$fieldName];
				} else {
					$weight = 1;
				}

				foreach ($arr as $word) {
					if (array_key_exists($word, $img)) {
						$img[$word] += $weight;
					} else {
						$img[$word] = $weight;
					}
				}
			}
			return $img;
		}

		/** @inheritdoc */
		public static function splitString($str) {
			if (!is_string($str)) {
				return [];
			}

			$to_space = [
				'&nbsp;',
				'&quote;',
				'. ',
				', ',
				' .',
				' ,',
				'?',
				':',
				';',
				'%',
				')',
				'(',
				'/',
				'<',
				'>',
				'- ',
				' -',
				'«',
				'»'
			];

			$str = str_replace('>', '> ', $str);
			$str = str_replace('"', ' ', $str);
			$str = strip_tags($str);
			$str = str_replace($to_space, ' ', $str);
			$str = preg_replace("/([ \t\r\n]{1-100})/u", ' ', $str);
			$str = mb_strtolower($str);
			$tmp = explode(' ', $str);

			$res = [];

			foreach ($tmp as $v) {
				$res[] = trim($v);
			}

			return $res;
		}

		/** @inheritdoc */
		public function updateSearchIndex($element_id, $index_image) {
			$element = umiHierarchy::getInstance()->getElement($element_id, true);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$domain_id = $element->getDomainId();
			$lang_id = $element->getLangId();
			$type_id = $element->getTypeId();

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT `rel_id` FROM `cms3_search` WHERE `rel_id` = '{$element_id}' LIMIT 0,1";
			$queryResult = $connection->queryResult($sql);
			$queryResult->setFetchType(IQueryResult::FETCH_ROW);

			if ($queryResult->length() == 0) {
				$sql = <<<SQL
INSERT INTO cms3_search (rel_id, domain_id, lang_id, type_id) 
VALUES('{$element_id}', '{$domain_id}', '{$lang_id}', '{$type_id}')
SQL;
				$connection->query($sql);
			}

			$sql = "DELETE FROM cms3_search_index WHERE rel_id = '{$element_id}'";
			$connection->query($sql);

			$sql = 'INSERT INTO cms3_search_index (rel_id, weight, word_id, tf) VALUES ';
			$n = 0;

			$total_weight = array_sum($index_image);
			foreach ($index_image as $word => $weight) {
				$word_id = $this->getSearchWordId($word);
				if (!$word_id) {
					continue;
				}
				$TF = $weight / $total_weight;
				$sql .= "('{$element_id}', '{$weight}', '{$word_id}', '{$TF}'), ";
				++$n;
			}

			if ($n) {
				$sql = mb_substr($sql, 0, mb_strlen($sql) - 2);
				$connection->query($sql);
			}

			$time = time();

			$sql = "UPDATE cms3_search SET indextime = '{$time}' WHERE rel_id = '{$element_id}'";
			$connection->query($sql);

			umiHierarchy::getInstance()->unloadElement($element_id);

			return true;
		}

		/** @deprecated */
		public static function getWordId($word) {
			return self::getInstance()->getSearchWordId($word);
		}

		/** @inheritdoc */
		public function getSearchWordId($word) {
			$word = trim($word, "\r\n\t? ;.,!@#$%^&*()_+-=\\/:<>{}[]'\"`~|");
			$word = mb_strtolower($word);

			if (mb_strlen($word) < $this->getMinWordLength()) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$word = $connection->escape($word);
			$sql = "SELECT id FROM cms3_search_index_words WHERE word = '{$word}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				return array_shift($fetchResult);
			}

			$sql = "INSERT INTO cms3_search_index_words (word) VALUES('{$word}')";
			$connection->query($sql);

			return (int) $connection->insertId();
		}

		/** @inheritdoc */
		public function getIndexPages() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT SQL_SMALL_RESULT COUNT(*) FROM cms3_search';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$count = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$count = (int) array_shift($fetchResult);
			}

			return $count;
		}

		/** @inheritdoc */
		public function getAllIndexablePages() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$query = new selector('pages');
			$disableIndexingFieldId = (int) $query->searchField('is_unindexed', true);

			$sql = <<<SQL
SELECT COUNT(DISTINCT h.obj_id)
FROM cms3_hierarchy h, cms3_objects o
LEFT JOIN cms3_object_content c ON c.obj_id=o.id AND c.field_id = $disableIndexingFieldId
WHERE 
c.int_val IS NULL AND 
h.is_deleted = '' AND 
h.is_active = '1' AND 
h.obj_id = o.id
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$count = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$count = (int) array_shift($fetchResult);
			}

			return $count;
		}

		/** @inheritdoc */
		public function getIndexWords() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT SQL_SMALL_RESULT SUM(weight) FROM cms3_search_index';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$count = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$count = (int) array_shift($fetchResult);
			}

			return $count;
		}

		/** @inheritdoc */
		public function getIndexWordsUniq() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT SQL_SMALL_RESULT COUNT(*) FROM cms3_search_index_words';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$count = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$count = (int) array_shift($fetchResult);
			}

			return $count;
		}

		/** @inheritdoc */
		public function getIndexLast() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT SQL_SMALL_RESULT indextime FROM cms3_search ORDER BY indextime DESC LIMIT 1';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$count = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$count = (int) array_shift($fetchResult);
			}

			return $count;
		}

		/** @inheritdoc */
		public function truncate_index() {
			$connection = ConnectionPool::getInstance()->getConnection();

			$sql = 'TRUNCATE TABLE cms3_search_index_words';
			$connection->query($sql);

			$sql = 'TRUNCATE TABLE cms3_search_index';
			$connection->query($sql);

			$sql = 'TRUNCATE TABLE cms3_search';
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function runSearch($searchString, $searchTypes = null, $hierarchy_rels = null, $orMode = false) {
			$words = $this->splitString($searchString);
			return $this->buildQueries($words, $searchTypes, $hierarchy_rels, $orMode);
		}

		/** @inheritdoc */
		public function buildQueries($words, $search_types = null, $hierarchy_rels = null, $orMode = false) {
			$lang_id = Service::LanguageDetector()->detectId();
			$domain_id = Service::DomainDetector()->detectId();
			$connection = ConnectionPool::getInstance()->getConnection();

			$umiConfig = mainConfiguration::getInstance();
			$morph_disabled = $umiConfig->get('system', 'search-morph-disabled');
			$searchInAnyPart = (bool) $umiConfig->get('kernel', 'search-in-any-part-of-string');
			$likeConditionPrefix = $searchInAnyPart ? '%' : '';

			$words_conds = [];
			$wordsChoose = [];
			$minWordLength = $this->getMinWordLength();

			foreach ($words as $i => $word) {
				if (mb_strlen($word) < $minWordLength) {
					unset($words[$i]);
					continue;
				}

				$word = $connection->escape($word);
				$word = str_replace(['%', '_'], ["\\%", "\\_"], $word);

				$word_subcond = "siw.word LIKE '{$likeConditionPrefix}{$word}%' ";

				if (!$morph_disabled) {
					$word_base = language_morph::get_word_base($word);

					if ((mb_strlen($word_base) >= $minWordLength) && ($word_base != $word)) {
						$word_base = $connection->escape($word_base);
						$word_subcond .= " OR siw.word LIKE '{$likeConditionPrefix}{$word_base}%'";
					}
				}

				$words_conds[] = '(' . $word_subcond . ')';
				$wordsChoose[] = ' WHEN (' . $word_subcond . ") THEN '{$word}'";
			}

			$words_cond = implode(' OR ', $words_conds);
			$wordsChooseString = '(CASE' . implode($wordsChoose) . ' END) as search_word';

			if (!$words_cond) {
				return [];
			}

			$perms_sql = '';
			$perms_tbl = '';

			$umiPermissions = permissionsCollection::getInstance();

			if (!$umiPermissions->isSv()) {
				$auth = Service::Auth();
				$user_id = $auth->getUserId();
				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				$groups = $user->getValue('groups');
				$groups[] = $user_id;

				$systemUsersPermissions = Service::SystemUsersPermissions();
				$groups[] = $systemUsersPermissions->getGuestUserId();
				$groups = array_extract_values($groups);
				$groups = implode(', ', $groups);
				$perms_sql = " AND c3p.level >= 1 AND c3p.owner_id IN({$groups})";
				$perms_tbl = 'INNER JOIN cms3_permissions as  `c3p` ON c3p.rel_id = s.rel_id';
			}

			$types_sql = '';

			if (is_array($search_types)) {
				if (umiCount($search_types)) {
					if ($search_types && $search_types[0]) {
						$types_sql = ' AND s.type_id IN (' . $connection->escape(implode(', ', $search_types)) . ')';
					}
				}
			}

			$hierarchy_rels_sql = '';

			if (is_array($hierarchy_rels) && umiCount($hierarchy_rels)) {
				$hierarchy_rels_sql = ' AND h.rel IN (' . $connection->escape(implode(', ', $hierarchy_rels)) . ')';
			}

			$connection->query(<<<'SQL'
CREATE TEMPORARY TABLE temp_search (rel_id int unsigned, tf float, word varchar(64), search_word varchar(64))
SQL
			);

			$sql = <<<EOF
				INSERT INTO temp_search SELECT SQL_SMALL_RESULT HIGH_PRIORITY
					s.rel_id,
					si.weight,
					siw.word,
					$wordsChooseString
				FROM cms3_search_index_words as `siw`
					INNER JOIN cms3_search_index as `si` ON si.word_id = siw.id
					INNER JOIN cms3_search as `s` ON s.rel_id = si.rel_id
					INNER JOIN cms3_hierarchy as  `h` ON h.id = s.rel_id
					{$perms_tbl}

				WHERE
					({$words_cond}) AND
					s.domain_id = '{$domain_id}' AND
					s.lang_id = '{$lang_id}' AND
					h.is_deleted = '0' AND
					h.is_active = '1'
					{$types_sql}
					{$hierarchy_rels_sql}
					{$perms_sql}
				GROUP BY s.rel_id, si.weight, search_word
EOF;
			$res = [];
			$connection->query($sql);

			if ($orMode) {
				$sql = <<<SQL
SELECT rel_id, SUM(tf) AS x
	FROM temp_search
		GROUP BY rel_id
			ORDER BY x DESC, rel_id DESC
SQL;
			} else {
				$wordsCount = umiCount($words);

				$sql = <<<SQL
SELECT rel_id, SUM(tf) AS x, COUNT(word) AS wc
	FROM temp_search
		GROUP BY rel_id
			HAVING wc >= '{$wordsCount}'
				ORDER BY x DESC, rel_id DESC
SQL;
			}

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$res[] = array_shift($row);
			}

			$connection->query('DROP TEMPORARY TABLE IF EXISTS temp_search');
			return $res;
		}

		/** @inheritdoc */
		public function prepareContext($element_id, $uniqueOnly = false) {
			if (!($element = umiHierarchy::getInstance()->getElement($element_id))) {
				return false;
			}

			if ($element->getValue('is_unindexed')) {
				return false;
			}

			$context = [];

			$type_id = $element->getObject()->getTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			$field_groups = $type->getFieldsGroupsList();
			foreach ($field_groups as $field_group_id => $field_group) {
				foreach ($field_group->getFields() as $field_id => $field) {
					if (!$field->getIsInSearch()) {
						continue;
					}

					$field_name = $field->getName();
					$val = $element->getValue($field_name);

					if ($val === null || !$val || is_object($val)) {
						continue;
					}

					$data_type = $field->getFieldType()->getDataType();

					if (in_array($data_type, ['relation', 'domain_id', 'link_to_object_type'])) {
						$val = (array) $val;
					}

					$context[] = is_array($val) ? $this->propertyArrayValueToString($data_type, $val) : $val;
				}
			}

			if ($uniqueOnly) {
				$context = array_unique($context);
			}

			$res = '';
			foreach ($context as $val) {
				if (is_array($val)) {
					continue;
				}
				$res .= $val . ' ';
			}

			$res = preg_replace("/%[A-z0-9_]+ [A-z0-9_]+\([^\)]+\)%/im", '', $res);

			$res = str_replace('%', '&#037', $res);
			return $res;
		}

		/**
		 * Приводит список значений поля к строковому представлению
		 * @param string $dataType тип поля
		 * @param array $valueList список значений
		 * @return string
		 */
		private function propertyArrayValueToString($dataType, array $valueList) {
			$valueToGlue = null;
			switch ($dataType) {
				case 'relation' : {
					$valueList = umiObjectsCollection::getInstance()
						->getObjectList($valueList);
					foreach ($valueList as $index => $object) {
						/** @var iUmiObject $object */
						$valueList[$index] = $object->getName();
					}

					$valueToGlue = $valueList;
					break;
				}
				case 'tags' : {
					$valueToGlue = $valueList;
					break;
				}
				default : {
					$valueToGlue = [];
				}
			}

			return implode(' ', $valueToGlue);
		}

		/** @inheritdoc */
		public function getContext($elementId, $searchString, $entryPattern = self::DEFAULT_ENTRY_PATTERN, $linePattern = self::DEFAULT_LINE_PATTERN) {
			$content = $this->prepareContext($elementId, true);

			$content = preg_replace("/%content redirect\((.*)\)%/im", "::CONTENT_REDIRECT::\\1::", $content);
			$content = preg_replace("/(%|&#037)[A-z0-9]+ [A-z0-9]+\((.*)\)(%|&#037)/im", '', $content);

			$words_arr = explode(' ', $searchString);

			$content = preg_replace("/([A-zА-я0-9])\.([A-zА-я0-9])/im", "\\1&#46;\\2", $content);

			$context = str_replace('>', '> ', $content);
			$context = str_replace('<br>', ' ', $context);
			$context = str_replace('&nbsp;', ' ', $context);
			$context = str_replace("\n", ' ', $context);
			$context = strip_tags($context);

			if (preg_match_all('/::CONTENT_REDIRECT::(.*)::/i', $context, $temp)) {
				$sz = umiCount($temp[1]);

				for ($i = 0; $i < $sz; $i++) {
					if (is_numeric($temp[1][$i])) {
						$turl = umiHierarchy::getInstance()->getPathById($temp[1][$i]);
					} else {
						$turl = strip_tags($temp[1][$i]);
					}
					$turl = trim($turl, "'");
					$context = str_replace(
						$temp[0][$i],
						"<p>%search_redirect_text% <a href=\"{$turl}\">{$turl}</a></p>",
						$context
					);
				}
			}

			$context .= "\n";

			$lines = [];
			foreach ($words_arr as $cword) {
				if (mb_strlen($cword) <= 1) {
					continue;
				}

				$tres = $context;
				$sword = language_morph::get_word_base($cword);
				$sword = preg_quote($sword, '/');
				$pattern_sentence = "/([^\.^\?^!^<^>.]*)$sword([^\.^\?^!^<^>.]*)[!\.\?\n]/imu";

				if (preg_match($pattern_sentence, $tres, $tres)) {
					$lines[] = $tres[0];
				}
			}

			$lines = array_unique($lines);

			$res_out = '';
			foreach ($lines as $line) {
				foreach ($words_arr as $cword) {
					$sword = language_morph::get_word_base($cword);
					$sword = preg_quote($sword, '/');
					$pattern_word = "/([^ ^.^!^\?.]*)($sword)([^ ^.^!^\?.]*)/imu";
					$line = preg_replace($pattern_word, sprintf($entryPattern, "\\1\\2\\3"), $line);
				}

				if ($line) {
					$res_out .= sprintf($linePattern, $line);
				}
			}

			if (!$res_out) {
				preg_match("/([^\.^!^\?.]*)([\.!\?]*)/im", $context, $res_out);
				$res_out = sprintf($linePattern, $res_out[0]);
			}
			return $res_out;
		}

		/** @inheritdoc */
		public function unindex_items($elementId) {
			$elementId = (int) $elementId;
			$connection = ConnectionPool::getInstance()->getConnection();

			$sql = "DELETE FROM cms3_search WHERE rel_id = '{$elementId}'";
			$connection->query($sql);

			$sql = "DELETE FROM cms3_search_index WHERE rel_id = '{$elementId}'";
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function index_items($elementId) {
			$hierarchy = umiHierarchy::getInstance();
			$children = $hierarchy->getChildrenTree($elementId, true, true, 99);
			$elements = [$elementId];
			$this->expandArray($children, $elements);

			foreach ($elements as $elementId) {
				$this->index_item($elementId);
			}
		}

		/** @inheritdoc */
		public function calculateIDF($wordId) {
			static $IDF = false;
			$wordId = (int) $wordId;
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($IDF === false) {
				$sql = 'SELECT COUNT(`rel_id`) FROM `cms3_search`';
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$d = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$d = (int) array_shift($fetchResult);
				}

				$sql = "SELECT COUNT(`rel_id`) FROM `cms3_search_index` WHERE `word_id` = {$wordId}";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$dd = 1;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$dd = (int) array_shift($fetchResult);
				}

				$IDF = log($d / $dd);
			}

			return $IDF;
		}

		public function suggestions($string, $limit = 10) {
			$string = trim($string);
			if (!$string) {
				return false;
			}
			$string = mb_strtolower($string);

			$rus = str_split('йцукенгшщзхъфывапролджэячсмитьбю');
			$eng = str_split('qwertyuiop[]asdfghjkl;\'zxcvbnm,.');

			$string_cp1251 = iconv('UTF-8', 'CP1251', $string);
			$mirrowed_rus = iconv('CP1251', 'UTF-8', str_replace($rus, $eng, $string_cp1251));
			$mirrowed_eng = iconv('CP1251', 'UTF-8', str_replace($eng, $rus, $string_cp1251));

			$mirrowed = ($mirrowed_rus != $string) ? $mirrowed_rus : $mirrowed_eng;

			$connection = ConnectionPool::getInstance()->getConnection('search');
			$string = $connection->escape($string);
			$mirrowed = $connection->escape($mirrowed);
			$limit = (int) $limit;

			$sql = <<<SQL
SELECT `siw`.`word` as `word`, COUNT(`si`.`word_id`) AS `cnt`
	FROM
		`cms3_search_index_words` `siw`,
		`cms3_search_index` `si`
	WHERE
		(
			`siw`.`word` LIKE '{$string}%' OR
			`siw`.`word` LIKE '{$mirrowed}%'
		) AND
		`si`.`word_id` = `siw`.`id`
	GROUP BY
		`siw`.`id`
	ORDER BY SUM(`si`.`tf`) DESC
	LIMIT {$limit}
SQL;
			return $connection->queryResult($sql);
		}

		/** @inheritdoc */
		public function getIndexWordList() {
			$sql = 'SELECT `word` FROM `cms3_search_index_words`';
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$wordList = [];

			foreach ($result as $row) {
				$wordList[] = $row['word'];
			}

			return $wordList;
		}

		/** @inheritdoc */
		public function getIndexList() {
			$sql = 'SELECT `rel_id`, `weight`, `word_id`, `tf` FROM `cms3_search_index`';
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$indexList = [];

			foreach ($result as $row) {
				$indexList[] = [
					'rel_id' => $row['rel_id'],
					'weight' => $row['weight'],
					'word_id' => $row['word_id'],
					'tf' => $row['tf'],
				];
			}

			return $indexList;
		}

		private function expandArray($arr, &$result) {
			if ($result === null) {
				$result = [];
			}

			foreach ($arr as $id => $childs) {
				$result[] = $id;
				$this->expandArray($childs, $result);
			}
		}

		/**
		 * Определяет доступна ли страница для индексации
		 * @param iUmiHierarchyElement $element
		 * @return bool
		 */
		private function isAllowed(iUmiHierarchyElement $element) {
			$isDeleted = $element->getIsDeleted();
			$isNotForIndex = $element->getValue('is_unindexed');
			$isNotActive = !$element->getIsActive();

			if ($isDeleted || $isNotForIndex || $isNotActive) {
				return false;
			}

			$allowVirtualCopy = (bool) mainConfiguration::getInstance()
				->get('modules', 'search.allow-virtual-copies');

			if ($allowVirtualCopy) {
				return true;
			}

			return $element->isOriginal();
		}

		/**
		 * Возвращает минимальную длину обрабатываемого (искомого/индексируемого) слова
		 * @return int
		 */
		private function getMinWordLength() {
			$minWordLength = (int) mainConfiguration::getInstance()
				->get('kernel', 'search-min-word-length');
			return ($minWordLength < self::MIN_WORD_LENGTH) ? self::MIN_WORD_LENGTH : $minWordLength;
		}

		/** @deprecated */
		public function elementIsReindexed($element_id, $updateTime) {
			$element_id = (int) $element_id;
			$updateTime = (int) $updateTime;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT `rel_id` FROM `cms3_search` WHERE `rel_id` = '{$element_id}' AND `indextime` > '{$updateTime}' LIMIT 0,1
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			return $result->length() > 0;
		}
	}
