<?php

	namespace UmiCms\Classes\Components\Stat\Yandex\Metric;

	/**
	 * Класс сериализатора для API "Яндекс.Метрика"
	 * @package UmiCms\Classes\Components\Stat\Yandex\Metric\Serializer
	 */
	class Serializer implements iSerializer {

		/** @var \iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/** @inheritdoc */
		public function __construct(\iDomainsCollection $domainCollection) {
			$this->domainCollection = $domainCollection;
		}

		/** @inheritdoc */
		public function getCounter($name, $domainId) {
			$domain = $this->getDomainCollection()
				->getDomain($domainId);

			if (!$domain instanceof \iDomain) {
				throw new \RuntimeException(sprintf('Incorrect domain id given: "%s"', $domainId));
			}

			$counter = new \stdClass();
			$counter->name = $name;
			$counter->site = $domain->getHost(true);
			$counter->mirrors = $this->getMirrorList($domain);
			$request = new \stdClass();
			$request->counter = $counter;
			return $request;
		}

		/**
		 * Возвращает список хостов зеркал домена
		 * @param \iDomain $domain домен
		 * @return string[]
		 */
		private function getMirrorList(\iDomain $domain) {
			$mirrorList = [];

			foreach ($domain->getMirrorsList() as $mirror) {
				$mirrorList[] = $mirror->getHost(true);
			}

			return $mirrorList;
		}

		/**
		 * Возвращает коллекцию доменов
		 * @return \iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}
	}
