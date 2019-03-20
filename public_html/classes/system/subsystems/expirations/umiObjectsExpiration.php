<?php
 class umiObjectsExpiration extends singleton implements iSingleton, iUmiObjectsExpiration {const DEFAULT_EXPIRED_OBJECTS_LIMIT = 50;protected $defaultExpires = 86400;protected function __construct() {}public function getLimit() {$vaa9f73eea60a006820d0f8768bc8a3fc = mainConfiguration::getInstance()->get('kernel', 'expired-objects-limit');return is_numeric($vaa9f73eea60a006820d0f8768bc8a3fc) ? (int) $vaa9f73eea60a006820d0f8768bc8a3fc : self::DEFAULT_EXPIRED_OBJECTS_LIMIT;}public static function getInstance($v4a8a08f09d37b73795649038408b5f33 = null) {return parent::getInstance(__CLASS__);}public function isExpirationExists($v16b2b26000987faccb260b9d39df1269) {$v4717d53ebfdfea8477f780ec66151dcb = ConnectionPool::getInstance()->getConnection();$vac5c74b64b4b8352ef2f181affb5ac2a = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id` = {$v16b2b26000987faccb260b9d39df1269}
			LIMIT 1
SQL;   $v26d59e24afcb9c11f03ffe8392b68734 = $v4717d53ebfdfea8477f780ec66151dcb->queryResult($vac5c74b64b4b8352ef2f181affb5ac2a);return $v26d59e24afcb9c11f03ffe8392b68734->length() > 0;}public function getExpiredObjectsByTypeId($v5f694956811487225d15e973ca38fbab, $vaa9f73eea60a006820d0f8768bc8a3fc = 50) {$v4717d53ebfdfea8477f780ec66151dcb = ConnectionPool::getInstance()->getConnection();$v07cc694b9b3fc636710fa08b6922c42b = time();$vac5c74b64b4b8352ef2f181affb5ac2a = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id`  IN (
					SELECT
						`id`
					FROM
						`cms3_objects`
					WHERE
						`type_id`='{$v5f694956811487225d15e973ca38fbab}'
					)
				AND (`entrytime` +  `expire`) <= {$v07cc694b9b3fc636710fa08b6922c42b}
			ORDER BY (`entrytime` +  `expire`)
			LIMIT {$vaa9f73eea60a006820d0f8768bc8a3fc}
SQL;   $result = [];$v26d59e24afcb9c11f03ffe8392b68734 = $v4717d53ebfdfea8477f780ec66151dcb->queryResult($vac5c74b64b4b8352ef2f181affb5ac2a);if ($v26d59e24afcb9c11f03ffe8392b68734->length() > 0) {$v26d59e24afcb9c11f03ffe8392b68734->setFetchType(IQueryResult::FETCH_ASSOC);foreach ($v26d59e24afcb9c11f03ffe8392b68734 as $vf1965a857bc285d26fe22023aa5ab50d) {$result[] = $vf1965a857bc285d26fe22023aa5ab50d['obj_id'];}}return $result;}public function update($v16b2b26000987faccb260b9d39df1269, $v09bcb72d61c0d6d1eff5336da6881557 = false) {if (!$v09bcb72d61c0d6d1eff5336da6881557) {$v09bcb72d61c0d6d1eff5336da6881557 = $this->getExpirationTime();}$v4717d53ebfdfea8477f780ec66151dcb = ConnectionPool::getInstance()->getConnection();$v16b2b26000987faccb260b9d39df1269 = (int) $v16b2b26000987faccb260b9d39df1269;$v09bcb72d61c0d6d1eff5336da6881557 = (int) $v09bcb72d61c0d6d1eff5336da6881557;$v07cc694b9b3fc636710fa08b6922c42b = time();$vac5c74b64b4b8352ef2f181affb5ac2a = <<<SQL
			UPDATE
				`cms3_objects_expiration`
			SET
				`entrytime`='{$v07cc694b9b3fc636710fa08b6922c42b}',
				`expire`='{$v09bcb72d61c0d6d1eff5336da6881557}'
			WHERE
				`obj_id` = '{$v16b2b26000987faccb260b9d39df1269}'
SQL;   $v4717d53ebfdfea8477f780ec66151dcb->query($vac5c74b64b4b8352ef2f181affb5ac2a);}public function add($v16b2b26000987faccb260b9d39df1269, $v09bcb72d61c0d6d1eff5336da6881557 = false) {if (!$v09bcb72d61c0d6d1eff5336da6881557) {$v09bcb72d61c0d6d1eff5336da6881557 = $this->getExpirationTime();}$v4717d53ebfdfea8477f780ec66151dcb = ConnectionPool::getInstance()->getConnection();$v16b2b26000987faccb260b9d39df1269 = (int) $v16b2b26000987faccb260b9d39df1269;$v09bcb72d61c0d6d1eff5336da6881557 = (int) $v09bcb72d61c0d6d1eff5336da6881557;$v07cc694b9b3fc636710fa08b6922c42b = time();$vac5c74b64b4b8352ef2f181affb5ac2a = <<<SQL
INSERT INTO `cms3_objects_expiration`
	(`obj_id`, `entrytime`, `expire`)
		VALUES ('{$v16b2b26000987faccb260b9d39df1269}', '{$v07cc694b9b3fc636710fa08b6922c42b}', '{$v09bcb72d61c0d6d1eff5336da6881557}')
SQL;   $v4717d53ebfdfea8477f780ec66151dcb->query($vac5c74b64b4b8352ef2f181affb5ac2a);}public function clear($v16b2b26000987faccb260b9d39df1269) {$v16b2b26000987faccb260b9d39df1269 = (int) $v16b2b26000987faccb260b9d39df1269;$v4717d53ebfdfea8477f780ec66151dcb = ConnectionPool::getInstance()->getConnection();$vac5c74b64b4b8352ef2f181affb5ac2a = <<<SQL
DELETE FROM `cms3_objects_expiration`
	WHERE `obj_id` = '{$v16b2b26000987faccb260b9d39df1269}'
SQL;   $v4717d53ebfdfea8477f780ec66151dcb->query($vac5c74b64b4b8352ef2f181affb5ac2a);}private function getExpirationTime() {$v3f48301f2668ec4eec370518ddcffe63 = mainConfiguration::getInstance();$v07cc694b9b3fc636710fa08b6922c42b = $v3f48301f2668ec4eec370518ddcffe63->get('kernel', 'objects-expiration-time');if (!is_numeric($v07cc694b9b3fc636710fa08b6922c42b)) {$v07cc694b9b3fc636710fa08b6922c42b = $this->defaultExpires;}return (int) $v07cc694b9b3fc636710fa08b6922c42b;}public function run() {}}