<?php

	interface iUmiBaseStream {

		/**
		 * Выполняет запрос по протоколу
		 * @link http://php.net/manual/en/streamwrapper.stream-open.php
		 *
		 * @param string $path url запроса
		 * @param string $mode тип доступа к потоку
		 * @param int $options дополнительные флаги
		 * @param string $openedPath путь до открытого ресурса
		 * @return bool
		 */
		public function stream_open($path, $mode, $options, $openedPath);

		public function stream_read($count);

		public function stream_write($data);

		public function stream_tell();

		public function stream_eof();

		public function stream_seek($offset, $whence);

		public function stream_flush();

		public function stream_close();

		public function url_stat();

		public function getProtocol();

		public static function getCalledStreams();
	}
