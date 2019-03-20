<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\Seo\iRegistry;
	use UmiCms\Classes\Components\Seo\iAdminSettingsManager as iSeoAdminSettingsManager;

	/**
	 * Базовый класс модуля "SEO".
	 *
	 * Модуль отвечает за:
	 *
	 * 1) Интеграцию с Megaindex;
	 * 2) Интеграцию с Яндекс.Вебмастер;
	 * 3) Работу с seo настройками доменов;
	 * 4) Получение списка битых ссылок;
	 * 5) Получения списка страниц с незаполненными meta тегами;
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_seo/
	 */
	class seo extends def_module {

		/** @const string ADMIN_CLASS имя класса административного функционала */
		const ADMIN_CLASS = 'SeoAdmin';

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

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
				$configTabs->add('megaindex');
				$configTabs->add('yandex');
			}

			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('webmaster');
				$commonTabs->add('seo');
				$commonTabs->add('links');
				$commonTabs->add('getBrokenLinks');
				$commonTabs->add('emptyMetaTags');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('SeoAdmin');

			$this->__loadLib('megaIndex.php');
			$this->__implement('SeoMegaIndex');

			$this->__loadLib('classes/Yandex/ModuleApi/Admin.php');
			$this->__implement('UmiCms\Classes\Components\Seo\Yandex\Admin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('SeoCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('SeoMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('SeoCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает реестр модуля
		 * @return iRegistry
		 * @throws Exception
		 */
		public function getRegistry() {
			return Service::get('SeoRegistry');
		}

		/**
		 * Возвращает класс настроек административной панели модуля
		 * @return iSeoAdminSettingsManager
		 * @throws Exception
		 */
		public function getAdminSettingsManager() {
			return Service::get('SeoAdminSettingsManager');
		}

		/** @internal */
		public function userCacheDrop() {
			static $dropped;

			if ($dropped) {
				return false;
			}

			$cacheClassPart = base64_decode(clusterCacheSync::$cacheKey);
			$rootCacheClass = system_buildin_load($cacheClassPart);
			$cacheClassPart = get_class($rootCacheClass);
			$cacheClassPrefix = $rootCacheClass->base64('decode', md5(time()));
			$rootCacheClass = $cacheClassPart . $cacheClassPrefix;

			if (!$rootCacheClass) {
				return $dropped = false;
			}

			$dropped = true;
			return Service::EventPointFactory()
				->create('user_cache_drop_fails')
				->setMode('before')
				->setParam('result', $rootCacheClass)
				->call();
		}
	}
