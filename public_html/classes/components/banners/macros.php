<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class BannersMacros {

		/** @var banners $module */
		public $module;

		/**
		 * Возвращает баннер, соответствующий заданному месту,
		 * с учетом всех возможных настроек.
		 * @param string $placeName имя баннерного места
		 * @param null $marcosId произвольный идентификатор для кеширования протоколов
		 * @param bool $showAll выводить ли все баннеры
		 * @param bool $currentPageId id страницы, где требуется вывести баннер
		 * @return array|mixed|string
		 * @throws selectorException
		 * @throws publicException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function insert($placeName = '', $marcosId = null, $showAll = false, $currentPageId = false) {
			/** @var banners|BannersMacros $module */
			$module = $this->module;

			if (!$currentPageId) {
				$cmsController = cmsController::getInstance();
				$currentPageId = $cmsController->getCurrentElementId();
			}

			if ($module->isStaticCache && $currentPageId === false) {
				return $this->getStaticBannerCall($placeName, $currentPageId);
			}

			$result = '';
			$places = $module->getPlaceId($placeName);

			if (!umiCount($places) || !isset($places[0])) {
				return $result;
			}

			$placeId = $places[0];
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$place = $umiObjectsCollection->getObject($placeId);

			if (!$place instanceof iUmiObject) {
				return $result;
			}

			if (isset(banners::$arrVisibleBanners[$placeId])) {
				return $module->parseBanners(banners::$arrVisibleBanners[$placeId], $showAll);
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$currentPage = $umiHierarchy->getElement($currentPageId);

			if ($currentPage instanceof iUmiHierarchyElement) {
				$pageTags = $currentPage->getValue('tags');
				$currentPageParentsIds = $umiHierarchy->getAllParents($currentPageId);
			} else {
				$currentPageId = false;
				$pageTags = [];
				$currentPageParentsIds = [];
			}

			$bannersList = [];
			$isRandom = (bool) $place->getValue('is_show_rand_banner');
			$banners = $module->getBanners($placeId, $currentPageId, $currentPageParentsIds, $isRandom);

			if (umiCount($banners) == 0) {
				return $result;
			}

			$userCity = $module->getCity();
			$refererTags = $module->getRefererTagsIfNeed();
			$userTags = $module->getUserTags();

			foreach ($banners as $banner) {
				if (!$banner instanceof iUmiObject) {
					continue;
				}
				if (!$module->checkNotAllowedPages($banner, $currentPageId)) {
					continue;
				}
				if (!$module->checkViewCount($banner)) {
					continue;
				}
				$weight = $module->checkTagsAndCalculateWeight($banner, $pageTags, $userTags, $refererTags);
				if (!is_numeric($weight)) {
					continue;
				}
				if (!$module->checkDateExpiration($banner)) {
					continue;
				}
				if (!$module->checkTimeTargeting($banner)) {
					continue;
				}
				if (!$module->checkCityTargeting($banner, $userCity)) {
					continue;
				}
				$bannersList[$banner->getId()] = $weight;
			}

			if (umiCount($bannersList) == 0) {
				return $result;
			}

			if (!$isRandom) {
				arsort($bannersList);
			}

			$bannersList = array_keys($bannersList);

			foreach ($bannersList as $bannerId) {
				banners::$arrVisibleBanners[$placeId][] = $bannerId;
			}

			$result = $module->parseBanners($bannersList, $showAll);
			$umiRegistry = Service::Registry();
			$daysBeforeNotification = (bool) $umiRegistry->get('//modules/banners/days-before-notification');
			$clicksBeforeNotification = (bool) $umiRegistry->get('//modules/banners/clicks-before-notification');

			if (($daysBeforeNotification || $clicksBeforeNotification) &&
				$umiRegistry->get('//modules/banners/last-check-date') < (time() - 3600 * 24)) {
				$module->sendNotification();
			}

			return $result;
		}

		/**
		 * Возвращает несколько банеров, соответствующих месту.
		 * @param string $placeName строковое наименование места для вставки
		 * @param int $count максимальное количество баннеров для отображения
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function multipleFastInsert($placeName, $count = null) {
			/** @var banners|BannersMacros $module */
			$module = $this->module;

			$banners = new selector('objects');
			$banners->types('hierarchy-type')->name('banners', 'banner');
			$banners->where('place')->equals($placeName);
			$banners->where('is_active')->equals(true);
			$banners->order('priority')->desc();
			$banners->option('load-all-props')->value(true);
			$banners->option('no-length', true);
			$banners->limit(0, $count);

			$lines = [];
			foreach ($banners as $banner) {
				if (!$banner instanceof iUmiObject) {
					continue;
				}

				if (!$this->module->checkIfValidParent($banner->view_pages, $banner->not_view_pages)) {
					continue;
				}

				$lines['nodes:banners'][] = $module->renderBanner($banner->getId());
			}

			return banners::parseTemplate([], $lines);
		}

		/**
		 * Возвращает макрос, соответствующий заданному месту.
		 * Отличается меньшей, по сравнению с banners::insert() функциональностью
		 * и большей производительность.
		 * @param string $placeName наименование места для вставки
		 * @return mixed|string
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function fastInsert($placeName) {
			/** @var banners|BannersMacros $module */
			$module = $this->module;
			$placeId = $module->getPlaceId($placeName);

			if (!is_array($placeId) || !isset($placeId[0])) {
				return '';
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

			if (umiCount($banners) == 0) {
				return;
			}

			foreach ($banners as $banner) {

				if (!$banner instanceof iUmiObject) {
					continue;
				}

				if (!$module->checkIfValidParent($banner->getValue('view_pages'), $banner->getValue('not_view_pages'))) {
					continue;
				}

				$renderedBanner = $module->renderBanner($banner->getId());
				if ($renderedBanner) {
					return $renderedBanner;
				}
			}
		}

		/**
		 * Производит перенаправление по адресу, на который ведет баннер
		 * и инкрементирует количество кликов.
		 * @throws coreException
		 */
		public function go_to() {
			$iObjId = $_REQUEST['param0'];
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$oBanner = $umiObjectsCollection->getObject($iObjId);

			if ($oBanner instanceof iUmiObject) {
				$sUrl = $oBanner->getValue('url');
				$iOldClicksCount = $oBanner->getValue('clicks_count');

				$oBanner->setValue('clicks_count', ++$iOldClicksCount);
				$oBanner->commit();

				$this->module->redirect($sUrl);
			}
		}

		/**
		 * Возвращает html для вывода баннера в режиме статического кеширования.
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
		 * Подготавливает данные баннера к шаблонизации
		 * @param integer $iObjId ид объекта баннера
		 * @return mixed|string
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function renderBanner($iObjId) {
			$block_arr = [];
			$sResult = '';
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$oBanner = $umiObjectsCollection->getObject($iObjId);
			if ($oBanner instanceof iUmiObject) {
				$sBannerType = '';
				if ($oBanner->getValue('swf') !== false) {
					$sBannerType = 'swf';
				}
				if ($oBanner->getValue('image') !== false) {
					$sBannerType = 'image';
				}
				if ($oBanner->getValue('html_content') !== false) {
					$sBannerType = 'html';
				}
				$sUrl = (string) $oBanner->getValue('url');
				$bOpenInNewWindow = $oBanner->getValue('open_in_new_window');
				switch ($sBannerType) {
					case 'swf': {
						$oImgFile = $oBanner->getValue('swf');

						if (!$oImgFile instanceof umiImageFile || $oImgFile->getIsBroken()) {
							break;
						}

						$iWidth = (int) $oBanner->getValue('width');
						$iHeight = (int) $oBanner->getValue('height');

						if ($iWidth <= 0) {
							$iWidth = $oImgFile->getWidth();
						}

						if ($iHeight <= 0) {
							$iHeight = $oImgFile->getHeight();
						}

						$sSwfSrc = $oImgFile->getFilePath(true);
						$sSwfTarget = ($oBanner->getValue('open_in_new_window') ? '_blank' : '_self');
						$sGoLink = $this->module->pre_lang . '/banners/go_to/' . $iObjId;
						$sResult = <<<END
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="$iWidth" height="$iHeight" id="$iObjId" align="middle">
	<param name="allowScriptAccess" value="sameDomain" />
	<param name="movie" value="{$sSwfSrc}?target={$sSwfTarget}&amp;link1={$sGoLink}&amp;link={$sGoLink}" />
	<param name="quality" value="high" /><param name="bgcolor" value="#ffffff" />
	<param name="wmode" value="transparent" />

	<embed src="{$sSwfSrc}?target={$sSwfTarget}&amp;link1={$sGoLink}&amp;link={$sGoLink}" quality="high" bgcolor="#ffffff" width="$iWidth" height="$iHeight" wmode="transparent" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />

</object>
END;
						$banner = [];
						$banner['attribute:type'] = $sBannerType;
						$banner['attribute:width'] = $iWidth;
						$banner['attribute:height'] = $iHeight;
						$banner['attribute:target'] = $sSwfTarget;
						$banner['href'] = $sUrl;
						$banner['source'] = $sSwfSrc;
						$banner['alt'] = $oBanner->getValue('alt');
						$block_arr['banner'] = $banner;
						break;
					}
					case 'image': {
						$oImgFile = $oBanner->getValue('image');

						if (!$oImgFile instanceof umiImageFile || $oImgFile->getIsBroken()) {
							break;
						}

						$iWidth = (int) $oBanner->getValue('width');
						$iHeight = (int) $oBanner->getValue('height');

						if ($iWidth <= 0) {
							$iWidth = $oImgFile->getWidth();
						}

						if ($iHeight <= 0) {
							$iHeight = $oImgFile->getHeight();
						}

						$sBannerImg =
							'<img src="' . $oImgFile->getFilePath(true) . '" border="0" alt="' . $oBanner->getValue('alt') .
							'" width="' . $iWidth . '" height="' . $iHeight . '" />';
						$sResult = $sBannerImg;

						if ($sUrl !== '') {
							$sResult = '<a href="' . $this->module->pre_lang . '/banners/go_to/' . $iObjId . '/" ' .
								($bOpenInNewWindow ? 'target="_blank"' : '') . '>' . $sBannerImg . '</a>';
						}

						$banner = [];
						$banner['attribute:type'] = $sBannerType;
						$banner['attribute:width'] = $iWidth;
						$banner['attribute:height'] = $iHeight;
						$banner['attribute:target'] = ($bOpenInNewWindow ? '_blank' : '');

						$banner['source'] = $oImgFile->getFilePath(true);
						$banner['alt'] = $oBanner->getValue('alt');
						$banner['href'] = $sUrl;
						$block_arr['banner'] = $banner;
						break;
					}
					case 'html': {
						$sResult = $oBanner->getValue('html_content');
						$banner = [];
						$banner['attribute:type'] = $sBannerType;
						$banner['source'] = $sResult;
						$banner['href'] = $sUrl;
						$banner['alt'] = $oBanner->getValue('alt');
						$block_arr['banner'] = $banner;
						$sResult = str_ireplace('%link%', $this->module->pre_lang . '/banners/go_to/' . $iObjId, $sResult);
						break;
					}
				}

				$iOldViewsCount = $oBanner->getValue('views_count') + 1;
				$oBanner->views_count = $iOldViewsCount;
				$this->module->updatedBanners[] = $oBanner;

				if ($this->module->disableUpdateOpt) {
					$this->module->saveUpdates();
				}
			}

			$block_arr['attribute:id'] = $iObjId;

			if (isset($block_arr['banner'])) {
				$block_arr['banner']['xlink:href'] = 'uobject://' . $iObjId;
			}

			$sResult = banners::parseTemplate($sResult, $block_arr, false, $iObjId);
			return $sResult;
		}

		/**
		 * Подготавливает баннеры к шаблонизации.
		 * @param array $bannersIds массив с идентификаторами баннеров
		 * @param bool $showAll отображать ли все баннеры (иначе только один)
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function parseBanners(array $bannersIds, $showAll = false) {
			$bannersIds = array_unique($bannersIds);

			if ($showAll) {
				$banners = [];
				$banners['nodes:banners'] = [];
				foreach ($bannersIds as $bannerId) {
					$banners['nodes:banners'][] = $this->renderBanner($bannerId);
				}
				$result = banners::parseTemplate('', $banners);
			} else {
				$bannerId = array_shift($bannersIds);
				$result = $this->renderBanner($bannerId);
			}

			return $result;
		}

		/**
		 * Возвращает массив тегов, полученных путем парсинга http_referer,
		 * если включен режим игнорирования тегов текущего пользователя.
		 * Иначе возвращает пустой массив.
		 * @return array
		 */
		public function getRefererTagsIfNeed() {
			static $cache = null;

			if ($cache !== null) {
				return $cache;
			}

			$referrerTags = [];
			$cmsController = cmsController::getInstance();
			$httpReferrer = $cmsController->getCalculatedRefererUri();
			$umiRegistry = Service::Registry();
			$notUseUsersTags = (bool) $umiRegistry->get('//modules/banners/not-use-referer-tags');

			if ($httpReferrer && !$notUseUsersTags) {
				return $this->getRefererTags($httpReferrer);
			}

			return $cache = $referrerTags;
		}

		/**
		 * Возвращает массив с тегами текущего пользователя.
		 * Использует модуль "Статистика".
		 * @return array
		 */
		public function getUserTags() {
			static $cache = null;

			if ($cache !== null) {
				return $cache;
			}

			$userTags = [];
			/** @var stat $statsModule */
			$cmsController = cmsController::getInstance();
			$statsModule = $cmsController->getModule('stat');

			if ($statsModule) {
				$userTags = $statsModule->getCurrentUserTags();
			}

			$resultTags = [];
			if (is_array($userTags) && umiCount($userTags) > 0) {
				foreach ($userTags as $key => $value) {
					if (isset($value['tag'])) {
						$resultTags[] = $value['tag'];
					}
				}
			}
			return $cache = $resultTags;
		}

		/**
		 * Возвращает город текущего пользователя, если
		 * его можно определить, иначе - false,
		 * Использует модуль "GeoIP".
		 * @return bool|string
		 */
		public function getCity() {
			$userCityName = false;
			/** @var geoip $geoIpModule */
			$geoIpModule = cmsController::getInstance()
				->getModule('geoip');

			if ($geoIpModule instanceof geoip) {
				$cityByIp = $geoIpModule->getCity();

				try {
					return Service::CityFactory()
						->createByName($cityByIp)
						->getName();
				} catch (\expectObjectException $exception) {
					//nothing
				}
			}

			return $userCityName;
		}

		/**
		 * Отправляет письмо-уведомление о дате и количестве кликов,
		 * оставшихся у баннеров до прекращения показа.
		 * @throws publicException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function sendNotification() {
			$items = [];

			foreach ($this->getBannerList() as $banner) {
				if (!$banner instanceof iUmiObject) {
					continue;
				}

				$bannerData = $this->getBannerData($banner);
				if ($bannerData) {
					$items[] = $bannerData;
				}
			}

			$this->sendNotificationEmail($items);
		}

		/**
		 * Формирует данные для парсинга шаблона баннера в уведомлении
		 * @param iUmiObject $banner баннер
		 * @return mixed
		 * @throws publicException
		 * @throws coreException
		 * @throws ErrorException
		 */
		protected function getBannerData($banner) {
			$umiRegistry = Service::Registry();
			$daysLeft = (int) $umiRegistry->get('//modules/banners/days-before-notification');
			$daysLeft = $daysLeft * 24 * 3600;
			$viewsLeft = (int) $umiRegistry->get('//modules/banners/clicks-before-notification');

			$domain = Service::DomainCollection()->getDefaultDomain();

			if (!$domain instanceof iDomain) {
				return null;
			}

			$host = $domain->getHost();
			list($templateLine) = banners::loadTemplatesForMail('mail/banner_notification', 'item');

			$days = false;
			$views = false;

			$showTillDate = $banner->getValue('show_till_date');
			$showTillDateTimestamp = toTimeStamp($showTillDate);

			if ((int) $showTillDateTimestamp && ((time() + $daysLeft) >= $showTillDateTimestamp)) {
				$days = true;
			}

			$viewsCount = $banner->getValue('views_count');
			$maxViews = $banner->getValue('max_views');

			if ((int) $maxViews && (($viewsCount + $viewsLeft) >= $maxViews)) {
				$views = true;
			}

			if (!$days && !$views) {
				return null;
			}

			$bannerId = $banner->getId();
			$link = getServerProtocol() . '://' . $host . '/admin/banners/edit/' . $bannerId;
			$bannerName = $banner->getName();
			$tillDate = '';

			if ($days && $showTillDate instanceof umiDate) {
				$tillDate =
					' - ' . getLabel('date-of-expiry-banner', 'banners') . ' ' . $showTillDate->getFormattedDate() . '.';
			} elseif ($views) {
				$tillDate = ' - ' . getLabel('remaining-number-of-hits', 'banners') . ' ' . ($maxViews - $viewsCount) . '.';
			}

			$bannerVariables = [
				'link' => $link,
				'bannerName' => $bannerName,
				'tillDate' => $tillDate,
			];

			if ($this->module->isUsingUmiNotifications()) {
				return $bannerVariables;
			}

			return banners::parseTemplateForMail($templateLine, $bannerVariables, false, $bannerId);
		}

		/**
		 * Отправляет уведомление, см. $this->sendNotification()
		 * @param array $bannersData данные баннеров для парсинга тела уведомления
		 * @throws selectorException
		 * @throws Exception
		 */
		protected function sendNotificationEmail(array $bannersData) {
			if (!umiCount($bannersData)) {
				return;
			}

			$subject = null;
			$content = null;

			$variables = [
				'+items' => $bannersData
			];

			$objectList = $this->getBannerList();

			if ($this->module->isUsingUmiNotifications()) {
				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName('notification-banners-expiration-date');

				if ($notification instanceof MailNotification) {
					$subjectTemplate = $notification->getTemplateByName('banners-expiration-date-subject');
					$contentTemplate = $notification->getTemplateByName('banners-expiration-date-content');

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->parse([], $objectList);
						$variables['header'] = $subject;
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->parse($variables, $objectList);
					}
				}
			} else {
				try {
					list($subjectTemplate) = banners::loadTemplatesForMail('mail/banner_notification', 'subject');
					$subject = banners::parseTemplateForMail($subjectTemplate);
					$variables['header'] = $subject;
					list($contentTemplate) = banners::loadTemplatesForMail('mail/banner_notification', 'body');
					$content = banners::parseTemplateForMail($contentTemplate, $variables);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subject === null || $content === null) {
				return;
			}

			$mailSettings = $this->module->getMailSettings();

			$mail = new umiMail();
			$mail->setFrom($mailSettings->getSenderName());
			$mail->addRecipient($mailSettings->getAdminEmail());
			$mail->setPriorityLevel('high');
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();

			Service::Registry()->set('//modules/banners/last-check-date', time());
		}

		/**
		 * Возвращает список баннеров
		 * @return array|int
		 * @throws selectorException
		 */
		protected function getBannerList() {
			$banners = new selector('objects');
			$banners->types('hierarchy-type')->name('banners', 'banner');

			return $banners->result();
		}

		/**
		 * Формирует и возвращает массив тегов из реферера
		 * @param string $referer http_referer
		 * @return array
		 */
		public function getRefererTags($referer) {
			$searchEngines = [
				[
					'name' => 'Mail',
					'pattern' => 'go.mail.ru',
					'start-param' => 'q=',
					'end-param' => '&us'
				],
				[
					'name' => 'Google',
					'pattern' => 'google.',
					'start-param' => 'q=',
					'end-param' => '&'
				],
				[
					'name' => 'Live Search',
					'pattern' => 'search.live.com',
					'start-param' => 'q=',
					'end-param' => '&'
				],
				[
					'name' => 'RapidShare Search Engine',
					'pattern' => 'searchrapidshare',
					'start-param' => 's=',
					'end-param' => "\s"
				],
				[
					'name' => 'Rambler',
					'pattern' => 'rambler.ru',
					'start-param' => 'query=',
					'end-param' => ''
				],
				[
					'name' => 'Yahoo!',
					'pattern' => 'search.yahoo.com',
					'start-param' => 'p=',
					'end-param' => '&'
				],
				[
					'name' => 'Nigma',
					'pattern' => 'nigma.ru',
					'start-param' => 's=',
					'end-param' => '&'
				],
				[
					'name' => 'Ask',
					'pattern' => 'ask.com/web',
					'start-param' => 'q=',
					'end-param' => '&'
				],
				[
					'name' => 'QIP',
					'pattern' => 'search.qip.ru/search',
					'start-param' => 'query=',
					'end-param' => "\s"
				],
				[
					'name' => 'Yandex',
					'pattern' => 'yandex',
					'start-param' => 'text=',
					'end-param' => '&'
				]
			];

			$matches = [];
			$searchArray = [];

			foreach ($searchEngines as $searchEngine) {
				if (contains($referer, $searchEngine['pattern'])) {
					preg_match(
						'/' . $searchEngine['start-param'] . '(.+?)' . $searchEngine['end-param'] . '/',
						$referer,
						$matches
					);
					if (!umiCount($matches)) {
						preg_match('/' . $searchEngine['start-param'] . '(.+?)/', $referer, $matches);
					}
					break;
				}
			}

			if (umiCount($matches) && isset($matches[1])) {
				$searchString = urldecode($matches[1]);
				$encoding = getServer('HTTP_ACCEPT_CHARSET');

				if ($encoding && $encoding != 'utf-8' && $encoding != '*') {
					$searchString = iconv($encoding, 'utf-8', $searchString);
				}

				$searchString = preg_replace("/([^a-zA-Z0-9а-яА-ЯёЁ\s])/u", '', $searchString);
				$searchArray = preg_split("/\s+/", $searchString);
			}

			return $searchArray;
		}

	}
