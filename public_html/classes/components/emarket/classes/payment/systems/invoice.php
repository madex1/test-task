<?php

	use UmiCms\Service;

	/** Внутренний способ оплаты "Счет для юридических лиц" */
	class invoicePayment extends payment {

		/** @const string printMacro название макроса для вывода счета */
		const printMacro = 'getInvoice';

		/** @const string printModule название модуля для вывода счета */
		const printModule = 'emarket';

		/** @var string $invoiceLink адрес, по которому можно получить счет для оплаты */
		private $invoiceLink;

		/**
		 * @inheritdoc
		 * Устанавливает адрес, по которому можно получить счет для оплаты
		 */
		public function __construct(iUmiObject $object) {
			$args = func_get_args();
			$payment = array_shift($args);

			if (!$payment instanceof iUmiObject) {
				throw new Exception('Payment expected for creating payment');
			}

			$order = array_shift($args);

			if (!$order instanceof order && $order !== null) {
				throw new Exception('Incorrect order given for creating payment');
			}

			parent::__construct($payment);
			$this->order = $order;

			/** @var emarket $emarket */
			$emarket = cmsController::getInstance()->getModule('emarket');
			$checkSum = $emarket->getCheckSum($this->order->getId());
			$this->invoiceLink = '/' . self::printModule . '/' . self::printMacro .
				'/' . $this->order->getId() . '/' . $checkSum . '/';
		}

		/**
		 * @inheritdoc
		 * Принимает режим работы в $_REQUEST['param2'].
		 *
		 * Имеет несколько режимов работы:
		 *
		 * 1) 'do' - Устанавливает у заказа номер документа и юр. лицо,
		 * при необходимости создает его. В конце перенаправляет на
		 * страницу успешной оплаты.
		 *
		 * 2) 'delete' - Удаляет юридическое лицо и продолжает обычное выполнение.
		 *
		 * 3) Возвращает данные создания формы выбора юридического лица.
		 *
		 * @throws privateException
		 * @throws errorPanicException
		 * @throws coreException
		 * @throws ErrorException
		 * @throws publicException
		 * @throws Exception
		 */
		public function process($template = null) {
			list($tpl_block, $tpl_item) = emarket::loadTemplates(
				'emarket/payment/invoice/' . $template,
				'legal_person_block',
				'legal_person_item'
			);

			$controller = cmsController::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$types = umiObjectTypesCollection::getInstance();

			$typeId = $types->getTypeIdByHierarchyTypeName('emarket', 'legal_person');
			$customer = customer::get();
			$order = $this->order;
			$mode = getRequest('param2');

			if ($mode == 'do') {
				$personId = getRequest('legal-person');
				$isNew = ($personId == null || $personId == 'new');

				if ($isNew) {
					$personId = $objects->addObject('', $typeId);
					$data = getRequest('data');
					/** @var DataForms $dataModule */
					$dataModule = $controller->getModule('data');

					if ($data && $dataModule) {
						$person = $objects->getObject($personId);
						$person->setName($data['new']['name']);
						$person->commit();
						$dataModule->saveEditedObjectWithIgnorePermissions($personId, $isNew, true);
					}
				}

				$person = $objects->getObject($personId);

				if ($person instanceof iUmiObject) {
					$customer = customer::get();
					$customer->legal_persons = array_unique(array_merge($customer->legal_persons, [$personId]));
				}

				$order->legal_person = $personId;
				$order->order();
				$order->setPaymentDocumentNumber($order->getId());
				$order->commit();

				$this->sendInvoiceMail();

				Service::Response()
					->getCurrentBuffer()
					->redirect($controller->getPreLang() . '/emarket/purchase/result/successful/');
			} elseif ($mode == 'delete') {
				$personId = (int) getRequest('person-id');
				$person = $objects->getObject($personId);

				if ($person instanceof iUmiObject) {
					$permissions = permissionsCollection::getInstance();

					if ($permissions->isOwnerOfObject($personId) && $person->getTypeGUID() == 'emarket-legalperson') {
						$customer = customer::get();
						$customer->legal_persons = array_diff($customer->legal_persons, [$personId]);
						$objects->delObject($personId);
					}
				}
			}

			$items = [];
			$persons = $customer->legal_persons;

			if (is_array($persons)) {
				foreach ($persons as $personId) {
					/** @var iUmiObject $person */
					$person = $objects->getObject($personId);

					$item_arr = [
						'attribute:id' => $personId,
						'attribute:name' => $person->getName()
					];

					$items[] = emarket::parseTemplate($tpl_item, $item_arr, false, $personId);
				}
			}

			$email = (string) $customer->getValue('email');

			if ($email === '') {
				$email = (string) $customer->getValue('e-mail');
			}

			$block_arr = [
				'attribute:type-id' => $typeId,
				'attribute:type_id' => $typeId,
				'xlink:href' => 'udata://data/getCreateForm/' . $typeId,
				'invoice_link' => $this->invoiceLink,
				'subnodes:items' => $items,
				'customer' => [
					'attribute:e-mail' => $email
				]
			];

			return emarket::parseTemplate($tpl_block, $block_arr);
		}

		/** @inheritdoc */
		public function poll() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');
			$buffer->push('Sorry, but this payment system doesn\'t support server polling.' . getRequest('param0'));
			$buffer->end();
		}

		/**
		 * Возвращает счет на оплату заказа
		 * @param order $order заказ
		 * @return string
		 */
		public function printInvoice(order $order) {
			$orderId = $order->getId();
			$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-invoice.xsl";
			return file_get_contents($uri);
		}

		/**
		 * Отправляет сообщение-уведомление с информацией о счете
		 * @param string $template шаблон письма в директории emarket/
		 * @throws coreException
		 * @throws publicException
		 * @throws ErrorException
		 * @throws Exception
		 */
		public function sendInvoiceMail($template = 'default') {
			$currentDomain = Service::DomainDetector()->detect();
			$legalPersonId = $this->order->getValue('legal_person');
			$legalPerson = umiObjectsCollection::getInstance()->getObject($legalPersonId);
			$variables = [
				'domain' => $currentDomain->getHost(),
				'invoice_link' => $this->invoiceLink
			];
			$objectList = [$this->order->getObject(), $legalPerson];

			$subject = null;
			$content = null;

			$module = cmsController::getInstance()
				->getModule('emarket');

			if ($module->isUsingUmiNotifications()) {
				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName('notification-emarket-invoice');

				if ($notification instanceof MailNotification) {
					$subjectTemplate = $notification->getTemplateByName('emarket-invoice-subject');
					$contentTemplate = $notification->getTemplateByName('emarket-invoice-content');

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->parse($variables, $objectList);
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->parse($variables, $objectList);
					}
				}
			} else {
				try {
					list($subjectTemplate, $contentTemplate) = emarket::loadTemplatesForMail(
						'emarket/' . $template,
						'invoice_subject',
						'invoice_content'
					);
					$subject = emarket::parseTemplateForMail($subjectTemplate, $variables, false, $legalPersonId);
					$content = emarket::parseTemplateForMail($contentTemplate, $variables, false, $legalPersonId);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subject === null || $content === null) {
				return;
			}

			$umiRegistry = Service::Registry();
			$fromMail = $umiRegistry->get("//modules/emarket/from-email/{$currentDomain->getId()}");
			$fromName = $umiRegistry->get("//modules/emarket/from-name/{$currentDomain->getId()}");
			$emailTo = $legalPerson->getValue('email');
			$name = $legalPerson->getValue('name');

			$mail = new umiMail();
			$mail->addRecipient($emailTo, $name);
			$mail->setFrom($fromMail, $fromName);
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();
		}

		/**
		 * Возвращает ссылку, по которой можно вывести счет для юр. лиц
		 * @return string
		 */
		public function getInvoiceLink() {
			return $this->invoiceLink;
		}
	}
