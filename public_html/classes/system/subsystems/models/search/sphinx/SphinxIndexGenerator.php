<?php
 class SphinxIndexGenerator {public $createViewTemplate = '
	CREATE OR REPLACE VIEW `{indexName}` AS
	SELECT `h`.id, `h`.type_id, `h`.domain_id, `h`.rel, `h`.obj_id, `o`.name,
	{fields}
	FROM `cms3_hierarchy` as `h`
		LEFT JOIN `cms3_objects` as `o` ON `o`.id = `h`.obj_id
	{sources}
	WHERE
		`h`.is_active = 1 AND
		`h`.is_deleted = 0 AND
		{searchCond}
		`o`.type_id IN ({typesCond})

	';public $searchCondTemplate = '
		({fieldAlias} IS NULL OR {fieldAlias} = 0) AND';public $selectFieldTemplate = '
		COALESCE({columns}) as `{alias}`';public $joinFieldSourceTemplate = '
		LEFT JOIN `{contentTable}` as `{fieldSourceUid}` ON `{fieldSourceUid}`.obj_id = `h`.obj_id AND `{fieldSourceUid}`.field_id = {fieldId}';public $createSphinxConfig = "
	source content
	{
		type			= mysql

		sql_host		= {mySqlHost}
		sql_user		= {mySqlUser}
		sql_pass		= {mySqlPass}
		sql_db			= {mySqlDB}
		sql_port		= {mySqlPort}

		sql_query_pre	= SET NAMES utf8

		sql_query		= \
			SELECT id, obj_id, domain_id, name, {sqlQuery} \
			FROM sphinx_content_index

			sql_attr_uint	= obj_id
			sql_attr_uint	= domain_id

		sql_query_info		= SELECT * FROM sphinx_content_index WHERE id=\$id
	}

	index content
	{
		source         = content

		path           = {pathToIndex}sphinx_content_index

		morphology     = stem_enru

		min_word_len   = 2

		charset_type   = utf-8
		charset_table  = 0..9, A..Z->a..z, _, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F, U+401->U+451, U+451
		blend_chars    = &, ., +, U+23
		min_infix_len  = 2
		enable_star    = 1
		index_exact_words = 1
		html_strip     = 1
	}

	indexer
	{
		mem_limit           = 32M
	}

	searchd
	{
		listen			= {listen}
		log				= {pathToLog}searchd.log
		query_log		= {pathToLog}query.log
		read_timeout	= 5
		max_children	= 30
		pid_file		= {pathToLog}searchd.pid
		max_matches		= 1000
		seamless_rotate	= 1
		preopen_indexes	= 1
		unlink_old		= 1
		workers			= threads # for RT to work
		binlog_path		= {binlog}
	}
	";protected $viewName;protected $types = [];protected $fieldAlias = [];protected $fieldInfo = [];protected $fieldAliasColumns = [];protected $fieldNames = [];public function __construct($v7d012f3d21cc1633c9f2a93e39eba073) {$this->viewName = $v7d012f3d21cc1633c9f2a93e39eba073;}public function addPages(iUmiObjectType $v726e8e4809d4c1b28a6549d86436a124, array $v01ae5af0b0bdd5f727b2e7315c747739, $v583c905ae08aa8f4230b7a9a6a90c1db = 'cms3_object_content') {$v05a24f4157a610fd1321f3e890c3957c = $this->getAllTypeFields($v726e8e4809d4c1b28a6549d86436a124);if (empty($v01ae5af0b0bdd5f727b2e7315c747739) && empty($v0a1946e72e4c48cee729e5d67295ae49)) {throw new InvalidArgumentException('Cannot add pages to index. Fields list empty.');}$this->types[$v726e8e4809d4c1b28a6549d86436a124->getId()] = $v726e8e4809d4c1b28a6549d86436a124;foreach ($v01ae5af0b0bdd5f727b2e7315c747739 as $v77be71a4a1d487f740f26b2d29b7991e) {$v77be71a4a1d487f740f26b2d29b7991e = (array) $v77be71a4a1d487f740f26b2d29b7991e;$v972bf3f05d14ffbdb817bef60638ff00 = $v77be71a4a1d487f740f26b2d29b7991e[0];$this->fieldAlias[$v972bf3f05d14ffbdb817bef60638ff00] = isset($v77be71a4a1d487f740f26b2d29b7991e[1]) ? $v77be71a4a1d487f740f26b2d29b7991e[1] : $v972bf3f05d14ffbdb817bef60638ff00;$this->fieldNames[] = $this->fieldAlias[$v972bf3f05d14ffbdb817bef60638ff00];if (!isset($v05a24f4157a610fd1321f3e890c3957c[$v972bf3f05d14ffbdb817bef60638ff00])) {throw new InvalidArgumentException(sprintf(      'Cannot add pages to index. Field "%s" does not exist.',      $v77be71a4a1d487f740f26b2d29b7991e     ));}if (!isset($this->fieldInfo[$v972bf3f05d14ffbdb817bef60638ff00])) {$this->fieldInfo[$v972bf3f05d14ffbdb817bef60638ff00] = [];}$v06e3d36fa30cea095545139854ad1fb9 = $v05a24f4157a610fd1321f3e890c3957c[$v972bf3f05d14ffbdb817bef60638ff00];$v34e3ad0a034cca090eab482cd2563665 = $v583c905ae08aa8f4230b7a9a6a90c1db . '#' . $v06e3d36fa30cea095545139854ad1fb9->getId();if (!isset($this->fieldInfo[$v972bf3f05d14ffbdb817bef60638ff00][$v34e3ad0a034cca090eab482cd2563665])) {$this->fieldInfo[$v972bf3f05d14ffbdb817bef60638ff00][$v34e3ad0a034cca090eab482cd2563665] = [$v06e3d36fa30cea095545139854ad1fb9, $v583c905ae08aa8f4230b7a9a6a90c1db];}}$this->fieldNames = array_unique($this->fieldNames);}public function addPagesList($v9440e4210933b3c724d9a1324c52707d, $vd14a8022b085f9ef19d479cbdd581127, $v5308cca1be08c0bdbdfd06290ce5bed2) {foreach ($v9440e4210933b3c724d9a1324c52707d as $vc63352b07f2cba4fb7574c869ac041cb) {$v070b66f82a0cbca506de4ff9f468658c = $vd14a8022b085f9ef19d479cbdd581127->getType($vc63352b07f2cba4fb7574c869ac041cb)->getAllFields();$vd05b6ed7d2345020440df396d6da7f73 = [];foreach ($v070b66f82a0cbca506de4ff9f468658c as $v06e3d36fa30cea095545139854ad1fb9) {$vd05b6ed7d2345020440df396d6da7f73[] = $v06e3d36fa30cea095545139854ad1fb9->getName();}$vd05b6ed7d2345020440df396d6da7f73 = array_uintersect($vd05b6ed7d2345020440df396d6da7f73, $v5308cca1be08c0bdbdfd06290ce5bed2, 'strcasecmp');if (umiCount($vd05b6ed7d2345020440df396d6da7f73) > 0) {$this->addPages(      $vd14a8022b085f9ef19d479cbdd581127->getType($vc63352b07f2cba4fb7574c869ac041cb),      $vd05b6ed7d2345020440df396d6da7f73     );}}}public function generateViewQuery() {if (empty($this->types)) {throw new RuntimeException('Cannot generate query for view. Index is empty.');}$v5ee7abd75aa3cfc023dfa9d408d68a85 = [];$vc8e9e1176e7b7360a3d0e6d76376cba3 = [];$v4073050e4d3c7e6cc1cea6819e3a76b4 = $this->getSearchCond();$this->mergeFieldTablesByType();foreach ($this->fieldInfo as $v972bf3f05d14ffbdb817bef60638ff00 => $v77be71a4a1d487f740f26b2d29b7991e) {$v5ee7abd75aa3cfc023dfa9d408d68a85[] = $this->getSelectFieldPartSql($v972bf3f05d14ffbdb817bef60638ff00);$vc8e9e1176e7b7360a3d0e6d76376cba3[] = $this->getJoinSourcePartSql($v77be71a4a1d487f740f26b2d29b7991e);}$v5ee7abd75aa3cfc023dfa9d408d68a85 = array_filter($v5ee7abd75aa3cfc023dfa9d408d68a85);return strtr(    $this->createViewTemplate,    [     '{indexName}' => $this->viewName,     '{fields}' => implode(',', $v5ee7abd75aa3cfc023dfa9d408d68a85),     '{sources}' => implode('', $vc8e9e1176e7b7360a3d0e6d76376cba3),     '{typesCond}' => implode(', ', array_keys($this->types)),     '{searchCond}' => $v4073050e4d3c7e6cc1cea6819e3a76b4    ]   );}public function generateSphinxConfig(array $v93da65a9fd0004d9477aeac024e08e15) {return strtr(    $this->createSphinxConfig,    array_merge(     $v93da65a9fd0004d9477aeac024e08e15,     [      '{sqlQuery}' => implode(', ', $this->fieldNames),     ]    )   );}protected function mergeFieldTablesByType() {foreach ($this->fieldInfo as $v972bf3f05d14ffbdb817bef60638ff00 => $v77be71a4a1d487f740f26b2d29b7991e) {$v54ca84a794888fe8d92834787dfa935a = [];foreach ($v77be71a4a1d487f740f26b2d29b7991e as $v34e3ad0a034cca090eab482cd2563665 => $vcaf9b6b99962bf5c2264824231d7a40c) {list($v06e3d36fa30cea095545139854ad1fb9) = $vcaf9b6b99962bf5c2264824231d7a40c;$v312024fec69c40bdcf715638ed7c05ac = umiFieldType::getDataTypeDB($v06e3d36fa30cea095545139854ad1fb9->getDataType());$v54ca84a794888fe8d92834787dfa935a[] = "`{$v34e3ad0a034cca090eab482cd2563665}`.`{$v312024fec69c40bdcf715638ed7c05ac}`";}$this->fieldAliasColumns[$this->fieldAlias[$v972bf3f05d14ffbdb817bef60638ff00]][] = implode(', ', $v54ca84a794888fe8d92834787dfa935a);}}protected function getSelectFieldPartSql($v972bf3f05d14ffbdb817bef60638ff00) {if (!array_key_exists($v972bf3f05d14ffbdb817bef60638ff00, $this->fieldAliasColumns)) {return '';}return strtr(    $this->selectFieldTemplate,    [     '{fieldName}' => $v972bf3f05d14ffbdb817bef60638ff00,     '{alias}' => $this->fieldAlias[$v972bf3f05d14ffbdb817bef60638ff00],     '{columns}' => implode(', ', $this->fieldAliasColumns[$v972bf3f05d14ffbdb817bef60638ff00])    ]   );}protected function getJoinSourcePartSql(array $v77be71a4a1d487f740f26b2d29b7991e) {$va12b4ea43cb390c701ca21b24717887a = [];foreach ($v77be71a4a1d487f740f26b2d29b7991e as $v34e3ad0a034cca090eab482cd2563665 => $vcaf9b6b99962bf5c2264824231d7a40c) {list($v06e3d36fa30cea095545139854ad1fb9, $ve9bbe1ca62969217c0cb0ab63e3c6f63) = $vcaf9b6b99962bf5c2264824231d7a40c;$va12b4ea43cb390c701ca21b24717887a[] = strtr(     $this->joinFieldSourceTemplate,     [      '{contentTable}' => $ve9bbe1ca62969217c0cb0ab63e3c6f63,      '{fieldSourceUid}' => $v34e3ad0a034cca090eab482cd2563665,      '{fieldId}' => $v06e3d36fa30cea095545139854ad1fb9->getId()     ]    );}return implode("\n", $va12b4ea43cb390c701ca21b24717887a);}protected function getSearchCond() {if (!array_key_exists('is_unindexed', $this->fieldInfo)) {return '';}$va7f4463b549ee7dc75f1580b8e0ac3ae = key($this->fieldInfo['is_unindexed']);$v06e3d36fa30cea095545139854ad1fb9 = $this->fieldInfo['is_unindexed'][$va7f4463b549ee7dc75f1580b8e0ac3ae][0];$ve6579b3c219d5e2a2ac682bdc964c428 = umiFieldType::getDataTypeDB($v06e3d36fa30cea095545139854ad1fb9->getDataType());$v1afd32818d1c9525f82aff4c09efd254 = "`{$va7f4463b549ee7dc75f1580b8e0ac3ae}`.{$ve6579b3c219d5e2a2ac682bdc964c428}";return strtr(    $this->searchCondTemplate,    [     '{fieldAlias}' => $v1afd32818d1c9525f82aff4c09efd254    ]   );}protected function getAllTypeFields(iUmiObjectType $v726e8e4809d4c1b28a6549d86436a124) {$result = [];foreach ($v726e8e4809d4c1b28a6549d86436a124->getFieldsGroupsList(true) as $vdb0f6f37ebeb6ea09489124345af2a45) {foreach ($vdb0f6f37ebeb6ea09489124345af2a45->getFields() as $v06e3d36fa30cea095545139854ad1fb9) {$result[$v06e3d36fa30cea095545139854ad1fb9->getName()] = $v06e3d36fa30cea095545139854ad1fb9;}}return $result;}}