<?php
	class dispatches extends def_module {
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				cmsController::getInstance()->getModule('users');
				$this->__loadLib("__admin.php");
				$this->__implement("__dispatches");

				$this->__loadLib("__messages.php");
				$this->__implement("__messages_messages");

				$this->__loadLib("__subscribers.php");
				$this->__implement("__subscribers_subscribers");

				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('lists');
					$commonTabs->add('subscribers');
					$commonTabs->add('messages', array('releases'));
				}

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__dispatches_custom_admin");
			}

			$this->__loadLib("__releasees.php");
			$this->__implement("__releasees_releasees");

			$this->__loadLib("__subscribers_import.php");
			$this->__implement("__subscribers_import_dispatches");

			$this->loadSiteExtension();

			// кастомы
			$this->__loadLib("__custom.php");
			$this->__implement("__custom_dispatches");

			$regedit = regedit::getInstance();
			$this->per_page = (int) $regedit->getVal("//modules/dispatches/per_page");
			if (!$this->per_page) $this->per_page = 15;
		}

		/**
		 * Публичный макрос отписки от рассылок
		 * @return string
		 */
		public function unsubscribe() {
			$subscriberId = (int) getRequest('id');
			$subscriberEmail = (string) getRequest('email');
			$subscriber = umiObjectsCollection::getInstance()->getObject($subscriberId);

			if ($this->isSubscriber($subscriber) && $subscriber->getName() == $subscriberEmail) {
				$subscriber->setValue('subscriber_dispatches', null);
				$subscriber->commit();
				return def_module::parseTPLMacroses('%subscribe_unsubscribed_ok%');
			}

			return def_module::parseTPLMacroses('%subscribe_unsubscribed_failed%');
		}

		protected function getSubscriberByUserId($iUserId) {
			$oSubscriber = null;

			$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeId($iSbsHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);

			$oSbsSelection = new umiSelection;
			$oSbsSelection->setObjectTypeFilter();
			$oSbsSelection->addObjectType($iSbsTypeId);
			$oSbsSelection->setPropertyFilter();
			$oSbsSelection->addPropertyFilterEqual($oSbsType->getFieldId('uid'), $iUserId);
			$arrSbsSelResults = umiSelectionsParser::runSelection($oSbsSelection);
			if (is_array($arrSbsSelResults) && count($arrSbsSelResults)) {
				$iSbsId = $arrSbsSelResults[0];
				$oSubscriber = umiObjectsCollection::getInstance()->getObject($iSbsId);
			}

			return $oSubscriber;
		}

		protected function getSubscriberByMail($sEmail) {
			$oSubscriber = null;

			$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeId($iSbsHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);

			$oSbsSelection = new umiSelection;
			$oSbsSelection->setObjectTypeFilter();

			$oSbsSelection->addObjectType($iSbsTypeId);
			$oSbsSelection->setNamesFilter();
			$oSbsSelection->addNameFilterEquals($sEmail);
			$arrSbsSelResults = umiSelectionsParser::runSelection($oSbsSelection);

			if (is_array($arrSbsSelResults) && count($arrSbsSelResults)) {
				$iSbsId = $arrSbsSelResults[0];
				$oSubscriber = umiObjectsCollection::getInstance()->getObject($iSbsId);
			}

			return $oSubscriber;
		}

		public function subscribe($sTemplate = "default") {
			$sResult = "";
			if(!$sTemplate) $sTemplate = "default";
			list(
				$sUnregistredForm, $sRegistredForm, $sDispatchesForm, $sDispatchRowForm
			) = def_module::loadTemplates("dispatches/".$sTemplate,
				"subscribe_unregistred_user", "subscribe_registred_user", "subscriber_dispatches", "subscriber_dispatch_row"
			);

			// check user registred
			$this->is_auth = false;
			$permissions = permissionsCollection::getInstance();
			if($permissions->isAuth()) {
				$iUserId = (int) $permissions->getUserId();
				$this->is_auth = true;
				$this->user_id = $iUserId;
			}

			if ($this->is_auth) {
				$arrRegBlock = array();
				// gen subscribe_registred_user form
				// check curr user in subscribers list
				$arrSbsDispatches = array();
				$oSubscriber = self::getSubscriberByUserId($this->user_id);
				if ($oSubscriber instanceof umiObject) {
					$arrSbsDispatches = $oSubscriber->getValue('subscriber_dispatches');
				}
				$arrRegBlock['subscriber_dispatches'] = self::parseDispatches($sDispatchesForm, $sDispatchRowForm, $arrSbsDispatches);
				$sResult = def_module::parseTemplate($sRegistredForm, $arrRegBlock);
			} else {
				$arrUnregBlock = array();
				$subscriberFields = $this->umiTypesHelper->getFieldsByObjectTypeGuid('dispatches-subscriber');
				$subscriberTypeId = $this->umiTypesHelper->getObjectTypeIdByGuid('dispatches-subscriber');
				if (isset($subscriberFields[$subscriberTypeId]['gender'])) {
					$oSbsGenderFld = umiFieldsCollection::getInstance()->getField($subscriberFields[$subscriberTypeId]['gender']);
					$arrGenders = umiObjectsCollection::getInstance()->getGuidedItems($oSbsGenderFld->getGuideId());
					$sGenders = Array();
					foreach ($arrGenders as $iGenderId => $sGenderName) {
						$sGenders[] = "<option value=\"".$iGenderId."\">".$sGenderName."</option>";
					}
					$arrUnregBlock['void:sbs_genders'] = $sGenders;
				}
				$sResult = def_module::parseTemplate($sUnregistredForm, $arrUnregBlock);
			}

			return $sResult;
		}

		/**
		 * Публичный макрос подписки на рассылки
		 * @return array|mixed|string
		 * @throws publicException
		 */
		public function subscribe_do() {
			$requestData = $this->getSubscriptionRequest();
			$email = $this->getSubscriptionEmail($requestData['email']);

			if (!umiMail::checkEmail($email)) {
				return $this->getSubscriptionError('%subscribe_incorrect_email%');
			}

			$data = $this->getInitialData($requestData);
			$permissions = permissionsCollection::getInstance();
			$dispatches = $this->getActualDispatches($requestData['dispatches']);
			$subscriber = $this->getExistingSubscriber($requestData['email']);

			if ($this->isSubscriber($subscriber)) {

				if ($permissions->isAuth()) {
					$this->updateSubscriber($subscriber, $data);
				} else {
					$this->subscribeDispatches($subscriber, $dispatches);
					list($templateBlock) = def_module::loadTemplates("dispatches/default", "subscribe_guest_alredy_subscribed");
					$result = array();
					$result['unsubscribe_link'] = $this->getUnSubscribeLink($subscriber, $email);
					return def_module::parseTemplate($templateBlock, $result);
				}

			} else {
				$subscriber = $this->createSubscriber($data);
			}

			$this->sendSubscribingLetter($subscriber, $email);
			$this->subscribeDispatches($subscriber, $dispatches);

			return $this->getSubscriptionResult($dispatches);
		}

		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . "/admin/dispatches/edit/"  . $objectId . "/";
		}

		protected function getAllDispatches() {
			static $cache = null;

			if (!is_null($cache)) {
				return $cache;
			}

			$dispatches = new selector('objects');
			$dispatches->types('object-type')->name('dispatches', 'dispatch');
			$dispatches->where('is_active')->equals(true);
			$dispatches->option('no-length')->value(true);
			$dispatches->option('load-all-props')->value(true);
			return $cache = $dispatches->result();
		}

		protected function parseDispatches($sDispatchesForm, $sDispatchRowForm, $arrChecked=array(), $bOnlyChecked=false) {

			$arrDispSelResults = self::getAllDispatches();
			$arrDispsBlock = array();
			$arrDispsBlock['void:rows'] = Array();

			if (is_array($arrDispSelResults) && count($arrDispSelResults)) {
				foreach ($arrDispSelResults as $dispatch) {
					if (!$dispatch instanceof umiObject) {
						continue;
					}
					$iNextDispId = $dispatch->getId();
					$arrDispRowBlock = "";
					$arrDispRowBlock['attribute:id'] = $arrDispRowBlock['void:disp_id'] = $dispatch->getId();
					$arrDispRowBlock['node:disp_name'] = $dispatch->getName();
					$arrDispRowBlock['attribute:is_checked'] = (in_array($iNextDispId, $arrChecked)? 1: 0);
					$arrDispRowBlock['void:checked'] = ($arrDispRowBlock['attribute:is_checked']? "checked": "");

					if ($arrDispRowBlock['attribute:is_checked']  || !$bOnlyChecked) {
						$arrDispsBlock['void:rows'][] = def_module::parseTemplate($sDispatchRowForm, $arrDispRowBlock, false, $iNextDispId);
					}
				}
			}
			$arrDispsBlock['nodes:items'] = $arrDispsBlock['void:rows'];
			return def_module::parseTemplate($sDispatchesForm, $arrDispsBlock);
		}

		/**
		 * Возвращает массив с данными запроса для подписки на рассылки
		 * @return array
		 */
		private function getSubscriptionRequest() {
			return array (
				'email' => trim(getRequest('sbs_mail')),
				'name' => getRequest('sbs_fname'),
				'lastName' => getRequest('sbs_lname'),
				'surname' => getRequest('sbs_father_name'),
				'gender' => (int) getRequest('sbs_gender'),
				'dispatches' => getRequest('subscriber_dispatches')
			);
		}

		/**
		 * Возвращает e-mail подписки
		 * @param mixed $email запрошенный e-mail
		 * @return mixed
		 */
		private function getSubscriptionEmail($email) {
			$permissions = permissionsCollection::getInstance();

			if ($permissions->isAuth()) {
				$user = umiObjectsCollection::getInstance()->getObject($permissions->getUserId());

				if ($user instanceof iUmiObject) {
					return $user->getValue('e-mail');
				}
			}

			return $email;
		}

		/**
		 * Возвращает данные подписки
		 * @param array $data запрошенные данные
		 * @return array
		 */
		private function getInitialData($data) {
			$permissions = permissionsCollection::getInstance();

			if ($permissions->isAuth()) {
				$user = umiObjectsCollection::getInstance()->getObject($permissions->getUserId());

				if ($user instanceof iUmiObject) {
					return array(
						'email' => $user->getValue('e-mail'),
						'name' => $user->getValue('fname'),
						'lastName' => $user->getValue('lname'),
						'surname' => $user->getValue('father_name'),
						'gender' => $user->getValue('gender'),
					);
				}
			}

			return $data;
		}

		/**
		 * Возвращает существующего подписчика, если таковой существует
		 * @param mixed $email подписчика
		 * @return bool|null|umiObject
		 */
		private function getExistingSubscriber($email) {
			$permissions = permissionsCollection::getInstance();
			if ($permissions->isAuth()) {
				return $this->getSubscriberByUserId($permissions->getUserId());
			}

			return $this->getSubscriberByMail($email);
		}

		/**
		 * Возвращает является ли объект - подписчиком
		 * @param mixed $object проверяемый объект
		 * @return bool
		 */
		private function isSubscriber($object) {
			return ($object instanceof iUmiObject && $object->getTypeGUID() == 'dispatches-subscriber');
		}

		/**
		 * Возвращает является ли объект - рассылкой
		 * @param mixed $object проверяемый объект
		 * @return bool
		 */
		private function isDispatch($object) {
			return ($object instanceof iUmiObject && $object->getTypeGUID() == 'dispatches-dispatch');
		}

		/**
		 * Обновляет данные объекта подписчика
		 * @param iUmiObject $subscriber объект подписчика
		 * @param array $data новые данные подписчика
		 */
		private function updateSubscriber(iUmiObject $subscriber, array $data) {
			$subscriber->setName($data['email']);
			$subscriber->setValue('fname', $data['name']);
			$subscriber->setValue('lname', $data['lastName']);
			$subscriber->setValue('father_name', $data['surname']);
			$subscriber->setValue('gender', $data['gender']);

			$permissions = permissionsCollection::getInstance();
			if ($permissions->isAuth()) {
				$subscriber->setValue('uid', $permissions->getUserId());
			}

			$subscriber->commit();
		}

		/**
		 * Создает нового подписчика
		 * @param array $data данные подписчика
		 * @return bool|umiObject
		 * @throws coreException
		 * @throws publicException
		 */
		private function createSubscriber(array $data) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$subscriberTypeId = $objectTypes->getTypeIdByHierarchyTypeName('dispatches', 'subscriber');
			$objects = umiObjectsCollection::getInstance();

			$subscriberId = $objects->addObject($data['email'], $subscriberTypeId);
			$subscriber = $objects->getObject($subscriberId);

			if ($this->isSubscriber($subscriber)) {
				$subscriber->setValue('subscribe_date', new umiDate());
				$this->updateSubscriber($subscriber, $data);

				return $subscriber;
			}

			throw new publicException(getLabel('error-cant-create-subscriber'));
		}

		/**
		 * Возвращает данные о результате подписки для шаблонизатора
		 * @param array $dispatches список ID рассылок
		 * @return array|mixed|string
		 */
		private function getSubscriptionResult(array $dispatches) {
			$permissions = permissionsCollection::getInstance();

			$result = '%subscribe_subscribe%';

			if ($permissions->isAuth()) {
				$blockTemplate = '%subscribe_subscribe_user%:<br /><ul>%rows%</ul>';
				$itemTemplate = '<li>%disp_name%</li>';
				$result = self::parseDispatches($blockTemplate, $itemTemplate, $dispatches, true);
			}

			return (!def_module::isXSLTResultMode()) ? $result : array('result' => $result);
		}

		/**
		 * Возвращает список рассылок для подписки
		 * @param array $dispatches список запрошенных рассылок
		 * @return array
		 */
		private function getActualDispatches($dispatches) {
			$result = array();

			$allDispatches = $this->getDispatchesList($this->getAllDispatches());
			if (!is_array($dispatches) || count($dispatches) === 0) {
				return $allDispatches;
			}

			foreach ($dispatches as $dispatchId) {
				$dispatch = umiObjectsCollection::getInstance()->getObject($dispatchId);
				if (!$this->isDispatch($dispatch)) {
					continue;
				}
				$result[] = $dispatchId;
			}

			sort($result);

			return (count($result) > 0 ? $result : $allDispatches);

		}

		/**
		 * Возвращает список ID действительных рассылок
		 * @param array $dispatches список объектов рассылок
		 * @return array
		 */
		private function getDispatchesList($dispatches) {
			$list = array();

			if (!is_array($dispatches) || count($dispatches) === 0) {
				return $list;
			}

			/** @var iUmiObject $dispatch */
			foreach ($dispatches as $dispatch) {
				if (!$this->isDispatch($dispatch)) {
					continue;
				}
				$list[] = $dispatch->getId();
			}
			return $list;
		}

		/**
		 * Отправляет письмо подписчику с информацией о подписке
		 * @param iUmiObject $subscriber объект подписчика
		 * @param string $subscriberEmail e-mail подписчика
		 * @param string $template имя шаблона письма
		 * @throws coreException
		 * @throws publicException
		 */
		private function sendSubscribingLetter(iUmiObject $subscriber, $subscriberEmail, $template = 'default') {
			$mailData = array();
			$mailData['domain'] = cmsController::getInstance()->getCurrentDomain()->getHost();
			$mailData['unsubscribe_link'] = $this->getUnSubscribeLink($subscriber, $subscriberEmail);
			list($templateMail, $templateSubject) = def_module::loadTemplatesForMail('dispatches/' . $template, 'subscribe_confirm', 'subscribe_confirm_subject');

			$content = def_module::parseTemplateForMail($templateMail, $mailData);
			$subject = def_module::parseTemplateForMail($templateSubject, $mailData);

			$confirmMail = new umiMail();
			$confirmMail->addRecipient($subscriberEmail);
			$nameFrom = regedit::getInstance()->getVal("//settings/fio_from");
			$emailFrom = regedit::getInstance()->getVal("//settings/email_from");
			$confirmMail->setFrom($emailFrom, $nameFrom);
			$confirmMail->setSubject($subject);
			$confirmMail->setContent($content);
			$confirmMail->commit();
			$confirmMail->send();
		}

		/**
		 * Подписывает подписчика на рассылки
		 * @param iUmiObject $subscriber объект подписчика
		 * @param array $dispatches список ID рассылок
		 */
		private function subscribeDispatches(iUmiObject $subscriber, array $dispatches) {
			$existingDispatches = $subscriber->getValue('subscriber_dispatches');
			$existingDispatches = array_map('intval', $existingDispatches);
			$newDispatches = array_unique(array_merge($existingDispatches, $dispatches));

			$subscriber->setValue('subscriber_dispatches', $newDispatches);
			$subscriber->commit();
		}

		/**
		 * Возвращает ссылку отписки от рассылки
		 * @param iUmiObject $subscriber объект подписчика
		 * @param string $email подписчика
		 * @return string
		 */
		public function getUnSubscribeLink(iUmiObject $subscriber, $email) {
			$domain = cmsController::getInstance()
				->getCurrentDomain()
				->getUrl();
			return $domain . $this->pre_lang . '/dispatches/unsubscribe/?id=' . $subscriber->getId() . '&email=' . $email;
		}

		/**
		 * Возвращает данные для шаблонизатора об ошибке
		 * @param string $message сообщение об ошибке
		 * @return array
		 */
		private function getSubscriptionError($message) {
			if (!def_module::isXSLTResultMode()) {
				return $message;
			}

			return array(
				'result' => array(
					'@class' => 'error',
					'node'	 => $message
				)
			);
		}

	};
?>