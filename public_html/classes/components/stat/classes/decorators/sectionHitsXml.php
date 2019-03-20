<?php

	/** Декоратор отчета "sectionHits" */
	class sectionHitsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
