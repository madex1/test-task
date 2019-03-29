<?php
$FORMS = [];

$FORMS['payment_block'] = <<<END
<form action="%pre_lang%/emarket/purchase/payment/choose/do/" method="post">
	Выберите подходящий вам способ оплаты:
	<ul>
		%items%
	</ul>
	
	<p>
		<input type="submit" />
	</p>
</form>
END;

$FORMS['payment_item'] = <<<END
	<li><input type="radio" name="payment-id" value="%id%" /> %name%</li>
END;

$FORMS['bonus_block'] = <<<END
	
	<form id="bonus_payment" method="post" action="%pre_lang%/emarket/purchase/payment/bonus/do/">
		<p>Вы можете оплатить ваш заказ накопленными бонусами. Доступно бонусов на %available_bonus%.</p>
		<p>Вы собираетесь оплатить заказ на сумму %actual_total_price%.</p>
		<label><input type="text" name="bonus"/>Количество бонусов</label>
		<input type="submit" value="Продолжить" />
	</form>

END;

?>
