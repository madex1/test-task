<?php

	/** Декоратор отчета "pagesHits" */
	class pagesHitsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
