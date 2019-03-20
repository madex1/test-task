<?php

use UmiCms\Service;

/**
	 * Основной класс контента, отвечает за
	 * 1) Работу со страницами контента
	 * 2) eip
	 * 3) Быстрое редактирование в табличном контроле
	 * 4) Автогенерируемые меню
	 * 5) Общие операции со страницами (управление шаблонами и контролем актуальности)
	 * 6) Работа с тегами страниц
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_struktura/
	 */
	class content extends def_module {

		/** @const string имя класса административного функционала */
		const ADMIN_CLASS = 'ContentAdmin';

		/** @var int $perPage количество элементов на странице */
		public $perPage;

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();
			$this->perPage = (int) Service::Registry()->get('//settings/elements_count_per_page');

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$configTabs = $this->getConfigTabs();

			if ($configTabs instanceof iAdminModuleTabs) {
				$configTabs->add('config');
				$configTabs->add('content_control');
			}

			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('sitetree', ['sitetree']);
				$commonTabs->add('tree', ['tree']);
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('ContentAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('ContentCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('ContentMacros');

			$this->__loadLib('menu.php');
			$this->__implement('ContentMenu');

			$this->__loadLib('tags.php');
			$this->__implement('ContentTags');

			$this->loadSiteExtension();

			$this->__loadLib('eip.php');
			$this->__implement('EditInPlace');

			$this->__loadLib('handlers.php');
			$this->__implement('ContentHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('ContentCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/** @inheritdoc */
		public function redirect($url = '', $ignoreErrorParam = true) {
			if (is_numeric($url)) {
				/** @var ContentMacros $this */
				$url = $this->get_page_url($url);
			}
			parent::redirect($url);
		}

		/**
		 * Возвращает список всех родительских страниц для заданной страницы,
		 * список включает саму страницу
		 * @param int $elementId идентификатор страницы
		 * @return array
		 */
		public function get_parents($elementId) {
			return umiHierarchy::getInstance()->getAllParents($elementId, true);
		}

		/**
		 * Возвращает идентификатор языка для построения меню
		 * @param int $rootPageId идентификатор корневого элемента меню
		 * @param iUmiHierarchy $umiHierarchy коллекция иерархических объектов
		 * @return int
		 * @throws publicAdminException если не удалось получить объект корневого элемента
		 * @throws coreException
		 */
		public function getLanguageId($rootPageId, iUmiHierarchy $umiHierarchy) {
			if ($rootPageId == 0) {
				return Service::LanguageDetector()->detectId();
			}

			$rootPage = $umiHierarchy->getElement($rootPageId);

			if (!$rootPage instanceof iUmiHierarchyElement) {
				throw new publicAdminException('Cannot get root page element');
			}

			return $rootPage->getLangId();
		}

		/**
		 * Возвращает идентификатор домена для построения меню
		 * @param int $rootPageId идентификатор корневого элемента меню
		 * @param iUmiHierarchy $umiHierarchy коллекция иерархических объектов
		 * @return int
		 * @throws publicAdminException если не удалось получить объект корневого элемента
		 * @throws coreException
		 */
		public function getDomainId($rootPageId, iUmiHierarchy $umiHierarchy) {
			if ($rootPageId == 0) {
				return Service::DomainDetector()->detectId();
			}

			$rootPage = $umiHierarchy->getElement($rootPageId);

			if (!$rootPage instanceof iUmiHierarchyElement) {
				throw new publicAdminException('Cannot get root page element');
			}

			return $rootPage->getDomainId();
		}

		/**
		 * Возвращает протокол работы сервера
		 * Враппер к getSelectedServerProtocol()
		 * @return string
		 */
		public function getServerProtocol() {
			return getSelectedServerProtocol();
		}

		/**
		 * Заблокировано ли редактирование страницы пользователем
		 * @param int $element_id идентификатор страницы
		 * @param int $user_id идентификатор пользователя
		 * @return bool
		 */
		public function systemIsLockedById($element_id, $user_id) {
			$umiHierarchy = umiHierarchy::getInstance();
			$ePage = $umiHierarchy->getElement($element_id);
			$oPage = $ePage->getObject();
			$lockTime = $oPage->getValue('locktime');
			if ($lockTime == null) {
				return false;
			}
			$lockUser = $oPage->getValue('lockuser');
			$lockDuration = Service::Registry()->get('//settings/lock_duration');
			return ($lockTime->timestamp + $lockDuration) > time() && $lockUser != $user_id;
		}

		/**
		 * Возвращает идентификатор пользователя, который
		 * заблокировал редактирование страницы
		 * @param int $element_id идентификатор страницы
		 * @return Mixed
		 */
		public function systemWhoLocked($element_id) {
			$umiHierarchy = umiHierarchy::getInstance();
			$ePage = $umiHierarchy->getElement($element_id);
			$oPage = $ePage->getObject();
			return $oPage->getValue('lock_user');
		}

		/**
		 * Отключает блокировку у всех страниц системы
		 * @throws publicAdminException
		 */
		public function systemUnlockAll() {
			$permissions = permissionsCollection::getInstance();

			if (!$permissions->isSv()) {
				throw new publicAdminException(getLabel('error-can-unlock-not-sv'));
			}

			$sel = new selector('pages');
			$result = $sel->result();

			/** @var iUmiHierarchyElement $page */
			foreach ($result as $page) {
				$object = $page->getObject();
				$object->setValue('locktime', null);
				$object->setValue('lockuser', null);
				$object->commit();
				$page->commit();
			}
		}

		/**
		 * Запускает отключение блокировки у всех страниц системы
		 * и перенаправляет на HTTP_REFERER
		 * @throws publicAdminException
		 * @throws coreException
		 */
		public function unlockAll() {
			$this->systemUnlockAll();
			$this->redirect($_SERVER['HTTP_REFERER']);
		}

		/**
		 * Отключает блокировку у страницы
		 * @param int $pageId идентификатор страницы
		 */
		public function unlockPage($pageId) {
			$element = umiHierarchy::getInstance()->getElement($pageId);
			if ($element instanceof iUmiHierarchyElement) {
				$pageObject = $element->getObject();
				$pageObject->setValue('locktime', 0);
				$pageObject->setValue('lockuser', 0);
				$pageObject->commit();
			}
		}

		/**
		 * Отключает или включает активность страницы,
		 * в зависимости от настроек актуальности
		 * @param iUmiHierarchyElement $page страницы
		 * @throws selectorException
		 */
		public function saveExpiration(iUmiHierarchyElement $page) {
			/** @var iUmiHierarchyElement $page */
			/** @var iUmiObject $pageObject */
			$pageObject = $page->getObject();
			$expirationTime = $pageObject->getValue('expiration_date');

			if ($expirationTime instanceof iUmiDate) {
				if ($expirationTime->timestamp > time()) {
					$pageObject->publish_status = $this->getPageStatusIdByFieldGUID('page_status_publish');
					$page->setIsActive();
				} elseif ($expirationTime->timestamp < time() && $expirationTime->timestamp != null) {
					$pageObject->publish_status = $this->getPageStatusIdByFieldGUID('page_status_unpublish');
					$page->setIsActive(false);
				}
				$pageObject->commit();
				$page->commit();
			}
		}

		/**
		 * Проверяет разрешено ли пользователю редактирования поля
		 * @param iUmiObject $object объект пользователя
		 * @param string $propName имя поля
		 * @return bool
		 * @throws coreException
		 */
		public function checkAllowedColumn(iUmiObject $object, $propName) {
			$userTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName('users', 'user')->getId();
			$isSv = permissionsCollection::getInstance()->isSv();
			$isObjectCustomer = $object->getTypeGUID() == 'emarket-customer';
			$isObjectUser = umiObjectTypesCollection::getInstance()
				->getType($object->getTypeId())
				->getHierarchyTypeId() == $userTypeId;

			$notAllowedProps = ['bonus', 'spent_bonus', 'filemanager_directory', 'groups'];

			if (!$isSv && ($isObjectCustomer || $isObjectUser)) {
				if (in_array($propName, $notAllowedProps)) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Очищает строку от ряда символов
		 * @param string $string строку
		 * @return mixed
		 */
		public function filterString($string) {
			return str_replace('"', "\\\"", str_replace("'", "\'", $string));
		}

		/**
		 * Возвращает список страниц, которым назначены шаблоны $templates
		 * @param int[] $templates массив с ID шаблонов
		 * @param int $limit максимальное количество получаемых страниц
		 * @param int $offset смещение, относительно которого будет производиться выборка страниц
		 * @return iUmiHierarchyElement[]
		 */
		public function getPagesByTemplatesIdList(array $templates, $limit = 0, $offset = 0) {
			$umiTemplates = templatesCollection::getInstance();
			$pages = [];

			foreach ($templates as $templateId) {
				$template = $umiTemplates->getTemplate(trim($templateId));

				if (!$template instanceof iTemplate) {
					continue;
				}

				$relatedPages = $template->getRelatedPages($limit, $offset);

				if (!empty($relatedPages) && is_array($relatedPages)) {
					foreach ($relatedPages as $relatedPage) {
						$pages[] = $relatedPage;
					}
				}
			}

			return array_unique($pages);
		}

		/**
		 * Возвращает количество страниц, которым назначены шаблоны $templates
		 * @param array $templates массив с ID шаблонов
		 * @return int число используемых страниц шаблонами
		 */
		public function getTotalPagesByTemplates(array $templates) {
			$total = 0;

			$templatesCollection = templatesCollection::getInstance();
			/** @var int $templateId */
			foreach ($templates as $templateId) {
				$template = $templatesCollection->getTemplate(trim($templateId));
				if (!$template instanceof iTemplate) {
					continue;
				}

				$total += $template->getTotalUsedPages();
			}

			return $total;
		}

		/**
		 * Меняет у массива дочерних страницы язык и домен
		 * @param array $children массив дочерних страниц
		 * @param int $langId идентификатор языка
		 * @param bool|int $domainId идентификатор домена
		 */
		public function changeChildsLang($children, $langId, $domainId = false) {
			$hierarchy = umiHierarchy::getInstance();

			if (!is_array($children)) {
				return;
			}

			foreach ($children as $elementId => $subChild) {
				$element = $hierarchy->getElement($elementId);
				if ($element instanceof iUmiHierarchyElement) {
					$element->setLangId($langId);

					if ($domainId) {
						$element->setDomainId($domainId);
					}

					$element->commit();

					if (is_array($subChild) && umiCount($subChild)) {
						$this->changeChildsLang($subChild, $langId, $domainId);
					}
				}
			}
		}

		/**
		 * Возвращает идентификатор статуса публикации по его строковому идентификатору
		 * @param string $statusStringId идентификатор статуса
		 * @return bool|int
		 * @throws selectorException
		 */
		public function getPageStatusIdByFieldGUID($statusStringId = 'page_status_publish') {
			$objectTypeId = null;
			$fields = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');

			/** @var iUmiField $field */
			foreach ($fields->getAllFields() as $field) {
				if ($field->getName() == 'publish_status') {
					$objectTypeId = $field->getGuideId();
				}
			}

			if (!$objectTypeId) {
				return false;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id($objectTypeId);
			$result = $sel->result();

			/** @var iUmiObject $object */
			foreach ($result as $object) {
				$statusId = $object->getValue('publish_status_id');

				if ($statusId == $statusStringId) {
					return $object->getId();
				}
			}

			return false;
		}

		/**
		 * Возвращает адрес редактирования страницы и адрес добавления дочерней страницы
		 * @param int $elementId идентификатор страницы
		 * @return array
		 */
		public function getEditLink($elementId) {
			return [
				$this->pre_lang . "/admin/content/add/{$elementId}/page/",
				$this->pre_lang . "/admin/content/edit/{$elementId}/"
			];
		}

		/** @inheritdoc */
		public function getMailObjectTypesGuidList() {
			return ['root-pages-type'];
		}

		/** @deprecated  */
		public function getVariableNamesForMailTemplates() {
			return [];
		}
	}
