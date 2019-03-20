<?php

	/** Декоратор отчета "auditoryVolumeGrowth" */
	class auditoryVolumeGrowthXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
