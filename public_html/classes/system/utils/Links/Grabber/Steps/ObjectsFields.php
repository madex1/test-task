<?php
 namespace UmiCms\Classes\System\Utils\Links\Grabber\Steps;class ObjectsFields extends ObjectsNames {const STEP_NAME = 'ObjectsFields';public function getName() {return self::STEP_NAME;}public function grab() {if ($this->isComplete()) {return $this;}$vaa9f73eea60a006820d0f8768bc8a3fc = (int) $this->getLimit();$v7a86c157ee9713c34fbd7a1ee40f0c5a = (int) $this->getOffset();$v4717d53ebfdfea8477f780ec66151dcb = $this->getConnection();$vac5c74b64b4b8352ef2f181affb5ac2a = <<<SQL
SELECT
	`obj_id`,
	`varchar_val`,
	`text_val`
FROM
	`cms3_object_content`
WHERE
	`varchar_val` IS NOT NULL
OR
	`text_val` IS NOT NULL
LIMIT
	$v7a86c157ee9713c34fbd7a1ee40f0c5a, $vaa9f73eea60a006820d0f8768bc8a3fc;
SQL;   $result = $v4717d53ebfdfea8477f780ec66151dcb->queryResult($vac5c74b64b4b8352ef2f181affb5ac2a);$v45d43298bda33b4868f69ba540aa3ee2 = [];if ($result->length() == 0) {$this->setResult($v45d43298bda33b4868f69ba540aa3ee2)     ->setCompleteStatus(true);}foreach ($result as $vf1965a857bc285d26fe22023aa5ab50d) {$ve334e4821b2fa4ff1d5b78c0774a337e = trim($vf1965a857bc285d26fe22023aa5ab50d['varchar_val']);$vc5137b81476e10272df1ef0f66746fc5 = $this->parseUrlsFromString($ve334e4821b2fa4ff1d5b78c0774a337e);$v1cb251ec0d568de6a929b520c4aed8d1 = trim($vf1965a857bc285d26fe22023aa5ab50d['text_val']);$v15ffcec13fb9bcf2cacbcedb25f5cb7e = $this->parseUrlsFromText($v1cb251ec0d568de6a929b520c4aed8d1);$v45a82579528dcdc6535fdcb693a36170 = array_merge($vc5137b81476e10272df1ef0f66746fc5, $v15ffcec13fb9bcf2cacbcedb25f5cb7e);if (umiCount($v45a82579528dcdc6535fdcb693a36170) === 0) {continue;}$vb80bb7740288fda1f201890375a60c8f = $vf1965a857bc285d26fe22023aa5ab50d['obj_id'];$v3e3756b25306c2f44e7fad597024e50c = $this->getObjectEditLinkByObjectId($vb80bb7740288fda1f201890375a60c8f);if (isset($v45d43298bda33b4868f69ba540aa3ee2[$v3e3756b25306c2f44e7fad597024e50c])) {foreach ($v45d43298bda33b4868f69ba540aa3ee2[$v3e3756b25306c2f44e7fad597024e50c] as $v572d4e421e5e6b9bc11d815e8a027112) {$v45a82579528dcdc6535fdcb693a36170[] = $v572d4e421e5e6b9bc11d815e8a027112;}}$v45d43298bda33b4868f69ba540aa3ee2[$v3e3756b25306c2f44e7fad597024e50c] = $v45a82579528dcdc6535fdcb693a36170;}return $this->setResult($v45d43298bda33b4868f69ba540aa3ee2)    ->setOffset($v7a86c157ee9713c34fbd7a1ee40f0c5a + $vaa9f73eea60a006820d0f8768bc8a3fc);}}