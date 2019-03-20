<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Редиректы"
	 * Позволяет добавлять, удалять и изменять редиректы
	 */
	class umiRedirects extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('UmiRedirectsAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('umiRedirectsCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('umiRedirectsCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылку на редактирование редиректа
		 * @param int $redirectId id сущности модуля
		 * @return array
		 */
		public function getEditLink($redirectId) {
			$prefix = $this->pre_lang;
			$addLink = false;
			$editLink = $prefix . "/admin/umiRedirects/edit/{$redirectId}/";

			return [$addLink, $editLink];
		}

		/**
		 * Валидирует параметры добавляемого или редактируемого редиректа.
		 * @see список параметров в umiRedirect->getPropsList()
		 * @param array $params параметры редиректа
		 * @throws publicAdminException
		 */
		public function validateRedirectParams(array $params) {
			$this->checkDoubles($params);
			$this->checkCircles($params);
		}

		/**
		 * Проверяет, что у данного редиректа нет дублей
		 * @param array $params параметры редиректа
		 * @return bool
		 * @throws publicAdminException
		 */
		public function checkDoubles(array $params) {
			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$redirectsMap = $umiRedirectsCollection->getMap();
			$source = $redirectsMap->get('SOURCE_FIELD_NAME');

			if (!isset($params[$source])) {
				throw new publicAdminException(getLabel('error-source-expected'));
			}

			$count = $umiRedirectsCollection->count([
				$source => $params[$source],
				$redirectsMap->get('CALCULATE_ONLY_KEY') => true,
			]);

			if ($count > 0) {
				throw new publicAdminException(getLabel('error-redirect-exists'));
			}

			return true;
		}

		/**
		 * Проверяет, что после добавления редиректа не будет циклический переадресаций
		 * @param array $params параметры редиректа
		 * @return bool
		 * @throws publicAdminException
		 */
		public function checkCircles(array $params) {
			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$map = $umiRedirectsCollection->getMap();
			$source = $map->get('SOURCE_FIELD_NAME');

			if (!isset($params[$source])) {
				throw new publicAdminException(getLabel('error-source-expected'));
			}

			$target = $map->get('TARGET_FIELD_NAME');

			if (!isset($params[$target])) {
				throw new publicAdminException(getLabel('error-target-expected'));
			}

			if ($params[$source] == $params[$target]) {
				throw new publicAdminException(getLabel('error-cyclic-redirect'));
			}

			$count = $umiRedirectsCollection->count([
				$source => $params[$target],
				$target => $params[$source],
				$map->get('CALCULATE_ONLY_KEY') => true,
			]);

			if ($count > 0) {
				throw new publicAdminException(getLabel('error-cyclic-redirect'));
			}

			return true;
		}
	}
