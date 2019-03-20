<?php

	/** Декоратор отчета "entryPoints" */
	class entryPointsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
