<?php

	/** Декоратор отчета "auditoryLoyality" */
	class auditoryLoyalityXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			return $this->generateDetailDynamic($array);
		}
	}
