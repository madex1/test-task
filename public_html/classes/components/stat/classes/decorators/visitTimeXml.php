<?php

	/** Декоратор отчета "visitTimeX" */
	class visitTimeXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateDetailDynamic($array);
		}
	}
