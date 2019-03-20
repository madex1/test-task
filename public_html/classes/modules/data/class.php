<?php

class data extends def_module {
	
	public function __construct() {
		parent::__construct();

		$this->loadCommonExtension();

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$commonTabs = $this->getCommonTabs();
			if ($commonTabs) {
				$commonTabs->add("types", array("type_add", "type_edit"));
				$commonTabs->add("guides", array("guide_add", "guide_items", "guide_item_edit", "guide_item_add"));
			}

			$configTabs = $this->getConfigTabs();
			if ($configTabs) {
				$configTabs->add("config");
			}

			$this->__loadLib("__admin.php");
			$this->__implement("__data");

			$this->__loadLib("__json.php");
			$this->__implement("__json_data");

			$this->__loadLib("__guides.php");
			$this->__implement("__guides_data");

			$this->__loadLib("__files.php");
			$this->__implement("__files_data");

			$this->loadAdminExtension();

			$this->__loadLib("__custom_adm.php");
			$this->__implement("__data_custom_admin");
		} else {
			$this->__loadLib("__rss.php");
			$this->__implement("__rss_data");
		}

		$this->__loadLib("__client_reflection.php");
		$this->__implement("__client_reflection_data");

		$this->__loadLib("__search.php");
		$this->__implement("__search_data");

		$this->loadSiteExtension();

