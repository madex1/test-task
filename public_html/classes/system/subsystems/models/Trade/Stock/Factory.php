<?php
 namespace UmiCms\System\Trade\Stock;use UmiCms\System\Trade\Stock;use \iUmiObject as iDataObject;class Factory implements iFactory {public function create(iDataObject $v7beebf4251f2ace3d8e03527fe1bf86e) {$this->validateDataObject($v7beebf4251f2ace3d8e03527fe1bf86e);return new Stock($v7beebf4251f2ace3d8e03527fe1bf86e->setSavingInDestructor(false));}private function validateDataObject(iDataObject $v7beebf4251f2ace3d8e03527fe1bf86e) {if ($v7beebf4251f2ace3d8e03527fe1bf86e->getTypeGUID() !== self::TYPE_GUID) {throw new \ErrorException('Incorrect type of stock data object');}return $this;}}