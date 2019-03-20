<?php

	/** Декоратор отчета "visitDeep" */
	class visitDeepXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateDetailDynamic($array);
		}
	}
