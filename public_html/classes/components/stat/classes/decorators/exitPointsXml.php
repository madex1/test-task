<?php

	/** Декоратор отчета "exitPoints" */
	class exitPointsXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateFlat($array);
		}
	}
