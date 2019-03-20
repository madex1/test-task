<?php
 namespace UmiCms\System\Cache\Statical;use UmiCms\System\Cache\Statical\Key\iGenerator;use UmiCms\System\Cache\Key\iValidator as KeyValidator;use UmiCms\System\Cache\Statical\Key\Validator\iFactory;use UmiCms\System\Cache\State\iValidator as StateValidator;class Facade implements iFacade {private $config;private $stateValidator;private $keyValidator;private $keyGenerator;private $storage;public function __construct(   \iConfiguration $v2245023265ae4cf87d02c8b6ba991139,   StateValidator $v01fd74d4554ac01959006884f7e27a2c,   iFactory $v21693c3c9c4c71448df6686ceb8786de,   iGenerator $vcdb937aef8a3c67a6d81d2ea354d3cc5,   iStorage $vddecebdea58b5f264d27f1f7909bab74  ) {$this->config = $v2245023265ae4cf87d02c8b6ba991139;$this->stateValidator = $v01fd74d4554ac01959006884f7e27a2c;$this->keyValidator = $v21693c3c9c4c71448df6686ceb8786de->create();$this->keyGenerator = $vcdb937aef8a3c67a6d81d2ea354d3cc5;$this->storage = $vddecebdea58b5f264d27f1f7909bab74;}public function save($v9a0364b9e99bb480dd25e1f0284c8555) {if (!$this->isCacheWorking()) {return false;}$v3c6e0b8a9c15224a8228b9a98ca1531d = $this->getKeyGenerator()    ->getKey();if (!$this->getKeyValidator()->isValid($v3c6e0b8a9c15224a8228b9a98ca1531d)) {return false;}return $this->getStorage()->save($v3c6e0b8a9c15224a8228b9a98ca1531d, $v9a0364b9e99bb480dd25e1f0284c8555);}public function load() {if (!$this->isCacheWorking()) {return false;}$v3c6e0b8a9c15224a8228b9a98ca1531d = $this->getKeyGenerator()    ->getKey();if (!$this->getKeyValidator()->isValid($v3c6e0b8a9c15224a8228b9a98ca1531d)) {return false;}$v0fea6a13c52b4d4725368f24b045ca84 = $this->getStorage()->load($v3c6e0b8a9c15224a8228b9a98ca1531d);if (!is_string($v0fea6a13c52b4d4725368f24b045ca84)) {return false;}if ($this->isDebug()) {$v0fea6a13c52b4d4725368f24b045ca84 .= self::DEBUG_SIGNATURE;}return $v0fea6a13c52b4d4725368f24b045ca84;}public function getTimeToLive() {return $this->getStorage()->getTimeToLive();}public function isEnabled() {return (bool) $this->getConfig()    ->get('cache', 'static.enabled');}public function enable() {$v2245023265ae4cf87d02c8b6ba991139 = $this->getConfig();$v2245023265ae4cf87d02c8b6ba991139->set('cache', 'static.enabled', true);$v2245023265ae4cf87d02c8b6ba991139->save();return $this;}public function disable() {$v2245023265ae4cf87d02c8b6ba991139 = $this->getConfig();$v2245023265ae4cf87d02c8b6ba991139->set('cache', 'static.enabled', false);$v2245023265ae4cf87d02c8b6ba991139->save();return $this;}public function deletePageListCache(array $v5a2576254d428ddc22a03fac145c8749) {if (!$this->isEnabled()) {return false;}$vcdb937aef8a3c67a6d81d2ea354d3cc5 = $this->getKeyGenerator();$vddecebdea58b5f264d27f1f7909bab74 = $this->getStorage();foreach ($v5a2576254d428ddc22a03fac145c8749 as $vb80bb7740288fda1f201890375a60c8f) {$vd36a87418dcd06d8fbb68d2a1776284e = $vcdb937aef8a3c67a6d81d2ea354d3cc5->getKeyList($vb80bb7740288fda1f201890375a60c8f);foreach ($vd36a87418dcd06d8fbb68d2a1776284e as $v3c6e0b8a9c15224a8228b9a98ca1531d) {$vddecebdea58b5f264d27f1f7909bab74->deleteForEveryQuery($v3c6e0b8a9c15224a8228b9a98ca1531d);}}return true;}private function isCacheWorking() {return $this->isEnabled() && $this->getStateValidator()->isValid();}private function isDebug() {return (bool) $this->getConfig()    ->get('cache', 'static.debug');}private function getConfig() {return $this->config;}private function getStateValidator() {return $this->stateValidator;}private function getKeyValidator() {return $this->keyValidator;}private function getKeyGenerator() {return $this->keyGenerator;}private function getStorage() {return $this->storage;}}