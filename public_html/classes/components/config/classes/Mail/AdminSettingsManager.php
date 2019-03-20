<?php

	namespace UmiCms\Classes\Components\Config\Mail;

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Mail\Settings\Common;
	use UmiCms\Classes\System\Utils\Mail\Settings\Custom;
	use UmiCms\Classes\System\Utils\Mail\Settings\Factory;

	/**
	 * Класс для управления настройками почты в административной панели
	 * @package UmiCms\Classes\Components\Config\Mail
	 */
	class AdminSettingsManager {

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getParams() {
			return array_merge($this->getCommonParams(), $this->getCustomParams());
		}
		
		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setCommonParams($params) {
			$settings = $this->getCommonMailSettings();
			
			$settings->setAdminEmail($params['string:admin_email'])
				->setSenderEmail($params['string:email_from'])
				->setSenderName($params['string:fio_from'])
				->setEngine($params['select:engine'])
				->setDisableParseContent($params['boolean:is-disable-parse-content']);
			
			$settings->Smtp()
				->setTimeout($params['int:timeout'])
				->setHost($params['string:host'])
				->setPort($params['string:port'])
				->setEncryption($params['select:encryption'])
				->setAuth($params['boolean:auth'])
				->setUserName($params['string:username'])
				->setPassword($params['smtp-password:password'])
				->setDebug($params['boolean:is-debug'])
				->setUseVerp($params['boolean:is-use-verp']);
		}

		/**
		 * @inheritdoc
		 * @throws \coreException
		 * @throws \Exception
		 */
		public function setCustomParams($params) {
			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$customParams = $params["mail-$domainId"];
				$settings = $this->getCustomMailSettings($domainId);

				$settings->setShouldUseCustomSettings($customParams["boolean:use-custom-settings-$domainId"])
					->setAdminEmail($customParams["string:admin_email-$domainId"])
					->setSenderEmail($customParams["string:email_from-$domainId"])
					->setSenderName($customParams["string:fio_from-$domainId"])
					->setEngine($customParams["select:engine-$domainId"])
					->setDisableParseContent($customParams["boolean:is-disable-parse-content-$domainId"]);

				$settings->Smtp()
					->setTimeout($customParams["int:timeout-$domainId"])
					->setHost($customParams["string:host-$domainId"])
					->setPort($customParams["string:port-$domainId"])
					->setEncryption($customParams["select:encryption-$domainId"])
					->setAuth($customParams["boolean:auth-$domainId"])
					->setUserName($customParams["string:username-$domainId"])
					->setPassword($customParams["smtp-password::password-$domainId"])
					->setDebug($customParams["boolean:is-debug-$domainId"])
					->setUseVerp($customParams["boolean:is-use-verp-$domainId"]);
			}
		}

		/**
		 * Возвращает общие настройки
		 * @return array
		 * @throws \Exception
		 */
		private function getCommonParams() {
			$settings = $this->getCommonMailSettings();
			$smtpSettings = $settings->Smtp();
			return [
				'mail' => [
					'string:admin_email' => $settings->getAdminEmail(),
					'string:email_from' => $settings->getSenderEmail(),
					'string:fio_from' => $settings->getSenderName(),
					'select:engine' => $this->getCommonEngineOptions(),
					'boolean:is-disable-parse-content' => $settings->isDisableParseContent(),
					'status:smtp-settings-label' => $this->getSmtpSettingsLabel(),
					'int:timeout' => $smtpSettings->getTimeout(),
					'string:host' => $smtpSettings->getHost(),
					'string:port' => $smtpSettings->getPort(),
					'select:encryption' => $this->getCommonEncryptionOptions(),
					'boolean:auth' => $smtpSettings->isAuth(),
					'string:username' => $smtpSettings->getUserName(),
					'smtp-password:password' => $smtpSettings->getPassword(),
					'boolean:is-debug' => $smtpSettings->isDebug(),
					'boolean:is-use-verp' => $smtpSettings->isUseVerp(),
				],
			];
		}

		/**
		 * Возвращает настройки, специфические для каждого сайта на текущей языковой версии
		 * @return array
		 * @throws \Exception
		 */
		private function getCustomParams() {
			$params = [];

			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$settings = $this->getCustomMailSettings($domainId);
				$smtpSettings = $settings->Smtp();

				$params["mail-$domainId"] = [
					'status:domain' => $domain->getDecodedHost(),
					"boolean:use-custom-settings-$domainId" => $settings->shouldUseCustomSettings(),
					"string:admin_email-$domainId" => $settings->getAdminEmail(),
					"string:email_from-$domainId" => $settings->getSenderEmail(),
					"string:fio_from-$domainId" => $settings->getSenderName(),
					"select:engine-$domainId" => $this->getCustomEngineOptions($domainId),
					"boolean:is-disable-parse-content-$domainId" => $settings->isDisableParseContent(),
					'status:smtp-settings-label' => $this->getSmtpSettingsLabel(),
					"int:timeout-$domainId" => $smtpSettings->getTimeout(),
					"string:host-$domainId" => $smtpSettings->getHost(),
					"string:port-$domainId" => $smtpSettings->getPort(),
					"select:encryption-$domainId" => $this->getCustomEncryptionOptions($domainId),
					"boolean:auth-$domainId" => $smtpSettings->isAuth(),
					"string:username-$domainId" => $smtpSettings->getUserName(),
					"smtp-password:password-$domainId" => $smtpSettings->getPassword(),
					"boolean:is-debug-$domainId" => $smtpSettings->isDebug(),
					"boolean:is-use-verp-$domainId" => $smtpSettings->isUseVerp(),
				];
			}

			return $params;
		}

		/**
		 * Возвращает элементы выпадающего списка для общей настройки "Средство доставки писем"
		 * @return array
		 * @throws \Exception
		 */
		private function getCommonEngineOptions() {
			return array_merge(
				$this->getEngineOptions(),
				['value' => $this->getCommonMailSettings()->getEngine()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки сайта "Средство доставки писем"
		 * @param int $domainId идентификатор домена
		 * @return array
		 * @throws \coreException
		 * @throws \Exception
		 */
		private function getCustomEngineOptions($domainId) {
			return array_merge(
				$this->getEngineOptions(),
				['value' => $this->getCustomMailSettings($domainId)->getEngine()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки "Средство доставки писем"
		 * @return array
		 */
		private function getEngineOptions() {
			return [
				'phpMail' => getLabel('mail-engine-php-mail'),
				'smtp' => getLabel('mail-engine-smtp'),
				'nullEngine' => getLabel('mail-engine-null-engine'),
			];
		}

		/**
		 * Возвращает элементы выпадающего списка для общей настройки "Шифрование"
		 * @return array
		 * @throws \Exception
		 */
		private function getCommonEncryptionOptions() {
			$encryption = $this->getCommonMailSettings()
				->Smtp()
				->getEncryption();
			return array_merge($this->getEncryptionOptions(), ['value' => $encryption]);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки сайта "Шифрование"
		 * @param int $domainId идентификатор домена
		 * @return array
		 * @throws \coreException
		 * @throws \Exception
		 */
		private function getCustomEncryptionOptions($domainId) {
			$encryption = $this->getCustomMailSettings($domainId)
				->Smtp()
				->getEncryption();

			return array_merge($this->getEncryptionOptions(), ['value' => $encryption]);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки SMTP "Шифрование"
		 * @return array
		 */
		private function getEncryptionOptions() {
			return [
				'ssl' => getLabel('mail-encryption-ssl'),
				'tls' => getLabel('mail-encryption-tls'),
				'auto' => getLabel('mail-encryption-auto'),
			];
		}

		/**
		 * Возвращает настройки общие для всех сайтов
		 * @param int $domainId идентификатор домена
		 * @return Custom
		 * @throws \coreException
		 */
		private function getCustomMailSettings($domainId) {
			/** @var Custom $custom */
			$custom = $this->getMailSettings()->createCustom($domainId);
			return $custom;
		}

		/**
		 * Возвращает настройки общие для всех сайтов
		 * @return Common
		 */
		private function getCommonMailSettings() {
			/** @var Common $common */
			$common = $this->getMailSettings()->createCommon();
			return $common;
		}

		/**
		 * Возвращает настройки почты
		 * @return Factory
		 */
		private function getMailSettings() {
			return Service::MailSettingsFactory();
		}

		/**
		 * Возвращает значение языковой константы "Настройки SMTP"
		 * @return string
		 */
		private function getSmtpSettingsLabel() {
			return getLabel('smtp-settings-label');
		}
	}