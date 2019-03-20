<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Баннеры"
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Баннеры;
	 * 2) Места показа баннеров;
	 *
	 * Модуль отвечает за вставку того или иного баннера,
	 * в зависимости от окружения. По баннерам ведется
	 * статистика показов и кликов.
	 *
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_bannery/
	 */
	class banners extends def_module {

		/** @var array массив с банерами, доступными для отображения и связанными по месту */
		static public $arrVisibleBanners = [];

		/** @var array массив с банерами, отрисованными в рамках текущей сессии */
		public $updatedBanners = [];

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			$this->isStaticCache = Service::StaticCache()->isEnabled();
			$this->per_page = 20;
			$this->disableUpdateOpt = (int) mainConfiguration::getInstance()
				->get('modules', 'banners.disable-update-optimization');

			if (Service::Request()->isAdmin()) {
				$geoIpModule = cmsController::getInstance()
					->getModule('geoip');

				if ($geoIpModule instanceof def_module) {
					/** @var banners|BannersAdmin $this */
					$this->switchGroupsActivity('city_targeting', true);
				}

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
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('lists');
				$commonTabs->add('places');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('BannersAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('BannersCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			/** @var banners $this */
			$this->__loadLib('macros.php');
			$this->__implement('BannersMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('BannersCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает список баннеров, удовлетворяющий параметрам показа на страницах,
		 * активности, дате начала показа и месту.
		 * @param int $placeId идентификатор места показа баннера
		 * @param int $currentPageId ид страницы, на которой требуется показать баннер
		 * @param array $currentPageParentsIds массив идентификаторов страниц, родительских странице с ид $currentPageId
		 * @param bool $isRandom включен ли режим случайного отображения баннеров
		 * @return array
		 * @throws selectorException
		 */
		public function getBanners($placeId, $currentPageId, array $currentPageParentsIds, $isRandom = false) {
			$banners = new selector('objects');
			$banners->types('hierarchy-type')->name('banners', 'banner');
			$banners->where('show_start_date')->less(time());
			$banners->where('is_active')->equals(1);
			$banners->where('place')->equals($placeId);
			$banners->option('or-mode')->field('view_pages');
			$banners->where('view_pages')->equals($currentPageId);
			$banners->where('view_pages')->equals($currentPageParentsIds);
			$banners->where('view_pages')->isnull();
			$banners->option('no-length')->value(true);
			$banners->option('load-all-props')->value(true);

			if ($isRandom) {
				$banners->order('rand');
			} else {
				$banners->order('id');
			}

			return $banners->result();
		}

		/**
		 * Проверяет находится ли текущая страница среди страниц, на
		 * которых не нужно показывать баннер.
		 * @param iUmiObject $banner объект баннера
		 * @param int $currentPageId ид текущей страницы
		 * @return bool
		 */
		public function checkNotAllowedPages(iUmiObject $banner, $currentPageId) {
			$notAllowedPages = $banner->getValue('not_view_pages');

			if (!is_array($notAllowedPages)) {
				$notAllowedPages = (array) $notAllowedPages;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$notAllowedPagesIds = [];

			if (umiCount($notAllowedPages) > 0) {
				/* @var iUmiHierarchyElement $notAllowedPage */
				foreach ($notAllowedPages as $notAllowedPage) {
					if (!$notAllowedPage instanceof iUmiHierarchyElement) {
						continue;
					}
					$notAllowedPageId = $notAllowedPage->getId();
					$notAllowedPagesIds[] = $notAllowedPageId;
					$umiHierarchy->unloadElement($notAllowedPageId);
				}
			}
			return in_array((int) $currentPageId, $notAllowedPagesIds) ? false : true;
		}

		/**
		 * Проверяет сооответствие настроек времени и даты показа баннера
		 * текущему времени.
		 * @param iUmiObject $banner объект баннера
		 * @return bool
		 */
		public function checkTimeTargeting(iUmiObject $banner) {
			$timeTargetingEnabled = $banner->getValue('time_targeting_is_active');

			if (!$timeTargetingEnabled) {
				return true;
			}

			$timeRanges = new ranges();
			$targetingByMonth = (string) $banner->getValue('time_targeting_by_month');

			if ($targetingByMonth !== '') {
				$months = $timeRanges->get($targetingByMonth, 1);

				if (!in_array((int) date('m'), $months)) {
					return false;
				}
			}

			$targetingByMonthDays = (string) $banner->getValue('time_targeting_by_month_days');

			if ($targetingByMonthDays !== '') {
				$monthDays = $timeRanges->get($targetingByMonthDays);
				if (!in_array((int) date('d'), $monthDays)) {
					return false;
				}
			}

			$targetingByWeekDays = (string) $banner->getValue('time_targeting_by_week_days');

			if ($targetingByWeekDays !== '') {
				$weekDays = $timeRanges->get($targetingByWeekDays);
				if (!in_array((int) date('w'), $weekDays)) {
					return false;
				}
			}

			$targetingByHours = (string) $banner->getValue('time_targeting_by_hours');

			if ($targetingByHours !== '') {
				$hours = $timeRanges->get($targetingByHours);
				if (!in_array((int) date('G'), $hours)) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Проверяет не истекло ли время показа баннера
		 * @param iUmiObject $banner объект баннера
		 * @return bool
		 */
		public function checkDateExpiration(iUmiObject $banner) {
			$showTillDate = $banner->getValue('show_till_date');

			if ($showTillDate instanceof umiDate && $showTillDate->timestamp) {

				if ($showTillDate->timestamp < $showTillDate->getCurrentTimeStamp()) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Проверяет соотстветствие тегов баннера тегам текущего пользователя,
		 * текущей страницы и тегам, полученным путем разбора реферера.
		 * Если баннер соотвествует, то вычисляется его вес, он же и возвращается.
		 * Если баннер не сооответствует, то возвращается false.
		 * @param iUmiObject $banner объект баннера
		 * @param array $pageTags массив с тегами текущей страницы
		 * @param array $userTags массив с тегами текущего пользователя
		 * @param array $refererTags массив с тегами, полученными путем разбора реферера
		 * @return bool|int
		 */
		public function checkTagsAndCalculateWeight(
			iUmiObject $banner,
			array $pageTags,
			array $userTags,
			array $refererTags
		) {
			$weight = 1;
			$bannerPagesTags = $banner->getValue('tags');

			if (is_array($bannerPagesTags) && umiCount($bannerPagesTags) > 0) {
				$commonTags = array_intersect($bannerPagesTags, $pageTags);
				if (umiCount($commonTags) == 0) {
					return false;
				}

				$weight += umiCount($commonTags);
			}

			$bannerUserTags = $banner->getValue('user_tags');

			if (!is_array($bannerUserTags) || umiCount($bannerUserTags) == 0) {
				return $weight;
			}

			$allowedTagsCounter = 0;
			foreach ($bannerUserTags as $bannerUserTag) {
				if (in_array($bannerUserTag, $userTags) || in_array($bannerUserTag, $refererTags)) {
					$allowedTagsCounter++;
				}
			}

			if ($allowedTagsCounter === 0) {
				return false;
			}

			return $weight + $allowedTagsCounter;
		}

		/**
		 * Проверяет не закончилось ли у баннера число показов
		 * @param iUmiObject $banner объект баннера
		 * @return bool
		 */
		public function checkViewCount(iUmiObject $banner) {
			$maxViews = (int) $banner->getValue('max_views');
			$viewsCount = (int) $banner->getValue('views_count');
			return ($maxViews <= 0 || $viewsCount <= $maxViews);
		}

		/**
		 * Проверяет соответствие города текущего пользователя, городу
		 * показа в баннере.
		 * @param iUmiObject $banner объект баннера
		 * @param bool|string $userCity город текущего пользователя
		 * @return bool
		 */
		public function checkCityTargeting(iUmiObject $banner, $userCity) {
			$cityTargetingEnabled = $banner->getValue('city_targeting_is_active');

			if (!$cityTargetingEnabled || is_bool($userCity)) {
				return true;
			}

			$bannerCity = $banner->getValue('city_targeting_city');

			if (!$bannerCity) {
				return true;
			}

			return $bannerCity == $userCity;
		}

		/**
		 * Возвращает ссылку на редактирование объектов модуля "Баннеры"
		 * @param integer $object_id id сущности модуля
		 * @param string $object_type строковой идентификатор типа сущности
		 * @return array|bool
		 */
		public function getEditLink($object_id, $object_type) {
			switch ($object_type) {
				case 'banner': {
					$link_add = $this->pre_lang . '/admin/banners/banner_add/';
					$link_edit = $this->pre_lang . "/admin/banners/banner_edit/{$object_id}/";
					return [$link_add, $link_edit];
				}
				default: {
					return false;
				}
			}
		}

		/**
		 * Валидирует текущую страниц и ее родителей по настройкам баннера.
		 * Возвращает результат - показывать или нет.
		 * @param array $pages массив с ид страниц, на которых нужно показывать баннер
		 * @param array $notPages массив с ид страниц, на которых не нужно показывать баннер
		 * @return bool
		 */
		public function checkIfValidParent($pages, $notPages) {
			$cmsController = cmsController::getInstance();
			$currentPageId = $cmsController->getCurrentElementId();

			if (umiCount($notPages)) {
				/** @var iUmiEntinty $notPage */
				foreach ($notPages as $notPage) {
					if ($notPage->getId() == $currentPageId) {
						return false;
					}
				}
			}

			if (!is_array($pages) || umiCount($pages) == 0) {
				return true;
			}

			$parents = $this->getCurrentParents();

			foreach ($pages as $page) {
				/** @var iUmiEntinty $page */
				if (in_array($page->getId(), $parents)) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Возвращает массив идентификаторов страниц, родительских текущей.
		 * @return array
		 */
		public function getCurrentParents() {
			static $parents = false;

			if (is_array($parents)) {
				return $parents;
			}

			$cmsController = cmsController::getInstance();
			$iCurrPageId = $cmsController->getCurrentElementId();

			if ($iCurrPageId) {
				return $parents = umiHierarchy::getInstance()->getAllParents($iCurrPageId, true);
			}

			return [];
		}

		/**
		 * Возвращает идентификатор места показа баннера по его названию
		 * @param string $placeName ид места показа баннера
		 * @return array|bool
		 * @throws selectorException
		 */
		public function getPlaceId($placeName) {
			static $cache = [];
			$placeName = (string) $placeName;

			if (isset($cache[$placeName])) {
				return [0 => (int) $cache[$placeName]];
			}

			$places = new selector('objects');
			$places->types('hierarchy-type')->name('banners', 'place');
			$places->option('no-length')->value(true);
			$places->option('load-all-props')->value(true);
			$places = $places->result();

			if (umiCount($places) == 0) {
				return false;
			}

			foreach ($places as $place) {
				if (!$place instanceof iUmiObject) {
					continue;
				}
				$cache[$place->getName()] = $place->getId();
			}

			if (isset($cache[$placeName])) {
				return [0 => (int) $cache[$placeName]];
			}

			return false;
		}

		/**
		 * Возвращает ссылку на редактирование баннера
		 * @param integer $objectId ид баннера
		 * @param bool|string $type не используется
		 * @return string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . '/admin/banners/edit/' . $objectId . '/';
		}

		/**
		 * Отключает активность запрошенных баннеров, если у них истекло максимальное количество просмотров.
		 */
		public function saveUpdates() {
			/** @var iUmiObject $banner */
			foreach ($this->updatedBanners as $i => $banner) {
				if ($banner instanceof iUmiObject) {
					if ($banner->max_views && ($banner->views_count >= $banner->max_views)) {
						$banner->is_active = false;
					}

					$banner->commit();
					unset($this->updatedBanners[$i]);
				}
			}
		}

		/** @inheritdoc */
		public function getMailObjectTypesGuidList() {
			return ['banners-banner', 'banners-banner-image', 'banners-banner-swf', 'banners-banner-html'];
		}

		/** Деструктор */
		public function __destruct() {
			$this->saveUpdates();
		}

		/** @deprecated  */
		public function getVariableNamesForMailTemplates() {
			return [];
		}
	}
