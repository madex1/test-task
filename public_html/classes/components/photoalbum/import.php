<?php

	use UmiCms\Service;

	/**
	 * Класс импорта файлов и архивов изображений.
	 * Для каждого загруженного изображения создает сущность "Фотография".
	 */
	class ImportPhotoAlbum {

		/** @var photoalbum основной класс модуля */
		public $module;

		/** @var array расширения файлов, доступных для импорта */
		private $allowedTypes = [
			'jpg',
			'jpeg',
			'gif',
			'bmp',
			'png'
		];

		/**
		 * Импортировать zip-архив с изображениями
		 * На каждое изображение создается сущность "Фотография"
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function upload_arhive() {
			global $_FILES;

			$referer = isset($_REQUEST['referer']) ? getRequest('referer') : $_SERVER['HTTP_REFERER'];
			$parentId = getRequest('parent_id');
			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($parentId);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicAdminException("Can't find parent album");
			}

			$folder = $this->module->_checkFolder($element);
			$addWaterMark = (bool) getRequest('watermark');

			if (isset($_FILES['zip_arhive']) && is_uploaded_file($_FILES['zip_arhive']['tmp_name'])) {
				$originalName = $_FILES['zip_arhive']['name'];
				$extension = mb_substr($originalName, mb_strrpos($originalName, '.') + 1);

				if ($extension != 'zip') {
					throw new publicAdminException("It's not arhive!");
				}

				$unzippedPhotos = umiFile::upload_zip($_FILES['zip_arhive'], '', $folder, $addWaterMark);
			} else {
				$zipFilePath = (string) getRequest('zip_arhive_src');

				if ($zipFilePath === '') {
					throw new publicAdminException(getLabel('zip-file-upload-error'));
				}

				$unzippedPhotos = umiFile::upload_zip('', $zipFilePath, $folder, $addWaterMark);
			}

			if (!is_array($unzippedPhotos)) {
				throw new publicAdminException("Zip extracting error! {$unzippedPhotos}");
			}

			usort($unzippedPhotos, function ($previousPhoto, $nextPhoto) {
				return strnatcmp(mb_strtolower($previousPhoto['filename']), mb_strtolower($nextPhoto['filename']));
			});

			foreach ($unzippedPhotos as $photo) {
				$photoDestinationPath = $folder . basename($photo['filename']);
				$info = getPathInfo($photoDestinationPath);

				if (isset($info['extension']) && in_array(mb_strtolower($info['extension']), $this->allowedTypes)) {
					$this->addPhotoFromZip($photoDestinationPath);
				}
			}

			$this->module->redirect($referer);
		}

		/**
		 * Импортировать изображение и создать сущность "Фотография"
		 * @param string $filePath путь до изображения
		 * @return bool
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function addPhotoFromZip($filePath) {
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$parentId = (int) getRequest('parent_id');

			$fileName = basename($filePath);
			$title = mb_substr($fileName, 0, mb_strrpos($fileName, '.'));

			/** @var iUmiHierarchyElement $parentElement */
			$parentElement = $hierarchy->getElement($parentId);

			if (!$parentElement instanceof iUmiHierarchyElement) {
				return true;
			}

			$tplId = $parentElement->getTplId();
			$domainId = $parentElement->getDomainId();
			$langId = $parentElement->getLangId();
			$hierarchyTypeId = $hierarchyTypes->getTypeByName('photoalbum', 'photo')->getId();
			$basePhotoTypeId = $objectTypes->getTypeIdByHierarchyTypeName('photoalbum', 'photo');

			if (!is_numeric($basePhotoTypeId) || $basePhotoTypeId === 0) {
				throw new publicAdminException(getLabel('error-photo-type-not-found'));
			}

			$userPhotoTypeId = Service::Registry()->get('//modules/photoalbum/zip_object_type');
			$userObjectType = $objectTypes->getType($userPhotoTypeId);
			$photoTypeId = $userObjectType instanceof iUmiObjectType ? $userPhotoTypeId : $basePhotoTypeId;

			$objectType = $objectTypes->getType($photoTypeId);

			if ($objectType->getHierarchyTypeId() != $hierarchyTypeId) {
				$this->module->errorNewMessage("Object type and hierarchy type doesn't match");
				$this->module->errorPanic();
			}

			$photo = new umiImageFile($filePath);

			if ($photo->getIsBroken()) {
				return false;
			}

			$elementId = $hierarchy->addElement(
				$parentId,
				$hierarchyTypeId,
				$title,
				$title,
				$photoTypeId,
				$domainId,
				$langId,
				$tplId
			);

			permissionsCollection::getInstance()->setDefaultPermissions($elementId);
			$element = $hierarchy->getElement($elementId, true);

			$element->setIsActive();
			$element->setIsVisible(false);
			$element->setName($title);
			$element->setValue('photo', $photo);
			$element->setValue('create_time', time());
			$element->commit();

			$parentElement->setUpdateTime(time());
			$parentElement->commit();

			return true;
		}

		/**
		 * Загрузить набор изображений и для каждого из них создать сущность "Фотография"
		 * После окончания перенаправить главную страницу модуля
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function uploadImages() {
			$parentId = getRequest('param0');
			$hierarchy = umiHierarchy::getInstance();

			/** @var iUmiHierarchyElement $parentElement */
			$parentElement = $hierarchy->getElement($parentId);
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();

			if (!$parentElement instanceof iUmiHierarchyElement) {
				throw new publicAdminException(getLabel('error-expect-parent-id'));
			}

			$folder = $this->module->_checkFolder($parentElement);
			$tplId = $parentElement->getTplId();
			$domainId = $parentElement->getDomainId();
			$langId = $parentElement->getLangId();

			$hierarchyTypeId = $hierarchyTypes->getTypeByName('photoalbum', 'photo')->getId();
			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('photoalbum', 'photo');
			$objectType = $objectTypes->getType($objectTypeId);

			if ($objectType->getHierarchyTypeId() != $hierarchyTypeId) {
				$this->module->errorNewMessage("Object type and hierarchy type doesn't match");
				$this->module->errorPanic();
			}

			if (!isset($_FILES['fs_upl_files']) || !is_array($_FILES['fs_upl_files'])) {
				throw new publicAdminException(getLabel('error-expect-files-array'));
			}

			$uploadedFiles = $_FILES['fs_upl_files'];
			$permissionsCollection = permissionsCollection::getInstance();

			foreach ($uploadedFiles['name'] as $id => $pathName) {
				/** @var iUmiFile $fileUploaded */
				$fileUploaded = umiImageFile::upload('fs_upl_files', $id, $folder);

				if ($fileUploaded) {
					$fileName = $fileUploaded->getFileName();
					$fileExt = $fileUploaded->getExt();

					if (in_array(mb_strtolower($fileExt), $this->allowedTypes)) {
						$pathInfo = getPathInfo($fileName);
						$title = $pathInfo['filename'];

						$elementId = $hierarchy->addElement(
							$parentId,
							$hierarchyTypeId,
							$title,
							$title,
							$objectTypeId,
							$domainId,
							$langId,
							$tplId
						);

						$permissionsCollection->setDefaultPermissions($elementId);
						$element = $hierarchy->getElement($elementId, true);
						$element->setIsActive();
						$element->setIsVisible(false);
						$element->setName($title);
						$element->setValue('photo', $fileUploaded);
						$element->setValue('create_time', time());
						$element->commit();
						$parentElement->setUpdateTime(time());
						$parentElement->commit();
					} else {
						$fileUploaded->delete();
					}
				}
			}

			$this->module->redirect($this->module->pre_lang . '/admin/photoalbum/lists/');
		}
	}


