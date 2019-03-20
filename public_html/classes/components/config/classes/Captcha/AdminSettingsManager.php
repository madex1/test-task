<?php

	namespace UmiCms\Classes\Components\Config\Captcha;

	use UmiCms\Service;

	/** Класс для управления настройками капчи в административной панели */
	class AdminSettingsManager implements iAdminSettingsManager {

		/** @inheritdoc */
		public function getParams() {
			return array_merge($this->getCommonParams(), $this->getSiteParams());
		}

		/** @inheritdoc */
		public function setCommonParams($params) {
			Service::CaptchaSettingsFactory()
				->getCommonSettings()
				->setStrategyName($params['select:captcha'])
				->setDrawerName($params['string:captcha-drawer'])
				->setShouldRemember($params['boolean:captcha-remember'])
				->setSitekey($params['string:recaptcha-sitekey'])
				->setSecret($params['string:recaptcha-secret']);
		}

		/** @inheritdoc */
		public function setSiteParams($params) {
			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$siteParams = $params["captcha-$domainId"];
				$name = $siteParams["select:captcha-$domainId"];
				Service::CaptchaSettingsFactory()
					->getSiteSettings($domainId)->setStrategyName($name)
					->setShouldUseSiteSettings($siteParams["boolean:use-site-settings-$domainId"])
					->setShouldRemember($siteParams["boolean:captcha-remember-$domainId"])
					->setDrawerName($siteParams["string:captcha-drawer-$domainId"])
					->setSitekey($siteParams["string:recaptcha-sitekey-$domainId"])
					->setSecret($siteParams["string:recaptcha-secret-$domainId"]);
			}
		}

		/**
		 * Возвращает настройки капчи, общие для всех сайтов
		 * @return array
		 */
		private function getCommonParams() {
			$settings = Service::CaptchaSettingsFactory()->getCommonSettings();
			return [
				'captcha' => [
					'select:captcha' => $this->getCommonOptions(),
					'boolean:captcha-remember' => $settings->shouldRemember(),
					'string:captcha-drawer' => $settings->getDrawerName(),
					'string:recaptcha-sitekey' => $settings->getSitekey(),
					'string:recaptcha-secret' => $settings->getSecret(),
				],
			];
		}

		/**
		 * Возвращает элементы выпадающего списка для настроек капчи,
		 * плюс название текущей общей стратегии капчи
		 * @return array
		 */
		private function getCommonOptions() {
			$settings = Service::CaptchaSettingsFactory()->getCommonSettings();
			return array_merge(
				$this->getOptions(),
				['value' => $settings->getStrategyName()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настроек капчи
		 * @return array
		 */
		private function getOptions() {
			return [
				'null-captcha' => getLabel('null-captcha', 'config'),
				'captcha' => getLabel('captcha', 'config'),
				'recaptcha' => getLabel('recaptcha', 'config'),
			];
		}

		/**
		 * Возвращает настройки капчи, специфические для каждого сайта на текущей языковой версии
		 * @return array
		 */
		private function getSiteParams() {
			$params = [];

			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$settings = Service::CaptchaSettingsFactory()->getSiteSettings($domainId);
				$params["captcha-$domainId"] = [
					'status:domain' => $domain->getDecodedHost(),
					"boolean:use-site-settings-$domainId" => $settings->shouldUseSiteSettings(),
					"select:captcha-$domainId" => $this->getCaptchaSiteOptions($domainId),
					"boolean:captcha-remember-$domainId" => $settings->shouldRemember(),
					"string:captcha-drawer-$domainId" => $settings->getDrawerName(),
					"string:recaptcha-sitekey-$domainId" => $settings->getSitekey(),
					"string:recaptcha-secret-$domainId" => $settings->getSecret(),
				];
			}

			return $params;
		}

		/**
		 * Возвращает элементы выпадающего списка для настроек капчи,
		 * плюс название текущей стратегии капчи для выбранного сайта
		 * @param int $domainId ИД домена сайта
		 * @return array
		 */
		private function getCaptchaSiteOptions($domainId) {
			$settings = Service::CaptchaSettingsFactory()->getSiteSettings($domainId);
			return array_merge(
				$this->getOptions(),
				['value' => $settings->getStrategyName()]
			);
		}
	}
