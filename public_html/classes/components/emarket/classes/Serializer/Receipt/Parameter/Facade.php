<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter;

	/**
	 * Класс абстрактного фасада параметра чека платежной системы
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter
	 */
	abstract class Facade implements iFacade {

		/** @var iRepository $repository репозиторий параметра чека */
		protected $repository;

		/** @inheritdoc */
		public function __construct(iRepository $repository) {
			$this->repository = $repository;
		}

		/**
		 * @inheritdoc
		 * @throws \privateException
		 */
		public function get($id) {
			$parameter = $this->getRepository()
				->load($id);

			$this->validateParameter($parameter);

			return $parameter;
		}

		/**
		 * Валидирует параметр
		 * @param $parameter
		 * @throws \privateException
		 */
		abstract protected function validateParameter($parameter);

		/**
		 * Возвращает параметр чека по его guid
		 * @param string $guid строковый идентификатор параметра
		 * @return iParameter
		 * @throws \privateException
		 */
		protected function getByGuid($guid) {
			$parameter = $this->getRepository()
				->loadByGuid($guid);

			$this->validateParameter($parameter);

			return $parameter;
		}

		/**
		 * Выбрасывает исключение о том что параметр не найден
		 * @throws \privateException
		 */
		protected function throwNotFoundException() {
			throw new \privateException('Check parameter not found');
		}

		/**
		 * Возвращает репозиторий параметра чека
		 * @return iRepository
		 */
		protected function getRepository() {
			return $this->repository;
		}
	}