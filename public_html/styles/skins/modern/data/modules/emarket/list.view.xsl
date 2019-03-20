<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
>

	<!-- Шаблон вкладки "Заказы" -->
	<xsl:template match="/result[@method = 'orders']/data[@type = 'list' and @action = 'view']">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="content-type">objects</xsl:with-param>
					<xsl:with-param name="control-params">orders</xsl:with-param>
					<xsl:with-param name="domains-show">1</xsl:with-param>
					<xsl:with-param name="hide-csv-import-button">1</xsl:with-param>
					<xsl:with-param name="js-ignore-props-edit">['order_items', 'number', 'customer_id', 'payment_name', 'delivery_name']</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Скидки" -->
	<xsl:template match="/result[@method = 'discounts']/data[@type = 'list' and @action = 'view']">
		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<a href="{$lang-prefix}/admin/emarket/discount_add/" class="btn color-blue">
					<xsl:text>&label-add-discount;</xsl:text>
				</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="content-type">objects</xsl:with-param>
					<xsl:with-param name="control-params">discounts</xsl:with-param>
					<xsl:with-param name="js-ignore-props-edit">['discount_type_id']</xsl:with-param>
					<xsl:with-param name="enable-objects-activity">1</xsl:with-param>
					<xsl:with-param name="allow-drag">1</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Валюты" -->
	<xsl:template match="/result[@method = 'currency']/data[@type = 'list' and @action = 'view']">
		<script src="/styles/skins/modern/data/modules/emarket/updateCurrencies.js?{$system-build}" />

		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<a href="{$lang-prefix}/admin/emarket/currency_add/" class="btn color-blue">
					<xsl:text>&label-add-currency;</xsl:text>
				</a>

				<a id="updateCurrenciesButton" class="btn color-blue">
					&label-button-update-currencies;
				</a>
			</div>

			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="content-type">objects</xsl:with-param>
					<xsl:with-param name="control-params">currency</xsl:with-param>
					<xsl:with-param name="allow-drag">1</xsl:with-param>
					<xsl:with-param name="disable-name-filter">true</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Доставка" -->
	<xsl:template match="/result[@method = 'delivery']/data[@type = 'list' and @action = 'view']">
		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<div class="btn-select bottom-list color-blue" style="width:325px;">
					<div class="selected">
						<a href="{$lang-prefix}/admin/emarket/delivery_add/" class="type_select btn color-blue"
						   umi:type="emarket::delivery" umi:prevent-default="true">
							<xsl:text>&label-add-delivery;</xsl:text>
						</a>
					</div>
					<ul class="list" />
				</div>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="content-type">objects</xsl:with-param>
					<xsl:with-param name="control-params">delivery</xsl:with-param>
					<xsl:with-param name="js-ignore-props-edit">['delivery_type_id']</xsl:with-param>
					<xsl:with-param name="hide-csv-import-button">1</xsl:with-param>
					<xsl:with-param name="enable-objects-activity">1</xsl:with-param>
					<xsl:with-param name="allow-drag">1</xsl:with-param>
					<xsl:with-param name="js-visible-props-menu">'name[250px]|domain_id_list[250px]'</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Оплата" -->
	<xsl:template match="/result[@method = 'payment']/data[@type = 'list' and @action = 'view']">
		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<div class="btn-select bottom-list color-blue" style="width:290px;">
					<div class="selected">
						<a href="{$lang-prefix}/admin/emarket/payment_add/" class="type_select btn color-blue"
						   umi:type="emarket::payment" umi:prevent-default="true">
							&label-add-payment;
						</a>
					</div>
					<ul class="list"/>
				</div>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="content-type">objects</xsl:with-param>
					<xsl:with-param name="control-params">payment</xsl:with-param>
					<xsl:with-param name="js-ignore-props-edit">['payment_type_id']</xsl:with-param>
					<xsl:with-param name="hide-csv-import-button">1</xsl:with-param>
					<xsl:with-param name="enable-objects-activity">1</xsl:with-param>
					<xsl:with-param name="allow-drag">1</xsl:with-param>
					<xsl:with-param name="js-visible-props-menu">'name[250px]|domain_id_list[250px]'</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Склады" -->
	<xsl:template match="/result[@method = 'stores']/data[@type = 'list' and @action = 'view']">
		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<a href="{$lang-prefix}/admin/emarket/store_add/" class="btn color-blue">&label-add-store;</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<script>
			function onAfterSetProperty(store_id, property_name, value) {
				if (property_name == 'primary' &amp;&amp; value) {
					jQuery('.tableItemContainer td[id$=primary]').not('#c_tree-emarket-stores_'+store_id+'_primary').html('<div style="width: 100px;"></div>');
				}
			}
		</script>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="content-type">objects</xsl:with-param>
					<xsl:with-param name="control-params">stores</xsl:with-param>
					<xsl:with-param name="allow-drag">1</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Статистика" -->
	<xsl:template match="/result[@method = 'stats']/data[@type = 'list' and @action = 'view']">
		<div class="tabs editing notextselect">
			<div class="section selected">
				<a class="ordersStats">&stat-order;</a>
			</div>
			<div class="section">
				<a class="topPopularProduct">&stat-popular;</a>
			</div>
			<div class="section">
				<a class="commonStats">&stat-common;</a>
			</div>
		</div>
		<div class="location">
			<script type="text/javascript" src="/styles/skins/modern/design/js/emarketstat.js?{$system-build}"/>
			<div class="loc-left">
				<xsl:call-template name="date-picker-range">
					<xsl:with-param name="fromDate" select="//data/@fromDate"/>
					<xsl:with-param name="toDate" select="//data/@toDate"/>
				</xsl:call-template>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<div id="ordersStats" class="tabsStat">
					<xsl:call-template name="orders-stats" />
				</div>
				<div id="topPopularProduct" class="tabsStat" style="display:none;">
					<xsl:call-template name="top-popular-product" />
				</div>
				<div id="commonStats" class="tabsStat" style="display:none;">
					<xsl:apply-templates select="//group[@name='stats']" mode="statsOrder"/>
				</div>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон группы статистики -->
	<xsl:template match="group[@name='stats']" mode="statsOrder">
		<h3 style="text-align:center">
			<xsl:value-of select="@label" />
		</h3>
		<table class="tableContent btable btable-bordered btable-striped" id="statTable">
			<thead>
				<tr>
					<th/>
					<th>&stats-order-by-range;</th>
					<th>&stats-order-all;</th>
				</tr>
			</thead>
			<tbody>
				<xsl:apply-templates select="option" mode="statsOrder" />
			</tbody>
		</table>
	</xsl:template>

	<!-- Шаблон показателя статистики -->
	<xsl:template match="option" mode="statsOrder">
		<tr class="stat">
			<xsl:attribute name="data-id">
				<xsl:value-of select="@type"/>
			</xsl:attribute>
			<td class="eq-col">
				<xsl:value-of select="@label"/>
			</td>
			<td class="stat-value">
				&js-index-stat-nodata;
			</td>
			<td class="stat-value-all">
				&js-index-stat-nodata;
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон контрола для выбора диапазона статистических данных -->
	<xsl:template name="date-picker-range">
		<xsl:param name="fromDate" />
		<xsl:param name="toDate" />
		<form id="statdate_settings" method="post">
			<div class="buttons">
				<div>
					<input type="button" id="startEmarketStat" value="&orders-filter;" class="btn color-blue btn-small"/>
				</div>
			</div>
			<div class="datePicker">
				<span>&orders-date;:</span>
				<label for="start_date">
					<acronym>&orders-date-from;</acronym>
					<input id="fromDate" type="text" style="width:auto; display: inline-block;" class="default" value="{document(concat('udata://system/convertDate/', $fromDate, '/(Y-m-d%20H:i:s)'))}" name="fromDate" />
				</label>
			</div>
			<div class="datePicker">
				<label for="end_date">
					<acronym>&orders-date-to;</acronym>
					<input id="toDate" type="text" style="width:auto; display: inline-block;" class="default" value="{document(concat('udata://system/convertDate/', $toDate, '/(Y-m-d%20H:i:s)'))}" name="toDate" />
				</label>
			</div>
		</form>
	</xsl:template>

	<!-- Шаблон вывода топа популярных товаров -->
	<xsl:template name="top-popular-product">
		<h3 style="text-align:center">
			<xsl:value-of select="//group[@name='popular']/option[@name='max-popular']/value" />
			<xsl:apply-templates select="//group[@name='popular']/option[@name='max-popular']/value" mode="suffix"/>
		</h3>
		<table class="tableContent btable btable-bordered" id="statTopPopular">
			<thead>
				<tr>
					<th style="width:20px">#</th>
					<th>&stat-popular-name;</th>
					<th name="amount"><div>&stat-popular-amount;</div></th>
					<th name="price"><div>&stat-popular-price;</div></th>
				</tr>
			</thead>
			<tbody />
		</table>
	</xsl:template>

	<!-- Шаблоны заголовка топа популярных товаров ("популярных товаров") -->
	<xsl:template match="//group[@name='popular']/option[@name='max-popular']/value" mode="suffix"> &top-populat-items;</xsl:template>

	<!-- Шаблоны заголовка топа популярных товаров ("популярных товара") -->
	<xsl:template match="//group[@name='popular']/option[@name='max-popular']/value[not(. &gt; 10 and . &lt; 20) and ((. mod 10) = 2 or (. mod 10) = 3 or (. mod 10) = 4)]" mode="suffix"> &top-populat-items2;</xsl:template>

	<!-- Шаблоны заголовка топа популярных товаров ("популярный товар") -->
	<xsl:template match="//group[@name='popular']/option[@name='max-popular']/value[not(. &gt; 10 and . &lt; 20) and ((. mod 10) = 1)]" mode="suffix"> &top-populat-items3;</xsl:template>

	<!-- Шаблон списка оплаченных заказов -->
	<xsl:template name="orders-stats">
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="content-type">objects</xsl:with-param>
			<xsl:with-param name="control-params">realpayments</xsl:with-param>
			<xsl:with-param name="domains-show">1</xsl:with-param>
			<xsl:with-param name="hide-csv-import-button">1</xsl:with-param>
			<xsl:with-param name="show-toolbar">0</xsl:with-param>
			<xsl:with-param name="enable-edit">false</xsl:with-param>
			<xsl:with-param name="js-required-props-menu">['order_items','total_price','payment_id','customer_id','order_date','payment_date','http_target','source_domain']</xsl:with-param>
			<xsl:with-param name="js-visible-props-menu">'order_items[250px]|total_price[250px]|payment_id[250px]|customer_id[250px]|order_date[250px]|payment_date[250px]|http_target[250px]|source_domain[250px]'</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

</xsl:stylesheet>
