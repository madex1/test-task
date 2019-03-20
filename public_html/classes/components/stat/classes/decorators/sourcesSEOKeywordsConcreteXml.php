<?php

	/** Декоратор отчета "sourcesSEOKeywordsConcrete" */
	class sourcesSEOKeywordsConcreteXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
