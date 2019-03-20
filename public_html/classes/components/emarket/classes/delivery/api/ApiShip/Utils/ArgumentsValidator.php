<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils;

	/**
	 * Валидатор аргументов
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils
	 */
	class ArgumentsValidator implements iArgumentsValidator {

		/** @inheritdoc */
		public static function notZeroNumber($argValue, $argName, $method) {
			if (!is_numeric($argValue) || $argValue == 0) {
				$message = sprintf(getLabel('error-not-zero-numeric', self::I18N_PATH), $argName, $method);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function notZeroFloat($argValue, $argName, $method) {
			if (!is_float($argValue) || $argValue == 0) {
				$message = sprintf(getLabel('error-not-zero-float', self::I18N_PATH), $argName, $method);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function notZeroInteger($argValue, $argName, $method) {
			if (!is_int($argValue) || $argValue == 0) {
				$message = sprintf(getLabel('error-not-zero-integer', self::I18N_PATH), $argName, $method);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function dateLessDate(\iUmiDate $argValue, $argName, $method, \iUmiDate $date) {
			if ($argValue->getDateTimeStamp() > $date->getDateTimeStamp()) {
				$date = $date->getFormattedDate(self::DATE_FORMAT);
				$message = sprintf(
					getLabel('error-date-less-date', self::I18N_PATH), $argName, $method, $date
				);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function dateGreaterDate(\iUmiDate $argValue, $argName, $method, \iUmiDate $date) {
			if ($argValue->getDateTimeStamp() < $date->getDateTimeStamp()) {
				$date = $date->getFormattedDate(self::DATE_FORMAT);
				$message = sprintf(
					getLabel('error-date-greater-date', self::I18N_PATH), $argName, $method, $date
				);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function float($argValue, $argName, $method) {
			if (!is_float($argValue)) {
				$message = sprintf(getLabel('error-integer', self::I18N_PATH), $argName, $method);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function notEmptyString($argValue, $argName, $method) {
			if (!is_string($argValue) || $argValue === '') {
				$message = sprintf(getLabel('error-not-empty-string', self::I18N_PATH), $argName, $method);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function stringOrNull($argValue, $argName, $method) {
			if (!(is_string($argValue) || $argName === null)) {
				$message = sprintf(getLabel('error-not-string-or-null', self::I18N_PATH), $argName, $method);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function notEmptyStringWithLessLength($argValue, $argName, $method, $maxLength) {
			self::stringWithLengthBetween($argValue, $argName, $method, 0, $maxLength);
		}

		/** @inheritdoc */
		public static function stringWithLengthBetween($argValue, $argName, $method, $minLength, $maxLength) {
			if (!(is_string($argValue) && mb_strlen($argValue) >= $minLength && mb_strlen($argValue) < $maxLength)) {
				$messageFormat = getLabel('error-wrong-string-length', self::I18N_PATH);
				$message = sprintf($messageFormat, $argName, $method, $minLength, $maxLength);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function arrayContainsValue($argValue, $argName, $method, $index) {
			if (!(is_array($argValue) && isset($argValue[$index]))) {
				$message = sprintf(getLabel('error-array-value-not-exists', self::I18N_PATH), $argName, $method, $index);
				self::throwException($message);
			}
		}

		/** @inheritdoc */
		public static function notEmptyArray($argValue, $argName, $method) {
			if (!(is_array($argValue) && umiCount($argValue) > 0)) {
				$message = sprintf(getLabel('error-not-empty-array', self::I18N_PATH), $argName, $method);
				self::throwException($message);
			}
		}

		/**
		 * Бросает исключени с заданным сообщеним
		 * @param string $message сообщение
		 * @throws \wrongParamException
		 */
		private static function throwException($message) {
			throw new \wrongParamException($message);
		}
	}
