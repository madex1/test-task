<?php
	/**
	 * Класс пользовательских макросов
	 */
	class ContentCustomMacros {
		/**
		 * @var content $module
		 */
		public $module;

		public function feedback() {
			$fio 	= (getRequest('fio')) 	? getRequest('fio') 	: "";
			$email 	= (getRequest('email')) ? getRequest('email') 	: "";
			$message= (getRequest('message'))?getRequest('message') : "";

			if(!$fio) return ["attribute:error"=>"Введите Имя"];
			if(!$email) return ["attribute:error"=>"Введите email, чтобы получить ответ"];
			if(!$message) return ["attribute:error"=>"Введите ваш вопрос"];

			$mailContent = <<<CONTENT
ФИО: $fio<br/>
Email: $email<br/>
Сообщение:<br/>
$message
CONTENT;
			$mail = new umiMail;
			
			$regedit = regedit::getInstance();
			$admin_email = $regedit->getVal("//settings/admin_email");

			//Выставляем получателей письма
			$mail->addRecipient($admin_email);

			//Указываем, от чьего имени придет письмо
			$email_from = $regedit->getVal("//settings/email_from");
			$fio_from = $regedit->getVal("//settings/fio_from");
			$mail->setFrom($email_from, $fio_from);
			 
			//Устанавливаем заголовок письма
			$mail->setSubject("Вопрос с сайта");
			 
			//Укажем, что это очень важное письмо
			$mail->setPriorityLevel('highest');
			 
			//Устанавливаем содержание письма
			$mail->setContent($mailContent);
			 
			//Подтверждаем отправку письма
			$mail->commit();
			 
			//Отправляем письмо. Если не выполнить send(), то письмо все равно отправится. Но где-то во время завершения работы скрипта.
			$mail->send();
 
			return ["attribute:success"=>"Ваше сообщение отправлено"];
		}
	}
?>