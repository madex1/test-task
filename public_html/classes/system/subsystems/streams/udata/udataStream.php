<?php

	use UmiCms\Service;

	class udataStream extends umiBaseStream {

		protected $scheme = 'udata';

		/** @inheritdoc */
		public function stream_open($path, $mode, $options, $openedPath) {
			$path = $this->removeHash($path);
			$queryHash = Service::Request()->queryHash();
			$key = $path . $queryHash;
			$cacheFrontend = Service::CacheFrontend();

			$data = $cacheFrontend->loadData($key);
			if ($data) {
				return $this->setData($data);
			}

			$macros = $this->parsePath($path);

			try {
				if (!is_array($data)) {
					$data = $this->executeMacros($macros);

					if ($data === false) {
						$data = [
							'error' => [
								'attribute:code' => 'require-more-permissions',
								'node:message' => getLabel('error-require-more-permissions'),
							],
						];
					}
				}
			} catch (publicException $e) {
				$data = [
					'error' => [
						'attribute:code' => $e->getCode() ?: null,
						'attribute:str-code' => $e->getStrCode() ?: null,
						'node:message' => $e->getMessage(),
					],
				];
				$data = $this->translateToXml(getArrayKey($macros, 'module'), getArrayKey($macros, 'method'), $data);
				return $this->setData($data);
			}

			if ($data === false) {
				return true;
			}

			$data = $this->translateToXml(getArrayKey($macros, 'module'), getArrayKey($macros, 'method'), $data);

			if ($this->expire) {
				$cacheFrontend->saveData($key, $data, $this->expire);
			}

			return $this->setData($data);
		}

		protected function parsePath($path) {
			$path = parent::parsePath($path);
			$path = str_replace(')(', ') (', $path);
			$path = preg_replace_callback(
				"|\(([a-zA-Z0-9%\.\/(_-]{0,}(\([a-zA-Z0-9_-]{0,}\))?[a-zA-Z0-9%\.\/)_-]{0,})\)|",
				function ($m) {
					return umiBaseStream::protectParams($m[1]);
				},
				$path
			);
			$path_arr = explode('/', trim($path, '/'));
			$macros = [];
			$params = [];

			$sz = umiCount($path_arr);
			for ($i = 0; $i < $sz; $i++) {
				$val = $this->normalizeString($path_arr[$i]);

				if ($i == 0) {
					$macros['module'] = $val;
				}

				if ($i == 1) {
					$macros['method'] = $val;
				}

				if ($i > 1) {
					$params[] = umiBaseStream::unprotectParams($val);
				}
			}
			$macros['params'] = $params;

			return $macros;
		}

		/**
		 * Выполняет макрос
		 * @param array $macros
		 * [
		 *     'module' => название модуля
		 *     'method' => название метода
		 *     'params' => параметры метода
		 * ]
		 * @return mixed
		 */
		protected function executeMacros($macros) {
			$moduleName = $macros['module'];
			$isVirtualModule = in_array($moduleName, ['core', 'system', 'custom']);

			if ($isVirtualModule) {
				$module = system_buildin_load($moduleName);
			} else {
				$module = cmsController::getInstance()->getModule($moduleName);
			}

			$methodName = isset($macros['method']) ? $macros['method'] : false;

			if (!($module && $methodName)) {
				return false;
			}

			$isAllowed = true;
			if (!$isVirtualModule) {
				$isAllowed = system_is_allowed($moduleName, $methodName);
			}

			if ($isAllowed) {
				return call_user_func_array([$module, $methodName], $macros['params']);
			}

			return false;
		}

		protected function translateToXml() {
			$args = func_get_args();
			$module = $args[0];
			$method = $args[1];
			$data = $args[2];

			if (is_scalar($data)) {
				$data = ['node:result' => (string) $data];
			}

			$data['@module'] = isset($data['@module']) ? $data['@module'] : $module;
			$data['@method'] = isset($data['@method']) ? $data['@method'] : $method;

			if (Service::Request()->Get()->isExist('show-something')) {
				$data['@queries-count'] = $this->getQueryCount();
			}

			return parent::translateToXml($data);
		}
	}
