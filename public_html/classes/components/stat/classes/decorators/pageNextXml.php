<?php

	/** Декоратор отчета "pageNext" */
	class pageNextXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
