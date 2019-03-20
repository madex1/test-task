<?php
	/**
	 * Базовый класс модуля "Редиректы"
	 * Позволяет добавлять, удалять и изменять редиректы
	 */
	class umiRedirects extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			$cmsController = cmsController::getInstance();

			if ($cmsController->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__umiRedirects");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__umiRedirects_custom_admin", true);
			} else {
				$this->loadSiteExtension();

				$this->__loadLib("__custom.php");
				$this->__implement("__custom_umiRedirects", true);
			}

			$this->loadCommonExtension();
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
		protected function checkDoubles(array $params) {
			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$source = $umiRedirectsCollection->getMap()->get('SOURCE_FIELD_NAME');

			if (!isset($params[$source])) {
				throw new publicAdminException(getLabel('error-source-expected'));
			}

			$redirects = $umiRedirectsCollection->get(
				[
					$source => $params[$source]
				]
			);

			if (count($redirects) > 0) {
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
		protected function checkCircles(array $params) {
			/** @var umiRedirectsCollection $umiRedirectsCollection */
			$umiRedirectsCollection = umiRedirectsCollection::getInstance();
			$collectionMap = $umiRedirectsCollection->getMap();

			$source = $collectionMap->get('SOURCE_FIELD_NAME');

			if (!isset($params[$source])) {
				throw new publicAdminException(getLabel('error-source-expected'));
			}

			$target = $collectionMap->get('TARGET_FIELD_NAME');

			if (!isset($params[$target])) {
				throw new publicAdminException(getLabel('error-target-expected'));
			}

			if ($params[$source] == $params[$target]) {
				throw new publicAdminException(getLabel('error-cyclic-redirect'));
			}

			$redirects = $umiRedirectsCollection->get(
				[
					$source => $params[$target],
					$target => $params[$source],
				]
			);

			if (count($redirects) > 0) {
				throw new publicAdminException(getLabel('error-cyclic-redirect'));
			}

			return true;
		}
	}
?>