<?php
	class banners extends def_module {
		/* @var array $arrVisibleBanners массив с банерами, доступными для отображения и связанными по месту */
		static $arrVisibleBanners = array();
		/* @var array $updatedBanners массив с банерами, отрисованными в рамках текущей сессии */
		public $updatedBanners = array();

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();
			if($this->cmsController->getCurrentMode() == "admin") {
				$geoIpModule = $this->cmsController->getModule('geoip');
				if ($geoIpModule instanceof def_module) {
					$baseModule = new baseModuleAdmin();
					$baseModule->switchGroupsActivity('city_targeting', true);
				}

				$commonTabs = $this->getCommonTabs();
				if ($commonTabs) {
					$commonTabs->add('lists');
					$commonTabs->add('places');
				}

				$this->__loadLib("__banners.php");
				$this->__implement("__banners_banners");

				$this->__loadLib("__admin.php");
				$this->__implement("__banners_admin");

				$this->__loadLib("__places.php");
				$this->__implement("__places_banners");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__banners_custom_admin");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_banners");

			$this->isStaticCache = (file_exists("./cache.config") || file_exists("banners.config")) ? true : false;
			$this->per_page = 20;
			$this->disableUpdateOpt = (int) $this->mainConfiguration->get('modules', 'banners.disable-update-optimization');
		}

		/** Деструктор */
		public function __destruct() {
			$this->saveUpdates();
		}

		/**
		 * Возващает html для вывода баннера в режиме статического кеширования.
		 * @param string $place имя баннерного места
		 * @param integer $element_id ид текущей страницы
		 * @return string
		 */
		public function getStaticBannerCall($place, $element_id) {
			return <<<JS
<div id="banner_place_{$place}"></div>
<script src="/static_banner.php?place={$place}&current_element_id={$element_id}" type="text/javascript" charset="utf-8"></script>
JS;
		}

		/**
		 * Вставить баннер на страницу, с учетом всех возможных настроек.
		 * @param string $placeName имя баннерного места
		 * @param null $marcosId не используется, оставлено для обратной совместимости
		 * @param bool $showAll выводить ли все баннеры
		 * @param bool $currentPageId id страницы, где требуется вывести баннер
		 * @return array|mixed|string
		 */
		public function insert($placeName = "", $marcosId = null, $showAll = false, $currentPageId = false) {
			if (!$currentPageId) {
				$currentPageId = $this->cmsController->getCurrentElementId();
			}

			if ($this->isStaticCache && $currentPageId === false) {
				return $this->getStaticBannerCall($placeName, $currentPageId);
			}

			$result = '';
			$places = $this->getPlaceId($placeName);
			if (!count($places) || !isset($places[0])) {
				return $result;
			}
			$placeId = $places[0];
			$place = $this->umiObjectsCollection->getObject($placeId);
			if (!$place instanceof umiObject) {
				return $result;
			}
			if (isset(self::$arrVisibleBanners[$placeId])) {
				return $this->parseBanners(self::$arrVisibleBanners[$placeId], $showAll);
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$currentPage = $umiHierarchy->getElement($currentPageId);

			if (!$currentPage instanceof umiHierarchyElement) {
				$currentPageId = false;
				$pageTags = array();
				$currentPageParentsIds = array();
			} else {
				$pageTags = $currentPage->getValue("tags");
				$currentPageParentsIds = $umiHierarchy->getAllParents($currentPageId);
			}

			$bannersList = array();
			$isRandom = (bool) $place->getValue('is_show_rand_banner');
			$banners = $this->getBanners($placeId, $currentPageId, $currentPageParentsIds, $isRandom);

			if (count($banners) == 0) {
				return $result;
			}

			$userCity = $this->getCity();
			$refererTags = $this->getRefererTagsIfNeed();
			$userTags = $this->getUserTags();

			foreach ($banners as $banner) {
				if (!$banner instanceof umiObject) {
					continue;
				}
				if (!$this->checkNotAllowedPages($banner, $currentPageId)) {
					continue;
				}
				if (!$this->checkViewCount($banner)) {
					continue;
				}
				$weight = $this->checkTagsAndCalculateWeight($banner, $pageTags, $userTags, $refererTags);
				if (!is_numeric($weight)) {
					continue;
				}
				if (!$this->checkDateExpiration($banner)) {
					continue;
				}
				if (!$this->checkTimeTargeting($banner)) {
					continue;
				}
				if (!$this->checkCityTargeting($banner, $userCity)) {
					continue;
				}
				$bannersList[$banner->getId()] = $weight;
			}

			if (count($bannersList) == 0) {
				return $result;
			}

			if (!$isRandom) {
				arsort($bannersList);
			}

			$bannersList = array_keys($bannersList);
			foreach ($bannersList as $bannerId) {
				self::$arrVisibleBanners[$placeId][] = $bannerId;
			}

			$result = $this->parseBanners($bannersList, $showAll);
			$daysBeforeNotification = (bool) $this->regedit->getVal("//modules/banners/days-before-notification");
			$clicksBeforeNotification = (bool) $this->regedit->getVal("//modules/banners/clicks-before-notification");

			if (($daysBeforeNotification || $clicksBeforeNotification) && $this->regedit->getVal("//modules/banners/last-check-date") < (time()-3600*24)) {
				$this->sendNotification();
			}

			return $result;
		}

		/**
		 * Возвращает список баннеров, удовлетворяющий параметрам показа на страницах,
		 * активности, дате начала показа и месту.
		 * @param int $placeId идентификатор места показа баннера
		 * @param int $currentPageId ид страницы, на которой требуется показать баннер
		 * @param array $currentPageParentsIds массив идентификаторов страниц, родительских странице с ид $currentPageId
		 * @param bool $isRandom включен ли режим случайного отображения баннеров
		 * @return array
		 */
		protected function getBanners($placeId, $currentPageId, array $currentPageParentsIds, $isRandom = false) {
			$banners = new selector('objects');
			$banners->types('hierarchy-type')->name('banners', 'banner');
			$banners->where("show_start_date")->less(time());
			$banners->where("is_active")->equals(1);
			$banners->where('place')->equals($placeId);
			$banners->option('or-mode')->field('view_pages');
			$banners->where('view_pages')->equals($currentPageId);
			$banners->where('view_pages')->equals($currentPageParentsIds);
			$banners->where('view_pages')->isnull(true);
			$banners->option('no-length')->value(true);
			$banners->option('load-all-props')->value(true);
			if ($isRandom) {
				$banners->order('rand');
			} else {
				$banners->order('id');
			}
			return $banners->result();
		}

		/**
		 * Возвращает массив тегов, полученных путем парсинга http_referer,
		 * если включен режим игнорирования тегов текущего пользователя.
		 * Иначе возвращает пустой массив.
		 * @return array
		 */
		protected function getRefererTagsIfNeed() {
			static $cache = null;

			if (!is_null($cache)) {
				return $cache;
			}

			$refererTags = array();
			$httpReferer = $this->cmsController->getCalculatedRefererUri();
			$notUseUsersTags = (bool) $this->regedit->getVal("//modules/banners/not-use-referer-tags");
			if ($httpReferer && !$notUseUsersTags) {
				return $this->getRefererTags($httpReferer);
			}
			return $cache = $refererTags;
		}

		/**
		 * Проверяет находится ли текущая страница среди страниц, на
		 * которых не нужно показывать баннер.
		 * @param umiObject $banner объект баннера
		 * @param int $currentPageId ид текущей страницы
		 * @return bool
		 */
		protected function checkNotAllowedPages(umiObject $banner, $currentPageId) {
			$notAllowedPages = $banner->getValue('not_view_pages');
			if (!is_array($notAllowedPages)) {
				$notAllowedPages = (array) $notAllowedPages;
			}
			$umiHierarchy = umiHierarchy::getInstance();
			$notAllowedPagesIds = array();
			if (count($notAllowedPages) > 0) {
				/* @var iUmiHierarchyElement $notAllowedPage*/
				foreach ($notAllowedPages as $notAllowedPage) {
					if (!$notAllowedPage instanceof iUmiHierarchyElement) {
						continue;
					}
					$notAllowedPageId = $notAllowedPage->getId();
					$notAllowedPagesIds[] = $notAllowedPageId;
					$umiHierarchy->unloadElement($notAllowedPageId);
				}

			}
			return (in_array((int) $currentPageId, $notAllowedPagesIds)) ? false : true;
		}

		/**
		 * Проверяет сооответствие настроек времени и даты показа баннера
		 * текущему времени.
		 * @param umiObject $banner объект баннера
		 * @return bool
		 */
		protected function checkTimeTargeting(umiObject $banner) {
			$timeTargetingEnabled = $banner->getValue('time_targeting_is_active');
			if (!$timeTargetingEnabled) {
				return true;
			}

			$timeRanges = new ranges();
			$targetingByMonth = $banner->getValue('time_targeting_by_month');
			if (strlen($targetingByMonth)) {
				$months = $timeRanges->get($targetingByMonth, 1);
				if (array_search((int) date("m"), $months) === false) {
					return false;
				}
			}
			$targetingByMonthDays = $banner->getValue('time_targeting_by_month_days');
			if (strlen($targetingByMonthDays)) {
				$monthDays = $timeRanges->get($targetingByMonthDays);
				if (array_search((int) date("d"), $monthDays) === false) {
					return false;
				}
			}
			$targetingByWeekDays = $banner->getValue('time_targeting_by_week_days');
			if (strlen($targetingByWeekDays)) {
				$weekDays = $timeRanges->get($targetingByWeekDays);
				if (array_search((int) date("w"), $weekDays) === false) {
					return false;
				}
			}
			$targetingByHours = $banner->getValue('time_targeting_by_hours');
			if (strlen($targetingByHours)) {
				$hours = $timeRanges->get($targetingByHours);
				if (array_search((int) date("G"), $hours) === false) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Проверяет не истекло ли время показа баннера
		 * @param umiObject $banner объект баннера
		 * @return bool
		 */
		protected function checkDateExpiration(umiObject $banner) {
			$showTillDate = $banner->getValue('show_till_date');
			if ($showTillDate instanceof umiDate && $showTillDate->timestamp) {
				if ($showTillDate->timestamp < $showTillDate->getCurrentTimeStamp()) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Проверяет соотстветствие тегов баннера тегам текущего пользователя,
		 * текущей страницы и тегам, полученным путем разбора реферера.
		 * Если баннер соотвествует, то вычисляется его вес, он же и возвращается.
		 * Если баннер не сооответствует, то возвращается false.
		 * @param umiObject $banner объект баннера
		 * @param array $pageTags массив с тегами текущей страницы
		 * @param array $userTags массив с тегами текущего пользователя
		 * @param array $refererTags массив с тегами, полученными путем разбора реферера
		 * @return bool|int
		 */
		protected function checkTagsAndCalculateWeight(umiObject $banner, array $pageTags, array $userTags, array $refererTags) {
			$weight = 1;
			$bannerPagesTags = $banner->getValue('tags');

			if (is_array($bannerPagesTags) && count($bannerPagesTags) > 0) {
				$commonTags = array_intersect($bannerPagesTags, $pageTags);
				if (count($commonTags) == 0) {
					return false;
				} else {
					$weight += count($commonTags);
				}
			}

			$bannerUserTags = $banner->getValue("user_tags");

			if (!is_array($bannerUserTags) || count($bannerUserTags) == 0) {
				return $weight;
			}

			$allowedTagsCounter = 0;
			foreach ($bannerUserTags as $bannerUserTag) {
				if (in_array($bannerUserTag, $userTags) || in_array($bannerUserTag, $refererTags)) {
					$allowedTagsCounter++;
				}
			}

			if ($allowedTagsCounter === 0) {
				return false;
			}

			return $weight + $allowedTagsCounter;
		}

		/**
		 * Проверяет не закончилось ли у баннера число показов
		 * @param umiObject $banner объект баннера
		 * @return bool
		 */
		protected function checkViewCount(umiObject $banner) {
			$maxViews = (int) $banner->getValue('max_views');
			$viewsCount = (int) $banner->getValue('views_count');
			return ($maxViews <= 0 || $viewsCount <= $maxViews) ? true : false;
		}

		/**
		 * Возвращает массив с тегами текущего пользователя.
		 * Использует модуль "Статистика".
		 * @return array
		 */
		protected function getUserTags() {
			static $cache = null;

			if (!is_null($cache)) {
				return $cache;
			}

			$userTags = array();
			$statsModule = $this->cmsController->getModule("stat");

			if ($statsModule) {
				$userTags = $statsModule->getCurrentUserTags();
			}

			$resultTags = array();
			if (is_array($userTags) && count($userTags) > 0) {
				foreach ($userTags as $key => $value) {
					if (isset($value['tag'])) {
						$resultTags[] = $value["tag"];
					}
				}
			}
			return $cache = $resultTags;
		}

		/**
		 * Возвращает город текущего пользователя, если
		 * его можно определить, иначе - false,
		 * Использует модуль "GeoIP".
		 * @return bool|String
		 */
		protected function getCity() {
			static $cache = null;

			if (!is_null($cache)) {
				return $cache;
			}

			$userCityName = false;
			$geoIpModule = $this->cmsController->getModule("geoip");
			if ($geoIpModule) {
				$info = $geoIpModule->lookupIp(getServer('REMOTE_ADDR'));
				if (isset($info['city'])) {
					$currentCity = $info['city'];
					$userCity = $this->umiObjectsCollection->getObject($currentCity);
					if ($userCity instanceof umiObject) {
						$userCityName = $userCity->getName();
					}
				}
			}
			return $cache = $userCityName;
		}

		/**
		 * Проверяет соответствие города текущего пользователя, городу
		 * показа в баннере.
		 * @param umiObject $banner объект баннера
		 * @param bool|string $userCity город текущего пользователя
		 * @return bool
		 */
		protected function checkCityTargeting(umiObject $banner, $userCity) {
			$cityTargetingEnabled = $banner->getValue('city_targeting_is_active');
			if (!$cityTargetingEnabled || is_bool($userCity)) {
				return true;
			}
			$bannerCity = $banner->getValue("city_targeting_city");
			if (!$bannerCity) {
				return true;
			}
			return ($bannerCity == $userCity) ? true : false;
		}

		/**
		 * Подготавливает баннеры к шаблонизации.
		 * @param array $bannersIds массив с идентификаторами баннеров
		 * @param bool $showAll отображать ли все баннеры (иначе только один)
		 * @return mixed
		 */
		protected function parseBanners(array $bannersIds, $showAll = false) {
			$bannersIds = array_unique($bannersIds);
			if ($showAll) {
				$banners = array();
				$banners['nodes:banners'] = array();
				foreach ($bannersIds as $bannerId) {
					$banners['nodes:banners'][] = self::renderBanner($bannerId);
				}
				$result = def_module::parseTemplate("", $banners);
			} else {
				$bannerId = array_shift($bannersIds);
				$result = self::renderBanner($bannerId);
			}
			return $result;
		}

		/**
		 * Отправляет письма-уведомление о дате и количестве кликов, оставщихся у баннеров
		 * до прекращения показа.
		 * @return void
		 */
		protected function sendNotification() {
			$daysLeft = (int) $this->regedit->getVal("//modules/banners/days-before-notification");
			$daysLeft = $daysLeft*24*3600;
			$viewsLeft = (int) $this->regedit->getVal("//modules/banners/clicks-before-notification");
			$host = '';
			$domain = $this->domainsCollection->getDefaultDomain();
			if ($domain instanceof domain) {
				$host = $domain->getHost();
			}
			list($templateLine) = def_module::loadTemplatesForMail("mail/banner_notification", "item");

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('banners', 'banner');
			$items = array();
			foreach ($sel->result() as $banner) {
				if (!$banner instanceof umiObject) {
					continue;
				}

				$tillDate = toTimeStamp($banner->getValue('show_till_date'));
				$viewsCount = $banner->getValue('views_count');
				$maxViews = $banner->getValue('max_views');

				$days = false;
				$views = false;

				if ((int) $tillDate && ((time() + $daysLeft) >= $tillDate)) $days = true;
				if ((int) $maxViews && (($viewsCount + $viewsLeft) >= $maxViews)) $views = true;

				if ($days || $views) {
					$bannerId = $banner->getId();
					$bannerName = $banner->getName();
					$link = "http://".$host.'/admin/banners/edit/'.$bannerId;
					$itemArr['link'] = $link;
					$itemArr['bannerName'] = $bannerName;

					if ($days) {
						$itemArr['tillDate'] = ' - срок показа истекает ' . $banner->getValue('show_till_date')->getFormattedDate().'.';
					} elseif ($views) {
						$itemArr['tillDate'] = ' - оставшееся количество показов: ' . ($maxViews - $viewsCount ). '.';
					} else {
						$itemArr['tillDate'] ='';
					}

					$items[] = def_module::parseTemplateForMail($templateLine, $itemArr, false, $bannerId);
				}
			}

			if (count($items)) {
				$blockArr = array();
				list($subject, $template) = def_module::loadTemplatesForMail("mail/banner_notification", "subject", "body");

				$mailMessage = new umiMail();
				$from = $this->regedit->getVal("//settings/email_from");
				$mailMessage->setFrom($from);
				$emailTo = $this->regedit->getVal("//settings/admin_email");
				$mailMessage->addRecipient($emailTo);
				$mailMessage->setPriorityLevel("high");
				$subject = def_module::parseTemplateForMail($subject, $blockArr);
				$mailMessage->setSubject($subject);

				$blockArr['header'] = $subject;
				$blockArr['+items'] = $items;

				$content = def_module::parseTemplateForMail($template, $blockArr);

				$mailMessage->setContent($content);
				$mailMessage->commit();
				$mailMessage->send();
				$this->regedit->setVal("//modules/banners/last-check-date", time());
			}
		}

		/**
		 * Подготавливает данные баннера к шаблонизации
		 * @param integer $iObjId ид объекта баннера
		 * @return mixed|string
		 */
		protected function renderBanner($iObjId) {
			$block_arr = Array();
			$sResult = "";
			$oBanner = $this->umiObjectsCollection->getObject($iObjId);
			if ($oBanner instanceof umiObject) {
				$sBannerType = "";
				if ($oBanner->getValue('swf') !== false) $sBannerType="swf";
				if ($oBanner->getValue('image') !== false) $sBannerType="image";
				if ($oBanner->getValue('html_content') !== false) $sBannerType="html";
				$sUrl =  $oBanner->getValue('url');
				$bOpenInNewWindow = $oBanner->getValue('open_in_new_window');
				switch ($sBannerType) {
					case "swf":
								$oImgFile = $oBanner->getValue('swf');
								if ($oImgFile instanceof umiImageFile && !$oImgFile->getIsBroken()) {
									$iWidth =  (int) $oBanner->getValue('width');
									$iHeight = (int) $oBanner->getValue('height');
									if ($iWidth<=0) $iWidth = $oImgFile->getWidth();
									if ($iHeight<=0) $iHeight = $oImgFile->getHeight();
									$sSwfSrc = $oImgFile->getFilePath(true);
									$sSwfTarget = ($oBanner->getValue('open_in_new_window')? "_blank": "_self");
									$sGoLink = $this->pre_lang . "/banners/go_to/" . $iObjId;
									$sResult = <<<END
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="$iWidth" height="$iHeight" id="$iObjId" align="middle">
	<param name="allowScriptAccess" value="sameDomain" />
	<param name="movie" value="{$sSwfSrc}?target={$sSwfTarget}&amp;link1={$sGoLink}&amp;link={$sGoLink}" />
	<param name="quality" value="high" /><param name="bgcolor" value="#ffffff" />
	<param name="wmode" value="transparent" />

	<embed src="{$sSwfSrc}?target={$sSwfTarget}&amp;link1={$sGoLink}&amp;link={$sGoLink}" quality="high" bgcolor="#ffffff" width="$iWidth" height="$iHeight" wmode="transparent" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />

</object>
END;
									$banner = Array();
									$banner['attribute:type'] = $sBannerType;
									$banner['attribute:width'] = $iWidth;
									$banner['attribute:height'] = $iHeight;
									$banner['attribute:target'] = $sSwfTarget;
									$banner['href'] = $sUrl;
									$banner['source'] = $sSwfSrc;
									$banner['alt'] = $oBanner->getValue('alt');
									$block_arr['banner'] = $banner;

								}
								break;
					case "image":
								$oImgFile = $oBanner->getValue('image');
								if ($oImgFile instanceof umiImageFile && !$oImgFile->getIsBroken()) {
									$iWidth =  (int) $oBanner->getValue('width');
									$iHeight = (int) $oBanner->getValue('height');
									if ($iWidth<=0) $iWidth = $oImgFile->getWidth();
									if ($iHeight<=0) $iHeight = $oImgFile->getHeight();
									$sBannerImg = "<img src=\"".$oImgFile->getFilePath(true)."\" border=\"0\" alt=\"".$oBanner->getValue('alt')."\" width=\"".$iWidth."\" height=\"".$iHeight."\" />";
									$sResult = $sBannerImg;
									if (strlen($sUrl)) {
										$sResult = "<a href=\"".$this->pre_lang."/banners/go_to/".$iObjId."/\" ".(($bOpenInNewWindow)? "target=\"_blank\"": "").">".$sBannerImg."</a>";
									}

									$banner = Array();
									$banner['attribute:type'] = $sBannerType;
									$banner['attribute:width'] = $iWidth;
									$banner['attribute:height'] = $iHeight;
									$banner['attribute:target'] = (($bOpenInNewWindow) ? "_blank" : "");

									$banner['source'] = $oImgFile->getFilePath(true);
									$banner['alt'] = $oBanner->getValue('alt');
									$banner['href'] = $sUrl;
									$block_arr['banner'] = $banner;
								}
								break;
					case "html":
								$sResult = $oBanner->getValue('html_content');

								$banner = Array();
								$banner['attribute:type'] = $sBannerType;
								$banner['source'] = $sResult;
								$banner['href'] = $sUrl;
								$banner['alt'] = $oBanner->getValue('alt');
								$block_arr['banner'] = $banner;
								$sResult = str_ireplace("%link%", $this->pre_lang."/banners/go_to/".$iObjId, $sResult);
								break;
					default:
						break;
				}
				$iOldViewsCount = $oBanner->getValue('views_count') + 1;
				$oBanner->views_count = $iOldViewsCount;
				$this->updatedBanners[] = $oBanner;

				if($this->disableUpdateOpt) {
					$this->saveUpdates();
				}
			}

			$block_arr['attribute:id'] = $iObjId;
			if(isset($block_arr['banner'])) {
				$block_arr['banner']['xlink:href'] = "uobject://" . $iObjId;
			}
			$sResult = def_module::parseTemplate($sResult, $block_arr, false, $iObjId);
			return $sResult;
		}

		/**
		 * Формирует и возвращает массив тегов из реферера
		 * @param string $referer http_referer
		 * @return array
		 */
		protected function getRefererTags($referer) {
				
			$searchEngines = array(
				array("name" => "Mail", "pattern" => "go.mail.ru", "start-param" => "q=", "end-param" => "&us" ),
				array("name" => "Google", "pattern" => "google.", "start-param" => "q=", "end-param" => "&"),
				array("name" => "Live Search", "pattern" => "search.live.com", "start-param" => "q=", "end-param" => "&"),
				array("name" => "RapidShare Search Engine", "pattern"=> "searchrapidshare", "start-param" => "s=", "end-param" => "\s"),
				array("name" => "Rambler", "pattern" => "rambler.ru", "start-param" => "query=", "end-param" => ""),
				array("name" => "Yahoo!", "pattern" => "search.yahoo.com", "start-param" => "p=", "end-param" => "&"),
				array("name" => "Nigma", "pattern" => "nigma.ru", "start-param" => "s=", "end-param" => "&"),
				array("name" => "Ask", "pattern" => "ask.com/web", "start-param" =>"q=", "end-param" => "&"),
				array("name" => "QIP", "pattern" => "search.qip.ru/search", "start-param" => "query=", "end-param" => "\s"),
				array("name" => "Яндекс", "pattern" => "yandex", "start-param" => "text=", "end-param" => "&")
			);
			
			$matches = array();
			$searchArray = array();
					
			foreach($searchEngines as $searchEngine) {
				if (strpos($referer, $searchEngine['pattern']) !== false) {					
					preg_match("/" . $searchEngine['start-param'] . "(.+?)" . $searchEngine['end-param'] . "/", $referer, $matches);
					if (!count($matches)) preg_match("/" . $searchEngine['start-param'] . "(.+?)/", $referer, $matches);
					break;
				}
			}

			if (count($matches) && isset($matches[1])) {
				$searchString = urldecode($matches[1]);
				$encoding = getServer('HTTP_ACCEPT_CHARSET');
				if ($encoding && $encoding != "utf-8" && $encoding != "*") $searchString = iconv($encoding, 'utf-8', $searchString);
				$searchString = preg_replace("/([^a-zA-Z0-9а-яА-ЯёЁ\s])/u", "", $searchString);
				$searchArray = preg_split("/\s+/", $searchString);
			}
			
			return $searchArray;

		}

		/**
		 * Отключает активность запрошенных баннеров, если у них истекло максимальное количество просмотров.
		 * @return void
		 */
		protected function saveUpdates() {
			foreach($this->updatedBanners as $i => $banner) {
				if($banner instanceof iUmiObject) {
					if($banner->max_views && ($banner->views_count >= $banner->max_views)) {
						$banner->is_active = false;
					}
					$banner->commit();
					unset($this->updatedBanners[$i]);
				}
			}
		}

		/**
		 * Производит перенаправление по адресу, на который ведет баннер
		 * @return void
		 */
		public function go_to(){
			$iObjId = $_REQUEST['param0'];
			$oBanner = $this->umiObjectsCollection->getObject($iObjId);
			if ($oBanner instanceof umiObject) {
				$sUrl = $oBanner->getValue('url');
				// write stats
				$iOldClicksCount = $oBanner->getValue('clicks_count');
				$oBanner->setValue('clicks_count', ++$iOldClicksCount);
				$oBanner->commit();
				// try redirect
				$this->redirect($sUrl);
			}
		}

		/**
		 * Возвращает ссылку на редактирование объектов модуля "Баннеры"
		 * @param integer $object_id id сущности модуля
		 * @param string $object_type строковой идентификатор типа сущности
		 * @return array|bool
		 */
		public function getEditLink($object_id, $object_type) {
			switch($object_type) {
				case "banner": {
					$link_add = $this->pre_lang . "/admin/banners/banner_add/";
					$link_edit = $this->pre_lang . "/admin/banners/banner_edit/{$object_id}/";
					return array($link_add, $link_edit);
					break;
				}
				default: {
					return false;
				}
			}
		}

		/**
		 * Макрос быстрой вставки баннера на страницу.
		 * Отличается меньшей, по сравнению с banners::insert() функциональностью.
		 * @param string $placeName наименование места для вставки
		 * @return mixed|string|void
		 */
		public function fastInsert($placeName) {
			$placeId = $this->getPlaceId($placeName);
			if (!is_array($placeId) || !isset($placeId[0])) {
				return "";
			}
			$placeId = $placeId[0];
			$banners = new selector('objects');
			$banners->types('hierarchy-type')->name('banners', 'banner');
			$banners->where('place')->equals($placeId);
			$banners->where('is_active')->equals(true);
			$banners->option('no-length')->value(true);
			$banners->option('load-all-props')->value(true);
			$banners->order('rand');
			$banners = $banners->result();

			if (count($banners) == 0) {
				return;
			}

			foreach($banners as $banner) {

				if (!$banner instanceof umiObject) {
					continue;
				}

				if (!$this->checkIfValidParent($banner->getValue('view_pages'), $banner->getValue('not_view_pages'))) {
					continue;
				}

				if ($renderedBanner = $this->renderBanner($banner->getId())) {
					return $renderedBanner;
				}
			}
		}
		
		/**
		 *  Макрос быстрой вставки нескольких баннеров на страницу
		 *  Обладает ограниченное функциональностью - фильтрация осуществляется только по активности 
		 *  и допустимым страницам для размещения.
		 *  @param string $placeName строковое наименование места для вставки
		 *  @param int $count максимальное количество баннеров для отображения
		 *  @return array
		 */
		public function multipleFastInsert($placeName, $count=NULL) {
			
			$banners = new selector('objects');
			$banners->types('hierarchy-type')->name('banners','banner');
			$banners->where('place')->equals($placeName);
			$banners->where('is_active')->equals(true);
			$banners->order('priority')->desc();
			$banners->option('load-all-props')->value(true);
			$banners->limit(0, $count);

			$lines = array();
			foreach( $banners as $banner ) {
				if (!$banner instanceof umiObject) {
					continue;
				}

				if($this->checkIfValidParent($banner->view_pages, $banner->not_view_pages) == false) {
					continue;
				}
				$lines['nodes:banners'][] = self::renderBanner($banner->getId());
			}
			return def_module::parseTemplate(array(), $lines);
		}

		/**
		 * Валидирует текущую страниц и ее родителей по настройкам баннера.
		 * Возращает результат - показывать или нет.
		 * @param array $pages массив с ид страниц, на которых нужно показывать баннер
		 * @param array $notPages  массив с ид страниц, на которых не нужно показывать баннер
		 * @return bool
		 */
		protected function checkIfValidParent($pages, $notPages) {

			$currentPageId = $this->cmsController->getCurrentElementId();
			if (count($notPages)) {
				foreach($notPages as $notPage) {
					if ($notPage->getId() == $currentPageId) return false;
				}
			}

			if(!is_array($pages) || count($pages) == 0) {
				return true;
			}

			$parents = $this->getCurrentParents();

			foreach($pages as $page) {
				if(in_array($page->getId(), $parents)) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Возвращает массив идентификаторов страниц, родительских текущей.
		 * @return Array
		 */
		protected function getCurrentParents() {
			static $parents = false;

			if(is_array($parents)) {
				return $parents;
			}

			$iCurrPageId = $this->cmsController->getCurrentElementId();

			if($iCurrPageId) {
				return $parents = umiHierarchy::getInstance()->getAllParents($iCurrPageId, true);
			} else {
				return Array();
			}
		}

		/**
		 * Возвращает идентификатор места показа баннера по его названию
		 * @param string $placeName ид места показа баннера
		 * @return array|bool
		 */
		protected function getPlaceId($placeName) {
			static $cache = Array();
			$placeName = (string) $placeName;

			if (isset($cache[$placeName])) {
				return array(0 => (int) $cache[$placeName]);
			}

			$places = new selector('objects');
			$places->types('hierarchy-type')->name('banners', 'place');
			$places->option('no-length')->value(true);
			$places->option('load-all-props')->value(true);
			$places = $places->result();

			if (count($places) == 0) {
				return false;
			}

			foreach ($places as $place) {
				if (!$place instanceof umiObject) {
					continue;
				}
				$cache[$place->getName()] = $place->getId();
			}

			if (isset($cache[$placeName])) {
				return array(0 => (int) $cache[$placeName]);
			}

			return false;
		}

		/**
		 * Возвращает ссылку на редактирование баннера
		 * @param integer $objectId ид баннера
		 * @return string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . "/admin/banners/edit/" . $objectId . "/";
		}
	};
?>