<?php

	/** Декоратор отчета "openstatSources" */
	class openstatSourcesXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
