<?php

	namespace UmiCms\Classes\Components\Seo\Yandex;

	use UmiCms\Classes\Components\Seo\Yandex\WebMaster\iClient;
	use UmiCms\Classes\System\Utils\Api\Http\Exception;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Exception\BadToken;
	use UmiCms\Service;
	use UmiCms\System\Request\iFacade as iRequest;

	/**
	 * Класс административного функционала интеграции с Яндекс.Вебмастер
	 * @package UmiCms\Classes\Components\Seo\Yandex
	 */
	class Admin implements \iModulePart {

		use \tModulePart;

		/** @var \SeoAdmin $admin экземпляр главного класса административного функционала */
		private $admin;

		/** @var iRequest $request фасад запроса */
		private $request;

		/**
		 * Конструктор
		 * @param \seo $module
		 * @throws \coreException
		 */
		public function __construct(\seo $module) {
			if (!$module->isClassImplemented($module::ADMIN_CLASS)) {
				throw new \coreException(
					getLabel('label-error-seo-admin-not-implemented')
				);
			}

			$this->admin = $module->getImplementedInstance($module::ADMIN_CLASS);
			$this->request = Service::Request();
		}

		/**
		 * Выводит данные для вкладки "Яндекс.Вебмастер": список сайтов.
		 *
		 * [
		 *      # => [
		 *          'id' => идентификатор сайта
		 *          'name' => название сайта
		 *          'status' => статус сайта (@see константы label-yandex-status-*)
		 *          'status_code' => код статуса сайта
		 *          'verify_status' => статус подтверждения прав на сайт
		 *          'verify_code' => код статуса подтверждения
		 *          'tic' => тИЦ
		 *          'downloaded_count' => количество страниц, загруженных роботом Яндекса
		 *          'excluded_count' => количество исключенных страниц
		 *          'searchable_count' => количество страниц в поиске
		 *          'problems_count' => количество найденных на сайте проблем
		 *      ]
		 * ]
		 *
		 * @throws \publicAdminException
		 */
		public function webmaster() {
			try {
				$siteList = $this->getWebMaster()
					->getSiteList(); // вызывается сразу, чтобы видеть ошибки на странице вкладки
			} catch (BadToken $exception) {
				throw new \publicAdminException(
					getLabel('label-error-yandex-web-master-invalid-token', false, $this->module->pre_lang)
				);
			}

			if ($this->module->ifNotJsonMode()) {
				$this->admin->setDataSetDirectCallMessage();
				return;
			}

			$addedSiteInfoList = [];
			$domainCollection = Service::DomainCollection();

			foreach ($siteList as $site) {
				$domainId = $domainCollection->getDomainIdByUrl($site['unicode_host_url']);

				if (!$domainId) {
					$domainId = $domainCollection->getDomainIdByUrl($site['ascii_host_url']);
				}

				if ($domainId) {
					$addedSiteInfoList[$domainId] = $this->getAddedSiteShortInfo($site['host_id']);
				}
			}

			/** @var \iDomain[] $notAddedDomainIdList */
			$notAddedDomainIdList = array_diff_key($domainCollection->getList(), $addedSiteInfoList);
			$notAddedSiteInfoList = [];

			foreach ($notAddedDomainIdList as $id => $domain) {
				$notAddedSiteInfoList[$id] = $this->getNotAddedSiteShortInfo($domain);
			}

			$siteInfoList = array_merge($addedSiteInfoList, $notAddedSiteInfoList);

			$this->module->printJson(
				$this->admin->prepareTableControlEntities($siteInfoList, umiCount($siteInfoList))
			);
		}

		/** Возвращает конфиг вкладки "Яндекс.Вебмастер" в формате JSON для табличного контрола */
		public function flushSiteListConfig() {
			$this->module->printJson($this->getSiteListConfig());
		}

		/**
		 * Возвращает полную информацию о сайте, добавленном в Яндекс.Вебмастер
		 * @param string|null $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return array
		 *
		 * [
		 *      'data' => [
		 *          '@site_id' => 'Идентификатор сайта',
		 *          'nodes:section' => [
		 *              0 => [
		 *                  [
		 *                      '@label' => 'Название секции'
		 *                      'nodes:history' => [
		 *                          0 => [
		 *                              [
		 *                                  '@id' => 'Идентификатор блока истории',
		 *                                  '@need-to-show' => 'Нужно ли показывать блок истории',
		 *                                  '@label' => 'Название блока истории',
		 *                                  'nodes:dataset' => [
		 *                                      0 => [
		 *                                          [
		 *                                              '@id' => 'Идентификатор набора данных',
		 *                                              '@label' => 'Название набора данных',
		 *                                              '@color' => 'Цвет набора данных',
		 *                                              'date_list' => '["2017.08.26", "2017.08.28"]', // список дат
		 *                                              'value_list' => '["5", "6"]', // список показателей
		 *                                          ]
		 *                                      ]
		 *                                  ]
		 *                              ]
		 *                          ]
		 *                      ]
		 *                  ]
		 *              ]
		 *          ]
		 *      ]
		 * ]
		 */
		public function getSiteInfo($siteId = null) {
			$this->admin->setDataType('form');
			$this->admin->setActionType('modify');

			$siteId = $siteId ?: $this->admin->getNumberedParameter(0);
			$webMaster = $this->getWebMaster();

			$data = [
				'@site_id' => $siteId,
				'nodes:section' => [
					[
						'@label' => getLabel('label-yandex-top-popular-queries'),
						'nodes:top' => [
							$this->prepareTop(
								'total_shows',
								getLabel('label-yandex-top-popular-queries-shows'),
								$this->prepareTopDataSet(
									$webMaster->getPopularQueryList($siteId,
										$webMaster::QUERY_ORDER_FIELD_TOTAL_SHOWS,
										$webMaster::QUERY_INDICATOR_TOTAL_SHOWS
									)
								)
							),
							$this->prepareTop(
								'total_clicks',
								getLabel('label-yandex-top-popular-queries-clicks'),
								$this->prepareTopDataSet(
									$webMaster->getPopularQueryList($siteId,
										$webMaster::QUERY_ORDER_FIELD_TOTAL_CLICKS,
										$webMaster::QUERY_INDICATOR_TOTAL_CLICKS
									)
								)
							)
						]
					],
					[
						'@label' => getLabel('label-yandex-indexation-history'),
						'nodes:history' => [
							$this->prepareHistory(
								'searchable_pages',
								getLabel('label-yandex-searchable-pages-history'),
								[
									$this->prepareHistoryDataSet(
										'all', getLabel('label-yandex-all'), $webMaster->getIndexingHistory(
										$siteId, $webMaster::INDEXING_INDICATOR_SEARCHABLE
									)
									),
								]
							),
							$this->prepareHistory(
								'downloaded_pages',
								getLabel('label-yandex-downloaded-pages-history'),
								[
									$this->prepareHistoryDataSet(
										'downloaded_2xx', getLabel('label-yandex-downloaded-with-code-2xx'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_DOWNLOADED_2XX
										)
									),
									$this->prepareHistoryDataSet(
										'downloaded_3xx', getLabel('label-yandex-downloaded-with-code-3xx'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_DOWNLOADED_3XX
										)
									),
									$this->prepareHistoryDataSet(
										'downloaded_4xx', getLabel('label-yandex-downloaded-with-code-4xx'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_DOWNLOADED_4XX
										)
									),
									$this->prepareHistoryDataSet(
										'downloaded_5xx', getLabel('label-yandex-downloaded-with-code-5xx'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_DOWNLOADED_5XX
										)
									),
								]
							),
							$this->prepareHistory(
								'exclude_pages',
								getLabel('label-yandex-excluded-pages-history'),
								[
									$this->prepareHistoryDataSet(
										'by_user', getLabel('label-yandex-excluded-by-user'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_EXCLUDED_DISALLOWED_BY_USER
										)
									),
									$this->prepareHistoryDataSet(
										'site_error', getLabel('label-yandex-excluded-by-site-error'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_EXCLUDED_SITE_ERROR
										)
									),
									$this->prepareHistoryDataSet(
										'not_supported', getLabel('label-yandex-excluded-by-yandex'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_EXCLUDED_NOT_SUPPORTED
										)
									)
								]
							),
							$this->prepareHistory(
								'failed_pages',
								getLabel('label-yandex-not-downloaded-pages-history'),
								[
									$this->prepareHistoryDataSet(
										'all', getLabel('label-yandex-all'),
										$webMaster->getIndexingHistory(
											$siteId, $webMaster::INDEXING_INDICATOR_FAILED_TO_DOWNLOAD
										)
									)
								]
							),
							$this->prepareHistory(
								'tic',
								getLabel('label-yandex-tic-history'),
								[
									$this->prepareHistoryDataSet(
										'all', getLabel('label-yandex-all'), $webMaster->getTicHistory($siteId)
									)
								]
							),
							$this->prepareHistory(
								'external_links_count',
								getLabel('label-yandex-external-links-count-history'),
								[
									$this->prepareHistoryDataSet(
										'all', getLabel('label-yandex-all'),
										$webMaster->getExternalLinksCountHistory($siteId)
									)
								]
							)
						]
					],
					[
						'@label' => getLabel('label-yandex-external-links'),
						'external_link_list' => [
							'@need-to-show' => (int) ($this->getExternalLinkCount($siteId) > 0)
						]
					],
				]
			];

			$this->admin->setData(['data' => $data]);
			$this->admin->doData();
		}

		/**
		 * Возвращает конфиг страницы "Информация о сайте из Яндекс.Вебмастер" в формате JSON для табличного контрола
		 * @param null|string $siteId идентификатор сайта
		 */
		public function flushExternalLinksListConfig($siteId = null) {
			$siteId = $siteId ?: $this->admin->getNumberedParameter(0);
			$this->module->printJson($this->getExternalLinksConfig($siteId));
		}

		/**
		 * Возвращает список внешних ссылок на сайт для табличного контрола
		 * @param null|string $siteId идентификатор сайта
		 */
		public function getExternalLinkList($siteId = null) {
			$siteId = $siteId ?: $this->admin->getNumberedParameter(0);
			$limit = $this->admin->getLimit();
			$offset = $this->admin->getOffset($limit);

			$externalLinkList = $this->getWebMaster()
				->getExternalLinkList($siteId, $offset, $limit);
			$counter = 0;

			foreach ($externalLinkList['links'] as &$link) {
				$link['id'] = $counter;
				$counter++;
			}

			$this->module->printJson(
				$this->admin->prepareTableControlEntities($externalLinkList['links'], $externalLinkList['count'])
			);
		}

		/**
		 * Добавляет сайт в Яндекс.Вебмастер
		 * @param int|null $domainId идентификатор домена, на основе его данных будет добавлен сайт
		 * @throws \publicAdminException
		 */
		public function addSite($domainId = null) {
			$domainId = $domainId ?: $this->request->Post()->get('domain_id');

			$domain = Service::DomainCollection()
				->getDomain($domainId);

			if (!$domain instanceof \iDomain) {
				throw new \publicAdminException(
					getLabel('label-error-domain-not-found-by-id', false, $domainId)
				);
			}

			try {
				$siteId = $this->getWebMaster()
					->addSite($domain->getUrl());
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$this->admin->setData(['site_id' => $siteId]);
			$this->admin->doData();
		}

		/**
		 * Подтверждает права на сайт в Яндекс.Вебмастер
		 * @param string|null $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @throws \publicAdminException
		 */
		public function verifySite($siteId = null) {
			$siteId = $siteId ?: $this->request->Post()->get('site_id');

			try {
				$webMaster = $this->getWebMaster();
				$verificationState = $webMaster->getVerificationState($siteId);
				$verificationId = $verificationState['verification_uin'];

				$this->createVerificationFile($verificationId);

				$result = $webMaster->verifySite($siteId);
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$this->admin->setData($result);
			$this->admin->doData();
		}

		/**
		 * Добавляет карту сайта в Яндекс.Вебмастер
		 * @param string|null $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @throws \publicAdminException
		 */
		public function addSiteMap($siteId = null) {
			$siteId = $siteId ?: $this->request->Post()->get('site_id');

			try {
				$siteMapUrlList = $this->getSiteMapUrlList($siteId);
				$siteMapUrl = array_shift($siteMapUrlList);

				$siteMapId = $this->getWebMaster()
					->addSiteMap($siteId, $siteMapUrl);

				$result = [
					'site_map_id' => $siteMapId
				];
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$this->admin->setData($result);
			$this->admin->doData();
		}

		/**
		 * Удаляет сайт из Яндекс.Вебмастер
		 * @param string|null $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @throws \publicAdminException
		 */
		public function deleteSite($siteId) {
			$siteId = $siteId ?: $this->request->Post()->get('site_id');

			try {
				$success = $this->getWebMaster()
					->deleteSite($siteId);
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$this->admin->setData(['success' => $success]);
			$this->admin->doData();
		}

		/**
		 * Создает файл для подтверждения прав на сайт в Яндекс.Вебмастер
		 * @see https://tech.yandex.ru/webmaster/doc/dg/concepts/verification-docpage/
		 * @param string $verificationId идентификатор подтверждения
		 * @return bool
		 * @throws \publicAdminException
		 */
		private function createVerificationFile($verificationId) {
			$filePath = CURRENT_WORKING_DIR . "/yandex_$verificationId.html";
			$fileContent = <<<HTML
<html>
	   <head>
		  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	   </head>
	   <body>Verification: $verificationId</body>
</html>
HTML;

			$fileCreated = file_put_contents($filePath, $fileContent);

			if (!$fileCreated) {
				throw new \publicAdminException(getLabel('label-error-yandex-create-verify-file'));
			}

			return true;
		}

		/**
		 * Возвращает краткую информацию о сайте, добавленном в Яндекс.Вебмастер
		 * @param string $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return array
		 * @see Admin::webmaster()
		 */
		private function getAddedSiteShortInfo($siteId) {
			$webMaster = $this->getWebMaster();
			$indexationInfo = $webMaster->getIndexationInfo($siteId);

			$url = $indexationInfo['unicode_host_url'];
			$name = $indexationInfo['host_display_name'] ?: $url;
			$status = $indexationInfo['host_data_status'] ?: 'UNDEFINED';

			$statisticInfo = $this->getSiteStatInfo($siteId);
			$verificationInfo = $webMaster->getVerificationState($siteId);

			return [
				'id' => $indexationInfo['host_id'],
				'name' => $name,
				'address' => $url,
				'status' => getLabel('label-yandex-site-status-' . $status),
				'status_code' => $status,
				'verify_status' => getLabel('label-yandex-verify-status-' . $verificationInfo['verification_state']),
				'verify_code' => $verificationInfo['verification_state'],
				'tic' => $statisticInfo['tic'],
				'is_site_map_added' => (int) $this->isSiteMapAdded($siteId),
				'downloaded_count' => $statisticInfo['downloaded_pages_count'],
				'excluded_count' => $statisticInfo['excluded_pages_count'],
				'searchable_count' => $statisticInfo['searchable_pages_count'],
				'problems_count' => array_sum($statisticInfo['site_problems'])
			];
		}

		/**
		 * Возвращает список карт сайта, добавленных клиентом
		 * @param string $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return array
		 *
		 * [
		 *      0 => [
		 *          'sitemap_id' => '3e8bcb20-f257-3409-b01f-b35c42dbd48d',
		 *          'sitemap_url' => 'http://foo.bar/sitemap.xml',
		 *          'added_date' => '2017-10-31T13:40:44.928+03:00'
		 *      ]
		 * ]
		 *
		 * @throws Exception\BadRequest
		 */
		private function getSiteMapList($siteId) {
			$webMaster = $this->getWebMaster();

			try {
				return $webMaster->getAddedSiteMapList($siteId);
			} catch (Exception\BadRequest $exception) {
				$catchableErrorList = [
					$webMaster::ERROR_CODE_HOST_NOT_INDEXED,
					$webMaster::ERROR_CODE_HOST_NOT_VERIFIED,
					$webMaster::ERROR_CODE_HOST_NOT_LOADED
				];

				if (!in_array($exception->getMessage(), $catchableErrorList)) {
					throw $exception;
				}

				return [];
			}
		}

		/**
		 * Возвращает список адресов сайта
		 * @param string $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return array
		 */
		private function getSiteUrlList($siteId) {
			$webMaster = $this->getWebMaster();
			$indexationInfo = $webMaster->getIndexationInfo($siteId);
			return [
				rtrim($indexationInfo['unicode_host_url'], '/'),
				rtrim($indexationInfo['ascii_host_url'], '/')
			];
		}

		/**
		 * Определяет была ли добавлена системна карта сайта в Яндекс.Вебмастер
		 * @param string $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return bool
		 */
		private function isSiteMapAdded($siteId) {
			$siteMapUrlList = $this->getSiteMapUrlList($siteId);

			foreach ($this->getSiteMapList($siteId) as $siteMap) {
				if (in_array($siteMap['sitemap_url'], $siteMapUrlList)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Возвращает список адресов системной карты сайта
		 * @param string $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return string[]
		 */
		private function getSiteMapUrlList($siteId) {
			$urlList = [];

			foreach ($this->getSiteUrlList($siteId) as $url) {
				$urlList[] = $url . '/sitemap.xml';
			}

			return $urlList;
		}

		/**
		 * Возвращает краткую информацию о сайте, не добавленном в Яндекс.Вебмастер
		 * @param \iDomain $domain домен
		 * @return array
		 * @see Admin::webmaster()
		 */
		private function getNotAddedSiteShortInfo(\iDomain $domain) {
			return [
				'id' => $domain->getId(),
				'name' => $domain->getHost(),
				'address' => $domain->getUrl(),
				'status' => getLabel('label-yandex-site-status-NOT_ADDED'),
				'status_code' => 'NOT_ADDED',
				'verify_status' => getLabel('label-yandex-verify-status-NONE'),
				'verify_code' => 'NONE',
				'tic' => getLabel('label-yandex-site-option-null-value'),
				'downloaded_count' => getLabel('label-yandex-site-option-null-value'),
				'excluded_count' => getLabel('label-yandex-site-option-null-value'),
				'searchable_count' => getLabel('label-yandex-site-option-null-value'),
				'problems_count' => getLabel('label-yandex-site-option-null-value'),
				'problem_category_count' => []
			];
		}

		/**
		 * Возвращает статистическую информацию о сайте
		 * @param string $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return array
		 * @see https://tech.yandex.ru/webmaster/doc/dg/reference/host-id-summary-docpage/
		 * @throws Exception\BadRequest
		 * @throws Exception\BadResponse
		 */
		private function getSiteStatInfo($siteId) {
			$webMaster = $this->getWebMaster();
			try {
				return $webMaster->getStatistic($siteId);
			} catch (Exception\BadRequest $exception) {
				$catchableErrorList = [
					$webMaster::ERROR_CODE_HOST_NOT_INDEXED,
					$webMaster::ERROR_CODE_HOST_NOT_VERIFIED,
					$webMaster::ERROR_CODE_HOST_NOT_LOADED
				];

				if (!in_array($exception->getMessage(), $catchableErrorList)) {
					throw $exception;
				}

				return [
					'tic' => getLabel('label-yandex-site-option-null-value'),
					'downloaded_pages_count' => getLabel('label-yandex-site-option-null-value'),
					'excluded_pages_count' => getLabel('label-yandex-site-option-null-value'),
					'searchable_pages_count' => getLabel('label-yandex-site-option-null-value'),
					'site_problems' => []
				];
			}
		}

		/**
		 * Возвращает конфиг вкладки "Яндекс.Вебмастер"
		 * @return array
		 */
		private function getSiteListConfig() {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'seo',
						'type' => 'load',
						'name' => 'webmaster'
					]
				],
				'default' => implode('|', [
					'name[230px]',
					'address[230px]',
					'status[230px]',
					'verify_status[250px]',
					'tic[120px]',
					'is_site_map_added[220px]',
				]),
				'fields' => [
					[
						'name' => 'name',
						'title' => getLabel('label-yandex-site-name'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false',
						'show_edit_page_link' => 'false'
					],
					[
						'name' => 'address',
						'title' => getLabel('label-yandex-site-address'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'status',
						'title' => getLabel('label-yandex-site-index-state'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'verify_status',
						'title' => getLabel('label-yandex-site-verify-state'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'tic',
						'title' => getLabel('label-yandex-site-verify-tic'),
						'type' => 'integer',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'is_site_map_added',
						'title' => getLabel('label-yandex-site-map-added'),
						'type' => 'bool',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'downloaded_count',
						'title' => getLabel('label-yandex-site-downloaded-count'),
						'type' => 'integer',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'excluded_count',
						'title' => getLabel('label-yandex-site-excluded-count'),
						'type' => 'integer',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'searchable_count',
						'title' => getLabel('label-yandex-site-searchable-count'),
						'type' => 'integer',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'problems_count',
						'title' => getLabel('label-yandex-site-problems-count'),
						'type' => 'integer',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					]
				]
			];
		}

		/**
		 * Возвращает конфиг страницы "Информация о сайте из Яндекс.Вебмастер"
		 * @param string $siteId идентификатор сайта
		 * @return array
		 */
		private function getExternalLinksConfig($siteId) {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'seo',
						'type' => 'load',
						'name' => 'getExternalLinkList/' . $siteId
					]
				],
				'default' =>
					'destination_url[500px]|source_url[500px]',
				'fields' => [
					[
						'name' => 'destination_url',
						'title' => getLabel('label-yandex-destination-url'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false',
						'show_edit_page_link' => 'false'
					],
					[
						'name' => 'source_url',
						'title' => getLabel('label-yandex-source-url'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false',
					],
					[
						'name' => 'discovery_date',
						'title' => getLabel('label-yandex-discovery-date'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'source_last_access_date',
						'title' => getLabel('label-yandex-source-last-access-date'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false',
					]
				]
			];
		}

		/**
		 * Формирует блок топа популярных запросов
		 * @param string $id идентификатор блока
		 * @param string $label название блока
		 * @param array $dataSetList список наборов данных, @see self::prepareTopDataSet()
		 * @return array
		 */
		private function prepareTop($id, $label, array $dataSetList) {
			$isEmptyDataSet = empty($dataSetList);

			return [
				'@id' => $id,
				'@need-to-show' => (int) !$isEmptyDataSet,
				'@label' => $label,
				'nodes:dataset' => $dataSetList
			];
		}

		/**
		 * Формирует набор данных из топа популярных запросов
		 * @param array $top топ популярных запросов
		 *
		 * [
		 *      # => [
		 *          'query_text' => текст запроса
		 *          'indicators' => [
		 *              НАЗВАНИЕ_ИНДИКАТОРА => значение
		 *          ]
		 *      ]
		 * ]
		 *
		 * @return array
		 */
		private function prepareTopDataSet(array $top) {
			$top5 = array_slice($top['queries'], 0, 5);
			$dataSet = [];

			foreach ($top5 as $position => $indicator) {
				$value = array_shift($indicator['indicators']);

				if ($value == 0) {
					continue;
				}

				$dataSet[] = [
					'@id' => $position,
					'@label' => $indicator['query_text'],
					'@color' => $this->getColorByTopDataSet($position),
					'value_list' => '[' . $value . ']'
				];
			}

			return $dataSet;
		}

		/**
		 * Определяет цвет набора данных топа по номеру позиции в топе
		 * @param int $position номер позиции в топе
		 * @return string
		 */
		private function getColorByTopDataSet($position) {
			switch ($position) {
				case 0: {
					return 'rgb(153, 102, 255)';
				}
				case 1: {
					return 'rgb(255, 205, 86)';
				}
				case 2: {
					return 'rgb(75, 192, 192)';
				}
				case 3: {
					return 'rgb(54, 162, 235)';
				}
				case 4: {
					return 'rgb(255, 99, 132)';
				}
				default: {
					return 'rgb(255, 99, 132)';
				}
			}
		}

		/**
		 * Формирует блок истории изменения связанных параметров
		 * @param string $id идентификатор блока
		 * @param string $label название блока
		 * @param array $dataSetList список наборов данных, @see self::prepareHistoryDataSet()
		 * @return array
		 */
		private function prepareHistory($id, $label, array $dataSetList) {
			$firstDataSet = $dataSetList[0];
			$isEmptyDataSet = (empty($firstDataSet['date_list']) || empty($firstDataSet['value_list']));

			return [
				'@id' => $id,
				'@need-to-show' => (int) !$isEmptyDataSet,
				'@label' => $label,
				'@x-label' => getLabel('label-yandex-date'),
				'@y-label' => getLabel('label-yandex-value'),
				'nodes:dataset' => $dataSetList
			];
		}

		/**
		 * Формирует набор данных из истории
		 * @param string $id идентификатор набора
		 * @param string $label название набора
		 * @param array $history история изменения показателя
		 *
		 * [
		 *      # => [
		 *          'date' => дата в формате Atom,
		 *          'value' => значение показателя,
		 *      ]
		 * ]
		 *
		 * @return array
		 */
		private function prepareHistoryDataSet($id, $label, array $history) {
			return [
				'@label' => $label,
				'@color' => $this->getColorByHistoryDataSet($id),
				'date_list' => $this->getDateList($history),
				'value_list' => $this->getValueList($history),
			];
		}

		/**
		 * Определяет цвет набора данных истории по его id
		 * @param string $id идентификатор набора
		 * @return string
		 */
		private function getColorByHistoryDataSet($id) {
			switch ($id) {
				case 'downloaded_2xx': {
					return 'rgb(153, 102, 255)';
				}
				case 'downloaded_3xx': {
					return 'rgb(255, 205, 86)';
				}
				case 'downloaded_4xx': {
					return 'rgb(75, 192, 192)';
				}
				case 'downloaded_5xx': {
					return 'rgb(54, 162, 235)';
				}
				case 'by_user': {
					return 'rgb(153, 102, 255)';
				}
				case 'not_supported': {
					return 'rgb(255, 205, 86)';
				}
				case 'site_error': {
					return 'rgb(75, 192, 192)';
				}
				default : {
					return 'rgb(255, 99, 132)';
				}
			}
		}

		/**
		 * Формирует набор дат
		 * @param array $history история изменения показателя
		 *
		 * [
		 *      # => [
		 *          'date' => дата в формате Atom,
		 *          'value' => значение показателя,
		 *      ]
		 * ]
		 *
		 * @return string
		 *
		 * ["2017.09.07", "2017.09.08", "2017.09.09"]
		 */
		private function getDateList(array $history) {
			$dateList = [];

			foreach ($history as $row) {
				$date = new \umiDate();
				$date->setDateByString($row['date']);

				$dateList[] = $date->getFormattedDate('Y.m.d');
			}

			if (empty($dateList)) {
				return '';
			}

			return '["' . implode('", "', $dateList) . '"]';
		}

		/**
		 * Формирует набор показателей
		 * @param array $history история изменения показателя
		 *
		 * [
		 *      # => [
		 *          'date' => дата в формате Atom,
		 *          'value' => значение показателя,
		 *      ]
		 * ]
		 *
		 * @return string
		 *
		 * ["2432", "2432", "138"]
		 */
		private function getValueList(array $history) {
			$valueList = [];

			foreach ($history as $row) {
				$valueList[] = round($row['value']);
			}

			if (empty($valueList)) {
				return '';
			}

			return '["' . implode('", "', $valueList) . '"]';
		}

		/**
		 * Возвращает количество внешних ссылок
		 * @param string $siteId идентификатор сайта в Яндекс.Вебмастер
		 * @return int
		 */
		private function getExternalLinkCount($siteId) {
			$links = $this->getWebMaster()
				->getExternalLinkList($siteId, 0, 1);
			return (int) $links['count'];
		}

		/**
		 * Возвращает клиента сервиса Яндекс.Вебмастер
		 * @return iClient
		 */
		private function getWebMaster() {
			return Service::get('YandexWebmasterClient');
		}
	}
