<?php

	/** Декоратор отчета "sourcesSEOKeywords" */
	class sourcesSEOKeywordsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
