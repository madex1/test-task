<?php

	/** PHP шаблонизатор. */
	class PhpTemplateEngine {

		/**
		 * @var callable[] $functions
		 *
		 * [
		 *      'function_name' => function()
		 * ]
		 */
		protected $functions = [];

		/**
		 * @var IPhpExtension[] $methods
		 *
		 * [
		 *      'method_name' => IPhpExtension
		 * ]
		 */
		protected $methods = [];

		/**
		 * Добавляет расширение с функциями.
		 * @param IPhpExtension $extension
		 * @return $this
		 */
		public function addExtension(IPhpExtension $extension) {

			if (is_callable([$extension, 'getFunctions']) && is_array($extension->getFunctions())) {
				foreach ($extension->getFunctions() as $functionName => $function) {
					$this->functions[$functionName] = $function;
				}
			}

			$classReflection = new ReflectionClass($extension);
			$methods = $classReflection->getMethods(ReflectionMethod::IS_PUBLIC);

			/** @var ReflectionMethod $method */
			foreach ($methods as $method) {
				if (isset($this->methods[$method->getName()])) {
					continue;
				}

				if ($method->isStatic()) {
					$this->methods[$method->getName()] = get_class($extension);
				} else {
					$this->methods[$method->getName()] = $extension;
				}
			}

			return $this;
		}

		/**
		 * Magic method: вызывает помошник вида.
		 * @param string $name имя помошника вида
		 * @param array $arguments аргументы
		 * @throws RuntimeException если коллекция помощников вида не была внедрена
		 * @return string
		 */
		public function callHelper($name, array $arguments) {
			if (isset($this->functions[$name])) {
				return $this->call($this->functions[$name], $name, $arguments);
			}

			if (isset($this->methods[$name])) {
				$class = $this->methods[$name];
				return $this->call([$class, $name], $name, $arguments);
			}

			throw new RuntimeException(sprintf('Function "%s" does not exist', $name));
		}

		/**
		 * Вызывает метод и возвращает его результат
		 * @param callable $method вызываемый метод
		 * @param string $name имя метода
		 * @param array $arguments аргументы вызова
		 * @return mixed
		 */
		protected function call($method, $name, array $arguments) {
			$start_time = microtime(true);
			$result = call_user_func_array($method, $arguments);
			$executionTime = number_format(microtime(true) - $start_time, 6);

			umiBaseStream::addLineCallLog(
				[$name . ': ' . print_r($arguments, true), $executionTime]
			);

			return $result;
		}
	}
