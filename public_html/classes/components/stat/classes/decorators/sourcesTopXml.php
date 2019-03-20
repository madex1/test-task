<?php

	/** Декоратор отчета "sourcesTop" */
	class sourcesTopXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
