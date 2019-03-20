<?php
class acquiropayPayment extends payment {
	public function validate() {
		return true;
	}
	public function process($template = null) {
		$merchant_id = $this->object->merchant_id;
		$product_id = $this->object->product_id;
		$secret_word = $this->object->secret_word;
		
		$cmsController = cmsController::getInstance();
		$protocol = !empty( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
		$www = $protocol . $cmsController->getCurrentDomain()->getHost();
		
		$language = strtolower( $cmsController->getCurrentLang()->getPrefix() );
		
		switch( $language ) {
		case 'ru':
			$language = 'ru';
			break;
		default:
			$language = 'en';
			break;
		}
		
		$this->order->order();
		
		$amount = $this->order->getActualPrice();
		$amount = number_format( $amount, 2, '.', '' );
		$token = md5( $merchant_id . $product_id . $amount . $this->order->getId() . $secret_word );
		$successUrl = empty( $this->object->ok_url ) ? $www . '/emarket/purchase/result/successful/' : $this->_http( $this->object->ok_url );
		$failUrl = empty( $this->object->ko_url ) ? $www . '/emarket/purchase/result/failed/' : $this->_http( $this->object->ko_url );
		$answerUrl = $www . '/emarket/gateway/' . $this->order->getId();
		
		$param = array();
		$param["formAction"] = 'https://secure.acquiropay.com/';
		$param["product_id"] = $product_id;
		$param["amount"] = $amount;
		$param["language"] = $language;
		$param["order_id"] = $this->order->getId();
		$param["ok_url"] = $successUrl;
		$param["cb_url"] = $answerUrl;
		$param["ko_url"] = $failUrl;
		$param["token"] = $token;
		
		$this->order->setPaymentStatus( 'initialized' );
		
		list( $templateString ) = def_module::loadTemplates( "emarket/payment/acquiropay/" . $template, "form_block" );
		return def_module::parseTemplate( $templateString, $param );
	}
	public function poll() {
		if( !getRequest( 'payment_id' ) ) {
			return false;
		}
		$merchant_id = $this->object->merchant_id;
		$secret_word = $this->object->secret_word;
		$payment_id = getRequest( 'payment_id' );
		$status = getRequest( 'status' );
		$cf = getRequest( 'cf' );
		$amount = getRequest( 'amount' );
		$hashString = md5( $merchant_id . $payment_id . $status . $cf . $secret_word );
		
		if( strcasecmp( $hashString, getRequest( 'sign' ) ) != 0 ) {
			return false;
		}
		
		if( ($this->order->getActualPrice() - $amount) != 0 ) {
			return false;
		}
		
		$buffer = \UmiCms\Service::Response()
			->getCurrentBuffer();
		$buffer->clear();
		$buffer->contentType( 'text/plain' );
		
		try {
			$this->order->payment_document_num = $payment_id;
			
			if( $status == 'OK' ) {
				$this->order->setPaymentStatus( 'accepted' );
				$buffer->push( 'success' );
			} else {
				$this->order->setPaymentStatus( 'declined' );
				$buffer->push( 'fail' );
			}
		} catch( Exception $e ) {
			$buffer->push( 'fail' );
		}
		
		$buffer->end();
		return true;
	} 
	
	public static function getOrderId() {
		return (int) getRequest( 'cf' );
	}
	
	/**
	 *
	 * @param string $url
	 * @return string URL with prefix-protocol if not exist
	 */
	private function _http($url) {
		return strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ? $url : 'http://' . $url;
	}
}
?>