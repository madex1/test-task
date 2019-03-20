<?php

	/** Декоратор отчета "openstatCampaigns" */
	class openstatCampaignsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
