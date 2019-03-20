<?php

	/** Декоратор отчета "sourcesDomains" */
	class sourcesDomainsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
