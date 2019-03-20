<?php

	/** Декоратор отчета "openstatServices" */
	class openstatServicesXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
