<?php
	abstract class __emarket_notification {
		public function notifyOrderStatusChange(order $order, $changedProperty) {
			$order->need_export = true;
			if($changedProperty == "status_id") {
				$order->status_change_date = new umiDate();
			}
			if(order::getCodeByStatus($order->getPaymentStatus()) == "accepted" && !$order->delivery_allow_date) {
				$sel = new selector('objects');
				$sel->types('hierarchy-type')->name('emarket', 'delivery');
				$sel->option('no-length')->value(true);
				if ($sel->first) {
					$order->delivery_allow_date = new umiDate();
				}
			}
			$statusId = $order->getValue($changedProperty);
			$codeName = order::getCodeByStatus($statusId);
			if($changedProperty == 'status_id' && (!$statusId || $codeName == 'payment')) return;
			$this->sendCustomerNotification($order, $changedProperty, $codeName);

			if($changedProperty == 'status_id' && $codeName == 'waiting') {
				$this->sendManagerNotification($order);
				$this->sendManagerPushNotification($order); // Уведомление на мобильное устройство
			}
		}
		public function sendCustomerNotification(order $order, $changedStatus, $codeName) {
			$customer = umiObjectsCollection::getInstance()->getObject($order->getCustomerId());
			$buyerOneClick = umiObjectsCollection::getInstance()->getObject($order->getValue('purchaser_one_click'));
			$emailOneClick = false;
			if ($buyerOneClick instanceof umiObject) {
				$emailOneClick = $buyerOneClick->email ? $buyerOneClick->email : $buyerOneClick->getValue("e-mail");
			}
			if ($emailOneClick) {
				$email = $emailOneClick;
			} else {
				$email    = $customer->email ? $customer->email : $customer->getValue("e-mail");
			}
			if($email) {
				$name  = $customer->lname . " " .$customer->fname . " " . $customer->father_name;
				$langs = cmsController::getInstance()
					->getLangConstantList();
				$statusString = "";
				$subjectString = $langs['notification-status-subject'];
				$regedit = regedit::getInstance();
				switch($changedStatus) {
					case 'status_id' : {
						if ($regedit->getVal('//modules/emarket/no-order-status-notification')) return;
						if($codeName == 'waiting') {
							$paymentStatusCodeName = order::getCodeByStatus($order->getPaymentStatus());
							$pkey = 'notification-status-payment-' . $paymentStatusCodeName;
							$okey = 'notification-status-' . $codeName;
							$statusString = ($paymentStatusCodeName == 'initialized') ?
												 ( (isset($langs[$okey]) ? ($langs[$okey] . " " . $langs['notification-and']) : "") . (isset($langs[$pkey]) ? (" ".$langs[$pkey]) : "" ) ) :
											( (isset($langs[$pkey]) ? ($langs[$pkey] . " " . $langs['notification-and']) : "") . (isset($langs[$okey]) ? (" ".$langs[$okey]) : "" ) );
							$subjectString = $langs['notification-client-neworder-subject'];
						} else {
							$key = 'notification-status-' . $codeName;
							$statusString = isset($langs[$key]) ? $langs[$key] : "_";
						}
						break;
					}
					case 'payment_status_id': {
						if ($regedit->getVal('//modules/emarket/no-payment-status-notification')) return;
						$key = 'notification-status-payment-' . $codeName;
						$statusString = isset($langs[$key]) ? $langs[$key] : "_";
						break;
					}
					case 'delivery_status_id': {
						if ($regedit->getVal('//modules/emarket/no-delivery-status-notification')) return;
						$key = 'notification-status-delivery-' . $codeName;
						$statusString = isset($langs[$key]) ? $langs[$key] : "_";
						break;
					}
				}
				$collection = umiObjectsCollection::getInstance();
				$paymentObject = $collection->getObject($order->payment_id);
				if($paymentObject) {
					$paymentType   = $collection->getObject($paymentObject->payment_type_id);
					$paymentClassName = $paymentType->class_name;
				} else {
					$paymentClassName = null;
				}
				$templateName  = ($paymentClassName == "receipt") ? "status_notification_receipt" : "status_notification";
				list($template) = def_module::loadTemplatesForMail("emarket/mail/default", $templateName);
				
				$param = array();
				$param["order_id"]        = $order->id;
				$param["order_name"]      = $order->name;
				$param["order_number"]    = $order->number;
				$param["status"]          = $statusString;
				$param["personal_params"] = $this->getPersonalLinkParams($customer->getId());
				
				$domain = cmsController::getInstance()->getCurrentDomain();

				$currentHost = getServer('HTTP_HOST');
				$param["domain"] = $domain->getCurrentHostName();
 
				if($paymentClassName == "receipt") {
					$param["receipt_signature"] = sha1("{$customer->getId()}:{$customer->email}:{$order->order_date}");
				}
				$content = def_module::parseTemplateForMail($template, $param);
				$regedit  = regedit::getInstance();
				$letter   = new umiMail();
				$letter->addRecipient($email, $name);

				$cmsController = cmsController::getInstance();
				$domains = domainsCollection::getInstance();
				$domainId = $cmsController->getCurrentDomain()->getId();
				$defaultDomainId = $domains->getDefaultDomain()->getId();

				if ($regedit->getVal("//modules/emarket/from-email/{$domainId}")) {
					$fromMail = $regedit->getVal("//modules/emarket/from-email/{$domainId}");
					$fromName = $regedit->getVal("//modules/emarket/from-name/{$domainId}");

				} elseif ($regedit->getVal("//modules/emarket/from-email/{$defaultDomainId}")) {
					$fromMail = $regedit->getVal("//modules/emarket/from-email/{$defaultDomainId}");
					$fromName = $regedit->getVal("//modules/emarket/from-name/{$defaultDomainId}");

				} else {
					$fromMail = $regedit->getVal("//modules/emarket/from-email");
					$fromName = $regedit->getVal("//modules/emarket/from-name");
				}

				$letter->setFrom($fromMail, $fromName);
				$letter->setSubject($subjectString);
				$letter->setContent($content);
				$letter->commit();
				$letter->send();
			}
		}

		public function sendManagerPushNotification(order $order) {
			$currentDomain = cmsController::getInstance()->getCurrentDomain();

			$selector = new selector("objects");
			$selector->types('object-type')->id('emarket-mobile-devices');
			$selector->where('domain_id')->equals($currentDomain->getId());
			$selector->where('active')->equals(1);
			$selector->option('no-length')->value(true);
			$selector->option('load-all-props')->value(true);

			if (!$selector->first) {
				return;
			}

			$tokens = array();
			foreach($selector->result as $deviceObject) {
				$token = $deviceObject->getValue('token');
				if (!$token) {
					continue;
				}
				$tokens[] = $token;
			}

			if (count($tokens) == 0) {
				return;
			}

			$punyConverter = new idna_convert();
			$request = array(
				'requestType' => 'sendPushMessage',
				'domain' => $punyConverter->decode($currentDomain->getHost()),
				'lang_prefix' => cmsController::getInstance()->getCurrentLang()->getPrefix(),
				'tokens' => $tokens,
				'orderId' => (string) $order->getId(),
				'orderNumber' => (string) $order->getValue('number'),
				'orderStatusId' => (string) $order->getValue('status_id')
			);

			$response = umiRemoteFileGetter::get(PUSH_SERVER, false, false, $request, false, 'POST', 10);
			// TODO - анализ ответа.
		}

		public function sendManagerNotification(order $order) {
			$regedit  = regedit::getInstance();
			$cmsController = cmsController::getInstance();
			$domains = domainsCollection::getInstance();
			$domainId = $cmsController->getCurrentDomain()->getId();
			$defaultDomainId = $domains->getDefaultDomain()->getId();

			if ($regedit->getVal("//modules/emarket/manager-email/{$domainId}")) {
				$emails	= $regedit->getVal("//modules/emarket/manager-email/{$domainId}");
				$fromMail = $regedit->getVal("//modules/emarket/from-email/{$domainId}");
				$fromName = $regedit->getVal("//modules/emarket/from-name/{$domainId}");

			} elseif ($regedit->getVal("//modules/emarket/manager-email/{$defaultDomainId}")) {
				$emails	= $regedit->getVal("//modules/emarket/manager-email/{$defaultDomainId}");
				$fromMail = $regedit->getVal("//modules/emarket/from-email/{$defaultDomainId}");
				$fromName = $regedit->getVal("//modules/emarket/from-name/{$defaultDomainId}");

			} else {
				$emails	  = $regedit->getVal('//modules/emarket/manager-email');
				$fromMail = $regedit->getVal("//modules/emarket/from-email");
				$fromName = $regedit->getVal("//modules/emarket/from-name");
			}

			$letter = new umiMail();

			$recpCount = 0;
			foreach (explode(',' , $emails) as $recipient) {
				$recipient = trim($recipient);
				if (strlen($recipient)) {
					$letter->addRecipient($recipient);
					$recpCount++;
				}
			}
			if(!$recpCount) return;

			list($template) = def_module::loadTemplatesForMail("emarket/mail/default", "neworder_notification");
			try {
				$payment = payment::get($order->payment_id, $order);
				$paymentName 	= $payment ? $payment->name : '';
				$paymentStatus  = order::getCodeByStatus($order->getPaymentStatus());
			} catch(coreException $e) {
				$paymentName 	= "";
				$paymentStatus 	= "";
			}
			$param = array();
			$param["order_id"]       = $order->id;
			$param["order_name"]     = $order->name;
			$param["order_number"]   = $order->number;
			$param["payment_type"]   = $paymentName;
			$param["payment_status"] = $paymentStatus;
			$param["price"]          = $order->getActualPrice();
			$param["domain"]         = cmsController::getInstance()->getCurrentDomain()->getCurrentHostName();
			$content = def_module::parseTemplateForMail($template, $param);
			$langs = cmsController::getInstance()
				->getLangConstantList();

			$letter->setFrom($fromMail, $fromName);
			$letter->setSubject($langs['notification-neworder-subject'] . " (#{$order->number})");
			$letter->setContent($content);
			$letter->commit();
			$letter->send();
		}
	};
?>
