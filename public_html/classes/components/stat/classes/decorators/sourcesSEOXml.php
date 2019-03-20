<?php

	/** Декоратор отчета "sourcesSEO" */
	class sourcesSEOXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
