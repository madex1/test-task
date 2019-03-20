<?php

	/** Расширение для PHP-шаблонизатора/ */
	interface IPhpExtension {

		/**
		 * Возвращает имя расширения.
		 * @return string
		 */
		public function getName();
	}
