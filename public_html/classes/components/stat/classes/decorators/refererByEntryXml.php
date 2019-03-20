<?php

	/** Декоратор отчета "refererByEntry" */
	class refererByEntryXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateDetailDynamic($array);
		}
	}
