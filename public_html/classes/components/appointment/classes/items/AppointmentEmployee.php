<?php

	/** Класс сотрудника записи на прием */
	class AppointmentEmployee implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iAppointmentEmployee,
		iUmiConstantMapInjector,
		iClassConfigManager,
		iUmiImageFileInjector {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;
		use \tUmiImageFileInjector;

		/** @var string $name имя сотрудника */
		private $name;

		/** @var string $photo путь до фотографии сотрудника */
		private $photo;

		/** @var string $description описание сотрудника */
		private $description;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'constructor' => [
				'callback' => [
					'before' => 'constructorCallbackBefore'
				]
			],
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId'
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName'
				],
				[
					'name' => 'PHOTO_FIELD_NAME',
					'required' => true,
					'setter' => 'setPhoto',
					'getter' => 'getPhoto'
				],
				[
					'name' => 'DESCRIPTION_FIELD_NAME',
					'required' => true,
					'setter' => 'setDescription',
					'getter' => 'getDescription'
				]
			]
		];

		/**
		 * Обработчик метода tCommonCollectionItem::__construct()#before.
		 * Преобразует значения для полей типа изображение.
		 * @param array $params параметры инициализации
		 * @return array
		 */
		public function constructorCallbackBefore(array $params) {
			$this->setImageFileHandler(
				new \umiImageFile(__FILE__)
			);

			$imagesClass = get_class($this->getImageFileHandler());
			$map = $this->getMap();

			foreach ($params as $fieldName => &$fieldValue) {
				if ($fieldName !== $map->get('PHOTO_FIELD_NAME')) {
					continue;
				}

				$fieldValue = ($fieldValue instanceof $imagesClass) ? $fieldValue : new $imagesClass($fieldValue);
			}

			return $params;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			if (!is_string($name)) {
				throw new Exception('Wrong value for name given');
			}

			$name = trim($name);

			if ($name === '') {
				throw new Exception('Empty value for name given');
			}

			if ($this->getName() != $name) {
				$this->setUpdatedStatus(true);
			}

			$this->name = $name;
			return true;
		}

		/** @inheritdoc */
		public function getPhoto() {
			return $this->photo;
		}

		/** @inheritdoc */
		public function setPhoto($photo) {
			$imagePath = ($photo instanceof \iUmiImageFile) ? $photo->getFilePath() : (string) $photo;
			/** @var \iUmiImageFile $image */
			$imageFileHandlerClass = $this->getImageFileHandler();
			$image = new $imageFileHandlerClass($imagePath);

			if ($image->getIsBroken() && !startsWith($imagePath, '.')) {
				$image = new $imageFileHandlerClass('.' . $imagePath);
			}

			$imagePath = $image->getIsBroken() ? null : $image->getFilePath(true);
			$imagePath = ltrim($imagePath, '.');

			if ($this->getPhoto() != $imagePath) {
				$this->setUpdatedStatus(true);
			}

			$this->photo = $imagePath;
			return true;
		}

		/** @inheritdoc */
		public function getDescription() {
			return $this->description;
		}

		/** @inheritdoc */
		public function setDescription($description) {
			if (!is_string($description)) {
				throw new Exception('Wrong value for description given');
			}

			$description = trim($description);

			if ($description === '') {
				throw new Exception('Empty value for description given');
			}

			if ($this->getDescription() != $description) {
				$this->setUpdatedStatus(true);
			}

			$this->description = $description;
			return true;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$nameField = $connection->escape($map->get('NAME_FIELD_NAME'));
			$photoField = $connection->escape($map->get('PHOTO_FIELD_NAME'));
			$descriptionField = $connection->escape($map->get('DESCRIPTION_FIELD_NAME'));

			$id = (int) $this->getId();
			$name = $connection->escape($this->getName());
			$photo = $connection->escape($this->getPhoto());
			$description = $connection->escape($this->getDescription());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$nameField` = '$name', `$photoField` = '$photo', `$descriptionField` = '$description'
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}
	}

