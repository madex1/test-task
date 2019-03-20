<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils;

	/**
	 * Интерфейс валидатора аргументов
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils
	 */
	interface iArgumentsValidator {

		/** @const string DATE_FORMAT формат даты для сообщения исключения */
		const DATE_FORMAT = 'Y-m-d';

		/** @const string TIME_FORMAT формат времени для сообщения исключения */
		const TIME_FORMAT = 'H:i';

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Проверяет, что значение аргумента является ненулевым числом
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 */
		public static function notZeroNumber($argValue, $argName, $method);

		/**
		 * Проверяет, что значение аргумента является ненулевым числом с точкой
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 */
		public static function notZeroFloat($argValue, $argName, $method);

		/**
		 * Проверяет, что значение аргумента является ненулевым целым числом
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 */
		public static function notZeroInteger($argValue, $argName, $method);

		/**
		 * Проверяет, что дата в первом аргументе раньше заданной даты
		 * @param \iUmiDate $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @param \iUmiDate $date дата, с которой производится сравнение
		 */
		public static function dateLessDate(\iUmiDate $argValue, $argName, $method, \iUmiDate $date);

		/**
		 * Проверяет, что дата в первом аргументе позже заданной даты
		 * @param \iUmiDate $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @param \iUmiDate $date дата, с которой производится сравнение
		 */
		public static function dateGreaterDate(\iUmiDate $argValue, $argName, $method, \iUmiDate $date);

		/**
		 * Проверяет, что значение аргумента является числом с точкой
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @return \wrongParamException
		 */
		public static function float($argValue, $argName, $method);

		/**
		 * Проверяет, что значение аргумента является непустой строкой
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @return \wrongParamException
		 */
		public static function notEmptyString($argValue, $argName, $method);

		/**
		 * Проверяет, что значение аргумента является строкой или null
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @return \wrongParamException
		 */
		public static function stringOrNull($argValue, $argName, $method);

		/**
		 * Проверяет, что значение аргумента является непустой строкой с длинной меньше заданной
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @param int $length максимальная длина строки
		 * @return \wrongParamException
		 */
		public static function notEmptyStringWithLessLength($argValue, $argName, $method, $length);

		/**
		 *  Проверяет, что значение аргумента является строкой с длиной в заданных пределах
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @param int $minLength минимальная длина строки
		 * @param int $maxLength максимальная длина строки
		 * @return mixed
		 */
		public static function stringWithLengthBetween($argValue, $argName, $method, $minLength, $maxLength);

		/**
		 * Проверяет, что значение аргумента является массивом со значением индекса
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @param string $index проверяемы индекс массива
		 * @return \wrongParamException
		 */
		public static function arrayContainsValue($argValue, $argName, $method, $index);

		/**
		 * Проверяет, что значение аргумента является непустым массивом
		 * @param mixed $argValue значение аргумента
		 * @param string $argName имя аргумента
		 * @param string $method метод, в который передавался аргумент
		 * @return \wrongParamException
		 */
		public static function notEmptyArray($argValue, $argName, $method);
	}
