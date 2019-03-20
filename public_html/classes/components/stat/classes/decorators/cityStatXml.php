<?php

	/** Декоратор отчета "cityStat" */
	class cityStatXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateDetailDynamic($array);
		}
	}
