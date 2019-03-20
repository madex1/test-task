<?php
error_reporting(0);
ini_set('display_errors', 0);

@session_start();
$sess_id = session_id();
@session_commit();

if (isset($configInstallerFile) && file_exists($configInstallerFile)) {
	$configInstaller = require_once $configInstallerFile;

	define('INSTALL_SERVER', $configInstaller['installServer']);
	define('UPDATE_SERVER', $configInstaller['updateServer']);
} else {
	define('INSTALL_SERVER', base64_decode('aHR0cHM6Ly9pbnN0YWxsLnVtaS1jbXMucnU='));
	define('UPDATE_SERVER', base64_decode('aHR0cDovL3VwZGF0ZXMudW1pLWNtcy5ydS91cGRhdGVzZXJ2ZXIv'));
}

if (!defined('PHP_FILES_ACCESS_MODE')) {
	define('PHP_FILES_ACCESS_MODE', octdec(substr(decoct(fileperms(__FILE__)), -4, 4)));
}

/** TODO убрать из проекта все, что связано с восстановлением системы */
if (isset($_REQUEST['doRestore'])) {
	header('Content-type: text/xml; charset=utf-8');
	echo doRestore();
	die();
}

if (isset($_REQUEST['getCodeImage'])) {
	header('Content-type: image/jpeg');
	$url1 = str_replace('updateserver/', base64_decode('Y2FwdGNoYS5waHA/cmVzZXQmUEhQU0VTU0lEPQ=='), UPDATE_SERVER);
	$url2 = str_replace('updateserver/', base64_decode('Y2FwdGNoYS5waHA/UEhQU0VTU0lEPQ=='), UPDATE_SERVER);
	get_file("{$url1}{$sess_id}");
	echo get_file("{$url2}{$sess_id}");
	die();
}
if (isset($_REQUEST['checkCode'])) {
	$code = $_REQUEST['captcha'];
	$url = str_replace('updateserver/', base64_decode('Y2FwdGNoYS5waHA/Y2hlY2s9dHJ1ZSZQSFBTRVNTSUQ9'), UPDATE_SERVER);
	$url .= "{$sess_id}&code={$code}";
	header('Content-type: text/xml; charset=utf-8');
	echo get_file($url);
	die();
}

if (isset($_REQUEST['getTrialKey'])) {
	header('Content-type: text/xml; charset=utf-8');
	echo getTrialKey();
	die();
}

if (isset($_REQUEST['check-license'])) {
	$key = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
	header('Content-type: text/xml; charset=utf-8');
	echo checkLicense($key);
	die();
}

if (isset($_REQUEST['check-mysql'])) {
	$param = [];
	$param['host'] = isset($_REQUEST['host']) ? trim(strip_tags($_REQUEST['host'])) : '';
	$param['dbname'] = isset($_REQUEST['dbname']) ? trim(strip_tags($_REQUEST['dbname'])) : '';
	$param['user'] = isset($_REQUEST['user']) ? trim(strip_tags($_REQUEST['user'])) : '';
	$param['password'] = isset($_REQUEST['password']) ? trim(strip_tags($_REQUEST['password'])) : '';
	header('Content-type: text/xml; charset=utf-8');
	echo checkMysql($param);
	die();
}

$step = 0;
$demosite = '';
if (file_exists('./install.ini') && (!(file_exists('./installed') && is_file('./installed')))) {
	$ini = parse_ini_file('./install.ini', true);

	// Проверяем предустановленный лицензионный ключ
	if (isset($ini['LICENSE']['key']) && strlen($ini['LICENSE']['key']) >= 35) {
		$xml = checkLicense($ini['LICENSE']['key']);
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$type = $dom->getElementsByTagName('response')->item(0)->getAttribute('type');
		if ($type == 'ok') {
			$step = 1;
		}
	}

	if ($step == 1 && isset($ini['DB']['port']) && strlen($ini['DB']['port']) > 0) {
		$ini['DB']['host'] .= ':' . $ini['DB']['port'];
	}

	// Проверяем предустановленные параметры подключения к базе
	if ($step == 1 && isset($ini['DB']['host']) && strlen($ini['DB']['host']) > 0
		&& isset($ini['DB']['dbname']) && strlen($ini['DB']['dbname']) > 0
		&& isset($ini['DB']['user']) && strlen($ini['DB']['user']) > 0
		&& isset($ini['DB']['password']) && strlen($ini['DB']['password']) > 0) {
		$xml = checkMysql($ini['DB']);
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$type = $dom->getElementsByTagName('response')->item(0)->getAttribute('type');
		if ($type == 'ok') {
			$step = 2;
		}
	}

	// Предустановленный демосайт
	if (isset($ini['DEMOSITE']['name']) && strlen($ini['DEMOSITE']['name']) > 0) {
		$demosite = $ini['DEMOSITE']['name'];
	}
}

