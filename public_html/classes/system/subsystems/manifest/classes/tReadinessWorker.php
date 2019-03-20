<?php

	/** Трейт работника с готовностью */
	trait tReadinessWorker {

		/** @var bool $isReady готов или нет */
		protected $isReady = false;

		/**
		 * Определяет готов или нет
		 * @return bool
		 */
		public function isReady() {
			return $this->isReady;
		}

		/**
		 * Устанавливает, что готов
		 * @return $this
		 */
		protected function setIsReady() {
			$this->isReady = true;
			return $this;
		}

		/**
		 * Устанавливает, что не готов
		 * @return $this
		 */
		protected function setIsNotReady() {
			$this->isReady = false;
			return $this;
		}
	}
