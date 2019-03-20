<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Неперехваченное исключение</title>
	<script type="text/javascript">
		function displayTrace(link) {
			if(link) link.style.display = 'none';
			document.getElementById('trace').style.display = '';
		}
	</script>
	<link href="/errors/style.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="exception">
		<div id="header">
			<h1>Неперехваченное исключение</h1>
			<a target="_blank" title="UMI.CMS" href="https://umi-cms.ru"><img class="logo" src="/styles/common/images/main_logo.png" alt="UMI.CMS" /></a>
		</div>
		<div id="message">
			<h2>Ошибка <?= $v42552b1f133f9f8eb406d4f306ea9fd1->type ? '(' . $v42552b1f133f9f8eb406d4f306ea9fd1->type . ')' : '' ?>: <?= $v42552b1f133f9f8eb406d4f306ea9fd1->message;?></h2>
			<p id="solution" style="display: none;"></p>
			<?php if (DEBUG_SHOW_BACKTRACE) {?>
				<p>
					<a href="#" onclick="javascript: displayTrace(this);">
						Показать отладочную информацию
					</a>
				</p>
				<div id="trace" class="trace" style="display: none;"><pre><?= $v42552b1f133f9f8eb406d4f306ea9fd1->traceAsString;?></pre></div>
			<?php }?>
		</div>
		<div id="footer">
			<p><a href="https://www.umi-cms.ru/support">Поддержка пользователей UMI.CMS</a></p>
		</div>
	</div>
</body>
</html>