if ((file_exists('./installed') && is_file('./installed')) || (substr(dirname(__FILE__), -4, 4) == '/smu' && (file_exists('../installed') && is_file('../installed')))) {
	$step = 10;
}

if ((file_exists('./restore') && is_file('./restore')) || (substr(dirname(__FILE__), -4, 4) == '/smu' && (file_exists('../restore') && is_file('../restore')))) {
	$step = 20;
}

if (!check_allow_remote_files()) {
	$step = 11;
	$error_header = 'Удаленные соединения запрещены';
	$error_content = 'Подробнее об ошибке: <a href="https://errors.umi-cms.ru/13041/" target="_blank">https://errors.umi-cms.ru/13041/</a>';
} elseif (($errors = check_writeable()) && (count($errors) > 0)) {
	$step = 11;
	$error_header = 'Проверьте разрешения на запись';
	$error_content = 'Перечисленные файлы и папки должны быть доступны на запись:<ol>';
	foreach ($errors as $path) {
		$error_content .= "<li>{$path}</li>";
	}
	$error_content .= '</ol>';
}

$sleep = get_sleep_time();

function getTrialKey() {
	$email = rawurlencode(trim($_REQUEST['email']));
	$lname = rawurlencode(trim($_REQUEST['lname']));
	$fname = rawurlencode(trim($_REQUEST['fname']));
	$domain = rawurlencode($_SERVER['HTTP_HOST']);
	$ip = rawurlencode($_SERVER['SERVER_ADDR']);
	$url = str_replace('updateserver/', base64_decode('dWRhdGEvY3VzdG9tL2dlbmVyYXRlVHJpYWxMaWNlbnNlRm9ySW5zdGFsbC84RE1FOERDSkhGRC8='), UPDATE_SERVER);
	$url .= "{$email}/{$fname}/{$lname}/{$domain}/{$ip}/trial";
	return get_file($url);
}

function get_sleep_time() {
	$sleep = 0;
	if (file_exists('./install.ini')) {
		$info = parse_ini_file('./install.ini', true);
		if (isset($info['SETUP']['sleep'])) {
			$sleep = (int) $info['SETUP']['sleep'];
			if ($sleep < 0) {
				$sleep = 0;
			}
		}
	}
	return $sleep;
}

function check_allow_remote_files() {
	return is_callable('curl_init');
}

function check_writeable() {
	$writeable = [
		'dirs' => [dirname(realpath(__FILE__))],
		'files' => [],
	];

	$file = get_file(INSTALL_SERVER . '/writable_directories.txt');
	$dirs = explode("\n", $file);

	foreach ($dirs as $dir) {
		$dir = trim($dir);
		if (!is_dir($dir)) {
			continue;
		}
		$writeable['dirs'][] = realpath($dir);
	}

	$errors = [];
	// Проверяем директории
	if (isset($writeable['dirs']) && count($writeable['dirs']) > 0) {
		foreach ($writeable['dirs'] as $dir) {
			if (file_exists($dir) && is_dir($dir) && !is_writeable($dir)) {
				$errors[] = $dir;
			}
		}
	}
	// Проверяем файлы
	if (isset($writeable['files']) && count($writeable['files']) > 0) {
		foreach ($writeable['files'] as $file) {
			if (file_exists($file) && is_file($file) && !is_writeable($file)) {
				$errors[] = $file;
			}
		}
	}
	// Возвращаем результат
	return $errors;
}

