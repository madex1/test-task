<?php

	/** Декоратор отчета "auditoryActivity" */
	class auditoryActivityXml extends xmlDecorator {

		/** @inheritdoc */
		protected function generate($array) {
			$dom = new DOMDocument('1.0', 'utf-8');
			$element = $dom->createElement('statistic');
			$root = $dom->appendChild($element);

			$element = $dom->createElement('details');
			$details = $root->appendChild($element);

			foreach ($array['detail'] as $val) {
				$detail = $dom->createElement('detail');
				$this->bind($detail, $val);
				$details->appendChild($detail);
			}

			$element = $dom->createElement('dynamics');
			$dynamics = $root->appendChild($element);

			foreach ($array['dynamic'] as $val) {
				$dynamic = $dom->createElement('dynamic');
				$this->bind($dynamic, $val);

				$dynamics->appendChild($dynamic);
			}

			return $dom->saveXML();
		}
	}
