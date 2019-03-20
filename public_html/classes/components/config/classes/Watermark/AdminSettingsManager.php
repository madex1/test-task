<?php

	namespace UmiCms\Classes\Components\Config\Watermark;

	use UmiCms\Service;

	/** Класс для управления настройками водяного знака в административной панели */
	class AdminSettingsManager implements iAdminSettingsManager {

		/** @var int Максимальное значение настройки "Прозрачность" */
		const MAX_ALPHA_VALUE = 100;

		/** @inheritdoc */
		public function getParams() {
			return array_merge($this->getCommonParams(), $this->getCustomParams());
		}

		/** @inheritdoc */
		public function setCommonParams($params) {
			Service::WatermarkSettingsFactory()
				->createCommon()
				->setImagePath($this->normalizeImagePath($params['string:image']))
				->setAlpha($this->normalizeAlpha($params['int:alpha']))
				->setVerticalAlign($params['select:valign'])
				->setHorizontalAlign($params['select:halign']);
		}

		/** @inheritdoc */
		public function setCustomParams($params) {
			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$customParams = $params["watermark-$domainId"];
				Service::WatermarkSettingsFactory()
					->createCustom($domainId)
					->setShouldUseCustomSettings($customParams["boolean:use-custom-settings-$domainId"])
					->setImagePath($this->normalizeImagePath($customParams["string:image-$domainId"]))
					->setAlpha($this->normalizeAlpha($customParams["int:alpha-$domainId"]))
					->setVerticalAlign($customParams["select:valign-$domainId"])
					->setHorizontalAlign($customParams["select:halign-$domainId"]);
			}
		}

		/**
		 * Возвращает общие настройки
		 * @return array
		 */
		private function getCommonParams() {
			$settings = Service::WatermarkSettingsFactory()->createCommon();
			return [
				'watermark' => [
					'string:image' => $settings->getImagePath(),
					'int:alpha' => $settings->getAlpha(),
					'select:valign' => $this->getCommonVerticalAlignOptions(),
					'select:halign' => $this->getCommonHorizontalAlignOptions(),
				],
			];
		}

		/**
		 * Возвращает элементы выпадающего списка для общей настройки "Вертикальное положение"
		 * @return array
		 */
		private function getCommonVerticalAlignOptions() {
			$settings = Service::WatermarkSettingsFactory()->createCommon();
			return array_merge(
				$this->getVerticalAlignOptions(),
				['value' => $settings->getVerticalAlign()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для общей настройки "Горизонтальное положение"
		 * @return array
		 */
		private function getCommonHorizontalAlignOptions() {
			$settings = Service::WatermarkSettingsFactory()->createCommon();
			return array_merge(
				$this->getHorizontalAlignOptions(),
				['value' => $settings->getHorizontalAlign()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки "Вертикальное положение"
		 * @return array
		 */
		private function getVerticalAlignOptions() {
			return [
				'top' => getLabel('watermark-valign-top'),
				'bottom' => getLabel('watermark-valign-bottom'),
				'center' => getLabel('watermark-valign-center'),
			];
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки "Горизонтальное положение"
		 * @return array
		 */
		private function getHorizontalAlignOptions() {
			return [
				'left' => getLabel('watermark-halign-left'),
				'right' => getLabel('watermark-halign-right'),
				'center' => getLabel('watermark-halign-center'),
			];
		}

		/**
		 * Возвращает настройки, специфические для каждого сайта на текущей языковой версии
		 * @return array
		 */
		private function getCustomParams() {
			$params = [];

			foreach (Service::DomainCollection()->getList() as $domain) {
				$domainId = $domain->getId();
				$settings = Service::WatermarkSettingsFactory()->createCustom($domainId);
				$params["watermark-$domainId"] = [
					'status:domain' => $domain->getDecodedHost(),
					"boolean:use-custom-settings-$domainId" => $settings->shouldUseCustomSettings(),
					"string:image-$domainId" => $settings->getImagePath(),
					"int:alpha-$domainId" => $settings->getAlpha(),
					"select:valign-$domainId" => $this->getCustomVerticalAlignOptions($domainId),
					"select:halign-$domainId" => $this->getCustomHorizontalAlignOptions($domainId),
				];
			}

			return $params;
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки сайта "Вертикальное положение"
		 * @param int $domainId ИД домена
		 * @return array
		 */
		private function getCustomVerticalAlignOptions($domainId) {
			$settings = Service::WatermarkSettingsFactory()->createCustom($domainId);
			return array_merge(
				$this->getVerticalAlignOptions(),
				['value' => $settings->getVerticalAlign()]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настройки сайта "Горизонтальное положение"
		 * @param int $domainId ИД домена
		 * @return array
		 */
		private function getCustomHorizontalAlignOptions($domainId) {
			$settings = Service::WatermarkSettingsFactory()->createCustom($domainId);
			return array_merge(
				$this->getHorizontalAlignOptions(),
				['value' => $settings->getHorizontalAlign()]
			);
		}

		/**
		 * Нормализует путь до изображения
		 * @param string $path путь до изображения
		 * @return string
		 */
		private function normalizeImagePath($path) {
			$path = trim($path);
			$path = str_replace('./', '', $path);

			if (mb_substr($path, 0, 1) == '/') {
				$path = mb_substr($path, 1);
			}

			if (!empty($path) && file_exists('./' . $path)) {
				$path = ('./' . $path);
			}

			return $path;
		}

		/**
		 * Нормализует значение настройки "Прозрачность"
		 * @param int $alpha новое значение
		 * @return int
		 */
		private function normalizeAlpha($alpha) {
			$alpha = (int) $alpha;
			return ($alpha > 0 && $alpha <= self::MAX_ALPHA_VALUE) ? $alpha : self::MAX_ALPHA_VALUE;
		}
	}
