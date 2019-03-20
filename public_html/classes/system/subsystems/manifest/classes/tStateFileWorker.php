<?php

	/** Трейт работника с файлом состояния */
	trait tStateFileWorker {

		/** @var array $state состояние */
		protected $state = [];

		/** @var string путь до файла */
		protected $statePath;

		/**
		 * Устанавливает путь до файла состояния
		 * @param string $filePath путь до файла
		 * @return $this
		 * @throws Exception
		 */
		public function setStatePath($filePath) {
			if (!is_string($filePath) || empty($filePath)) {
				throw new Exception('Wrong state path given');
			}

			$this->statePath = $filePath;
			return $this;
		}

		/**
		 * Загружает состояние.
		 * Если не удалось загрузить состояние - откатывается на первоначальное состояние
		 * @return $this
		 */
		public function loadState() {
			$stateFilePath = $this->getStatePath();

			if (is_file($stateFilePath)) {
				$packedState = file_get_contents($stateFilePath);
				$state = $this->unpackState($packedState);

				if (is_array($state)) {
					return $this->setState($state);
				}
			}

			return $this->resetState();
		}

		/**
		 * Возвращает параметр состояния
		 * @param string $index название параметра
		 * @return mixed|null
		 */
		protected function getStatePart($index) {
			$state = $this->getState();
			return isset($state[$index]) ? $state[$index] : null;
		}

		/**
		 * Устанавливает параметр состояния
		 * @param string $index название
		 * @param mixed $statePart значение
		 * @return $this
		 */
		protected function setStatePart($index, $statePart) {
			$this->state[$index] = $statePart;
			return $this;
		}

		/**
		 * Возвращает первоначальное состояние
		 * @return array
		 */
		protected function getStartState() {
			return [];
		}

		/**
		 * Возвращает состояние
		 * @return array
		 */
		protected function getState() {
			return $this->state;
		}

		/**
		 * Устанавливает состояние
		 * @param array $state состояние
		 * @return $this
		 */
		protected function setState(array $state) {
			$this->state = $state;
			return $this;
		}

		/**
		 * Сохраняет состояние
		 * @return $this
		 * @throws Exception
		 */
		protected function saveState() {
			$state = $this->getState();
			$packedState = $this->packState($state);
			$stateFilePath = $this->getStatePath();

			$result = file_put_contents($stateFilePath, $packedState);

			if (!$result) {
				throw new Exception("Can'\t save state: {$stateFilePath}");
			}

			return $this;
		}

		/**
		 * Откатывает состояние на первоначальное
		 * @return $this
		 */
		protected function resetState() {
			$startState = $this->getStartState();
			return $this->setState($startState);
		}

		/**
		 * Возвращает путь до файла состояния
		 * @return string
		 * @throws Exception
		 */
		protected function getStatePath() {
			if ($this->statePath === null) {
				throw new Exception('You must set state path');
			}

			return $this->statePath;
		}

		/**
		 * Упаковывает состояние
		 * @param array $state состояние
		 * @return string
		 */
		protected function packState(array $state) {
			return serialize($state);
		}

		/**
		 * Распаковывает состоение
		 * @param string $state запакованное состояние
		 * @return mixed
		 */
		protected function unpackState($state) {
			return unserialize($state);
		}
	}
