<?php

	use UmiCms\Service;

	/** Класс работы с Sphinx API */
	class SphinxSearch {

		/** @var string DEFAULT_BEFORE_MATCH разметка до вхождения по умолчанию */
		const DEFAULT_BEFORE_MATCH = '<strong>';

		/** @var string DEFAULT_AFTER_MATCH разметка после вхождения по умолчанию */
		const DEFAULT_AFTER_MATCH = '</strong>';

		/** @var search $module */
		public $module;

		/** Генерирует базовый View для контента */
		public function generateView() {
			$contentIndex = new SphinxIndexGenerator('sphinx_content_index');
			$this->setIndexType($contentIndex);

			$sql = $contentIndex->generateViewQuery();
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query($sql);

			$config = mainConfiguration::getInstance();
			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			$dir = new umiDirectory($pathToSphinx);

			if (empty($pathToSphinx)) {
				$pathToSphinx = SYS_TEMP_PATH . DIRECTORY_SEPARATOR . 'sphinx';
			}

			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToConfig);

			if (file_exists($pathToConfig)) {
				$fileName = $pathToConfig . 'view.sql';
				file_put_contents($fileName, $sql);
			}
			if ($connection->errorNumber() == 0) {
				$this->sendJson([
					'status' => 'ok',
					'message' => getLabel('build-view-finish')
				]);
			} else {
				$this->sendJson([
					'status' => 'fail',
					'message' => getLabel('build-view-finish-error')
				]);
			}
		}

		/** Генерирует базовый конфиг для Sphinx */
		public function generateSphinxConfig() {
			$config = mainConfiguration::getInstance();

			$mySqlPort = $config->get('connections', 'core.port');
			if (empty($mySqlPort)) {
				$mySqlPort = 3306;
			}

			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			$dir = new umiDirectory($pathToSphinx);
			if (empty($pathToSphinx)) {
				$pathToSphinx = SYS_TEMP_PATH . DIRECTORY_SEPARATOR . 'sphinx';
			}
			$pathToIndex = $pathToSphinx . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToIndex);
			$binlog = $pathToSphinx . DIRECTORY_SEPARATOR . 'log';
			$pathToLog = $pathToSphinx . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToLog);
			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToConfig);

			$contentIndex = new SphinxIndexGenerator('sphinx_content_index');
			$this->setIndexType($contentIndex);

			$configSphinx = $contentIndex->generateSphinxConfig([
				'{mySqlHost}' => $config->get('connections', 'core.host'),
				'{mySqlUser}' => $config->get('connections', 'core.login'),
				'{mySqlPass}' => $config->get('connections', 'core.password'),
				'{mySqlDB}' => $config->get('connections', 'core.dbname'),
				'{mySqlPort}' => $mySqlPort,
				'{pathToIndex}' => $pathToIndex,
				'{listen}' => $config->get('sphinx', 'sphinx.port'),
				'{pathToLog}' => $pathToLog,
				'{binlog}' => $binlog,
			]);

			if (file_exists($pathToConfig)) {
				$fileName = $pathToConfig . 'sphinx.conf';
				file_put_contents($fileName, $configSphinx);
				$this->sendJson([
					'status' => 'ok',
					'message' => getLabel('build-config-sphinx-finish')
				]);
			} else {
				$this->sendJson([
					'status' => 'fail',
					'message' => getLabel('build-config-sphinx-finish-error')
				]);
			}
		}

		/** Создан ли конфиг для Sphinx */
		public function isExistsConfig() {
			$config = mainConfiguration::getInstance();
			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			if (empty($pathToSphinx)) {
				$pathToSphinx = SYS_TEMP_PATH . DIRECTORY_SEPARATOR . 'sphinx';
			}
			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'sphinx.conf';
			$this->sendJson([
				'response' => file_exists($pathToConfig)
			]);
		}

		/**
		 * Отправляет на Sphinx запрос для поиска данных и возвращает результат
		 * @param string $phrase поисковая фраза
		 * @param int $limit ограничение на количество результатов
		 * @param string $index тип выборки
		 * @param SphinxClient $sphinx клиент для работы со Sphinx
		 * @internal param SphinxClient $MAX_MATCHES Максимальное кол-во документов в результате, которое сфинкс держит в
		 *   памяти
		 * @internal param $fieldWeights Веса полей в ранжировании результатов
		 * @return array|void
		 */
		public function findResult($phrase, $limit, $index, $sphinx) {
			$config = mainConfiguration::getInstance();
			define('MAX_MATCHES', 1000);
			define('HIGHLIGHT_INDEX', 'content');

			$fieldWeights = [
				'title' => (int) ($config->get('sphinx', 'sphinx.title') ?: 10),
				'h1' => (int) ($config->get('sphinx', 'sphinx.h1') ?: 10),
				'meta_keywords' => (int) ($config->get('sphinx', 'sphinx.meta_keywords') ?: 5),
				'meta_descriptions' => (int) ($config->get('sphinx', 'sphinx.meta_descriptions') ?: 3),
				'content' => (int) ($config->get('sphinx', 'sphinx.field_content') ?: 1),
				'tags' => (int) ($config->get('sphinx', 'sphinx.tags') ?: 50)
			];

			if (!$phrase) {
				return;
			}

			$sphinx->Open();

			$sphinx->SetSortMode(SPH_SORT_RELEVANCE);
			$sphinx->SetFieldWeights($fieldWeights);

			$sphinx->SetLimits(0, $limit, 1000);

			$event = new umiEventPoint('sphinxExecute');
			$event->setParam('sphinx', $sphinx);
			$event->setMode('before');
			$event->call();

			$sphinx->ResetGroupBy();
			$sphinx->SetFilter(
				'domain_id',
				[
					Service::DomainDetector()->detectId()
				]
			);

			$results = $sphinx->Query($sphinx->EscapeString($phrase), $index);
			$umiHierarchy = umiHierarchy::getInstance();

			if (is_array($results) && array_key_exists('matches', $results)) {
				$pageIds = [];

				foreach ($results['matches'] as $id => $document) {
					$pageIds[] = $id;
				}

				$umiHierarchy->loadElements($pageIds);

				foreach ($results['matches'] as $id => $document) {
					$page = $umiHierarchy->getElement($id);

					if ($page) {
						$results['matches'][$id]['page'] = $page;
					} else {
						unset($results['matches'][$id]);
					}
				}
			}

			return $results;
		}

		/**
		 * Помечает в тексте подсветку вхождение поисковой фразы
		 * @param array $var массив текстов для подсветки
		 * @param string $phrase поисковая фраза
		 * @param string $beforeMatch разметка до вхождения
		 * @param string $afterMatch разметка после вхождения
		 * @param SphinxClient $sphinx
		 * @return mixed
		 */
		public function highlighter($var, $phrase, $sphinx, $beforeMatch = self::DEFAULT_BEFORE_MATCH, $afterMatch = self::DEFAULT_AFTER_MATCH) {
			$res = $sphinx->BuildExcerpts($var, HIGHLIGHT_INDEX, $phrase, [
				'before_match' => $beforeMatch,
				'after_match' => $afterMatch
			]);
			return $res[0];
		}

		/**
		 * Добавляет поля во View
		 * @param SphinxIndexGenerator $contentIndex
		 */
		protected function setIndexType($contentIndex) {
			$types = umiObjectTypesCollection::getInstance();

			$pagesType = $types->getSubTypesList($types->getType('root-pages-type')->getId());

			$indexFields = [
				'title',
				'h1',
				'meta_keywords',
				'meta_descriptions',
				'content',
				'tags',
				'is_unindexed',
				'readme',
				'anons',
				'description',
				'descr',
				'message',
				'question',
				'answers',
			];

			$contentIndex->addPagesList($pagesType, $types, $indexFields);

			$event = new umiEventPoint('sphinxCreateView');
			$event->addRef('contentIndex', $contentIndex);
			$event->setMode('before');
			$event->call();
		}

		/**
		 * Преобразует данные в json и выводит в буффер
		 * @param mixed $data данные
		 * @throws coreException
		 */
		protected function sendJson($data) {
			Service::Response()
				->printJson([
					'result' => $data
				]);
		}
	}
