<?php

	use Yandex\OAuth\OAuthClient;

	abstract class __emarket_admin_yandex_market extends baseModuleAdmin {

	public function yandex_market_config() {
		$regedit = regedit::getInstance();

		$params = Array();

		$domains = domainsCollection::getInstance()->getList();
		foreach($domains as $domain) {
			$domain_id = $domain->getId();
			$domain_name = $domain->getHost();

			$config = Array();
			$config["string:clientId-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/clientId");
			$config["string:password-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/password");
			$config["string:token-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/token");
			$config["string:login-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/login");
			$config["string:marketToken-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/marketToken");
			$config["string:marketCampaignId-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/marketCampaignId");

			$config["boolean:cashOnDelivery-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/cashOnDelivery");
			$config["boolean:cardOnDelivery-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/cardOnDelivery");
			$config["boolean:shopPrepaid-{$domain_id}"] = $regedit->getVal("//modules/emarket/yandex_market/{$domain_id}/shopPrepaid");

			$params[$domain_name] = $config;
		}

		$mode = (string) getRequest('param0');
		if($mode == "do") {
			$params = $this->expectParams($params);

			foreach($domains as $domain) {
				$domain_id = $domain->getId();
				$domain_name = $domain->getHost();

				$clientId = trim($params[$domain_name]["string:clientId-{$domain_id}"]);
				$password = trim($params[$domain_name]["string:password-{$domain_id}"]);
				$token = trim($params[$domain_name]["string:token-{$domain_id}"]);
				$login = trim($params[$domain_name]["string:login-{$domain_id}"]);
				$marketToken = trim($params[$domain_name]["string:marketToken-{$domain_id}"]);
				$marketCampaignId = trim($params[$domain_name]["string:marketCampaignId-{$domain_id}"]);
				$cashOnDelivery = trim($params[$domain_name]["boolean:cashOnDelivery-{$domain_id}"]);
				$cardOnDelivery = trim($params[$domain_name]["boolean:cardOnDelivery-{$domain_id}"]);
				$shopPrepaid = trim($params[$domain_name]["boolean:shopPrepaid-{$domain_id}"]);

				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/clientId", $clientId);
				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/password", $password);
				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/token", $token);
				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/login", $login);
				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/marketToken", $marketToken);
				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/marketCampaignId", $marketCampaignId);

				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/cashOnDelivery", $cashOnDelivery);
				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/cardOnDelivery", $cardOnDelivery);
				$regedit->setVar("//modules/emarket/yandex_market/{$domain_id}/shopPrepaid", $shopPrepaid);

				if ($clientId && $password && $token && $login && $marketToken && $marketCampaignId && !$cashOnDelivery && !$cardOnDelivery && !$shopPrepaid) {
					$this->errorNewMessage(getLabel('error-yandex_market-no-payment-method', false, $domain_name));
				}
			}

			$this->chooseRedirect();
		}

		$this->setDataType('settings');
		$this->setActionType("modify");

		$data = $this->prepareData($params, "settings");

		$this->setData($data);
		return $this->doData();
	}

	public function yandexMarketCreateToken() {
		$domain = getRequest('domain');
		$clientId = getRequest('clientId');
		$password = getRequest('password');
		$token = getRequest('token');
		$login = getRequest('login');
		$marketToken = getRequest('marketToken');
		$marketCampaignId = getRequest('marketCampaignId');
		$cashOnDelivery = getRequest('cashOnDelivery');
		$cardOnDelivery = getRequest('cardOnDelivery');
		$shopPrepaid = getRequest('shopPrepaid');

		if (strlen($domain) == 0) {
			throw new publicAdminException(getLabel('error-yandex_market-no-domain'));
		}

		$domainId = domainsCollection::getInstance()->getDomainId($domain);
		if (!$domainId) {
			throw new publicAdminException(getLabel('error-yandex_market-domain-not-add'));
		}

		if (strlen($clientId)==0 ||strlen($password)==0 ) {
			throw new publicAdminException(getLabel('error-yandex-market-empty-field'));
		}

		// Сохраняем данные
		$regedit = regedit::getInstance();
		$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/clientId", $clientId);
		$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/password", $password);
		if (strlen($login)) {
			$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/login", $login);
		}
		if (strlen($marketToken)) {
			$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/marketToken", $marketToken);
		}
		if (strlen($marketCampaignId)) {
			$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/marketCampaignId", $marketCampaignId);
		}

		$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/cashOnDelivery", $cashOnDelivery);
		$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/cardOnDelivery", $cardOnDelivery);
		$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/shopPrepaid", $shopPrepaid);

		// Запоминаем, для какого домена был запрос на генерацию токена
		$regedit->setVar("//modules/emarket/yandex_market/getTokenRequestDomainId", $domainId);

		$buffer = \UmiCms\Service::Response()
			->getCurrentBuffer();
		$buffer->send();

		// Client secret is not required in this case
		$client = new OAuthClient($clientId);
		//Redirect for get code and redirect to callback url for get token
		$client->authRedirect();
	}

	public function yandexMarketTokenCallback() {
		$regedit = regedit::getInstance();
		$domainId = cmsController::getInstance()->getCurrentDomain()->getId();

		$domainRequestId = $regedit->getVal("//modules/emarket/yandex_market/getTokenRequestDomainId");
		if ($domainId != $domainRequestId) {
			throw new publicAdminException(getLabel('error-yandex_market-domain-error'));
		}

		$clientId = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/clientId");
		$clientSecret = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/password");

		$code = getRequest('code');
		try {
			$client = new OAuthClient($clientId, $clientSecret);
			//Get token by code
			$client->requestAccessToken($code);
			$accessToken = $client->getAccessToken();
		} catch(Exception $e) { // Отказ предоставить доступ к авторизационным данным.
			throw new publicAdminException(getLabel('error-yandex_market-permission-denied'));
		}

		$regedit->setVar("//modules/emarket/yandex_market/{$domainId}/token", $accessToken);
		//Redirect
		\UmiCms\Service::Response()
			->getCurrentBuffer()
			->redirect('/admin/emarket/yandex_market_config/');
	}

}
?>
