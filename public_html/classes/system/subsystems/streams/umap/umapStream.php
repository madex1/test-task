<?php

	use UmiCms\Service;

	class umapStream extends umiBaseStream {

		protected $scheme = 'umap';

		public function stream_open($path, $mode, $options, $openedPath) {
			$cacheFrontend = Service::CacheFrontend();
			$path = $this->removeHash($path);
			$path = $this->parsePath($path);

			$data = $cacheFrontend->loadData($path);
			if ($data) {
				return $this->setData($data);
			}

			if (!$path) {
				return $this->setDataError('not-found');
			}

			$matches = new matches();
			$matches->setCurrentURI($path);
			$data = $matches->execute(false);

			if ($this->expire) {
				$cacheFrontend->saveData($path, $data, $this->expire);
			}

			return $this->setData($data);
		}

		protected function parsePath($path) {
			$path = parent::parsePath($path);
			$this->path = $path ?: false;
			return $this->path;
		}
	}