// Проверка подключения к базе данных
function checkMysql($params) {
	$exceptionMessage = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<response type=\"exception\"><error code=\"%d\"><![CDATA[%s]]></error></response>";

	if (!extension_loaded('mysqli')) {
		return sprintf($exceptionMessage, 13039, 'Не установлено расширение mysqli');
	}

	$link = mysqli_init();

	if (!$link) {
		return sprintf($exceptionMessage, 13011, 'Не удалось подключиться к mysql-серверу.');
	}

	if (!mysqli_real_connect($link, $params['host'], $params['user'], $params['password'], $params['dbname'])) {
		return sprintf($exceptionMessage, 13011, 'Не удалось подключиться к указанной базе данных');
	}

	return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<response type=\"ok\" />";
}

/**
 * Отправляет запрос на сервер обновлений для получения файла установщика.
 * Сервер обновлений валидирует переданный ключ и возвращает либо
 * файл installer.php для указанной в лицензии ветки, либо
 * xml-сообщение об ошибке.
 * @param string $key лицензионный или доменный ключ
 * @return string
 */
function checkLicense($key) {
	$param = [];
	$param['type'] = 'get-installer';
	$param['ip'] = isset($_SERVER['SERVER_ADDR']) ? ($_SERVER['SERVER_ADDR']) : str_replace("\\", '', $_SERVER['DOCUMENT_ROOT']);
	$param['host'] = $_SERVER['HTTP_HOST'];
	$param['key'] = $key;
	$param['revision'] = 'last';

	$url = UPDATE_SERVER . '?' . http_build_query($param, '', '&');
	$contents = get_file($url);
	$dom = new DOMDocument();
	if ($dom->loadXML($contents)) {
		return $contents;
	}

	$installerPath = dirname(__FILE__) . '/installer.php';
	file_put_contents($installerPath, $contents);
	umask(0);
	chmod($installerPath, PHP_FILES_ACCESS_MODE);
	return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<response type=\"ok\" />";
}

// Проверяет, была ли система установлена и забекапирована
function isBackup() {
	if (substr(dirname(__FILE__), -4, 4) == '/smu') {
		$backup_dir = realpath('..');
	} else {
		$backup_dir = realpath('.');
	}

	$backup_dir .= '/umibackup';
	// Директория бэкапов отстутствует
	if (!is_dir($backup_dir)) {
		return false;
	}
	// Отсутствуют файлы с информацией о бекапировании
	if (!is_file($backup_dir . '/backup_database.xml') && !is_file($backup_dir . '/backup_files.xml')) {
		return false;
	}

	return true;
}

function doRestore() {
	$_REQUEST['step'] = 'rollback';
	$_REQUEST['guiUpdate'] = 'true';
	return include('installer.php');
}

header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="<?= INSTALL_SERVER ?>/style.css" type="text/css" rel="stylesheet" />
	<title>Установка UMI.CMS</title>
</head>

