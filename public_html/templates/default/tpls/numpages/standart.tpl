<?php
	$FORMS = [];

	$FORMS['pages_block'] = <<<END

	<div class="numpage" style="padding-left: 10px;">
		<div class="small">Страницы:&nbsp;&nbsp;%pages%</div>
		
	</div>

END;



	$FORMS['pages_item'] = <<<END
	<a href="%link%"><b>%num%</b></a>&nbsp;%quant%
END;

	$FORMS['pages_item_a'] = <<<END
	<span class="active_num">%num%</span>&nbsp;%quant%
END;

	$FORMS['pages_quant'] = <<<END
|
END;

	$FORMS['pages_block_empty'] = <<<END

	<div class="numpage" style="padding-left: 10px;">
		
	</div>


END;
?>
