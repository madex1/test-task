<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY nbsp "&#160;"> ]>

<xsl:stylesheet	version="1.0"
				   xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				   xmlns:php="http://php.net/xsl"
				   xsl:extension-element-prefixes="php"
				   exclude-result-prefixes="php">

	<xsl:output encoding="utf-8" method="html" indent="yes"/>

	<xsl:template match="/">
		<xsl:apply-templates />
	</xsl:template>

	<xsl:template match="/udata">
		<xsl:apply-templates mode="print-reciept" />
	</xsl:template>

	<xsl:template match="/udata[@module='emarket' and @method='order']">
		<xsl:apply-templates select="document(concat('uobject://',/udata/@id))/udata/object" mode="print-reciept" />
	</xsl:template>

	<xsl:template match="/udata/object" mode="print-reciept">
		<xsl:variable name="payment" select="document(concat('uobject://',//property[@name='payment_id']/value/item/@id))/udata/object/properties" />
		<xsl:variable name="price" select="//property[@name='total_price']/value" />
		<xsl:variable name="customer" select="document(concat('uobject://',//property[@name='customer_id']/value/item/@id))/udata/object" />
		<xsl:variable name="name-string" select="concat($customer//property[@name='lname']/value, ' ', $customer//property[@name='fname']/value, ' ', $customer//property[@name='father_name']/value)" />
		<xsl:variable name="address"  select="document(concat('uobject://',//property[@name='delivery_address']/value/item[1]/@id))/udata/object" />
		<xsl:variable name="address-string">
			<xsl:choose>
				<xsl:when test="$address">
					<xsl:value-of select="concat($address//property[@name='index']/value,', ',$address//property[@name='region']/value,', ',$address//property[@name='city']/value,', ',$address//property[@name='street']/value,', д. ',$address//property[@name='house']/value,', кв.',$address//property[@name='flat']/value)" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select='""' />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<html>
			<head id="header">
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>Квитанция для физических лиц</title>

				<script src="/js/emarket_receipt.js" />
				<link rel="stylesheet" href="/styles/common/css/emarket_receipt.css" />
			</head>

			<body id="receipt">
				<div style="width:180mm;">
					Для оплаты выбранных товаров, пожалуйста, распечатайте данную квитанцию.
					Вы сможете произвести по ней оплату в Сбербанке или любом другом банке, обслуживающем физических лиц.<br />
					<hr/>
				</div>
				<table border="0" style="width: 180mm; height: 145mm;" class="table">
					<tr style="height: 70mm;">
						<td class="center border width_50 strong">
							Извещение<div style="height: 53mm;"></div>Кассир
						</td>
						<td class="border">
							<table border="0" class="margin width_122">
								<tr><td class="right"><i class="small">Форма № ПД-4</i></td></tr>
								<tr><td class="underline"><xsl:value-of select="$payment//property[@name='reciever']/value" />&#160;</td></tr>
								<tr><td class="small center">(наименование получателя платежа)</td></tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="underline" style="width:37mm;"><xsl:value-of select="$payment//property[@name='reciever_inn']/value" />&#160;</td>
									<td style="width:9mm;">&#160;</td>
									<td class="underline"><xsl:value-of select="$payment//property[@name='reciever_account']/value" />&#160;</td>
								</tr>
								<tr>
									<td class="small center">(ИНН получателя платежа)</td>
									<td class="small">&#160;</td>
									<td class="small center">(номер счета получателя платежа)</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td>в </td>
									<td class="small underline" style="width:73mm;" ><xsl:value-of select="$payment//property[@name='reciever_bank']/value" />&#160;</td>
									<td class="right">БИК </td>
									<td class="underline" style="width:33mm;"><xsl:value-of select="$payment//property[@name='bik']/value" />&#160;</td>
								</tr>
								<tr>
									<td></td>
									<td class="small center">(наименование банка получателя платежа)</td>
									<td></td>
									<td></td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td width="1%" class="wrap">Номер кор./сч. банка получателя платежа  </td>
									<td width="100%" class="underline"><xsl:value-of select="$payment//property[@name='reciever_bank_account']/value" /></td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="underline" style="width:60mm;"><xsl:text>Счет № </xsl:text><span id="py-order-name-1"><xsl:text>И/</xsl:text><xsl:value-of select="@id" /><xsl:text>/Ф</xsl:text></span><xsl:text> от </xsl:text><xsl:value-of select="php:function('date', 'd.m.Y')" /></td>
									<td style="width:2mm;"> </td>
									<td class="underline">&#160;</td>
								</tr>
								<tr>
									<td class="small center">(наименование платежа)</td>
									<td class="small"> </td>
									<td class="small center">(номер лицевого счета (код) плательщика)</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td width="1%" class="wrap">Ф.И.О. плательщика  </td>
									<td width="99%" class="underline">  <span id="py-fio-1"><xsl:value-of select="$name-string" /></span>  </td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td width="1%" class="wrap">Адрес плательщика  </td>
									<td width="99%" class="underline">  <span id="py-address-1"><xsl:value-of select="$address-string" /></span>  </td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td>Сумма платежа  <span class="u" id="py-order-price-1"><xsl:value-of select="floor($price)" /></span> руб. <span class="u"> <xsl:value-of select="round((number($price)-floor($price))*100)" /> </span> коп.</td>
									<td class="right">  Сумма платы за услуги  _____ руб. ____ коп.</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td>Итого  _______ руб. ____ коп.</td>
									<td class="right">  «______»________________ 201____ г.</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="small">
										С условиями приема указанной в платежном документе суммы, в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен.
									</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="right strong">Подпись плательщика _____________________</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr valign="top">
						<td class="width_50 border center strong" style="height:70mm;">
							<div style="height: 58mm;"></div>Квитанция<div style="height:11px"></div>Кассир
						</td>
						<td class="border">
							<table border="0" class="margin width_122">
								<tr><td class="right small"> </td></tr>
								<tr><td class="underline"><xsl:value-of select="$payment//property[@name='reciever']/value" />&#160;</td></tr>
								<tr><td class="center small">(наименование получателя платежа)</td></tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="underline" style="width:37mm;"><xsl:value-of select="$payment//property[@name='reciever_inn']/value" /></td>
									<td style="width:9mm;"> </td>
									<td class="underline"><xsl:value-of select="$payment//property[@name='reciever_account']/value" /></td>
								</tr>
								<tr>
									<td class="center small">(ИНН получателя платежа)</td>
									<td class="small"> </td>
									<td class="center small">(номер счета получателя платежа)</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td>в </td>
									<td class="underline small" style="width:73mm;"><xsl:value-of select="$payment//property[@name='reciever_bank']/value" />&#160;</td>
									<td class="right">БИК  </td>
									<td class="underline" style="width:33mm;"><xsl:value-of select="$payment//property[@name='bik']/value" />&#160;</td>
								</tr>
								<tr>
									<td></td>
									<td class="center small">(наименование банка получателя платежа)</td>
									<td></td>
									<td></td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td width="1%" class="wrap">Номер кор./сч. банка получателя платежа  </td>
									<td width="100%" class="underline"><xsl:value-of select="$payment//property[@name='reciever_bank_account']/value" /></td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="underline" style="width:60mm;"><xsl:text>Счет № </xsl:text><span id="py-order-name-2"><xsl:text>И/</xsl:text><xsl:value-of select="@id" /><xsl:text>/Ф</xsl:text></span><xsl:text> от </xsl:text><xsl:value-of select="php:function('date', 'd.m.Y')" /></td>
									<td style="width:2mm;">&#160;</td>
									<td class="underline">&#160;</td>
								</tr>
								<tr>
									<td class="center small">(наименование платежа)</td>
									<td class="small"> </td>
									<td class="center small">(номер лицевого счета (код) плательщика)</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td width="1%" class="wrap">Ф.И.О. плательщика  </td>
									<td width="99%" class="underline">  <span id="py-fio-2"><xsl:value-of select="$name-string" /></span>  </td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td width="1%" class="wrap">Адрес плательщика  </td>
									<td width="99%" class="underline">  <span id="py-address-2"><xsl:value-of select="$address-string" /></span>  </td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td>Сумма платежа <span class="u" id="py-order-price-2"><xsl:value-of select="floor($price)" /></span> руб. <span class="u"> <xsl:value-of select="round((number($price)-floor($price))*100)" /> </span> коп.</td>
									<td class="right">Сумма платы за услуги  _____ руб. ____ коп.</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td>Итого  _______ руб. ____ коп.</td>
									<td class="right">  «______»________________ 201____ г.</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="small">
										С условиями приема указанной в платежном документе суммы, в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен.
									</td>
								</tr>
							</table>
							<table border="0" class="margin width_122">
								<tr>
									<td class="right strong">Подпись плательщика _____________________</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</body>
		</html>
	</xsl:template>

</xsl:stylesheet>
