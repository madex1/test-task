<?php
	abstract class __custom_events_config {
		public function runGarbageCollector(iUmiEventPoint $e) {
			if ($e->getMode() == "process") {
				try {
					$gc = new garbageCollector();
					$gc->run();
				} catch (maxIterationsExeededException $e) {}
			}
		}
	}
?>
