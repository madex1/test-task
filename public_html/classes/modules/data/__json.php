<?php

class __json_data {
	public function json_move_field_after() {
		$field_id = (int) getRequest('param0');
		$before_field_id = (int) getRequest('param1');
		$is_last = (string) getRequest('param2');
		$type_id = (int) getRequest('param3');
		$connection = ConnectionPool::getInstance()->getConnection();

		if($is_last != "false") {
			$new_group_id = (int) $is_last;
		} else {
			$sql = <<<SQL
SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$before_field_id}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$new_group_id = ($result->length() > 0) ? array_shift($result->fetch()) : null;
		}

			$sql = <<<SQL
SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$group_id = ($result->length() > 0) ? array_shift($result->fetch()) : null;

		if ($is_last == "false") {
			$after_field_id = $before_field_id;
		} else {
			$sql = "SELECT field_id FROM cms3_fields_controller WHERE group_id = '{$group_id}' ORDER BY ord DESC LIMIT 1";

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$after_field_id = ($result->length() > 0) ? array_shift($result->fetch()) : 0;
		}

		$res = (string) (int) umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)->moveFieldAfter($field_id, $after_field_id, $new_group_id, ($is_last != "false") ? false : true);
		$res = "";
		$this->flush($res);
	}


	public function json_move_group_after() {
		$group_id = (int) getRequest('param0');
		$before_group_id = (string) getRequest('param1');
		$type_id = (int) getRequest('param2');
		$connection = ConnectionPool::getInstance()->getConnection();

		if($before_group_id != "false") {
			$sql = "SELECT ord FROM cms3_object_field_groups WHERE type_id = '{$type_id}' AND id = '".((int) $before_group_id)."'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$neword = ($result->length() > 0) ? array_shift($result->fetch()) : 0;

		} else {
			$sql = "SELECT MAX(ord) FROM cms3_object_field_groups WHERE type_id = '{$type_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$neword = ($result->length() > 0) ? array_shift($result->fetch()) + 5 : 5;
		}

		umiObjectTypesCollection::getInstance()->getType($type_id)->setFieldGroupOrd($group_id, $neword, ($before_group_id == "false") ? true : false);
		$this->flush();
	}


	public function json_delete_field() {
		$field_id = (int) getRequest('param0');
		$type_id = (int) getRequest('param1');
		$connection = ConnectionPool::getInstance()->getConnection();
		$sql = "SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'";
		$result = $connection->queryResult($sql);
		$result->setFetchType(IQueryResult::FETCH_ROW);
		$group_id = ($result->length() > 0) ? array_shift($result->fetch()) : null;

		umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)->detachField($field_id);
		$this->flush();
	}


	public function json_delete_group() {
		$group_id = (int) getRequest('param0');
		$type_id = (int) getRequest('param1');

		umiObjectTypesCollection::getInstance()->getType($type_id)->delFieldsGroup($group_id);

		$this->flush("");
	}


}

?>