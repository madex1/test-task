<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\UmiSliders;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class UmiSlidersMacros implements iModulePart {

		use tModulePart;

		/**
		 * Возвращает список слайдов слайдера по его кастомному идентификатору (поле "Идентификатор").
		 * Список готов для трансляции шаблонизатора.
		 * @param string $template имя шаблона (только для tpl шаблонизатора)
		 * @param string $sliderCustomId кастомный идентификатор
		 * @return mixed
		 * @throws publicException
		 */
		public function getSlideListBySliderCustomId($template = 'default', $sliderCustomId) {
			if (!is_string($sliderCustomId) || $sliderCustomId === '') {
				throw new publicException(getLabel('label-error-slider-custom-id-expected', $this->getModuleName()));
			}

			$domainId = Service::DomainDetector()->detectId();
			$languageId = Service::LanguageDetector()->detectId();
			/** @var \umiSliders $module */
			$module = $this->getModule();
			$slider = $module->getSlidersCollection()
				->getByCustomIdDomainAndLanguage($sliderCustomId, $domainId, $languageId);

			if (!$slider instanceof UmiSliders\iSlider) {
				$message = getLabel('label-error-slider-with-custom-id-not-exists', $this->getModuleName(), $sliderCustomId);
				throw new publicException($message);
			}

			$slidesCollection = $module->getSlidesCollection();
			/** @var UmiSliders\iSlide[] $slideList */
			$slideList = $slidesCollection->get(
				$this->getQueryParamsForGettingSlides($slider, $slidesCollection->getMap())
			);

			return $this->getSlideListDataForTemplate($template, $slideList, $slider);
		}

		/**
		 * Алиас UmiSlidersMacros::getSlidesBySliderName()
		 * @param string $template
		 * @param string $sliderCustomId
		 * @return mixed
		 * @throws publicException
		 */
		public function getSlideListBySliderName($template = 'default', $sliderCustomId) {
			return $this->getSlidesBySliderName($template, $sliderCustomId);
		}

		/**
		 * Возвращает список слайдов слайдера по его имени.
		 * Список готов для трансляции шаблонизатора.
		 * @param string $template имя шаблона (только для tpl шаблонизатора)
		 * @param string $sliderName имя слайдера
		 * @return mixed
		 * @throws publicException
		 */
		public function getSlidesBySliderName($template = 'default', $sliderName) {
			if (!is_string($sliderName) || $sliderName === '') {
				throw new publicException(getLabel('label-error-slider-name-expected', $this->getModuleName()));
			}

			/** @var \umiSliders $module */
			$module = $this->getModule();
			$slider = $module->getSlidersCollection()
				->getByName($sliderName);

			if (!$slider instanceof UmiSliders\iSlider) {
				$message = getLabel('label-error-slider-with-name-not-exists', $this->getModuleName(), $sliderName);
				throw new publicException($message);
			}

			$slidesCollection = $module->getSlidesCollection();
			/** @var UmiSliders\iSlide[] $slideList */
			$slideList = $slidesCollection->get(
				$this->getQueryParamsForGettingSlides($slider, $slidesCollection->getMap())
			);

			return $this->getSlideListDataForTemplate($template, $slideList, $slider);
		}

		/**
		 * Возвращает структуру запроса на получения списка слайдов
		 * @param UmiSliders\iSlider $slider слайдер, к которому принадлежат слайды
		 * @param iUmiConstantMap $slideConstants константы слайдов
		 * @return array
		 */
		public function getQueryParamsForGettingSlides(UmiSliders\iSlider $slider, iUmiConstantMap $slideConstants) {
			$queryParams = [
				$slideConstants->get('SLIDER_ID_FIELD_NAME') => $slider->getId(),
				$slideConstants->get('IS_ACTIVE_FIELD_NAME') => true,
				$slideConstants->get('ORDER_KEY') => [
					$slideConstants->get('ORDER_FIELD_NAME') => $slideConstants->get('ORDER_DIRECTION_ASC')
				]
			];

			$slideListLimit = (int) $slider->getSlidesCount();

			if ($slideListLimit > 0) {
				$queryParams[$slideConstants->get('LIMIT_KEY')] = $slideListLimit;
			}

			return $queryParams;
		}

		/**
		 * Возвращает структуру данных списка слайдов для трансляции шаблонизатора
		 * @param string $template имя шаблона (только для tpl шаблонизатора)
		 * @param UmiSliders\iSlide[] $slideList список слайдов
		 * @param UmiSliders\iSlider $slider слайдер
		 * @return mixed
		 * @throws publicException
		 */
		protected function getSlideListDataForTemplate($template, array $slideList, UmiSliders\iSlider $slider) {
			if (umiCount($slideList) === 0) {
				$message = getLabel('label-error-there-are-no-slides', $this->getModuleName(), $slider->getName());
				throw new publicException($message);
			}

			if ($slider->isSlidesRandomOrderEnable()) {
				shuffle($slideList);
			}

			/** @var \umiSliders $module */
			$module = $this->getModule();
			list($sliderTemplate, $slideTemplate) = $module::loadTemplates(
				'umiSliders/' . $template,
				'slider',
				'slide'
			);

			$slidesItems = [];

			foreach ($slideList as $slide) {
				$slidesItems[] = $module::parseTemplate($slideTemplate,
					$this->getSlideDataForTemplate($slide)
				);
			}

			$result = [
				'subnodes:slides' => $slidesItems
			];

			$result += $this->getSliderDataForTemplate($slider);

			return $module::parseTemplate(
				$sliderTemplate, $result
			);
		}

		/**
		 * Возвращает структуру данных слайдера для трансляции шаблонизатора
		 * @param UmiSliders\iSlider $slider слайдер
		 * @return array
		 */
		protected function getSliderDataForTemplate(UmiSliders\iSlider $slider) {
			/** @var UmiSliders\iSlider|iUmiConstantMapInjector $slider */
			$constants = $slider->getMap();
			return [
				$constants->get('ID_FIELD_NAME') => $slider->getId(),
				$constants->get('NAME_FIELD_NAME') => $slider->getName(),
				$constants->get('CUSTOM_ID_FIELD_NAME') => $slider->getCustomId(),
				$constants->get('SLIDING_SPEED_FIELD_NAME') => $slider->getSlidingSpeed(),
				$constants->get('SLIDING_DELAY_FIELD_NAME') => $slider->getSlidingDelay(),
				$constants->get('SLIDING_LOOP_ENABLE_FIELD_NAME') => $slider->isSlidingLoopEnable(),
				$constants->get('SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME') => $slider->isSlidingAutoPlayEnable()
			];
		}

		/**
		 * Возвращает структуру данных слайда для трансляции шаблонизатора
		 * @param UmiSliders\iSlide $slide слайд
		 * @return array
		 */
		protected function getSlideDataForTemplate(UmiSliders\iSlide $slide) {
			$tplTemplate = umiTemplater::create('TPL');
			return [
				'@name' => $slide->getName(),
				'@title' => $slide->getTitle(),
				'@image' => $slide->getImagePath(),
				'@text' => $tplTemplate->parse([], $slide->getText()),
				'@link' => $tplTemplate->parse([], $slide->getLink()),
				'@open_in_new_tab' => (int) $slide->isNeedToOpenLinkInNewTab()
			];
		}
	}
