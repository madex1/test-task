<?php
	abstract class __cache_config extends baseModuleAdmin {

		public function cache() {
			$settings = self::getStaticCacheSettings();
			$streamsSettings = self::getStreamsCacheSettings();

			$cacheFrontend = \UmiCms\Service::CacheFrontend();
			$enginesList = $cacheFrontend->getCacheEngineList();
			$currentEngineName = $cacheFrontend->getCacheEngineName();

			$engines = array(getLabel('cache-engine-none'));
			foreach($enginesList as $engineName) {
				$engines[$engineName] = getLabel("cache-engine-" . $engineName);
			}

			$engines['value'] = $currentEngineName;
			$cacheEngineLabel = $currentEngineName ? getLabel("cache-engine-" . $currentEngineName) : getLabel('cache-engine-none');
			$cacheStatus = ($cacheFrontend->isCacheEnabled()) ? getLabel('cache-engine-on') : getLabel('cache-engine-off');

			$params = array(
				'engine' => array(
					'status:current-engine' => $cacheEngineLabel,
					'status:cache-status' => $cacheStatus,
					'select:engines' => $engines
				),

				'streamscache' => array(
					'boolean:cache-enabled'	=> NULL,
					"int:cache-lifetime"	=> NULL,
				),

				'static' => array(
					'boolean:enabled'	=> NULL,
					'select:expire'		=> Array(
						'short'		=> getLabel('cache-static-short'),
						'normal'	=> getLabel('cache-static-normal'),
						'long'		=> getLabel('cache-static-long')
					)
				),

				'test' => array(

				),
			);

			if($settings['expire'] == false) {
				unset($params['static']['select:expire']);
			}

			if($currentEngineName) {
				$params['engine']['status:reset'] = true;
			}

			if(!$streamsSettings['cache-enabled']) {
				unset($params['streamscache']['int:cache-lifetime']);
			}

			if(!$currentEngineName) {
				unset($params['streamscache']);
			}

			$mode = (string) getRequest('param0');

			$is_demo = isDemoMode();
			if($mode == 'do' and !$is_demo) {
				$params = $this->expectParams($params);

				if(!isset($params['static']['select:expire'])) {
					$params['static']['select:expire'] = "normal";
				}

				$settings = Array(
					'enabled'	=> $params['static']['boolean:enabled'],
					'expire'	=> $params['static']['select:expire'],
				);

				if (isset($params['streamscache']['boolean:cache-enabled'])) {
					$streamsSettings['cache-enabled'] = $params['streamscache']['boolean:cache-enabled'];
				}
				
				if (isset($params['streamscache']['int:cache-lifetime'])) {
					$streamsSettings['cache-lifetime'] = $params['streamscache']['int:cache-lifetime'];
				}

				self::setStaticCacheSettings($settings);
				self::setStreamsCacheSettings($streamsSettings);

				\UmiCms\Service::CacheFrontend()->switchCacheEngine($params['engine']['select:engines']);

				$this->chooseRedirect($this->pre_lang . "/admin/config/cache/");
			} else if ($mode == "reset" ) {
				if(!$is_demo)
					\UmiCms\Service::CacheFrontend()->flush();

				$this->chooseRedirect($this->pre_lang . "/admin/config/cache/");
			}

			$settings = self::getStaticCacheSettings();
			$params['static']['boolean:enabled'] = $settings['enabled'];
			$params['static']['select:expire']['value'] = $settings['expire'];

			if($settings['expire'] == false) {
				unset($params['static']['select:expire']);
			}

			$streamsSettings = self::getStreamsCacheSettings();
			$params['streamscache']['boolean:cache-enabled'] = $streamsSettings['cache-enabled'];
			$params['streamscache']['int:cache-lifetime'] = $streamsSettings['cache-lifetime'];

			if(!$params['streamscache']['boolean:cache-enabled']) {
				unset($params['streamscache']['int:cache-lifetime']);
			}

			if(!$currentEngineName) {
				unset($params['streamscache']);
			}

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}



		public static function getStaticCacheSettings() {
			$config = mainConfiguration::getInstance();
			$enabled = $config->get('cache', 'static.enabled');

			return $enabled ? $settings = array(
				'enabled'		=> $enabled,
				'expire'		=> $config->get('cache', 'static.mode'),
			) : array('enabled' => false, 'expire' => false);
		}


		public static function setStaticCacheSettings($settings) {
			if(!is_array($settings)) return false;
			$config = mainConfiguration::getInstance();

			$config->set('cache', 'static.enabled', getArrayKey($settings, 'enabled'));
			$config->set('cache', 'static.mode', getArrayKey($settings, 'expire'));
			$config->save();
		}

		public static function getStreamsCacheSettings() {
			$config = mainConfiguration::getInstance();
			$enabled = $config->get('cache', 'streams.cache-enabled');

			return $enabled ? $settings = array(
				'cache-enabled'		=> $enabled,
				'cache-lifetime'=> $config->get('cache', 'streams.cache-lifetime')
			) : array('cache-enabled' => false, 'cache-lifetime' => 0);
		}


		public static function setStreamsCacheSettings($settings) {
			if(!is_array($settings)) return false;
			$config = mainConfiguration::getInstance();

			$config->set('cache', 'streams.cache-enabled', getArrayKey($settings, 'cache-enabled'));
			$config->set('cache', 'streams.cache-lifetime', getArrayKey($settings, 'cache-lifetime'));
			$config->save();
		}
	};
?>