<?php

	$FORMS = [];

	$FORMS['captcha'] = <<<CAPTCHA
<p>
	Введите текст на картинке<br />
	<img src="/captcha.php?lang_id=%lang_id%" /><br />
	<input type="text" name="captcha" />
</p>

CAPTCHA;

	$FORMS['recaptcha'] = <<<RECAPTCHA
<script src='%recaptcha-url%?hl=ru'></script>
<div class="%recaptcha-class%" data-sitekey="%recaptcha-sitekey%"></div>

RECAPTCHA;
