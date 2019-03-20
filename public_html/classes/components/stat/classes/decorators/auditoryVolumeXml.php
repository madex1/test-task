<?php

	/** Декоратор отчета "auditoryVolume" */
	class auditoryVolumeXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
