<?php
$FORMS = [];

$FORMS['groups_block'] = <<<END
<ul>
	%lines%
</ul>
END;

$FORMS['groups_line'] = <<<END
<li>
	%data getPropertyGroup('%id%', '%group_id%', '%template%')%
</li>
END;


$FORMS['group'] = <<<END
[Group], %title% (%name%)
<ul>
    %lines%
</ul>
END;

$FORMS['group_line'] = <<<END
<li>
    %prop%
</li>
END;



$FORMS['int'] = <<<END
[Int], %title% (%name%): %value%

END;

$FORMS['price'] = <<<END
[Price], %title% (%name%): %value%

END;


$FORMS['string'] = <<<END
[String], %title% (%name%): %value%

END;

$FORMS['text'] = <<<END
[Text], %title% (%name%): %value%

END;


$FORMS['relation'] = <<<END
[Relation] %title% (%name%): %value% (%object_id%)

END;

$FORMS['file'] = <<<END
[File], %title% (%name%)<br />
Filename: %filename%;<br />
Filepath: %filepath%;<br />
Filepath: %src%;<br />
Size: %size%<br />
Extension: %ext%<br />
<a href="%src%">%src%</a>
END;

$FORMS['swf_file'] = $FORMS['img_file'] = <<<END
[Image File], %title% (%name%)<br />
Filename: %filename%;<br />
Filepath: %filepath%;<br />
Filepath: %src%;<br />
Size: %size%<br />
Extension: %ext%<br />
Alt: %img_alt%<br />
Title: %img_title%<br />
%width% %height%<br />
<img src="%src%" width="%width%" height="%height%" alt="%img_alt%" title="%img_title%" />

END;

$FORMS['date'] = <<<END
[Date], %title% (%name%): %value%

END;

$FORMS['boolean_yes'] = <<<END
[Boolean], %title% (%name%): Да
END;

$FORMS['boolean_no'] = <<<END
[Boolean], %title% (%name%): Нет
END;


$FORMS['wysiwyg'] = <<<END
[HTML text], %title% (%name%): %value%

END;


/* Multiple property blocks */

$FORMS['relation_mul_block'] = <<<END
[Relation multiple], %title% (%name%): %items%
END;

/* Multiple property item */

$FORMS['relation_mul_item'] = <<<END
%value%(%object_id%)%quant%
END;

/* Multiple property quant */
$FORMS['symlink_block'] = <<<END
[Symlink multiple], %title%: %items%
END;

$FORMS['symlink_item'] = <<<END
<a href="%link%">%value%(%id%, %object_id%)</a>%quant%
END;

$FORMS['symlink_quant'] = <<<END
, 
END;


$FORMS['guide_block'] = <<<END
<select name="guide_%guide_id%">
%items%
</select>
END;

$FORMS['guide_block_empty'] = <<<END

END;

$FORMS['guide_block_line'] = <<<END
<option value="%id%">%text%</option>
END;

$FORMS['trade_offer_collection_block'] = <<<END
<div>
	<p>Идентификатор поля = "%field_id%"</p>
	<p>Гуид поля = "%name%"</p>
	<p>Наименование поля = "%title%"</p>
	<ul>
		%offer_collection%
	</ul>
</div>
END;

$FORMS['trade_offer_collection_block_empty'] = <<<END
<div>Торговые предложения не указаны</div>
END;

$FORMS['trade_offer_block'] = <<<END
<li>
	<p>Идентификатор предложения = "%id%"</p>
	<p>Идентификатор объекта = "%object-id%"</p>
	<p>Идентификатор типа = "%type-id%"</p>
	<p>Имя предложения = "%name%"</p>
	<p>Артикул Предложения = "%vendor-code%"</p>
	%price_collection%
	%stock_balance_collection%
	%characteristic_collection%
</li>
END;

$FORMS['trade_offer_price_collection_block'] = <<<END
<ul>
	%items%
</ul>
END;

$FORMS['trade_offer_price_collection_block_empty'] = <<<END
<div>Цены не заданы</div>
END;

$FORMS['trade_offer_price'] = <<<END
<li>
	<p>Идентификатор цены = "%id%"</p>
	<p>Значение цены = "%value%"</p>
	<p>Форматированное значение цены = "%formatted-value%"</p>
	<p>Основная ли цена = "%is-main%"</p>
	<p>Идентификатор типа цены = "%type-id%"</p>
	<p>Название типа цены = "%type-title%"</p>
</li>
END;

$FORMS['trade_offer_stock_balance_collection'] = <<<END
<ul>
	%items%
</ul>
END;

$FORMS['trade_offer_stock_balance_collection_empty'] = <<<END
<div>Остатки не заданы</div>
END;

$FORMS['trade_offer_stock_balance'] = <<<END
<li>
	<p>Идентификатор остатка = "%id%"</p>
	<p>Идентификатор склада = "%stock-id%"</p>
	<p>Название склада = "%stock-name%"</p>
	<p>Значение остатка = "%value%"</p>
</li>
END;

$FORMS['trade_offer_characteristic_collection'] = <<<END
<ul>
	%items%
</ul>
END;

$FORMS['trade_offer_characteristic_collection_empty'] = <<<END
<div>Характеристики не заданы</div>
END;

?>
