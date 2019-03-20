<?php
	class invoicePayment extends payment {
		/** Название макроса вывода счета */
		const printMacro = 'getInvoice';

		/** Название модуля макроса для вывода счета */
		const printModule = 'emarket';

		private $invoiceLink;

		public function __construct(umiObject $object) {
			$args = func_get_args();
			$payment = array_shift($args);

			if (!$payment instanceof umiObject) {
				throw new Exception('Payment expected for creating payment');
			}

			$order = array_shift($args);

			if (!$order instanceof order && $order !== null) {
				throw new Exception('Incorrect order given for creating payment');
			}

			parent::__construct($payment);
			$this->order = $order;

			$emarket = cmsController::getInstance()->getModule('emarket');

			$checkSum = $emarket->getCheckSum($this->order->getId());
			$this->invoiceLink = '/' . self::printModule . '/' . self::printMacro .
				'/' . $this->order->getId() . '/' . $checkSum . '/';
		}

		public function validate() {
			return true;
		}

		public function process($template = null) {
			list($tpl_block, $tpl_item) = def_module::loadTemplates("emarket/payment/invoice/" . $template, 'legal_person_block', 'legal_person_item');

			$controller = cmsController::getInstance();
			$objects    = umiObjectsCollection::getInstance();
			$types      = umiObjectTypesCollection::getInstance();

			$typeId = $types->getTypeIdByHierarchyTypeName("emarket", "legal_person");
			$customer = customer::get();
			$order = $this->order;
			$mode = getRequest('param2');

			if ($mode == 'do') {
				$personId = getRequest('legal-person');
				$isNew = ($personId == null || $personId == 'new');

				if ($isNew) {
					$personId = $objects->addObject("", $typeId);
					$data = getRequest('data');
					$dataModule = $controller->getModule("data");

					if ($data && $dataModule) {
						$person = $objects->getObject($personId);
						$person->setName($data['new']['name']);
						$dataModule->saveEditedObjectWithIgnorePermissions($personId, $isNew, true);
					}
				}

				$person = $objects->getObject($personId);
				if ($person instanceof iumiObject) {
					$customer = customer::get();
					$customer->legal_persons = array_merge($customer->legal_persons, array($personId));
				}

				$order->legal_person = $personId;
				$order->order();
				$order->payment_document_num = $order->id;

				$this->sendInvoiceMail();

				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->redirect($controller->getPreLang() . '/emarket/purchase/result/successful/');

				return true;

			} elseif ($mode == 'delete') {
				$personId = (int) getRequest('person-id');
				$person = $objects->getObject($personId);

				if ($person instanceof umiObject) {
					$permissions = permissionsCollection::getInstance();
					if ($permissions->isOwnerOfObject($personId) && $person->getTypeGUID() == 'emarket-legalperson') {
						$customer = customer::get();
						$customer->legal_persons = array_diff($customer->legal_persons, array($personId));
						$objects->delObject($personId);
					}
				}
			}

			$items = array();
			$persons = $customer->legal_persons;

			if (is_array($persons)) {
				foreach ($persons as $personId) {
					$person = $objects->getObject($personId);

					$item_arr = array(
						'attribute:id'   => $personId,
						'attribute:name' => $person->name
					);

					$items[] = def_module::parseTemplate($tpl_item, $item_arr, false, $personId);
				}
			}

			$email = (string) $customer->getValue('email');

			if ($email === '') {
				$email = (string) $customer->getValue('e-mail');
			}

			$block_arr = array(
				'attribute:type-id' => $typeId,
				'attribute:type_id' => $typeId,
				'xlink:href'        => 'udata://data/getCreateForm/' . $typeId,
				'invoice_link'      => $this->invoiceLink,
				'subnodes:items'    => $items,
				'customer'          => array(
					'attribute:e-mail' => $email
				)
			);

			return def_module::parseTemplate($tpl_block, $block_arr);
		}

		public function poll() {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');
			$buffer->push('Sorry, but this payment system doesn\'t support server polling.' . getRequest('param0'));
			$buffer->end();
		}

	public function printInvoice(order $order) {
		$orderId = $order->getId();
		$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-invoice.xsl";
		return file_get_contents($uri);
	}

		/**
		 * Отправялет сообщение-уведомление с информацией о счете
		 * @param string $template шаблон письма в директории emarket/
		 * @return null
		 */
		public function sendInvoiceMail($template = 'default') {
			list($invoiceSubject, $invoiceMailContent) = def_module::loadTemplatesForMail(
				"emarket/" . $template, 'invoice_subject', 'invoice_content');

			$person = umiObjectsCollection::getInstance()->getObject($this->order->legal_person);
			$emailTo = $person->getValue('email');
			$name = $person->getValue('name');

			$currentDomain = cmsController::getInstance()->getCurrentDomain();

			$mailData = array(
				'domain'       => $currentDomain->getHost(),
				'invoice_link' => $this->invoiceLink
			);

			$mailSubject = def_module::parseTemplateForMail($invoiceSubject, $mailData, false, $this->order->legal_person);
			$mailContent = def_module::parseTemplateForMail($invoiceMailContent, $mailData, false, $this->order->legal_person);

			$regedit = regedit::getInstance();
			$fromMail = $regedit->getVal("//modules/emarket/from-email/{$currentDomain->getId()}");
			$fromName = $regedit->getVal("//modules/emarket/from-name/{$currentDomain->getId()}");

			$invoiceMail = new umiMail();
			$invoiceMail->addRecipient($emailTo, $name);
			$invoiceMail->setFrom($fromMail, $fromName);
			$invoiceMail->setSubject($mailSubject);
			$invoiceMail->setContent($mailContent);
			$invoiceMail->commit();
			$invoiceMail->send();
		}

		/**
		 * Возвращает ссылку, по которой можно вывести счет для юр. лиц
		 * @return string
		 */
		public function getInvoiceLink() {
			return $this->invoiceLink;
		}
	};
?>