		$this->__loadLib("__custom.php");
		$this->__implement("__custom_data");
	}


	public function getProperty($element_id, $prop_id, $template = "default", $is_random = false) {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			if($prop = (is_numeric($prop_id)) ? $element->getObject()->getPropById($prop_id) : $element->getObject()->getPropByName($prop_id)) {
				return self::parseTemplate($this->renderProperty($prop, $template, $is_random), Array(), $element_id);
			} else {
				list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
				return $template_not_exists;
			}
		} else {
			list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
			return $template_not_exists;
		}
	}

	public function getPropertyPrice($element_id, $prop_id, $template = "default", $is_random = false) {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			if($prop = (is_numeric($prop_id)) ? $element->getObject()->getPropById($prop_id) : $element->getObject()->getPropByName($prop_id)) {
				return self::parseTemplate($this->renderProperty($prop, $template, $is_random), Array(), $element_id);
			} else {
				list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
				return $template_not_exists;
			}
		} else {
			list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
			return $template_not_exists;
		}
	}

	public function getPropertyGroup($element_id, $group_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if(strstr($group_id, " ") !== false) {
			$group_ids = explode(" ", $group_id);
			$res = "";
			foreach($group_ids as $group_id) {
				if(!($group_id = trim($group_id))) continue;
				$res .= $this->getPropertyGroup($element_id, $group_id, $template);
			}
			return $res;
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			if(!is_numeric($group_id)) $group_id = $element->getObject()->getPropGroupId($group_id);

			$type_id = $element->getObject()->getTypeId();
			if($group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)) {
				if($group->getIsActive() == false) return "";
				list($template_block, $template_line) = self::loadTemplates("data/".$template, "group", "group_line");

				$lines = array();
				$props = $element->getObject()->getPropGroupById($group_id);
				$sz = count($props);
				for($i = 0; $i < $sz; $i++) {
					$prop_id = $props[$i];

					if($prop = $element->getObject()->getPropById($prop_id)) {
						if($prop->getIsVisible() === false) {
							continue;
						}
					}

					$line_arr = Array();
					$line_arr['id'] = $element_id;
					$line_arr['prop_id'] = $prop_id;

					if($prop_val = $this->getProperty($element_id, $prop_id, $template)) {
						$line_arr['prop'] = $prop_val;
					} else {
						continue;
					}

					$lines[] = self::parseTemplate($template_line, $line_arr);

				}
				if(!count($lines)) return "";	//TODO: check

				$block_arr = Array();
				$block_arr['name'] = $group->getName();
				$block_arr['title'] = $group->getTitle();
				$block_arr['+lines'] = $lines;
				$block_arr['template'] = $template;

				return self::parseTemplate($template_block, $block_arr);
			} else {
				return "";
			}
		} else {
			return "";
		}

	}


	public function getAllGroups($element_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			list($template_block, $template_line) = self::loadTemplates("data/".$template, "groups_block", "groups_line");

			$block_arr = Array();

			$object_type_id = $element->getObject()->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$groups = $object_type->getFieldsGroupsList();

			$lines = array();
			foreach($groups as $group_id => $group) {
				if(!$group->getIsActive() || !$group->getIsVisible()) continue;

				$line_arr = Array();
					 $line_arr['id']         = $element_id;
				$line_arr['group_id']   = $group_id;
				$line_arr['group_name'] = $group->getName();

				$lines[] = self::parseTemplate($template_line, $line_arr);
			}


			$block_arr['+lines'] = $lines;
			$block_arr['id'] = $element_id;
			$block_arr['template'] = $template;
			return self::parseTemplate($template_block, $block_arr);
		} else {
			return "";
		}
	}


	/*	Of-object block. TODO: refactoring with element-block.		*/

	public function getPropertyOfObject($object_id, $prop_id, $template = "default", $is_random = false) {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if($object = umiObjectsCollection::getInstance()->getObject($object_id)) {
			if($prop = (is_numeric($prop_id)) ? $object->getPropById($prop_id) : $object->getPropByName($prop_id)) {
				return self::parseTemplate($this->renderProperty($prop, $template, $is_random), Array(), false, $object_id);
			} else {
				list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
				return $template_not_exists;
			}
		} else {
			list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
			return $template_not_exists;
		}
	}


	public function getPropertyGroupOfObject($object_id, $group_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(strstr($group_id, " ") !== false) {
			$group_ids = explode(" ", $group_id);
			$res = "";
			foreach($group_ids as $group_id) {
				if(!($group_id = trim($group_id))) continue;
				$res .= $this->getPropertyGroupOfObject($object_id, $group_id, $template);
			}
			return $res;
		}


		if($object = umiObjectsCollection::getInstance()->getObject($object_id)) {
			if(!is_numeric($group_id)) $group_id = $object->getPropGroupId($group_id);

			$type_id = $object->getTypeId();
			if($group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)) {
				if($group->getIsActive() == false) return "";

				try {
					list($template_block, $template_line) = self::loadTemplates("data/".$template, "group", "group_line");
				} catch(publicException $e) {
					return "";
				}

				$lines = array();
				$props = $object->getPropGroupById($group_id);
				$sz = count($props);
				for($i = 0; $i < $sz; $i++) {
					$prop_id = $props[$i];

					if($prop = $object->getPropById($prop_id)) {
						if($prop->getIsVisible() === false) {
							continue;
						}
					}

					$line_arr = Array();
					$line_arr['id'] = $object_id;
					$line_arr['prop_id'] = $prop_id;

					if($prop_val = $this->getPropertyOfObject($object_id, $prop_id, $template)) {
						$line_arr['prop'] = $prop_val;
					} else {
						continue;
					}

					$lines[] = self::parseTemplate($template_line, $line_arr);
				}



				$block_arr = Array();
				$block_arr['name'] = $group->getName();
				$block_arr['title'] = $group->getTitle();
				$block_arr['+lines'] = $lines;
				$block_arr['template'] = $template;
				return self::parseTemplate($template_block, $block_arr);
			} else {
				return "";
			}
		} else {
			return "";
		}

	}


	public function getAllGroupsOfObject($object_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if($object = umiObjectsCollection::getInstance()->getObject($object_id)) {
			list($template_block, $template_line) = self::loadTemplates("data/".$template, "groups_block", "groups_line");

			$block_arr = Array();

			$object_type_id = $object->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$groups = $object_type->getFieldsGroupsList();

			$lines = array();
			foreach($groups as $group_id => $group) {
				if(!$group->getIsActive() || !$group->getIsVisible()) continue;

				$line_arr = Array();
				$line_arr['group_id'] = $group_id;
				$line_arr['group_name'] = $group->getName();

				$lines[] = self::parseTemplate($template_line, $line_arr);
			}


			$block_arr['+lines'] = $lines;
			$block_arr['id'] = $object_id;
			$block_arr['template'] = $template;
			return self::parseTemplate($template_block, $block_arr);
		} else {
			return "";
		}
	}





	private function renderProperty(umiObjectProperty &$property, $template, $is_random = false) {
		$data_type = $property->getDataType();

		switch($data_type) {
			case "string": {
				return $this->renderString($property, $template);
			}

			case "text": {
				return $this->renderString($property, $template, false, "text");
			}

			case "wysiwyg": {
				return $this->renderString($property, $template, false, "wysiwyg");
			}

			case "int": {
				return $this->renderInt($property, $template);
			}

			case "price": {
				return $this->renderPrice($property, $template);
			}

			case "float": {
				return $this->renderFloat($property, $template);
			}

			case "boolean": {
				return $this->renderBoolean($property, $template);
			}

			case "img_file": {
				return $this->renderImageFile($property, $template);
			}

			case "multiple_image": {
				return $this->renderMultipleImageFiles($property, $template);
			}

			case "relation": {
				return $this->renderRelation($property, $template, false, $is_random);
			}

			case "symlink": {
				return $this->renderSymlink($property, $template, false, $is_random);
			}

			case "swf_file": {
				return $this->renderFile($property, $template, false, "swf_file");
			}

			case "file": {
				return $this->renderFile($property, $template);
			}

			case "date": {
				return $this->renderDate($property, $template);
			}

			case "tags": {
				return $this->renderTags($property,$template);
			}

			case "optioned": {
				return $this->renderOptioned($property, $template);
			}

			default: {
				return "I don't know, how to render this sort of property (\"{$data_type}\") :(";
			}
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа string
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 * @param string $templateBlock Блок в выбранном шаблоне
	 *
	 * @return mixed
	 */
	private function renderString(umiObjectProperty &$property, $template, $showNull = false, $templateBlock = "string") {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "{$templateBlock}", "{$templateBlock}_empty");

			if(!$tpl) {
				list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "string", "string_empty");
			}

			if((is_array($value) || !strlen($value)) && !$showNull) {
				return $tpl_empty;
			}

			return self::parseTemplate($tpl, array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'value' => $value,
				'template' => $template
			));
		} else {
			list($tpl_block, $tpl_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "string_mul_block", "string_mul_block_empty", "string_mul_item", "string_mul_quant");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$items = array();
			$sz = count($value);

			for($i = 0; $i < $sz; $i++) {
				$arrayItem = array(
					'value' => $value[$i]
				);
				$arrayItem['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

				$items[] = self::parseTemplate($tpl_item, $arrayItem);
			}

			return self::parseTemplate($tpl_block, array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'+items' => $items,
				'template' => $template
			));
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа integer
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 *
	 * @return mixed
	 */
	private function renderInt(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "int", "int_empty");

			if((is_null($value) || $value === false || $value === "") && !$showNull) {
				return $tpl_empty;
			}

			return self::parseTemplate($tpl, array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'value' => $value
			));
		} else {
			list($tpl_block, $tpl_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "int_mul_block", "int_mul_block_empty", "int_mul_item", "int_mul_quant");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$items = array();
			$sz = count($value);

			for($i = 0; $i < $sz; $i++) {
				$arrayItem = array(
					'value' => $value[$i]
				);
				$arrayItem['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

				$items[] = self::parseTemplate($tpl_item, $arrayItem);
			}

			return self::parseTemplate($tpl_block, array(
				'name' => $name,
				'title' => $title,
				'+items' => $items,
				'template' => $template
			));
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа price
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 *
	 * @return mixed
	 */
	private function renderPrice(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "price", "price_empty");
		if(empty($value) && !$showNull) {
			return $tpl_empty;
		}

		if($property->getIsMultiple() === false) {
			$arrayBlock = array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'currency_symbol' => "",
				'template' => $template
			);

			$session = \UmiCms\Service::Session();
			if ($currency = $session->get("eshop_currency")) {
				if ($exchangeRate = $currency['exchange']) {
					$value = $value/$exchangeRate;
					$arrayBlock['currency_symbol'] = $currency['symbol'];
				}
			}

			$arrayBlock['value'] = number_format($value, (($value-floor($value)) > 0.005)?2:0, '.', ' ');

			return self::parseTemplate($tpl, $arrayBlock);
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа float
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 *
	 * @return mixed
	 * @throws publicException Тип поля `multi-float` не поддерживается
	 */
	private function renderFloat(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "float", "float_empty");
		if(empty($value) && !$showNull) {
			return $tpl_empty;
		}

		if($property->getIsMultiple() === false) {
			return self::parseTemplate($tpl, array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'value' => $value,
				'template' => $template
			));
		} else {
			throw new publicException("Not supported");
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа boolean
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 *
	 * @return mixed
	 * @throws publicException Тип поля `multi-boolean` не поддерживается
	 */
	private function renderBoolean(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		$arrBlock = array(
			'name' => $name,
			'title' => $title,
			'template' => $template
		);
		list($tpl_yes, $tpl_no) = self::loadTemplates("data/".$template, "boolean_yes", "boolean_no");
		if(empty($value) && !$showNull) {
			return self::parseTemplate($tpl_no, $arrBlock);
		}

		if($property->getIsMultiple() === false) {
			$tpl = ($value) ? $tpl_yes : $tpl_no;
			return self::parseTemplate($tpl, $arrBlock);
		} else {
			throw new publicException("Not supported");
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа imageFile
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 *
	 * @return mixed
	 * @throws publicException Тип поля `multi-image` не поддерживается
	 */
	private function renderImageFile(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "img_file", "img_file_empty");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arr = array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'size' => $value->getSize(),
				'filename' => $value->getFileName(),
				'filepath' => $value->getFilePath(),
				'src' => $value->getFilePath(true),
				'ext' => $value->getExt(),
				'template' => $template
			);

			if(wa_strtolower($value->getExt()) == "swf") {
				list($tpl) = self::loadTemplates("data/".$template, "swf_file");
			}

			if($value instanceof iUmiImageFile) {
				$arr['width'] = $value->getWidth();
				$arr['height'] = $value->getHeight();
			}

			return self::parseTemplate($tpl, $arr);
		} else {
			throw new publicException("Not supported");
		}
	}

	/**
	 * Загружает и применяет шаблон для поля типа "Набор изображений"
	 * Блоки TPL-шаблона:
	 * multiple_images - основной блок полей типа "Набор изображений"
	 * multiple_images_empty - если в поле не содержатся данные
	 * multiple_images_item - блок для каждого отдельного изображения
	 *
	 * @param umiObjectProperty $property обрабатываемое свойство
	 * @param string $template имя шаблона
	 * @return mixed
	 */
	private function renderMultipleImageFiles(umiObjectProperty &$property, $template) {
		list($baseBlock, $emptyBlock, $imageBlock) = self::loadTemplates("data/".$template, 'multiple_images',
			'multiple_images_empty', 'multiple_images_item');

		$value = $property->getValue();

		if (empty($value)) {
			return $emptyBlock;
		}

		$imageInfo = [];
		$imagesList = [];

		/** @var umiImageFile $image */
		foreach ($value as $image) {
			$imageInfo['size'] = $image->getSize();
			$imageInfo['filename'] = $image->getFileName();
			$imageInfo['filepath'] = $image->getFilePath();
			$imageInfo['src'] = $image->getFilePath(true);
			$imageInfo['ext'] = $image->getExt();
			$imageInfo['alt'] = $image->getAlt();
			$imagesList[] = self::parseTemplate($imageBlock, $imageInfo);
		}

		$propertyInfo = [
			'field_id' => $property->getField()->getId(),
			'name' => $property->getName(),
			'title' => $property->getTitle(),
			'template' => $template,
			'items' => $imagesList
		];

		return self::parseTemplate($baseBlock, $propertyInfo);
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа relation
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 * @param bool $isRandom Случайное значение[для Multiple полей]
	 *
	 * @return mixed
	 */
	private function renderRelation(umiObjectProperty &$property, $template, $showNull = false, $isRandom = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();
		$umiObjectsCollection = umiObjectsCollection::getInstance();

		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "relation", "relation_empty");

			$arrayBlock = array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'object_id' => $value,
				'template' => $template
			);

			if(empty($value) && !$showNull) {
				return self::parseTemplate($tpl_empty, $arrayBlock, false, $value);
			}

			$valueObject = $umiObjectsCollection->getObject($value);
			if ($valueObject instanceof iUmiObject) {
				$arrayBlock['value'] = $valueObject->getName();
				$umiObjectsCollection->unloadObject($value);
			}
			return self::parseTemplate($tpl, $arrayBlock);
		} else {
			list($tpl_block, $tpl_block_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "relation_mul_block", "relation_mul_block_empty", "relation_mul_item", "relation_mul_quant");

			if(empty($value) && !$showNull) {
				return $tpl_block_empty;
			}

			if($isRandom) {
				$value = $value[rand(0, count($value) - 1)];
				$value = Array($value);
			}

			$items = array();
			$sz = count($value);

			for($i = 0; $i < $sz; $i++) {
				$valueObject = $umiObjectsCollection->getObject($value[$i]);

				if ($valueObject instanceof iUmiObject) {
					$valueName = $valueObject->getName();
					$umiObjectsCollection->unloadObject($value[$i]);
				}

				$arrayItem = array(
					'object_id' =>  $value[$i],
					'value' => $valueName
				);
				$arrayItem['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

				$items[] = self::parseTemplate($tpl_item, $arrayItem, false, $value[$i]);
			}

			$arrayBlock = array(
				'name' => $name,
				'title' => $title,
				'+items' => $items,
				'template' => $template
			);

			return self::parseTemplate($tpl_block, $arrayBlock);
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа symlink
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 * @param bool $isRandom Выбрать случайное значение
	 *
	 * @return mixed
	 */
	private function renderSymlink(umiObjectProperty &$property, $template, $showNull = false, $isRandom = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		list($tpl_block, $tpl_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "symlink_block", "symlink_block_empty", "symlink_item", "symlink_quant");

		if(empty($value) && !$showNull) {
			return $tpl_empty;
		}

		if((bool) $isRandom) {
			$value = $value[rand(0, count($value) - 1)];
			$value = Array($value);
		}

		$items = array();
		$sz = count($value);

		for($i = 0; $i < $sz; $i++) {
			$element = $value[$i];
			$elementId = $element->getId();

			$arrayItem = array(
				'id' => $elementId,
				'object_id' => $element->getObject()->getId(),
				'value' => $element->getName(),
				'link' => umiHierarchy::getInstance()->getPathById($elementId)
			);
			$arrayItem['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

			$items[] = self::parseTemplate($tpl_item, $arrayItem, $elementId);
		}

		return self::parseTemplate($tpl_block, array(
			'name' => $name,
			'title' => $title,
			'+items' => $items,
			'template' => $template
		));
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа file
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 * @param string $templateBlock Блок в выбранном шаблоне
	 *
	 * @return mixed
	 */
	private function renderFile(umiObjectProperty &$property, $template, $showNull = false, $templateBlock = "file") {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "{$templateBlock}", "{$templateBlock}_empty");

			if(!$tpl) {
				list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "file", "file_empty");
			}

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arrayBlock = array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'size' => $value->getSize(),
				'filename' => $value->getFileName(),
				'filepath' => $value->getFilePath(),
				'src' => $value->getFilePath(true),
				'ext' => $value->getExt(),
				'modifytime' => $value->getModifyTime(),
				'template' => $template
			);

			if ($value instanceof umiImageFile) {
				$arrayBlock['width'] = $value->getWidth();
				$arrayBlock['height'] = $value->getHeight();
			}

			return self::parseTemplate($tpl, $arrayBlock);
		} else {
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа date
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 *
	 * @return mixed
	 */
	private function renderDate(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "date", "date_empty");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			return self::parseTemplate($tpl, array(
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'timestamp' => $value->getFormattedDate("U"),
				'value' => $value->getFormattedDate(),
				'template' => $template
			));
		}
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа Tags
	 * @param string $template Шаблон
	 *
	 * @return mixed
	 */
	public function renderTags($property, $template) {
		$values = $property->getValue();
		list($tpl_block, $tpl_block_item, $tpl_block_empty) = self::loadTemplates("data/".$template, "tags_block", "tags_item", "tags_empty");

		$itemsArray = array();
		foreach($values as $key => $value) {
			$itemsArray[] = self::parseTemplate($tpl_block_item, array(
				'tag' => $value,
				'name' => $value
			));
		}

		if(count($itemsArray) < 1) {
			return $tpl_block_empty;
		}

		return self::parseTemplate($tpl_block, array(
			'+items' => $itemsArray,
			'template' => $template
		));
	}

	/**
	 * Загружает и применяет шаблон для свойства объекта
	 *
	 * @param umiObjectProperty $property Свойство, типа Optioned(составное)
	 * @param string $template Шаблон
	 * @param bool $showNull Показывать пустые значения
	 *
	 * @return mixed
	 */
	private function renderOptioned(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		list($tpl_block, $tpl_block_empty, $tpl_item) = self::loadTemplates("data/".$template, "optioned_block", "optioned_block_empty", "optioned_item");

		if(empty($value) && !$showNull) {
			return $tpl_block_empty;
		}

		$itemsArray = array();
		foreach($value as $info) {
			$objectId = getArrayKey($info, 'rel');
			$elementId = getArrayKey($info, 'symlink');

			$itemArray = array(
				'int'			=> getArrayKey($info, 'int'),
				'float'			=> getArrayKey($info, 'float'),
				'text'			=> getArrayKey($info, 'text'),
				'varchar'		=> getArrayKey($info, 'varchar'),
				'field_name'	=> $name
			);

			if($objectId) {
				if($object = selector::get('object')->id($objectId)) {
					$itemArray['object-id'] = $object->getId();
					$itemArray['object-name'] = $object->getName();
				}
			}

			if($elementId) {
				if($element = selector::get('element')->id($elementId)) {
					$itemArray['element-id'] = $element->getId();
					$itemArray['element-name'] = $element->getName();
					$itemArray['element-link'] = $element->link;
				}
			}

			$itemsArray[] = self::parseTemplate($tpl_item, $itemArray, false, $objectId);
		}

		return self::parseTemplate($tpl_block, array(
			'field_id'			=> $property->getField()->getId(),
			'field_name'		=> $name,
			'name'				=> $name,
			'title'				=> $title,
			'subnodes:items'	=> $itemsArray,
			'template'			=> $template
		));
	}

	public function doSelection($template = "default", $uselName) {
		$this->templatesMode('tpl');

		$scheme_old = getRequest('scheme');
		$params = func_get_args();
		$params = array_slice($params, 2, count($params) - 2);
		$stream = new uselStream;
		$result = $stream->call($uselName, $params);

		$oldResultMode = def_module::isXSLTResultMode(false);

		list($objects_block, $objects_line, $objects_empty,
		$elements_block, $elements_line, $elements_empty,
		$separator, $separator_last) = self::loadTemplates("data/usel/".$template,
			"objects_block", "objects_block_line", "objects_block_empty",
			"elements_block", "elements_block_line", "elements_block_empty",
			"separator", "separator_last"
		);

		switch($result['mode']) {
			case "objects":
				$tpl_block = $objects_block;
				$tpl_line = $objects_line;
				$tpl_empty = $objects_empty;
				break;

			case "pages":
				$tpl_block = $elements_block;
				$tpl_line = $elements_line;
				$tpl_empty = $elements_empty;
				break;

			default: {
				throw new publicException("Unsupported return mode \"{$result['mode']}\"");
			}
		}


		if($result['sel'] instanceof selector) {
			$sel = $result['sel'];
			$results = $sel->result;
			$total = $sel->length;
			$limit = $sel->limit;

			if($total == 0) {
				$tpl_block = $tpl_empty;
			}

			$objectsCollection = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			$block_arr = Array();
			$lines = Array();
			$objectId = false;
			$elementId = false;
			$sz = count($results);
			$c = 0;

			foreach($results as $item) {
				$line_arr = array();

				if($result['mode'] == "objects") {
					$object = $item;
					if($object instanceof iUmiObject) {
						$objectId = $object->id;
						$line_arr['attribute:id'] = $object->id;
						$line_arr['attribute:name'] = $object->getName();
						$line_arr['attribute:type-id'] = $object->getTypeId();
						$line_arr['xlink:href'] = "uobject://" . $objectId;
					} else {
						continue;
					}
				} else {
					$element = $item;
					if($element instanceof iUmiHierarchyElement) {
						$elementId = $element->id;
						$line_arr['attribute:id'] = $element->id;
						$line_arr['attribute:name'] = $element->getName();
						$line_arr['attribute:link'] = $hierarchy->getPathById($element->id);
						$line_arr['xlink:href'] = "upage://" . $element->id;
					} else {
						continue;
					}
				}
				$line_arr['void:separator'] = (($sz == ($c + 1)) && $separator_last) ? $separator_last : $separator;
				$lines[] = self::parseTemplate($tpl_line, $line_arr, $elementId, $objectId);
				++$c;
			}

			$block_arr['subnodes:items'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $limit;
			$result = self::parseTemplate($tpl_block, $block_arr);
			def_module::isXSLTResultMode($oldResultMode);
			return $result;
		} else {
			throw new publicException("Can't execute selection");
		}
	}

	public function getRestrictionsList() {
		$this->templatesMode('xslt');

		$block_arr = array();

		$restrictions = baseRestriction::getList();
		$items_arr = array();
		foreach($restrictions as $restriction) {
			if($restriction instanceof baseRestriction) {
				$items_arr[] = $restriction;
			}
		}
		$block_arr['items']['nodes:item'] = $items_arr;

		return $block_arr;
	}

	public function config() {
		if(class_exists("__data")) {
			return __data::config();
		}
	}

	public function getObjectEditLink($objectId, $type = false) {
		return $this->pre_lang . '/admin/data/guide_item_edit/' . $objectId . '/';
	}

	public function getGuideItems($template = "default", $guide_id = false, $per_page = 100, $curr_page = 0) {
		if(!$curr_page) $curr_page = (int) getRequest('p');
		if(!$guide_id) $guide_id = (int) getRequest('param0');

		if(!$template) $template = "default";
		list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("data/".$template, "guide_block", "guide_block_empty", "guide_block_line");

		$sel = new selector('objects');
		$sel->types('object-type')->id($guide_id);
		$sel->limit($per_page * $curr_page, $per_page);

		selectorHelper::detectFilters($sel);

		$block_arr = array();
		$lines = array();

		foreach ($sel->result as $element) {
			$line_arr = array();
			$line_arr['attribute:id'] = $element->getId();
			$line_arr['xlink:href'] = "uobject://" . $element->getId();
			$line_arr['node:text'] = $element->getName();
			$lines[] = self::parseTemplate($template_line, $line_arr);
		}

		$block_arr['attribute:guide_id']  = $guide_id;
		$block_arr['subnodes:items'] = $lines;
		$block_arr['total'] = $sel->total;

		return self::parseTemplate($template_block, $block_arr);
	}

	public function assembleErrorFields($errorFields) {
		$result     = array();
		$collection = umiFieldsCollection::getInstance();
		foreach($errorFields as $fieldId){
			$field = $collection->getField($fieldId);
			$result[] = $field->getTitle();
		}
		return !empty($result) ? implode(', ', $result) : '';
	}

	public function checkRequiredFields($typeId) {
		$type = umiObjectTypesCollection::getInstance()->getType($typeId);
		if (!$type instanceof umiObjectType) throw new coreException(getLabel('label-cannot-detect-type'));

		$allFields = $type->getAllFields();

		$inputData = getRequest('data');
		if((!$inputData || !@is_array($inputData['new'])) && (!isset($_FILES['data']['name']['new'])) ) {
			$inputData = array();
		} else {
			$tmp = array();
			if(@is_array($inputData['new']))
				$tmp = array_merge($tmp, $inputData['new']);
			if(isset($_FILES['data']['name']['new']) && is_array($_FILES['data']['name']['new']))
				$tmp = array_merge($tmp, $_FILES['data']['name']['new']);
			$inputData = $tmp;
		}

		$errorFields = array();
		foreach($allFields as $field) {
			if($field->getIsRequired()) {
				$fieldName = $field->getName();
				if(!isset($inputData[$fieldName]) || empty($inputData[$fieldName])) {
					$errorFields[] = $field->getId();
				}
			}
		}
		return !empty($errorFields) ? $errorFields : true;
	}
};

?>