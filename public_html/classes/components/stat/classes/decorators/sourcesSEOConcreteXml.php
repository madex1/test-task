<?php

	/** Декоратор отчета "sourcesSEOConcrete" */
	class sourcesSEOConcreteXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
