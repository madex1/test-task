<?php

	use UmiCms\Classes\Components\Seo\iRegistry;
	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Статистика".
	 * Модуль отвечает за:
	 *
	 * 1) Сбор статистики;
	 * 2) Формирование отчетов по статистике;
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_statistika/
	 */
	class stat extends def_module {

		/** @var bool $isStatCollected была собрана статистика в рамках текущей сессии */
		private $isStatCollected = false;

		/** @var array $domainArray список доменов системы */
		public $domainArray = [];

		/** @var string $domain текущий домен */
		public $domain = '';

		/** @var null|array $usersArray список идентификаторов пользователей системы */
		public $usersArray;

		/** @var string|int $user идентификатор текущего пользователя */
		public $user = '0';

		/** @var string $mode режим работы системы */
		public $mode = '';

		/** @var int $from_time timestamp нижней границы фильтра */
		public $from_time;

		/** @var int $to_time timestamp верхней границы фильтра */
		public $to_time;

		/** @var int $items_per_page ограничение на количество выводимых позиций отчетов */
		public $items_per_page;

		/** @var bool Флаг работы модуля */
		public $enabled = false;

		/** @const int DEF_ITEMS_PER_PAGE количество элементов на странице по умолчанию */
		const DEF_ITEMS_PER_PAGE = 20;

		/** @const string ADMIN_CLASS имя класса административного функционала */
		const ADMIN_CLASS = 'StatAdmin';

		/** @inheritdoc */
		/** Конструктор */
		public function __construct() {
			parent::__construct();
			$this->initState();

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('yandexMetric');
				$commonTabs->add('total', ['tag']);
				$commonTabs->add('popular_pages', ['sectionHits']);
				$commonTabs->add('visits', [
					'visits_sessions',
					'visits_visitors',
					'auditoryActivity',
					'auditoryLoyality',
					'auditoryLocation',
					'visitDeep',
					'visitTime'
				]);
				$commonTabs->add('sources', [
					'engines',
					'phrases',
					'entryPoints',
					'exitPoints'
				]);
				$commonTabs->add('openstatCampaigns', [
					'openstatServices',
					'openstatSources',
					'openstatAds'
				]);
			}

			$configTabs = $this->getConfigTabs();

			if ($configTabs instanceof iAdminModuleTabs) {
				$configTabs->add('config');
				$configTabs->add('yandex');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('StatAdmin');

			$this->__loadLib('classes/Yandex/ModuleApi/Admin.php');
			$this->__implement('UmiCms\Classes\Components\Stat\Yandex\Admin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('StatCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			if (!$this->enabled) {
				return;
			}

			$this->__loadLib('macros.php');
			$this->__implement('StatMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('StatCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Определяет, включен ли на сайте сбор статистики
		 * @return bool
		 */
		public function isEnabled() {
			return $this->enabled;
		}

		/**
		 * Возвращает реестр модуля
		 * @return iRegistry
		 */
		public function getRegistry() {
			return Service::get('StatRegistry');
		}

		/**
		 * Запускает сбор статистики
		 * @return mixed
		 */
		public function pushStat() {
			$session = Service::Session();

			if (!$session->isExist('old_logged_in_value')) {
				$session->set('old_logged_in_value', false);
			}

			if (!$this->enabled || $this->isStatCollected) {
				return false;
			}

			if (defined('STAT_DISABLE')) {
				if (STAT_DISABLE) {
					return false;
				}
			}

			$this->isStatCollected = true;

			$element_id = cmsController::getInstance()->getCurrentElementId();
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if ($element instanceof iUmiHierarchyElement) {
				$tags = $element->getValue('tags');
			} else {
				return false;
			}

			$stat = new statistic();
			$stat->setReferer(getServer('HTTP_REFERER'));
			$stat->setUri(getServer('REQUEST_URI'));
			$stat->setServerName(getServer('HTTP_HOST') ?: getServer('SERVER_NAME'));
			$stat->setRemoteAddr(getServer('REMOTE_ADDR'));

			$isAuthorized = Service::Auth()->isAuthorized();
			if ($isAuthorized != $session->get('old_logged_in_value')) {
				$stat->doLogin();
			}

			$session->set('old_logged_in_value', $isAuthorized);

			if (is_array($tags)) {
				foreach ($tags as $tag) {
					$stat->event($tag);
				}
			}
			$stat->run();
		}

		/**
		 * Возвращает теги, собранные текущим пользователем
		 * @return mixed
		 */
		public function getCurrentUserTags() {
			if (!$this->enabled) {
				return;
			}

			$statData = Service::Session()->get('stat');
			$statData = is_array($statData) ? $statData : [];

			if (isset($statData['user_id'])) {
				$stat_user_id = $statData['user_id'];
			} else {
				return false;
			}

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var fastUserTagsXml|fastUserTags $report */
			$report = $factory->get('fastUserTags');
			$report->setParams(['user_id' => $stat_user_id]);
			$user_info = $report->get();

			return $user_info['labels'];
		}

		/**
		 * Список идентификатор и логинов всех пользователей системы
		 * @return array|null
		 * @throws coreException
		 */
		public function getUsersList() {
			if (is_array($this->usersArray)) {
				return $this->usersArray;
			}

			$this->usersArray = [];
			$umiObjects = umiObjectsCollection::getInstance();
			$umiObjectsTypes = umiObjectTypesCollection::getInstance();
			$usersHierarchyTypeId = $umiObjectsTypes->getTypeIdByHierarchyTypeName('users', 'user');
			$aUsersList = $umiObjects->getGuidedItems($usersHierarchyTypeId);

			foreach ($aUsersList as $iId => $sLogin) {
				$this->usersArray[$iId] = $sLogin;
			}

			$this->usersArray[0] = getLabel('all');
			return $this->usersArray;
		}

		/**
		 * Формирует дату на русском языке
		 * @param string $_sFormat формат даты для date()
		 * @param int $_iTimeStamp timestamp для которого нужно
		 * сформировать дату
		 * @return string
		 */
		public function makeDate($_sFormat, $_iTimeStamp = -1) {
			$aMonthLong = [
				'Январь',
				'Февраль',
				'Март',
				'Апрель',
				'Май',
				'Июнь',
				'Июль',
				'Август',
				'Сентябрь',
				'Октябрь',
				'Ноябрь',
				'Декабрь'
			];

			$aMonthShort = [
				'Янв',
				'Фев',
				'Мар',
				'Апр',
				'Май',
				'Июнь',
				'Июль',
				'Авг',
				'Сен',
				'Окт',
				'Ноя',
				'Дек'
			];

			if ($_iTimeStamp == -1) {
				$_iTimeStamp = time();
			}

			$iFormatLength = mb_strlen($_sFormat);
			$sDate = '';
			for ($i = 0; $i < $iFormatLength; $i++) {
				switch ($_sFormat[$i]) {
					case 'F': {
						$sDate .= $aMonthLong[(int) date('n', $_iTimeStamp)];
						break;
					}
					case 'M': {
						$sDate .= $aMonthShort[(int) date('n', $_iTimeStamp) - 1];
						break;
					}
					default: {
						$sDate .= date($_sFormat[$i], $_iTimeStamp);
					}
				}
			}
			return $sDate;
		}

		/**
		 * Возвращает данные для формирования облака тегов
		 * из тегов, по которым есть статистика
		 * @return array
		 */
		public function tags_cloud() {
			$max_font_size = 28;
			$min_font_size = 8;

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var allTagsXml|allTags $report */
			$report = $factory->get('allTags');
			$report->setDomain($this->domain);
			$report->setUser($this->user);
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$result = $report->get();

			$max = $result['max'];
			$sum = $result['sum'];
			$lines = [];
			$sz = umiCount($result['labels']);

			for ($i = 0; $i < $sz; $i++) {
				$label = $result['labels'][$i];
				$id = $label['id'];
				$tag = $label['tag'];
				$cnt = $label['cnt'];
				$font_size = ceil(($max_font_size - $min_font_size) * ($cnt / $max)) + $min_font_size;
				$proc = round($cnt * 100 / $sum, 1);
				$lines[] = [
					'attribute:id' => $id,
					'attribute:weight' => $proc,
					'attribute:fontweight' => $font_size,
					'node:name' => $tag
				];
			}
			return !empty($lines)
				? [
					'nodes:tag' => $lines
				]
				: [
					'nodes:message' => [
						[
							'node:name' => getLabel('message-no-tags')
						]
					]
				];
		}

		/**
		 * Возвращает облако тегов из всех тегов,
		 * которые заданы в поля одноименного типа
		 * @throws Exception
		 */
		public function get_tags_cloud() {
			$id = addslashes(getRequest('param0'));

			$existing_tags = isset($_GET['exist']) ? explode(',', $_GET['exist']) : false;

			if ($existing_tags !== false) {
				array_walk($existing_tags, 'trim');
			}

			$max_font_size = 18;
			$min_font_size = 6;

			$umiFieldsTypes = umiFieldTypesCollection::getInstance();
			$fieldWithMultipleValue = true;
			$tagsFieldType = $umiFieldsTypes->getFieldTypeByDataType('tags', $fieldWithMultipleValue);

			$umiFields = umiFieldsCollection::getInstance();
			$tagFields = $umiFields->getFieldIdListByType($tagsFieldType);

			$tagFields = array_map('intval', $tagFields);
			$tagFields = '(' . implode(', ', $tagFields) . ')';

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT varchar_val AS `tag`, COUNT(*) AS `cnt` FROM cms3_object_content WHERE field_id IN {$tagFields} AND varchar_val IS NOT NULL GROUP BY varchar_val;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$tags = [];
			$max = 0;
			$sum = 0;

			foreach ($result as $row) {
				$tags[] = $row;
				$sum += $row['cnt'];

				if ($row['cnt'] > $max) {
					$max = $row['cnt'];
				}
			}

			$lines = [];
			$sz = umiCount($tags);

			for ($i = 0; $i < $sz; $i++) {
				$label = $tags[$i];
				$tag = $label['tag'];
				$cnt = $label['cnt'];
				$font_size = ceil(($max_font_size - $min_font_size) * ($cnt / $max)) + $min_font_size;
				$lines[] =
					"<a href=\"javascript:void(0);\" name=\"{$id}_tag_list_item\" onclick=\"javascript: return window.parent.returnNewTag('{$id}', '{$tag}', this);\" style=\"font-size: {$font_size}pt;\">{$tag}</a>";
			}

			$res = implode(', ', $lines);

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/html');
			$buffer->charset('utf-8');

			$res = <<<HTML
<html>
<head>
<style>
a {
	text-decoration: none;
	color: #0088e8;
}

a.disabledTag {
	text-decoration: none;
	color: #676767;
}

select, input, button { font: 11px Tahoma,Verdana,sans-serif; }
button { width: 70px; }


#buttons {
    margin-top: 1em; border-top: 1px solid #999;
    padding: 2px; text-align: right;
}
</style>

<script>
	function onExit() {
		window.parent.focusTagsInput('{$id}');
		window.parent.Windows.closeAll();
		return false;
	}
    function onLoad() {
        var aTags   = document.getElementsByTagName('a');
        var sExTags = window.parent.document.getElementById('{$id}').value;
        for(i=0; i<aTags.length; i++) {
            if(aTags[i].getAttribute('name') == '{$id}_tag_list_item') {
                var sTagText = "";
                if(aTags[i].text) sTagText = aTags[i].text;
                else              sTagText = aTags[i].innerText;
                if(sExTags.lastIndexOf(sTagText) != -1) {
                    aTags[i].className = 'disabledTag';
                }
            }
        }
    }
</script>
</head>
<body onload="onLoad()">
<table width="100%" height="100%" border="0">
<tr><td valign="middle" align="center">{$res}</td></tr>
</table>
</body>
</html>
HTML;

			$this->flush($res);
		}

		/** Возвращает реферер */
		public function json_get_referer_pages() {
			$this->updateFilter();
			$requestId = (int) $_REQUEST['requestId'];
			$host = getRequest('host');

			if ($host) {
				$_SERVER['HTTP_HOST'] = $host;
			}

			$domain_url = 'http://' . $_SERVER['HTTP_HOST'];
			$referer_uri = str_replace($domain_url, '', $_SERVER['HTTP_REFERER']);

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var pageNextXml|pageNext $report */
			$report = $factory->get('pageNext');
			$report->setStart(time() - 3600 * 24 * 7);
			$report->setFinish(time() + 3600 * 24);

			if (!$referer_uri) {
				$referer_uri = '/';
			}

			$report->setParams([
				'page_uri' => $referer_uri
			]);

			$result = $report->get();

			$res = <<<END
var response = new lLibResponse({$requestId});
response.links = new Array();
END;

			$total = 0;

			foreach ($result as $r_item) {
				$total += (int) $r_item['abs'];
				$res .= <<<END
response.links[response.links.length] = {"uri": "{$r_item['uri']}", "abs": "{$r_item['abs']}"};
END;
			}

			$res .= <<<END
response.total = '{$total}';
END;

			$res .= <<<END
lLib.getInstance().makeResponse(response);
END;

			$this->flush($res);
		}

		/** Обновляет данные фильтров */
		public function updateFilter() {
			$cookieJar = Service::CookieJar();
			try {
				$aParam = [
					'config' => [
						'string:domain' => null,
						'int:user' => null,
						'int:start_day' => null,
						'int:start_month' => null,
						'int:start_year' => null,
						'int:end_day' => null,
						'int:end_month' => null,
						'int:end_year' => null,
					]
				];

				$aParam = baseModuleAdmin::expectedParams($aParam);

				if (in_array($aParam['config']['string:domain'], $this->domainArray) ||
					$aParam['config']['string:domain'] == 'all') {
					$this->domain = $aParam['config']['string:domain'];
					$cookieJar->set('stat_domain', $this->domain);
				} else {
					if ($cookieJar->isExists('stat_domain') &&
						(in_array($cookieJar->get('stat_domain'), $this->domainArray) ||
							$cookieJar->get('stat_domain') == 'all')) {
						$this->domain = $cookieJar->get('stat_domain');
					}
				}

				if (in_array($aParam['config']['int:user'], array_keys($this->getUsersList())) ||
					$aParam['config']['int:user'] == 0) {
					$this->user = $aParam['config']['int:user'];
					$cookieJar->set('stat_user', $this->user);
				} else {
					if ($cookieJar->isExists('stat_user') &&
						(in_array($cookieJar->get('stat_user'), $this->getUsersList()) ||
							$cookieJar->get('stat_user') == 'all')) {
						$this->user = $cookieJar->get('stat_user');
					}
				}

				$fd = (int) $aParam['config']['int:start_day'];
				$fm = (int) $aParam['config']['int:start_month'];
				$fy = (int) $aParam['config']['int:start_year'];
				$this->from_time = (int) strtotime($fy . '-' . $fm . '-' . $fd);
				$cookieJar->set('from_time', $this->from_time);

				$td = (int) $aParam['config']['int:end_day'];
				$tm = (int) $aParam['config']['int:end_month'];
				$ty = (int) $aParam['config']['int:end_year'];

				$this->to_time = (int) strtotime($ty . '-' . $tm . '-' . $td);
				if ($this->to_time < $this->from_time) {
					$this->to_time = strtotime('+1 day', $this->from_time);
				}
				$cookieJar->set('to_time', $this->to_time);
			} catch (Exception $e) {
				if ($cookieJar->isExists('from_time')) {
					$this->from_time = (int) $cookieJar->get('from_time');
				}
				if ($cookieJar->isExists('to_time')) {
					$this->to_time = (int) $cookieJar->get('to_time');
				}
				if ($cookieJar->isExists('stat_domain')) {
					$this->domain =
						(in_array($cookieJar->get('stat_domain'), $this->domainArray) ||
							$cookieJar->get('stat_domain') == 'all')
							? $cookieJar->get('stat_domain') : 'all';
				}
				if (!$this->domain) {
					$this->domain = 'all';
				}
				if ($cookieJar->isExists('stat_user')) {
					$this->user = (in_array($cookieJar->get('stat_user'), array_keys($this->getUsersList())) ||
						$cookieJar->get('stat_user') == 0) ? $cookieJar->get('stat_user') : 0;
				}
				if (!$this->user) {
					$this->user = 0;
				}
			}
		}

		/** Инициализирует свойства класса */
		protected function initState() {
			$umiRegistry = Service::Registry();
			$this->enabled = $umiRegistry->get('//modules/stat/collect');

			if ($this->enabled) {
				$domainId = Service::DomainDetector()->detectId();
				$enabledForDomain = $umiRegistry->get("//modules/stat/collect/{$domainId}");
				if ($enabledForDomain === "0") {
					$this->enabled = false;
				}
			}

			$this->items_per_page = (int) $umiRegistry->get('//modules/stat/items_per_page') ?: self::DEF_ITEMS_PER_PAGE;
			$this->ts = time();
			$this->from_time = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
			$this->to_time = strtotime('+1 day', $this->from_time);
			$this->domain = 'all';

			foreach (Service::DomainCollection()->getList() as $domain) {
				$this->domainArray[$domain->getHost()] = $domain->getHost();
			}

			$this->domainArray['all'] = getLabel('all');
			$this->mode = Service::Request()->mode();
		}
	}