<body>
	<form id="form1" action="" autocomplete="off">
		<div class="header">
			<p class="check_user"><?php echo (strlen($error_header) > 0) ? $error_header : ''; ?></p>
			<a href="https://umi-cms.ru" title="UMI.CMS" target="_blank"><img alt="" src="<?= INSTALL_SERVER ?>/logo_.png" class="logo" /></a>
		</div>

		<div class="main">
			<div class="display_none shadow_some step0">
				<div class="padding_big">
					<p class="vvod_key">Введите ключ</p>
					<div class="clear"></div>
					<div class="b_input">
						<input type="text" name="key" value="<?php echo isset($ini['LICENSE']['key']) ? $ini['LICENSE']['key'] : ''; ?>" />
					</div>
					<div class="info">
						<div class="img_stop">
							<img alt="" src="<?= INSTALL_SERVER ?>/ikon_stop.png" />
						</div>
						<div class="img_stop_text">
							<a href="https://www.umi-cms.ru/buy/free_license/?licence=trial" id="getTrialLink" target="_blank">Получить бесплатный ключ</a>
						</div>
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<div class="display_none first_block shadow_some" id="getTrialKey">
				<div class="padding_big field_license_user">
					<div class="display_block_left">
						<label for="lname_trial">Фамилия</label>
						<input id="lname_trial" name="lname_trial" class="">
						<div class="clear"></div>
					</div>
					<div class="display_block_left">
						<label for="fname_trial">Имя</label>
						<input id="fname_trial" name="fname_trial" class="">
						<div class="clear"></div>
					</div>
					<div class="display_block_left">
						<label for="email_trial">E-mail *</label>
						<input id="email_trial" name="email_trial" class="">
						<div class="clear"></div>
					</div>
					<div class="display_block_left">
						<label for="code_trial"><img src="install.php?getCodeImage" /></label>
						<input class="" name="code_trial" id="code_trial">
						<div class="clear"></div>
					</div>
					<div class="display_block_left">
						<p class="email_note">* Пожалуйста, вводите настоящий адрес, потому что на него будет отправлен триальный ключ.</p>
						<div class="clear"></div>
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<div class="display_none shadow_some step1">
				<div class="padding_big">
					<div class="display_block_left mar_left220px">
						<label>Имя хоста<br />
							<input name="host" type="text" value="<?php echo isset($ini['DB']['host']) ? $ini['DB']['host'] : 'localhost'; ?>" tabindex="1" /></label><br /><br />
						<label>Логин<br />
							<input name="user" type="text" value="<?php echo isset($ini['DB']['user']) ? $ini['DB']['user'] : ''; ?>" tabindex="3" /></label>
					</div>
					<div class="display_block_left posit_text24px">
						<label>Имя базы данных<br />
							<input name="dbname" type="text" value="<?php echo isset($ini['DB']['dbname']) ? $ini['DB']['dbname'] : ''; ?>" tabindex="2" /></label><br /><br />
						<label>Пароль<br />
							<input name="password" type="password" value="<?php echo isset($ini['DB']['password']) ? $ini['DB']['password'] : ''; ?>" tabindex="4" /></label>
					</div>
					<div class="clear"></div>
					<div class="info display_none marn">
						<div class="img_stop">
							<img alt="" src="<?= INSTALL_SERVER ?>/ikon_stop.png" />
						</div>
						<div class="img_stop_text"></div>
					</div>
					<div class="info1">
						<p>Предупреждение: при установке будут очищены все таблицы, используемые UMI.CMS.</p>
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<div class="display_none shadow_some step2">
				<div class="padding_big">
					<div class="display_block_left">
						<p class="style_p important">Если вы устанавливаете систему поверх существующего сайта, то его содержимое будет заменено на содержимое UMI.CMS. Настоятельно рекомендуется выполнить бэкап (резервное копирование) вашего сайта.</p>
						<input name="backup" id="cbbackup" type="checkbox" autocomplete="off" value="none" class="radio_but" /><label for="cbbackup"></label><span class="label">
						Подтверждаю, что сделал бэкап всех файлов,<br /> а также дамп базы данных средствами хостинг-провайдера.
					</span><br />
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<div class="display_none shadow_some step3 step4 step5 step7">
				<div class="padding_big">
					<div class="loading">
						<p class="vvod_key">Установка системы</p>
						<p class="slider">
							<a href="#" class="wrapper">Показать ход установки</a>
						</p>
						<div class="clear"></div>
						<div class="b_input">
							<img alt="" src="<?= INSTALL_SERVER ?>/progress_bar_img.gif" class="progressbar_img" />
						</div>
						<div class="progressbar_wrap" style="display:none;">
							<div class="vnutrenniy" class="scroll-pane"></div>
						</div>
					</div>
					<div class="info display_none">
						<div class="img_stop">
							<img alt="" src="<?= INSTALL_SERVER ?>/ikon_stop.png" />
						</div>
						<div class="img_stop_text"></div>
					</div>
				</div>
			</div>

			<div class="display_none third_block shadow_some step8">
				<div class="padding_big">
					<div class="display_block_left mar_left220px">
						<label>Логин<br />
							<input name="sv_login" type="text" value="<?php echo isset($ini['SUPERVISOR']['login']) ? $ini['SUPERVISOR']['login'] : ''; ?>"
							       tabindex="1" /></label><br /><br />
						<label>Пароль<br />
							<input name="sv_password" type="password" value="<?php echo isset($ini['SUPERVISOR']['password']) ? $ini['SUPERVISOR']['password'] : ''; ?>"
							       tabindex="3" /></label>
					</div>
					<div class="display_block_left posit_text24px">
						<label>E-mail<br />
							<input name="sv_email" type="text" value="<?php echo isset($ini['SUPERVISOR']['email']) ? $ini['SUPERVISOR']['email'] : ''; ?>"
							       tabindex="2" /></label><br /><br />
						<label>Пароль ещё раз<br />
							<input name="sv_password2" type="password" value="<?php echo isset($ini['SUPERVISOR']['password']) ? $ini['SUPERVISOR']['password'] : ''; ?>"
							       tabindex="4" /></label>
					</div>
					<div class="info display_none">
						<div class="img_stop">
							<img alt="" src="<?= INSTALL_SERVER ?>/ikon_stop.png" />
						</div>
						<div class="img_stop_text"></div>
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<!-- CHANGE TYPE OF SITE-->
			<div class="display_none fourth_block shadow_some select_demosite_type">
				<div class="paid_solutions" style="display: none;">

					<input type="radio" name="type_of_site" id="type_of_site1" value="paid" /><label for="type_of_site1"><span></span>Мои покупки
					</label>
					<div class="description"></div>
				</div>
				<div class="umiru_templates">

					<input type="radio" name="type_of_site" id="type_of_site2" value="umiru" /><label for="type_of_site2"><span></span>Бесплатные готовые решения
						<img src="<?= INSTALL_SERVER ?>//umiru.png" />
					</label>
					<div class="description">
						<p>Установите одно из более 500 готовых решений для разных сфер бизнеса.</p>
					</div>
				</div>
				<div class="demo_templates">

					<input type="radio" name="type_of_site" id="type_of_site3" value="demo" /><label for="type_of_site3"><span></span>Демо-сайт
						<img src="<?= INSTALL_SERVER ?>/demo.png" />
					</label>
					<div class="description">
						<p>Демонстрационные сайты, включающие все возможности UMI.CMS: модули, функции и настройки, структуру и контент.</p>
						<p>Демо-сайты могут использоваться как образец для веб-разработки.</p>
					</div>
				</div>
				<div class="clear"></div>
			</div>

			<!--// CHANGE TYPE OF SITE-->
			<!-- CHANGE DEMO SITE -->
			<div class="display_none fourth_block shadow_some select_our_demosite">
				<div class="no_demo shadow_some">
					<input type="radio" name="demosite" id="demosite" value="_blank" /><label for="demosite"><span></span>Без демо-сайта</label>
				</div>
				<div class="clear"></div>
				<div class="demo_example_left posit_text24px"></div>
				<div class="demo_example_left"></div>
				<div class="clear"></div>
			</div>

			<!--// CHANGE DEMO SITE-->
			<!--// UMIRU -->
			<div class="display_none fourth_block shadow_some select_umi_demosite">
				<div class="choose_umisite">
					<div class="category">
						<ul></ul>
					</div>
					<div class="umiru_body">
						<div class="doc">
							<input class="search" placeholder="Введите номер сайта" value="" type="text" />
							<input value="Найти" class="next_step_submit" type="submit" />
							<div class="paging">
								<a class="left_arrow"></a>
								<div class="pages"></div>
								<a class="right_arrow allow"></a>
								<div class="clear"></div>
							</div>
						</div>
						<div class="site_holder"></div>
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<!--// UMIRU -->
			<div class="display_none last_block text_align2 shadow_some step9">
				<div class="padding_big">
					<img alt="" src="<?= INSTALL_SERVER ?>/galochka_big.png" />
					<p class="the_end_p">Установка системы завершена</p>
					<p class="the_end_p" style="font-size: 0.9em; margin-bottom: 30px; line-height: 1.5em">В целях безопасности убедитесь, что в корневой директории сайта
						отсутствует файл install.ini<br /><b>Если он не был удален автоматически, удалите его вручную.</b></p>
					<div class="next_step_but"><a href="/"><span>Перейти на сайт</span></a></div>
					<div class="clear"></div>
				</div>
			</div>

			<div class="display_none last_block text_align2 shadow_some step10">
				<div class="padding_big">
					<p class="the_end_p">Если вы хотите переустановить систему, удалите файл "installed"<br> из корневой директории сайта и обновите эту страницу.</p>
					<?php
					if (isBackup()) {
						echo '<p class="the_end_p">Если вы делали бэкап (средствами UMI.CMS) <br>и хотите восстановить систему, создайте в корневой<br> директории сайта файл "restore" и обновите эту страницу.</p>';
					}
					?>
					<p class="the_end_p"><b>Внимание! Переустановка или восстановление системы<br />приведут к уничтожению всех имеющихся сейчас<br /> данных вашего сайта. <br />Это
							действие нельзя будет отменить.</b></p>
					<div class="clear"></div>
				</div>
			</div>

			<div class="display_none last_block text_align_if_error step11">
				<p class="the_end_p"><?php echo $error_content; ?></p>
				<div class="clear"></div>
			</div>

			<div class="display_none shadow_some step20">
				<div class="padding_big">
					<div class="loading">
						<p class="vvod_key">Восстановление</p>
						<p class="slider">
							<a href="#" class="wrapper">Показать подробности</a>
						</p>
						<div class="clear"></div>
						<div class="b_input">
							<img alt="" src="<?= INSTALL_SERVER ?>/progress_bar_img.gif" class="progressbar_img" />
						</div>
						<div class="progressbar_wrap" style="display:none;">
							<div class="vnutrenniy" class="scroll-pane"></div>
						</div>
					</div>
					<div class="info display_none">
						<div class="img_stop">
							<img alt="" src="<?= INSTALL_SERVER ?>/ikon_stop.png" />
						</div>
						<div class="img_stop_text"></div>
					</div>
				</div>
			</div>
		</div>

		<div class="next_step">
			<input type="button" class="back_step_submit marginr_2px back" value="<   Назад" />
			<input type="submit" class="back_step_submit marginr_px next" value="Далее   >" disabled="disabled" />
		</div>

		<script src="<?= INSTALL_SERVER ?>/a.php" type="text/javascript"></script>

		<div id="install_ad">
			<script type="text/javascript">show_install_ad();</script>
		</div>

		<div class="footer">
			<div class="load_bottom">
				<p class="step_up">Шаг 1 из 8</p>
				<ul>
					<li class="list_style_noneleft">
						<div class="color_dif">Проверка <br />подлинности</div>
					</li>
					<li>
						<div>Настройка<br />базы данных</div>
					</li>
					<li>
						<div>Проверка<br />бэкапа</div>
					</li>
					<li>
						<div>Проверка<br />сервера</div>
					</li>
					<li style="display:none">
						<div>Бэкап<br />системы</div>
					</li>
					<li>
						<div>Установка<br />системы</div>
					</li>
					<li>
						<div>Выбор<br />решения</div>
					</li>
					<li>
						<div>Установка<br />сайта</div>
					</li>
					<li style="width /*\**/:84px\9; *width:84.34px;">
						<div>Настройка<br />доступа</div>
					</li>
				</ul>
			</div>
		</div>
	</form>

	<script src="<?= INSTALL_SERVER ?>/js/jquery-1.4.2.min.js" type="text/javascript"></script>
	<script src="<?= INSTALL_SERVER ?>/js/jquery.corner.js" type="text/javascript"></script>
	<script src="<?= INSTALL_SERVER ?>/js/script.js" type="text/javascript"></script>
	<script type="text/javascript">
		var installServer = '<?=INSTALL_SERVER?>';
		var step = <?php echo $step; ?>;
		var demoSite = <?php echo($demosite == '' ? "''" : "'" . $demosite . "'"); ?>;
		var sleep = <?php echo $sleep; ?>;
	</script>
</body>
</html>
