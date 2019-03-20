<?php

	/** Декоратор отчета "sourcesDomainsConcrete" */
	class sourcesDomainsConcreteXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
