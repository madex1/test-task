<?php
 namespace UmiCms\System\Data\Field\Restriction;class Collection implements iCollection {private $connection;public function __construct(\IConnection $v4717d53ebfdfea8477f780ec66151dcb) {$this->connection = $v4717d53ebfdfea8477f780ec66151dcb;}public function delete($vb80bb7740288fda1f201890375a60c8f) {$vb80bb7740288fda1f201890375a60c8f = (int) $vb80bb7740288fda1f201890375a60c8f;$v26549ba3ca6f190ace0b2fa7c9b6b049 = <<<SQL
DELETE FROM `cms3_object_fields_restrictions` WHERE `id` = $vb80bb7740288fda1f201890375a60c8f
SQL;