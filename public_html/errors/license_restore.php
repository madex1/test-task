<?php
 require_once '../libs/config.php';use UmiCms\Service;$vb1444fb0c07653567ad325aa25d4e37a = Service::Registry();if ($vb1444fb0c07653567ad325aa25d4e37a->checkSelfKeycode()) {echo 'jQuery(document).ready(function() {
		jQuery("#licenseButton")
		.add("#license_msg")
		.add("div.b_input")
		.add("p.vvod_key")
		.remove();
		jQuery("h1").html("Проверка лицензионного ключа");
		jQuery("h2").html("Произошла ошибка при проверке лицензионного ключа.<br/>Если Вы являетесь владельцем сайта, обратитесь, пожалуйста, в службу заботы о клиентах.");
		})';exit();}$v47b24364cadf239653e995b148e694f1 = Service::DomainCollection()  ->getDefaultDomain()  ->getHost();?>

jQuery.ajaxSetup({
	error: function() {
		document.getElementById('license_msg').innerHTML = "Произошла ошибка при обращении к серверу.<br/>Попробуйте повторить попытку или обратиться в <a href=\"http://www.umi-cms.ru/support/\" target=\"_blank\">Службу Заботы</a> UMI.CMS.";
		document.getElementById('licenseButton').disabled = false;
		return false;
	}
});

function checkSystem() {
	document.getElementById('more_info').style.display='none';

	var keycode = document.getElementById('keycode').value;
	keycode = keycode.trim();
	if (keycode.length == 0) {
		document.getElementById('license_msg').innerHTML = "Ошибка: лицензионный ключ не указан.";
		return false;
	}

	document.getElementById('license_msg').innerHTML = "Проверка лицензионного ключа... Пожалуйста, подождите.";
	document.getElementById('licenseButton').disabled = true;

	jQuery.get('/errors/save_domain_keycode.php', {'keycode':keycode}, function(response) {
		var errors = jQuery('error', response);

		if (errors.length > 0) {
			document.getElementById('license_msg').innerHTML = "Ошибка: " + errors[0].textContent;
			document.getElementById('licenseButton').disabled = false;
			return false;
		}

		var result = jQuery('result', response);

		if (result[0].textContent != 'true') {
			document.getElementById('license_msg').innerHTML = "Ошибка: некорректный ответ сервера. Попробуйте повторить попытку.";
			document.getElementById('licenseButton').disabled = false;
			return false;
		}

		var isDomainNotDefault = jQuery('is_domain_not_default', response);

		if (isDomainNotDefault[0].textContent === '1') {
			document.getElementById('license_msg').innerHTML = 'Для активации системы перейдите на основной домен <?= $v47b24364cadf239653e995b148e694f1 ?>.';
			document.getElementById('license_msg').style.color = 'red';
			return false;
		}

		document.getElementById('license_msg').innerHTML = 'Активация лицензии...';
		checkLicenseCode();
		document.getElementById('licenseButton').disabled = false;
	});

}

function requestsController() {
	requestsController.self = this;
}

requestsController.prototype.requests = new Array();

requestsController.getSelf = function () {
	if(!requestsController.self) {
		requestsController.self = new requestsController();
	}
	return requestsController.self;
};

requestsController.prototype.sendRequest = function (url, handler) {
	var requestId = this.requests.length;
	this.requests[requestId] = handler;

	var url = url;
	var scriptObj = document.createElement("script");
	scriptObj.src = url + "&requestId=" + requestId;
	document.body.appendChild(scriptObj);
};

requestsController.prototype.reportRequest = function (requestId, args) {
	this.requests[requestId](args);
	this.requests[requestId] = undefined;
}

function checkLicenseCode(frm) {
	var keycodeInput = document.getElementById('keycode');
	var keycode = keycodeInput.value;

	<?php $v836c673259e51101a01e755a34f97359 = Service::Request()->serverAddress();?>
	var ip = "<?= isset($v836c673259e51101a01e755a34f97359) ? $v836c673259e51101a01e755a34f97359 : str_replace("\\", '', Service::Request()->documentRoot());?>";
	var domain = "<?= Service::Request()->host();?>";

	var url = "https://umi-cms-2.umi-cms.ru/updatesrv/initInstallation/?keycode=" + keycode + "&domain=" + domain + "&ip=" + ip;

	var handler = function (response) {
		if(response['status'] == "OK") {
			document.getElementById('license_msg').style.color = "green";

			var res = "Лицензия \"" + response['license_type'] + "\" активирована.<br />Владелец " + response['last_name'] + " " + response['first_name'] + " " + response['second_name'] + " (" + response['email'] + ")<br />";
			var domain_keycode = response['domain_keycode'];

			document.getElementById('licenseButton').value = "Ok >>";

			document.getElementById('licenseButton').onclick = function () {
				window.location = "/";
			}

			document.getElementById('license_msg').innerHTML = res;

			var url = "/errors/save_domain_keycode.php?domain_keycode=" + domain_keycode + "&domain=" + domain + "&ip=" + ip + "&license_codename=" + response['license_codename'];
			requestsController.getSelf().sendRequest(url, function () {});
		} else {
			document.getElementById('license_msg').innerHTML = "Ошибка: " + response['msg'];
		}
	};

	requestsController.getSelf().sendRequest(url, handler);
}
