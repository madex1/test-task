<?php

	/**
	 * Абстрактный декоратор результата отчета, также
	 * выступает как прокси для результата отчета
	 */
	abstract class xmlDecorator {

		/** @var simpleStat|mixed $decorated отчет по статистике */
		protected $decorated;

		/**
		 * Конструктор
		 * @param simpleStat $object отчет по статистике
		 */
		public function __construct($object) {
			$this->decorated = $object;
		}

		/**
		 * Возвращает результат отчета
		 * @return array
		 */
		public function get() {
			return $this->decorated->get();
		}

		/**
		 * Возвращает xml, сформированный из результатов отчета
		 * @param mixed $array параметры формирования
		 * @return string|mixed
		 */
		abstract protected function generate($array);

		/**
		 * Магический метод, передает
		 * управление отчету
		 * @param string $name имя метода
		 * @param array $args параметры вызова метода
		 * @return mixed
		 */
		public function __call($name, $args) {
			return call_user_func_array([$this->decorated, $name], $args);
		}

		/**
		 * Устанавливает узлу атрибуты
		 * @param DOMElement $node узел
		 * @param array $attributes array(имя атрибута => значение атрибута)
		 */
		protected function bind($node, $attributes) {
			foreach ($attributes as $key => $val) {
				$node->setAttribute($key, $val);
			}
		}

		/**
		 * Формирует плоский результат отчета и
		 * возвращает его в виде xml
		 * @param array $array данные отчета
		 * @return string
		 */
		protected function generateFlat($array) {
			$dom = new DOMDocument('1.0', 'utf-8');
			$element = $dom->createElement('statistic');
			$root = $dom->appendChild($element);

			foreach ($array as $val) {
				$data = $dom->createElement('data');
				$this->bind($data, $val);
				$root->appendChild($data);
			}

			return $dom->saveXML();
		}

		/**
		 * Формирует динамического результат отчета и
		 * возвращает его в виде xml
		 * @param array $array данные отчета
		 * @return string
		 */
		protected function generateDetailDynamic($array) {
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

