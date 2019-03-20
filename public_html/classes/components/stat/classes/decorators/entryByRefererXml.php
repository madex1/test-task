<?php

	/** Декоратор отчета "entryByReferer" */
	class entryByRefererXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateDetailDynamic($array);
		}
	}
