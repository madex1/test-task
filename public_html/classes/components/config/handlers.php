<?php

	use UmiCms\Service;

	/** Класс обработчиков событий */
	class ConfigHandlers {

		/** @var config $module */
		public $module;

		/**
		 * Обработчик события запуска системного cron'а.
		 * Запускает системный сборщик мусора
		 * @param iUmiEventPoint $e события запуска системного cron'а
		 */
		public function runGarbageCollector(iUmiEventPoint $e) {
			if ($e->getMode() == 'process') {
				try {
					$gc = new garbageCollector();
					$gc->run();
				} catch (maxIterationsExeededException $e) {
					// ignored
				}
			}
		}

		/**
		 * Обработчик событий сохранения изменений объектов и страниц
		 * через административную панель.
		 * Ведет лог изменений.
		 * @param iUmiEventPoint $event событие сохранения изменений объекта|страницы
		 * @throws coreException
		 */
		public function systemEventsNotify(iUmiEventPoint $event) {
			$eventId = $event->getEventId();

			$titleLabel = $titleLabel = 'event-' . $eventId . '-title';
			$contentLabel = 'event-' . $eventId . '-content';

			$title = getLabel($titleLabel, 'common/content/config');
			$content = getLabel($contentLabel, 'common/content/config');

			if ($titleLabel == $title) {
				return;
			}

			/** @var iUmiHierarchyElement $element */
			$element = $event->getRef('element');

			if ($element) {
				$hierarchy = umiHierarchy::getInstance();
				$oldbForce = $hierarchy->forceAbsolutePath();

				$params = [
					'%page-name%' => $element->getName(),
					'%page-link%' => $hierarchy->getPathById($element->getId())
				];

				$hierarchy->forceAbsolutePath($oldbForce);
			} else {
				$params = [];
			}

			/** @var iUmiObject $object */
			$object = $event->getRef('object');

			if ($object) {
				$params['%object-name%'] = $object->getName();
				$objectTypes = umiObjectTypesCollection::getInstance();
				$objectType = $objectTypes->getType($object->getTypeId());
				$hierarchyTypeId = $objectType->getHierarchyTypeId();

				if ($hierarchyTypeId) {
					$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
					$hierarchyType = $hierarchyTypes->getType($hierarchyTypeId);
					$params['%object-type%'] = $hierarchyType->getTitle();
				}
			}

			$title = str_replace(array_keys($params), array_values($params), $title);
			$content = str_replace(array_keys($params), array_values($params), $content);
			$this->module->dispatchSystemEvent($title, $content);
		}

		/** Выполняет операции по обслуживанию кеширования через базу данных */
		public function maintainDataBaseCache() {
			/** @var databaseCacheEngine $dataBaseCacheEngine */
			$dataBaseCacheEngine = Service::CacheEngineFactory()
				->create();
			$dataBaseCacheEngine->dropExpired();
			$dataBaseCacheEngine->optimise();
		}
	}
