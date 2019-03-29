<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink">

	<!-- Edit order -->
	<xsl:template match="/result[@method = 'order_edit']/data/object" mode="form-modify">
		<xsl:variable name="order-info" select="document(concat('udata://emarket/order/', @id))/udata" />
		<xsl:variable name="customer-id" select="$order-info/customer/object/@id" />
		<xsl:variable name="type-customer" select="$order-info/customer/object/@type-guid" />
		<xsl:variable name="one-click-order" select="//group[@name = 'purchase_one_click']" />

		<xsl:call-template name="notify">
			<xsl:with-param name="order-info" select="$order-info" />
			<xsl:with-param name="one-click-order" select="$one-click-order" />
		</xsl:call-template>

		<!-- Информация о заказе -->
		<xsl:apply-templates select=".//group[@name = 'order_props']" mode="form-modify">
			<xsl:with-param name="show-name"><xsl:text>0</xsl:text></xsl:with-param>
		</xsl:apply-templates>

		<xsl:apply-templates select="//payment" mode="payment-view" />

		<!-- Информация о заказчике -->
		<xsl:apply-templates select="$order-info/customer">
			<xsl:with-param name="customer-id" select="$customer-id" />
			<xsl:with-param name="type-customer" select="$type-customer" />
			<xsl:with-param name="one-click-order" select="$one-click-order" />
		</xsl:apply-templates>

		<xsl:apply-templates select=".//group[@name = 'order_payment_props' or @name = 'order_delivery_props']" mode="form-modify">
			<xsl:with-param name="show-name"><xsl:text>0</xsl:text></xsl:with-param>
		</xsl:apply-templates>

		<xsl:apply-templates select=".//group[@name = 'statistic_info']" mode="form-modify">
			<xsl:with-param name="show-name"><xsl:text>0</xsl:text></xsl:with-param>
		</xsl:apply-templates>

		<!-- Наименования заказа (с удалением) -->
		<xsl:apply-templates select="$order-info" mode="order-items" />

		<!-- Список всех заказов покупателя -->
		<xsl:apply-templates select="document(concat('udata://emarket/ordersList/', $customer-id, '?links'))/udata">
			<xsl:with-param name="customer-id" select="$customer-id" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="group[@name = 'order_props']" mode="form-modify">
		<div class="panel properties-group">
			<div class="header">
				<span>
					<xsl:value-of select="@title" />
				</span>
				<div class="l" /><div class="r" />
			</div>

			<div class="content">
				<div class="field text">
					<label>
						<span class="label">
							<a href="{$lang-prefix}/admin/emarket/order_printable/{/result/data/object/@id}/" target="_blank" class="edit_a">&label-printable-version;</a>
						</span>
					</label>
				</div>
				<xsl:apply-templates select="field" mode="form-modify" />
				<xsl:call-template name="std-form-buttons" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group[@name = 'purchase_one_click']" mode="form-modify">
		<xsl:param name="type-customer" />
		<xsl:param name="customer-id" />

		<xsl:apply-templates select="document(concat('uobject://', field/values/item[@selected]/@id))/udata/object">
			<xsl:with-param name="panel-title" select="@title" />
			<xsl:with-param name="customer-id" select="$customer-id" />
			<xsl:with-param name="type-customer" select="$type-customer" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="field[@name = 'number']" mode="form-modify">
		<div class="field">
			<label>
				<span class="label">
					<acronym>
						<xsl:value-of select="concat(@title, ': #', .)" />
					</acronym>
				</span>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@name = 'customer_id']" mode="form-modify">
		<div class="field">
			<label>
				<span class="label">
					<acronym>
						<xsl:value-of select="concat(@title, ': ')" />
						<xsl:apply-templates select="values/item[@selected = 'selected']" mode="order-customer-link" />
					</acronym>
				</span>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@name = 'order_create_date']" mode="form-modify" />

	<xsl:template match="item" mode="order-customer-link">
		<xsl:variable name="customer-info" select="document(concat('uobject://', @id))/udata" />
		<xsl:variable name="fname" select="$customer-info//property[@name = 'fname']/value" />
		<xsl:variable name="lname" select="$customer-info//property[@name = 'lname']/value" />
		<xsl:variable name="login" select="$customer-info//property[@name = 'login']/value" />

		<xsl:value-of select="concat($fname, ' ', $lname, ' (', $login, ')')" />
	</xsl:template>


	<xsl:template match="field[@name = 'order_items' or @name = 'total_original_price']" mode="form-modify" />
	<xsl:template match="field[@name = 'total_price' or @name = 'total_amount']" mode="form-modify" />

	<xsl:template match="field[@name='delivery_address']" mode="form-modify">
		<xsl:if test="count(values/item[@selected])">
			<xsl:variable name="address" select="document(concat('uobject://', values/item[@selected]/@id))/udata" />
			<div class="field text">
				<label for="{generate-id()}">
					<span class="label">
						<acronym>
							<xsl:apply-templates select="." mode="sys-tips" />
							<xsl:value-of select="@title" />
						</acronym>
						<xsl:apply-templates select="." mode="required_text" />
						<xsl:text>&nbsp;(</xsl:text>
						<a href="{$lang-prefix}/admin/emarket/delivery_address_edit/{values/item[@selected]/@id}/" class="edit_a">
							<xsl:text>&label-edit;</xsl:text>
						</a>
						<xsl:text>)</xsl:text>
					</span>
					<span>
						<xsl:apply-templates select="document(concat('uobject://', values/item[@selected]/@id))/udata/object" mode="delivery-address" />
					</span>
				</label>
			</div>
		</xsl:if>
	</xsl:template>


	<xsl:template match="udata" mode="order-items">
		<xsl:variable name="order-info" select="document(concat('uobject://', @id))/udata" />

		<div class="panel properties-group">
			<div class="header">
				<span>
					<xsl:text>&label-order-items-group;</xsl:text>
				</span>
				<div class="l" /><div class="r" />
			</div>

			<div class="content">

				<div>
					<a href="{$lang-prefix}/admin/emarket/editOrderAsUser/{@id}/">
						<xsl:attribute name="title">&label-edit-as-user-tip;</xsl:attribute>
						<xsl:text>&label-edit-as-user;</xsl:text>
					</a>
				</div>

				<table class="tableContent left">
					<thead>
						<tr>
							<th align="left">
								<xsl:text>&label-order-items-group;</xsl:text>
							</th>

							<th align="left">
								<xsl:text>&label-order-items-current-price;</xsl:text>
							</th>

							<th align="left">
								<xsl:text>&label-order-items-discount;</xsl:text>
							</th>

							<th align="left">
								<xsl:text>&label-order-items-original-price;</xsl:text>
							</th>

							<th align="left">
								<xsl:text>&label-order-items-amount;</xsl:text>
							</th>

							<th align="left">
								<xsl:text>&label-order-items-summ;</xsl:text>
							</th>

							<th>
								<xsl:text>&label-delete;</xsl:text>
							</th>
						</tr>
					</thead>
					<tbody>
						<xsl:apply-templates select="items/item" mode="order-items" />
						<xsl:apply-templates select="discount" mode="order-summary" />
						<xsl:apply-templates select="summary/price/bonus" mode="order-summary" />
						<xsl:apply-templates select="$order-info//group[@name = 'order_delivery_props']" mode="order_delivery" />

						<tr>
							<td>
								<strong>
									<xsl:text>&label-order-items-result;:</xsl:text>
								</strong>
							</td>

							<td colspan="4" />

							<td>
								<strong>
									<xsl:apply-templates select="summary/price/actual" mode="price" />
								</strong>
							</td>
							<td />
						</tr>
					</tbody>
				</table>

				<xsl:call-template name="std-form-buttons" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="discount-size">
		<xsl:apply-templates select="document(@xlink:href)/udata/object" mode="modificator-size" />
	</xsl:template>

	<xsl:template match="object" mode="modificator-size" />

	<xsl:template match="object[.//property[@name = 'proc']]" mode="modificator-size">
		<xsl:value-of select="concat(' &#8212; ', .//property[@name = 'proc']/value, '%')" />
	</xsl:template>

	<xsl:template match="object[.//property[@name = 'size']]" mode="modificator-size">
		<xsl:value-of select="concat(', ', .//property[@name = 'size']/value)" />
	</xsl:template>

	<xsl:template match="discount" mode="order-summary">
		<tr>
			<td>
				<strong>
					<xsl:text>&label-order-discount;</xsl:text>
				</strong>
			</td>

			<td colspan="4">
				<a href="{$lang-prefix}/admin/emarket/discount_edit/{@id}/">
					<xsl:value-of select="@name" />
				</a>
				<xsl:apply-templates select="document(concat('uobject://', @id, '.discount_modificator_id'))//item" mode="discount-size" />
				<xsl:apply-templates select="description" />
			</td>
			<td><xsl:apply-templates select="document(concat('udata://emarket/applyPriceCurrency/', ../summary/price/discount, '/'))/udata/price/actual" mode="price" /></td>
			<td />
		</tr>
	</xsl:template>

	<xsl:template match="bonus" mode="order-summary">
		<tr>
			<td>
				<strong>
					<xsl:text>Использованные бонусы</xsl:text>
				</strong>
			</td>
			<td colspan="4"/>
			<td><xsl:value-of select="."/></td>
			<td />
		</tr>
	</xsl:template>

	<xsl:template match="discount/description" />
	<xsl:template match="discount/description[. != '']">
		<xsl:value-of select="concat(' (', ., ')')" disable-output-escaping="yes" />
	</xsl:template>

	<xsl:template match="item" mode="order-items">
		<tr>
			<td>
				<xsl:apply-templates select="document(concat('uobject://',@id))/udata/object" mode="order-item-name" />
			</td>

			<td>
				<xsl:choose>
					<xsl:when test="price/original &gt; 0">
						<xsl:apply-templates select="price/original" mode="price" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="price/actual" mode="price" />
					</xsl:otherwise>
				</xsl:choose>
			</td>

			<td>
				<xsl:apply-templates select="discount" />
			</td>

			<td>
				<xsl:apply-templates select="price/actual" mode="price" />
			</td>

			<td>
				<input type="text" name="order-amount-item[{@id}]" value="{amount}" size="3" />
			</td>

			<td>
				<xsl:apply-templates select="total-price/actual" mode="price" />
			</td>

			<td class="center">
				<input type="checkbox" name="order-del-item[]" value="{@id}" class="check" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="object" mode="order-item-name">
		<xsl:value-of select="@name" />
	</xsl:template>

	<xsl:template match="object[//property/@name = 'item_link']" mode="order-item-name">
		<a href="{$lang-prefix}/admin/catalog/edit/{//property/value/page/@id}/">
			<xsl:value-of select="@name" />
		</a>
	</xsl:template>

	<xsl:template match="discount">
		<a href="{$lang-prefix}/admin/emarket/discount_edit/{@id}/">
			<xsl:attribute name="title">
				<xsl:value-of select="description" disable-output-escaping="yes" />
			</xsl:attribute>

			<xsl:value-of select="@name" />
		</a>
		<xsl:apply-templates select="document(concat('uobject://', @id, '.discount_modificator_id'))//item" mode="discount-size" />
	</xsl:template>

	<xsl:template match="udata[@method = 'ordersList']" />

	<xsl:template match="udata[@method = 'ordersList' and count(items/item)]">
		<xsl:param name="customer-id" />
		<div class="panel properties-group">
			<div class="header">
				<span>
					<xsl:text>&label-customer-order;</xsl:text>
				</span>
				<div class="l" /><div class="r" />
			</div>

			<div class="content">

				<div>
					<a href="{$lang-prefix}/admin/emarket/actAsUser/{$customer-id}/">
						<xsl:attribute name="title">&label-act-as-user-tip;</xsl:attribute>
						<xsl:text>&label-act-as-user;</xsl:text>
					</a>
				</div>

				<table class="tableContent">
					<thead>
						<tr>
							<th>
								<xsl:text>&label-orders-name;</xsl:text>
							</th>
							<th>
								<xsl:text>&label-orders-date;</xsl:text>
							</th>
							<th>
								<xsl:text>&label-orders-status;</xsl:text>
							</th>
						</tr>
					</thead>
					<tbody>
						<xsl:apply-templates select="items/item" />
					</tbody>
				</table>
				<xsl:call-template name="std-form-buttons" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@method = 'ordersList']/items/item">
		<xsl:variable name="order-info" select="document(concat('uobject://', @id))/udata" />

		<tr>
			<td>
				<a href="{$lang-prefix}/admin/emarket/order_edit/{@id}/">
					<xsl:value-of select="concat('&js-order-name;', $order-info//property[@name = 'number']/value)" />
				</a>
			</td>

			<td>
				<xsl:apply-templates select="$order-info//property[@name = 'order_date']/value/@unix-timestamp" />
			</td>

			<td>
				<xsl:value-of select="$order-info//property[@name = 'status_id']/value/item/@name" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="*" mode="price">
		<xsl:value-of select="." />
	</xsl:template>

	<xsl:template match="*[../@prefix]" mode="price">
		<xsl:value-of select="concat(../@prefix, ' ', .)" />
	</xsl:template>

	<xsl:template match="*[../@suffix]" mode="price">
		<xsl:value-of select="concat(., ' ', ../@suffix)" />
	</xsl:template>


	<xsl:template match="customer">
		<xsl:param name="customer-id" />
		<xsl:param name="type-customer" />
		<xsl:param name="one-click-order" />

		<div class="panel properties-group">
			<div class="header">
				<span>
					<xsl:text>&label-order-customer-group;</xsl:text>
				</span>
				<div class="l" /><div class="r" />
			</div>

			<div class="content">

				<div class="field text">
					<!-- Информация о покупателе в 1 клик -->
					<xsl:apply-templates select="$one-click-order" mode="form-modify"/>

					<xsl:choose>
						<xsl:when test="$one-click-order/field/values/item[@selected]">
							<span class="label">
								<a href="javascript:void(0);" class="toggle_fields_expander edit_a">
									<xsl:call-template name="type-customer-label">
										<xsl:with-param name="type-customer" select="$type-customer" />
									</xsl:call-template>
								</a>
								<xsl:call-template name="type-customer-edit">
									<xsl:with-param name="customer-id" select="$customer-id" />
									<xsl:with-param name="type-customer" select="$type-customer" />
								</xsl:call-template>
							</span>
							<div class="toggle_fields">
								<table class="tableContent">
									<xsl:apply-templates select="object/properties/group/property" mode="customer-info" />
								</table>
							</div>
						</xsl:when>
						<xsl:otherwise>
							<span class="label">
								<acronym>
									<xsl:call-template name="type-customer-label">
										<xsl:with-param name="type-customer" select="$type-customer" />
									</xsl:call-template>
								</acronym>
								<xsl:call-template name="type-customer-edit">
									<xsl:with-param name="customer-id" select="$customer-id" />
									<xsl:with-param name="type-customer" select="$type-customer" />
								</xsl:call-template>
							</span>
							<table class="tableContent">
								<xsl:apply-templates select="object/properties/group/property" mode="customer-info" />
							</table>
						</xsl:otherwise>
					</xsl:choose>
				</div>

				<xsl:call-template name="std-form-buttons" />
			</div>
		</div>
	</xsl:template>

	<xsl:template name="type-customer-label">
		<xsl:param name="type-customer" />

		<xsl:choose>
			<xsl:when test="$type-customer = 'emarket-customer'">
				&label-customer-unregister;
			</xsl:when>
			<xsl:when test="$type-customer = 'users-user'">
				&label-customer-register;
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="type-customer-edit">
		<xsl:param name="customer-id" />
		<xsl:param name="type-customer" />

		<xsl:text>&nbsp;(</xsl:text>
		<xsl:choose>
			<xsl:when test="$type-customer = 'emarket-customer'">
				<a href="{$lang-prefix}/admin/data/guide_item_edit/{$customer-id}/" class="edit_a">
					<xsl:text>&label-edit;</xsl:text>
				</a>
			</xsl:when>
			<xsl:when test="$type-customer = 'users-user'">
				<a href="{$lang-prefix}/admin/users/edit/{$customer-id}/" class="edit_a">
					<xsl:text>&label-edit;</xsl:text>
				</a>
			</xsl:when>
		</xsl:choose>
		<xsl:text>)</xsl:text>
	</xsl:template>

	<xsl:template match="object[@type-guid = 'emarket-purchase-oneclick']">
		<div class="field text">
			<span class="label">
				<acronym>
					&label-customer-oneclick;
				</acronym>
				<xsl:text>&nbsp;(</xsl:text>
				<a href="{$lang-prefix}/admin/data/guide_item_edit/{@id}" class="edit_a">
					<xsl:text>&label-edit;</xsl:text>
				</a>
				<xsl:text>)</xsl:text>
			</span>

			<table class="tableContent oneClickTable">
				<xsl:apply-templates select="//object/properties/group/property" mode="customer-info" />
			</table>
		</div>
	</xsl:template>

	<xsl:template match="property[@name = 'last_request_time']" mode="customer-info" priority="1" />
	<xsl:template match="property[@name = 'is_activated']" mode="customer-info" priority="1" />
	<xsl:template match="property[@name='delivery_addresses']" mode="customer-info" />
	<xsl:template match="property[@name = 'referer']" mode="customer-info" priority="1" />
	<xsl:template match="property[@name = 'target']" mode="customer-info" priority="1" />

	<xsl:template match="property[not(@name='delivery_addresses')]" mode="customer-info">
		<tr>
			<td class="eq-col">
				<xsl:value-of select="title" />
			</td>

			<td>
				<xsl:apply-templates select="." mode="value" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="object" mode="delivery-address">
		<table class="tableContent">
			<xsl:apply-templates select="properties/group/property" mode="customer-info" />
		</table>
	</xsl:template>

	<xsl:template match="property" mode="delivery-address">
		<tr>
			<td class="eq-col">
				<xsl:value-of select="title" />
			</td>

			<td>
				<xsl:apply-templates select="." mode="value" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="property" mode="value">
		<xsl:value-of select="value" />
	</xsl:template>

	<xsl:template match="property[@type = 'boolean']" mode="value">
		<xsl:text>&label-no;</xsl:text>
	</xsl:template>

	<xsl:template match="property[@type = 'boolean' and value = '1']" mode="value">
		<xsl:text>&label-yes;</xsl:text>
	</xsl:template>

	<xsl:template match="property[@type = 'relation']" mode="value">
		<xsl:apply-templates select=".//item" mode="value" />
	</xsl:template>

	<xsl:template match="property[@type = 'relation']/value/item" mode="value">
		<xsl:value-of select="@name" />
	</xsl:template>

	<xsl:template match="property[@type = 'relation']/value/item[not(position() = last())]" mode="value">
		<xsl:value-of select="@name" />
	</xsl:template>


	<xsl:template match="group[@name = 'order_delivery_props']" mode="order_delivery" />

	<xsl:template match="group[@name = 'order_delivery_props' and count(property[@name = 'delivery_id']/value/item) &gt; 0]" mode="order_delivery">
		<xsl:variable name="delivery-item" select="property[@name = 'delivery_id']/value/item" />
		<xsl:variable name="delivery-price" select="property[@name = 'delivery_price']/value" />

		<tr>
			<td>
				<xsl:text>&label-order-delivery;: </xsl:text>
				<a href="{$lang-prefix}/admin/emarket/delivery_edit/{$delivery-item/@id}/">
					<xsl:value-of select="$delivery-item/@name" />
				</a>
			</td>
			<td colspan="4" />

			<td>
				<xsl:apply-templates select="document(concat('udata://emarket/applyPriceCurrency/', $delivery-price, '/'))/udata/price/actual" mode="price" />
			</td>
			<td />
		</tr>
	</xsl:template>

	<xsl:template match="payment[@type='kupivkredit']" mode="payment-view">
		<xsl:variable name="orderId" select="//object/@id" />
		<div class="panel properties-group">
			<div class="header">
				<span>&label-paymenttype-kvk;</span><div class="l" /><div class="r" />
			</div>

			<div class="content">
				<script type="text/javascript" src="/js/cms/kvk-api.js"></script>
				<script type="text/javascript">
					var kvkAPI = new kvkAPI(<xsl:value-of select="$orderId" />);
				</script>

				<table class="tableContent" id="kvkInfo">
					<tr>
						<td colspan="2"><xsl:value-of select="extended-status" /></td>
					</tr>
					<tr>
						<td colspan="2" class="credit actions">
							<input type="button" value="&label-kvk-refresh;" name="refresh" onclick="kvkAPI.refresh();" />
							<xsl:apply-templates select="actions/item"  mode="payment-info" />
						</td>
					</tr>
					<xsl:apply-templates select="//object/properties/group[@name = 'order_credit_props']/field" mode="payment-info" />
				</table>
				<p></p>
				<center><a href="javascript:;" onclick="kvkAPI.loadMoreFields(this)">&label-kvk-more-fields;</a></center>

				<xsl:call-template name="std-form-buttons" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="actions/item" mode="payment-info">
		<input type="button" value="{@title}" name="{@name}" onclick="kvkAPI.{@name}();" />
	</xsl:template>

	<xsl:template match="field" mode="payment-info">
		<xsl:variable name="value">
			<xsl:apply-templates select="." mode="value" />
		</xsl:variable>
		<xsl:if test="$value != ''">
			<tr>
				<td class="eq-col"><xsl:value-of select="@title" /></td>
				<td><xsl:value-of select="$value" disable-output-escaping="yes" /></td>
			</tr>
		</xsl:if>
	</xsl:template>

	<xsl:template match="field[@type='boolean']" mode="value">
		<xsl:if test=". != 0 and . != ''"><![CDATA[
			<img style="width:13px;height:13px;" src="/images/cms/admin/mac/tree/checked.png" />
		]]></xsl:if>
	</xsl:template>

	<xsl:template match="field[@type='relation']" mode="value">
		<xsl:value-of select="values/item[@selected='selected']" />
	</xsl:template>

	<xsl:template match="/result[@method = 'order_edit']/data/payment" mode="form-modify" />

	<xsl:template name="notify">
		<xsl:param name="order-info" />
		<xsl:param name="one-click-order" />

		<xsl:if test="$one-click-order/field/values/item[@selected] and $order-info/status[@guid = 'emarket-orderstatus-27262']">
			<div id="notifyList">
				<p class="notify" style="margin-top:0px;"><strong><xsl:text>&label-notify-found;:</xsl:text></strong></p>

				<ol class="notify">
					<li>
						&label-ordering-one-click;
						<a href="{$lang-prefix}/admin/emarket/editOrderAsUser/{$order-info/@id}/">
							<xsl:attribute name="title">&label-edit-as-user-tip;</xsl:attribute>
							<xsl:text>&label-edit-as-user;</xsl:text>
						</a>.
					</li>
				</ol>
			</div>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>