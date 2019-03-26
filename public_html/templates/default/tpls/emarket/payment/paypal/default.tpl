<?php
$FORMS = [];

$FORMS['form_block'] = <<<END

<form action="%formAction%" method="post">

	<input type="hidden" name="cmd" value="_xclick">
	<input type="hidden" name="business" value="%paypalemail%">
	<input type="hidden" name="item_name" value="Payment for order #%order_id%">
	<input type="hidden" name="item_number" value="%order_id%">
	<input type="hidden" name="amount" value="%total%">
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="return" value="%return_success%">
	<input type="hidden" name="rm" value="2">
	<input type="hidden" name="cancel_return" value="%cancel_return%">
	<input type="hidden" name="notify_url" value="%notify_url%" />
	<input type="hidden" name="currency_code" value="%currency%">

	<p>
		Нажмите кнопку "Оплатить" для перехода на сайт платежной системы <strong>PayPal</strong>.
	</p>        

	<p>
		<input type="submit" value="Оплатить" />
	</p>
</form>
END;

?>
