<?php

	class custom extends def_module {

		public function cms_callMethod($method_name, $args) {
			return call_user_func_array([$this, $method_name], $args);
		}

		public function __call($method, $args) {
			throw new publicException('Method ' . get_class($this) . '::' . $method . " doesn't exist");
		}

		public function sendback(){

			$result ='';

			$Name = htmlspecialchars(getRequest('Name'));

			$Email = getRequest('Email');

			$Comment = htmlspecialchars(getRequest('Comment'));

			if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {

    				$result .= "<- Incorrect email value ->";

				}


			if (empty($Name)){

    				$result .= "<- Empty name value ->";

				}

			if (empty($Comment)){

    				$result .= "<- Empty comment value ->";
    				
				}	



			if($result==''){

			$mail = new umiMail;
 
 			$mail->addRecipient("neo_mat@inbox.ru", "Support");
 
 			$mail->setFrom($Email, $Name);
 
 			$mail->setSubject("FeedBack");
 
 			$mail->setContent($Comment);

 			$mail->commit();
 
 			$mail->send();

			return "ok";


			}else{

			return $result;

			}



			
		}



	}
