<?php
 abstract class IterableAction extends Action implements iReadinessWorker, iStateFileWorker {use tReadinessWorker;use tStateFileWorker;}