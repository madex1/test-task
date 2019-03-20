<?php

	/** Декоратор отчета "openstatAds" */
	class openstatAdsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
