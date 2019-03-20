<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:xlink="http://www.w3.org/TR/xlink">

	<!-- Идентификатор заказа -->
	<xsl:variable name="order-id" select="result[@method = 'order_edit']/data/object/@id"/>

	<!-- Шаблон страницы заказа -->
	<xsl:template match="/result[@method = 'order_edit']/data/object" mode="form-modify">
		<xsl:variable name="order-info" select="document(concat('udata://emarket/order/', @id))/udata"/>
		<xsl:variable name="customer-id" select="$order-info/customer/object/@id"/>
		<xsl:variable name="type-customer" select="$order-info/customer/object/@type-guid"/>
		<xsl:variable name="one-click-order" select="//group[@name = 'purchase_one_click']"/>
		<xsl:variable name="legal-person-id" select="//group[@name = 'order_payment_props']/field[@name = 'legal_person']/values/item[@selected = 'selected']/@id"/>

		<xsl:call-template name="notify">
			<xsl:with-param name="order-info" select="$order-info"/>
			<xsl:with-param name="one-click-order" select="$one-click-order"/>
		</xsl:call-template>

		<!-- Информация о заказе -->
		<xsl:apply-templates select=".//group[@name = 'order_props']" mode="form-modify">
			<xsl:with-param name="show-name">
				<xsl:text>0</xsl:text>
			</xsl:with-param>
		</xsl:apply-templates>

		<xsl:apply-templates select="//payment" mode="payment-view"/>

		<!-- Информация о заказчике -->
		<xsl:apply-templates select="$order-info/customer">
			<xsl:with-param name="customer-id" select="$customer-id"/>
			<xsl:with-param name="type-customer" select="$type-customer"/>
			<xsl:with-param name="one-click-order" select="$one-click-order"/>
			<xsl:with-param name="legal-person-id" select="$legal-person-id"/>
		</xsl:apply-templates>

		<xsl:apply-templates select=".//group[@name = 'order_payment_props' or @name = 'order_delivery_props']"
							 mode="form-modify">
			<xsl:with-param name="show-name">
				<xsl:text>0</xsl:text>
			</xsl:with-param>
		</xsl:apply-templates>

		<xsl:apply-templates select=".//group[@name = 'statistic_info']" mode="form-modify">
			<xsl:with-param name="show-name">
				<xsl:text>0</xsl:text>
			</xsl:with-param>
		</xsl:apply-templates>

		<!-- Наименования заказа (с удалением) -->
		<xsl:apply-templates select="$order-info" mode="order-items"/>

		<!-- Список всех заказов покупателя -->
		<xsl:if test="$customer-id">
			<xsl:apply-templates select="document(concat('udata://emarket/ordersList/', $customer-id, '?links'))/udata">
				<xsl:with-param name="customer-id" select="$customer-id"/>
			</xsl:apply-templates>
		</xsl:if>

		<script type="text/javascript">
			$(function() {
				$('.toggle_fields').slideToggle();
				$('.toggle_fields_expander').bind('click', function() {
					$('.toggle_fields').slideToggle();
				});
			})
		</script>
	</xsl:template>

	<!-- Шаблон свойств заказа -->
	<xsl:template match="group[@name = 'order_props']" mode="form-modify">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
		<div name="g_{@name}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<a data-name="{@name}" data-label="{@title}"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:value-of select="@title"/>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="@name"/>
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-6" style="min-height:30px;">
								<label>
									<span class="label">
										<a href="{$lang-prefix}/admin/emarket/order_printable/{/result/data/object/@id}/"
										   target="_blank" class="edit_a">&label-printable-version;</a>
									</span>
								</label>
							</div>
							<xsl:apply-templates select="field" mode="form-modify"/>
						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="group-tip-hide"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон данных оформления в 1 клик -->
	<xsl:template match="group[@name = 'purchase_one_click']" mode="form-modify">
		<xsl:param name="type-customer"/>
		<xsl:param name="customer-id"/>

		<xsl:apply-templates select="document(concat('uobject://', field/values/item[@selected]/@id))/udata/object">
			<xsl:with-param name="panel-title" select="@title"/>
			<xsl:with-param name="customer-id" select="$customer-id"/>
			<xsl:with-param name="type-customer" select="$type-customer"/>
		</xsl:apply-templates>
	</xsl:template>

	<!-- Шаблон номера заказа -->
	<xsl:template match="field[@name = 'number']" mode="form-modify">
		<div class="col-md-6" style="min-height:30px;">
			<div class="title-edit">
				<acronym>
					<xsl:value-of select="concat(@title, ': #', .)"/>
				</acronym>
			</div>
		</div>
	</xsl:template>

	<!--  Шаблон покупатель заказа -->
	<xsl:template match="field[@name = 'customer_id']" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<acronym>
					<xsl:value-of select="concat(@title, ': ')"/>
					<br/>
					<xsl:apply-templates select="values/item[@selected = 'selected']" mode="order-customer-link"/>
				</acronym>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон поля "Служебная информация" -->
	<xsl:template match="field[@type = 'text' and @name = 'service_info']" mode="form-modify">
		<div class="col-md-12 wysiwyg-field default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<span>
				<textarea name="{@input_name}" id="{generate-id()}" readonly="readonly">
					<xsl:apply-templates select="." mode="required_attr">
						<xsl:with-param name="old_class" select="@type" />
					</xsl:apply-templates>
					<xsl:value-of select="." />
				</textarea>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон названий способа оплаты и доставки -->
	<xsl:template match="field[@name = 'payment_name' or @name = 'delivery_name']" mode="form-modify">
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="">
					<xsl:value-of select="@title"/>
				</acronym>
			</div>
			<span>
				<input class="default" type="text" name="{@input_name}" value="{.}" id="{generate-id()}" disabled="disabled"/>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон настроек доставки (провайдер, тариф, точка выдачи, точка приема) -->
	<xsl:template match="field[@name = 'delivery_provider' or @name = 'delivery_tariff' or
		@name = 'delivery_point_in' or @name = 'delivery_point_out']" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<acronym title="{@tip}" class="acr">
					<xsl:value-of select="@title"/>
				</acronym>
			</div>
			<span>
				<input class="default" type="text" name="{@input_name}" value="{.}" disabled="disabled"/>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон типа доставки (до двери/до склада) -->
	<xsl:template match="field[@name = 'delivery_type']" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<acronym title="{@tip}" class="acr">
					<xsl:value-of select="@title"/>
				</acronym>
			</div>
			<div class="layout-row-icon">
				<div class="layout-col-control">
					<select class="default newselect" autocomplete="off" name="{@input_name}"
							id="relationSelect{generate-id()}" disabled="disabled">
						<xsl:choose>
							<xsl:when test=". = '1'">
								<option value="1" selected="selected">&label-to-door;</option>
								<option value="2">&label-to-point;</option>
							</xsl:when>
							<xsl:when test=". = '2'">
								<option value="1">&label-to-door;</option>
								<option value="2" selected="selected">&label-to-point;</option>
							</xsl:when>
							<xsl:otherwise>
								<option value="1">&label-to-door;</option>
								<option value="2">&label-to-point;</option>
							</xsl:otherwise>
						</xsl:choose>
					</select>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон типа выдачи (от двери/со склада) -->
	<xsl:template match="field[@name = 'pickup_type']" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<acronym title="{@tip}" class="acr">
					<xsl:value-of select="@title"/>
				</acronym>
			</div>
			<div class="layout-row-icon">
				<div class="layout-col-control">
					<select class="default newselect" autocomplete="off" name="{@input_name}"
							id="relationSelect{generate-id()}">
						<xsl:choose>
							<xsl:when test=". = '1'">
								<option value="1" selected="selected">&label-from-door;</option>
								<option value="2">&label-from-point;</option>
							</xsl:when>
							<xsl:when test=". = '2'">
								<option value="1">&label-from-door;</option>
								<option value="2" selected="selected">&label-from-point;</option>
							</xsl:when>
							<xsl:otherwise>
								<option value="1">&label-from-door;</option>
								<option value="2">&label-from-point;</option>
							</xsl:otherwise>
						</xsl:choose>
					</select>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон даты созданиия заказа -->
	<xsl:template match="field[@name = 'order_create_date']" mode="form-modify"/>

	<!-- Шаблон ФИО покупателя -->
	<xsl:template match="item" mode="order-customer-link">
		<xsl:variable name="customer-info" select="document(concat('uobject://', @id))/udata"/>
		<xsl:variable name="fname" select="$customer-info//property[@name = 'fname']/value"/>
		<xsl:variable name="lname" select="$customer-info//property[@name = 'lname']/value"/>
		<xsl:variable name="login" select="$customer-info//property[@name = 'login']/value"/>
		<xsl:value-of select="concat($fname, ' ', $lname, ' (', $login, ')')"/>
	</xsl:template>

	<!-- Шаблон ряда скрытых полей (они выводятся нестандартным образом) -->
	<xsl:template
			match="field[@name = 'order_items' or @name = 'total_original_price' or @name = 'order_discount_value']"
			mode="form-modify"/>
	<xsl:template match="field[@name = 'total_price' or @name = 'total_amount']" mode="form-modify"/>

	<!-- Шаблон адреса доставки -->
	<xsl:template match="field[@name='delivery_address']" mode="form-modify">
		<xsl:if test="count(values/item[@selected])">
			<div class="col-md-12">
				<label for="{generate-id()}">
					<div class="title-edit">
						<acronym>
							<xsl:apply-templates select="." mode="sys-tips"/>
							<xsl:value-of select="@title"/>
						</acronym>
						<xsl:apply-templates select="." mode="required_text"/>
						<xsl:text>&nbsp;(</xsl:text>
						<a href="{$lang-prefix}/admin/emarket/delivery_address_edit/{values/item[@selected]/@id}/"
						   class="edit_a">
							<xsl:text>&label-edit;</xsl:text>
						</a>
						<xsl:text>)</xsl:text>
					</div>
					<div>
						<xsl:apply-templates
								select="document(concat('uobject://', values/item[@selected]/@id))/udata/object"
								mode="delivery-address"/>
					</div>
				</label>
			</div>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон списка наименований заказа -->
	<xsl:template match="udata" mode="order-items">
		<xsl:variable name="order-info" select="document(concat('uobject://', @id))/udata"/>
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'order_items')"/>
		<div name="g_order_items">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<summary class="group-tip">
				<xsl:text>Управление товарами в заказе.</xsl:text>
			</summary>
			<a data-name="'order_items'" data-label="&label-order-items-group;"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:text>&label-order-items-group;</xsl:text>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'order_items'"/>
					<xsl:with-param name="force-show" select="1"/>
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-12" style="margin-bottom:10px;">
								<a href="{$lang-prefix}/admin/emarket/editOrderAsUser/{@id}/"
								   class="btn color-blue btn-small">
									<xsl:attribute name="title">&label-edit-as-user-tip;</xsl:attribute>
									<xsl:text>&label-edit-as-user;</xsl:text>
								</a>
							</div>
							<div class="col-md-12">
								<table class="btable btable-bordered btable-striped">
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
												<xsl:text>&label-weight;</xsl:text>
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
										<xsl:apply-templates select="items/item" mode="order-items"/>
										<tr>
											<td>
												<strong>
													<xsl:text>&label-order-discount;</xsl:text>
												</strong>
											</td>
											<td colspan="5">
												<a href="{$lang-prefix}/admin/emarket/discount_edit/{discount/@id}/">
													<xsl:value-of select="discount/@name"/>
												</a>
												<xsl:apply-templates
														select="document(concat('uobject://', discount/@id, '.discount_modificator_id'))//item"
														mode="discount-size"/>
												<xsl:apply-templates select="discount/description"/>
											</td>
											<td>
												<input type="text" class="default" name="order-discount-value"
													   value="{discount_value}" size="3">
													<xsl:attribute name="value">
														<xsl:choose>
															<xsl:when test="../summary/price/discount">
																<xsl:value-of
																		select="document(concat('udata://emarket/applyPriceCurrency/', ../summary/price/discount, '/'))/udata/price/actual"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="discount_value"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:attribute>
												</input>
											</td>
											<td/>
										</tr>
										<xsl:apply-templates select="summary/price/bonus" mode="order-summary"/>
										<xsl:apply-templates select="$order-info//group[@name = 'order_delivery_props']"
															 mode="order_delivery"/>
										<tr>
											<td colspan="6">
												<strong>
													<xsl:text>&label-order-items-result;:</xsl:text>
												</strong>
											</td>

											<td>
												<strong>
													<xsl:apply-templates select="summary/price/actual" mode="price"/>
												</strong>
											</td>
											<td/>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&type-edit-tip;</xsl:text>
							</h3>
							<div class="content">
							</div>
							<div class="group-tip-hide"/>
						</div>
					</div>

				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон скидки на товар -->
	<xsl:template match="item" mode="discount-size">
		<xsl:apply-templates select="document(@xlink:href)/udata/object" mode="modificator-size"/>
	</xsl:template>

	<!-- Заглушка шаблона скидки на товар -->
	<xsl:template match="object" mode="modificator-size"/>

	<!-- Шаблон скидки на товар в процентах -->
	<xsl:template match="object[.//property[@name = 'proc']]" mode="modificator-size">
		<xsl:value-of select="concat(' &#8212; ', .//property[@name = 'proc']/value, '%')"/>
	</xsl:template>

	<!-- Шаблон абсолютной скидки на товар -->
	<xsl:template match="object[.//property[@name = 'size']]" mode="modificator-size">
		<xsl:value-of select="concat(', ', .//property[@name = 'size']/value)"/>
	</xsl:template>

	<!-- Шаблон используемых бонусов -->
	<xsl:template match="bonus" mode="order-summary">
		<tr>
			<td>
				<strong>
					<xsl:text>Использованные бонусы</xsl:text>
				</strong>
			</td>
			<td colspan="4"/>
			<td>
				<xsl:value-of select="."/>
			</td>
			<td/>
		</tr>
	</xsl:template>

	<!-- Шаблон пустого описания типа скидки -->
	<xsl:template match="discount/description"/>

	<!-- Шаблон описания типа скидки -->
	<xsl:template match="discount/description[. != '']">
		<xsl:value-of select="concat(' (', ., ')')" disable-output-escaping="yes"/>
	</xsl:template>

	<!-- Шаблон наименования в списке -->
	<xsl:template match="item" mode="order-items">
		<tr>
			<td>
				<xsl:apply-templates select="document(concat('uobject://',@id))/udata/object" mode="order-item-name"/>
			</td>

			<td>
				<xsl:choose>
					<xsl:when test="price/original &gt; 0">
						<xsl:apply-templates select="price/original" mode="price"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="price/actual" mode="price"/>
					</xsl:otherwise>
				</xsl:choose>
			</td>

			<td>
				<input type="text" class="default" name="item-discount-value[{@id}]" value="{discount_value}" size="3"/>
			</td>

			<td>
				<xsl:apply-templates select="price/actual" mode="price"/>
			</td>

			<td>
				<input type="number" class="default" name="order-weight-item[{@id}]" value="{weight}" size="3"/>
			</td>

			<td>
				<input type="number" min="1" class="default" name="order-amount-item[{@id}]" value="{amount}" size="3"/>
			</td>

			<td>
				<xsl:apply-templates select="total-price/actual" mode="price"/>
			</td>

			<td class="center">
				<div class="checkbox">
					<input type="checkbox" name="order-del-item[]" value="{@id}" class="check"/>
				</div>

			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон названия наименования с ссылкой на его страницу редактирования -->
	<xsl:template match="object" mode="order-item-name">
		<a href="{$lang-prefix}/admin/emarket/orderItemEdit/{@id}/" class="edit_a">
			<xsl:value-of select="@name"/>
		</a>
	</xsl:template>

	<!-- Шаблон названия наименования с ссылкой на страницу редактирования товара -->
	<xsl:template match="object[//property/@name = 'item_link']" mode="order-item-name">
		<a href="{$lang-prefix}/admin/catalog/edit/{//property/value/page/@id}/">
			<xsl:value-of select="@name" />
		</a>
	</xsl:template>

	<!-- Шаблон скидки -->
	<xsl:template match="discount">
		<a href="{$lang-prefix}/admin/emarket/discount_edit/{@id}/">
			<xsl:attribute name="title">
				<xsl:value-of select="description" disable-output-escaping="yes"/>
			</xsl:attribute>

			<xsl:value-of select="@name"/>
		</a>
		<xsl:apply-templates select="document(concat('uobject://', @id, '.discount_modificator_id'))//item"
							 mode="discount-size"/>
	</xsl:template>

	<!-- Шаблон пустого списка заказов текущего пользователя -->
	<xsl:template match="udata[@method = 'ordersList']"/>

	<!-- Шаблон списка заказов текущего пользователя -->
	<xsl:template match="udata[@method = 'ordersList' and count(items/item)]">
		<xsl:param name="customer-id"/>
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'orders_list')"/>
		<div name="g_orders_list">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<summary class="group-tip">
				<xsl:text>История заказов данного покупателя.</xsl:text>
			</summary>
			<a data-name="'orders_list'" data-label="&label-customer-order;"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:text>&label-customer-order;</xsl:text>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'orders_list'"/>
					<xsl:with-param name="force-show" select="1"/>
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-12" style="margin-bottom: 10px;">
								<a href="{$lang-prefix}/admin/emarket/actAsUser/{$customer-id}/"
								   class="btn color-blue btn-small">
									<xsl:attribute name="title">&label-act-as-user-tip;</xsl:attribute>
									<xsl:text>&label-act-as-user;</xsl:text>
								</a>
							</div>
							<div class="col-md-12">
								<table class="btable btable-bordered btable-striped">
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
										<xsl:apply-templates select="items/item"/>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&type-edit-tip;</xsl:text>
							</h3>
							<div class="content">
							</div>
							<div class="group-tip-hide"/>
						</div>
					</div>

				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон заказа в списке заказов текущего пользователя -->
	<xsl:template match="udata[@method = 'ordersList']/items/item">
		<xsl:variable name="order-info" select="document(concat('uobject://', @id))/udata"/>

		<tr>
			<td>
				<a href="{$lang-prefix}/admin/emarket/order_edit/{@id}/">
					<xsl:value-of select="concat('&js-order-name;', $order-info//property[@name = 'number']/value)"/>
				</a>
			</td>

			<td>
				<xsl:apply-templates select="$order-info//property[@name = 'order_date']/value/@unix-timestamp"/>
			</td>

			<td>
				<xsl:value-of select="$order-info//property[@name = 'status_id']/value/item/@name"/>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон цены -->
	<xsl:template match="*" mode="price">
		<xsl:value-of select="."/>
	</xsl:template>

	<!-- Шаблон цены с префиксом -->
	<xsl:template match="*[../@prefix]" mode="price">
		<xsl:value-of select="concat(../@prefix, ' ', .)"/>
	</xsl:template>

	<!-- Шаблон цены с постфиксом -->
	<xsl:template match="*[../@suffix]" mode="price">
		<xsl:value-of select="concat(., ' ', ../@suffix)"/>
	</xsl:template>

	<!-- Шаблон покупателя -->
	<xsl:template match="customer">
		<xsl:param name="customer-id"/>
		<xsl:param name="type-customer"/>
		<xsl:param name="one-click-order"/>
		<xsl:param name="legal-person-id"/>

		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'customer')"/>
		<div name="g_customer">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<summary class="group-tip">
				<xsl:text>Управление неотправленными рассылками.</xsl:text>
			</summary>
			<a data-name="order-customer-group" data-label="&label-order-customer-group;"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:text>&label-order-customer-group;</xsl:text>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'customer'"/>
					<xsl:with-param name="force-show" select="1"/>
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="field text">
								<!-- Информация о покупателе в 1 клик -->
								<div class="col-md-12">
									<xsl:apply-templates select="$one-click-order" mode="form-modify"/>
								</div>
								<xsl:choose>
									<xsl:when test="$one-click-order/field/values/item[@selected]">
										<div class="col-md-12">
											<span class="label">
												<a href="javascript:void(0);" class="toggle_fields_expander edit_a">
													<acronym>
														<xsl:call-template name="type-customer-label">
															<xsl:with-param name="type-customer" select="$type-customer"/>
														</xsl:call-template>
													</acronym>
												</a>
												<xsl:call-template name="type-customer-edit">
													<xsl:with-param name="customer-id" select="$customer-id"/>
													<xsl:with-param name="type-customer" select="$type-customer"/>
												</xsl:call-template>
											</span>
											<div class="toggle_fields">
												<table class="btable btable-bordered btable-striped"
													   style="margin-top: 10px">
													<xsl:apply-templates select="object/properties/group/property"
																		 mode="customer-info"/>
												</table>
											</div>
										</div>
									</xsl:when>
									<xsl:otherwise>
										<div class="col-md-12" style="margin-bottom: 10px;">
											<span class="label">
												<acronym>
													<xsl:call-template name="type-customer-label">
														<xsl:with-param name="type-customer" select="$type-customer"/>
													</xsl:call-template>
												</acronym>
												<xsl:call-template name="type-customer-edit">
													<xsl:with-param name="customer-id" select="$customer-id"/>
													<xsl:with-param name="type-customer" select="$type-customer"/>
												</xsl:call-template>
											</span>
										</div>
										<div class="col-md-12">
											<table class="btable btable-bordered btable-striped">
												<xsl:apply-templates select="object/properties/group/property"
																	 mode="customer-info"/>
											</table>
										</div>
									</xsl:otherwise>
								</xsl:choose>
								<xsl:if test="$legal-person-id">
									<div class="col-md-12" style="margin-bottom: 10px;">
										<span class="label">
											<a href="javascript:void(0);" class="toggle_fields_expander edit_a">
												<acronym>
													&label-legal-person;
												</acronym>
											</a>
											<xsl:text>&nbsp;(</xsl:text>
											<a href="{$lang-prefix}/admin/emarket/legalPersonEdit/{$legal-person-id}/" class="edit_a">
												<xsl:text>&label-edit;</xsl:text>
											</a>
											<xsl:text>)</xsl:text>
										</span>
									</div>
									<div class="toggle_fields">
										<table class="btable btable-bordered btable-striped" style="margin-top: 10px">
											<xsl:apply-templates
											select="document(concat('uobject://', $legal-person-id))/udata/object/properties/group/property"
																 mode="customer-info"/>
										</table>
									</div>
								</xsl:if>
							</div>
						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&type-edit-tip;</xsl:text>
							</h3>
							<div class="content">
							</div>
							<div class="group-tip-hide"/>
						</div>
					</div>

				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон типа покупателя -->
	<xsl:template name="type-customer-label">
		<xsl:param name="type-customer"/>

		<xsl:choose>
			<xsl:when test="$type-customer = 'emarket-customer'">
				&label-customer-unregister;
			</xsl:when>
			<xsl:when test="$type-customer = 'users-user'">
				&label-customer-register;
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон ссылки на страницу редактирования покупателя -->
	<xsl:template name="type-customer-edit">
		<xsl:param name="customer-id"/>
		<xsl:param name="type-customer"/>

		<xsl:text>&nbsp;(</xsl:text>
		<xsl:choose>
			<xsl:when test="$type-customer = 'emarket-customer'">
				<a href="{$lang-prefix}/admin/emarket/customerEdit/{$customer-id}/" class="edit_a">
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

	<!-- Шаблон информации о том, что заказ оформлен в 1 клик -->
	<xsl:template match="object[@type-guid = 'emarket-purchase-oneclick']">
		<div class="title-edit">
			<acronym>
				&label-customer-oneclick;
			</acronym>
			<xsl:text>&nbsp;(</xsl:text>
			<a href="{$lang-prefix}/admin/emarket/oneClickOrderDataEdit/{@id}" class="edit_a">
				<xsl:text>&label-edit;</xsl:text>
			</a>
			<xsl:text>)</xsl:text>
		</div>

		<table class="btable btable-bordered btable-striped oneClickTable" style="margin-top: 10px;">
			<xsl:apply-templates select="//object/properties/group/property" mode="customer-info"/>
		</table>
	</xsl:template>

	<!-- Заглушки полей покупателя -->
	<xsl:template match="property[@name = 'last_request_time']" mode="customer-info" priority="1"/>
	<xsl:template match="property[@name = 'is_activated']" mode="customer-info" priority="1"/>
	<xsl:template match="property[@name='delivery_addresses']" mode="customer-info"/>
	<xsl:template match="property[@name = 'referer']" mode="customer-info" priority="1"/>
	<xsl:template match="property[@name = 'target']" mode="customer-info" priority="1"/>

	<!-- Шаблон поля покупателя -->
	<xsl:template match="property[not(@name='delivery_addresses')]" mode="customer-info">
		<tr>
			<td width="50%">
				<xsl:value-of select="title"/>
			</td>

			<td width="50%" field-name="{@name}">
				<xsl:apply-templates select="." mode="value"/>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон списка полей адреса -->
	<xsl:template match="object" mode="delivery-address">
		<table class="btable btable-bordered btable-striped">
			<xsl:apply-templates select="properties/group/property" mode="customer-info"/>
		</table>
	</xsl:template>

	<!-- Шаблон поля адреса -->
	<xsl:template match="property" mode="delivery-address">
		<tr>
			<td class="eq-col">
				<xsl:value-of select="title"/>
			</td>

			<td>
				<xsl:apply-templates select="." mode="value"/>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон значения поля  -->
	<xsl:template match="property" mode="value">
		<xsl:value-of select="value"/>
	</xsl:template>

	<!-- Шаблон значения булевого поля "false"  -->
	<xsl:template match="property[@type = 'boolean']" mode="value">
		<xsl:text>&label-no;</xsl:text>
	</xsl:template>

	<!-- Шаблон значения булевого поля "true"  -->
	<xsl:template match="property[@type = 'boolean' and value = '1']" mode="value">
		<xsl:text>&label-yes;</xsl:text>
	</xsl:template>

	<!-- Шаблон значения поля выпадающий список -->
	<xsl:template match="property[@type = 'relation']" mode="value">
		<xsl:apply-templates select=".//item" mode="value"/>
	</xsl:template>

	<!-- Шаблон значения выпадающего списка -->
	<xsl:template match="property[@type = 'relation']/value/item" mode="value">
		<xsl:value-of select="@name"/>
	</xsl:template>

	<!-- Шаблон непоследнего значения выпадающего списка -->
	<xsl:template match="property[@type = 'relation']/value/item[not(position() = last())]" mode="value">
		<xsl:value-of select="@name"/>
	</xsl:template>

	<!-- Заглушка группы полей настроек доставки -->
	<xsl:template match="group[@name = 'order_delivery_props']" mode="order_delivery"/>

	<!-- Шаблон доставки -->
	<xsl:template
			match="group[@name = 'order_delivery_props' and count(property[@name = 'delivery_id']/value/item) &gt; 0]"
			mode="order_delivery">
		<xsl:variable name="delivery-item" select="property[@name = 'delivery_id']/value/item"/>
		<xsl:variable name="delivery-price" select="property[@name = 'delivery_price']/value"/>

		<tr>
			<td>
				<xsl:text>&label-order-delivery;: </xsl:text>
				<xsl:value-of select="$delivery-item/@name"/>
			</td>
			<td colspan="5"/>

			<td>
				<xsl:apply-templates
						select="document(concat('udata://emarket/applyPriceCurrency/', $delivery-price, '/'))/udata/price/actual"
						mode="price"/>
			</td>
			<td/>
		</tr>
	</xsl:template>

	<!-- Шаблон параметров способа оплаты "Купи в кредит" -->
	<xsl:template match="payment[@type='kupivkredit']" mode="payment-view">
		<xsl:variable name="orderId" select="//object/@id"/>
		<a data-name="paymenttype-kvk" data-label="&label-paymenttype-kvk;"/>
		<div class="panel-settings">
			<div class="title field-group-toggle">
				<a class="btn-action group-tip-show">
					<i class="small-ico i-info"/>
					<xsl:text>&type-edit-tip;</xsl:text>
				</a>
				<div class="round-toggle"/>
				<h3>&label-paymenttype-kvk;</h3>
			</div>

			<div class="content">
				<script type="text/javascript" src="/js/cms/kvk-api.js?{$system-build}"/>
				<script type="text/javascript">
					var kvkAPI = new kvkAPI(<xsl:value-of select="$orderId"/>);
				</script>
				<div class="layout">
					<div class="column">
						<div class="row">
							<table class="btable btable-bordered btable-striped" id="kvkInfo">
								<tr>
									<td colspan="2">
										<xsl:value-of select="extended-status"/>
									</td>
								</tr>
								<tr>
									<td colspan="2" class="credit actions">
										<input type="button" class="btn color-blue btn-small"
											   value="&label-kvk-refresh;" name="refresh" onclick="kvkAPI.refresh();"/>
										<xsl:apply-templates select="actions/item" mode="payment-info"/>
									</td>
								</tr>
								<xsl:apply-templates
										select="//object/properties/group[@name = 'order_credit_props']/field"
										mode="payment-info"/>
							</table>
							<p/>
							<center>
								<a href="javascript:;" onclick="kvkAPI.loadMoreFields(this)">&label-kvk-more-fields;</a>
							</center>

						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="group-tip-hide"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон кнопки "Купи в кредит" -->
	<xsl:template match="actions/item" mode="payment-info">
		<input type="button" value="{@title}" name="{@name}" onclick="kvkAPI.{@name}();"/>
	</xsl:template>

	<!-- Шаблон поля "Купи в кредит" -->
	<xsl:template match="field" mode="payment-info">
		<xsl:variable name="value">
			<xsl:apply-templates select="." mode="value"/>
		</xsl:variable>
		<xsl:if test="$value != ''">
			<tr>
				<td class="eq-col">
					<xsl:value-of select="@title"/>
				</td>
				<td>
					<xsl:value-of select="$value" disable-output-escaping="yes"/>
				</td>
			</tr>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон булева поля "Купи в кредит" -->
	<xsl:template match="field[@type='boolean']" mode="value">
		<xsl:if test=". != 0 and . != ''"><![CDATA[
			<img style="width:13px;height:13px;" src="/images/cms/admin/mac/tree/checked.png" />
		]]></xsl:if>
	</xsl:template>

	<!-- Шаблон поля типа "выпадающий список" "Купи в кредит" -->
	<xsl:template match="field[@type='relation']" mode="value">
		<xsl:value-of select="values/item[@selected='selected']"/>
	</xsl:template>

	<!-- Заглушка шаблона способа оплаты-->
	<xsl:template match="/result[@method = 'order_edit']/data/payment" mode="form-modify"/>

	<!-- Шаблон уведомления о заказа в 1 клик -->
	<xsl:template name="notify">
		<xsl:param name="order-info"/>
		<xsl:param name="one-click-order"/>

		<xsl:if test="$one-click-order/field/values/item[@selected] and $order-info/status[@guid = 'emarket-orderstatus-27262']">
			<div id="notifyList">
				<p class="notify" style="margin-top:0px;">
					<strong>
						<xsl:text>&label-notify-found;:</xsl:text>
					</strong>
				</p>

				<ol class="notify">
					<li>
						&label-ordering-one-click;
						<a href="{$lang-prefix}/admin/emarket/editOrderAsUser/{$order-info/@id}/">
							<xsl:attribute name="title">&label-edit-as-user-tip;</xsl:attribute>
							<xsl:text>&label-edit-as-user;</xsl:text>
						</a>
						.
					</li>
				</ol>
			</div>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон настроек доставки -->
	<xsl:template match="group[@name = 'order_delivery_props']" mode="form-modify">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
		<div name="g_{@name}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<a data-name="{@name}" data-label="{@title}"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:value-of select="@title"/>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="@name"/>
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:variable name="deliveryGuid"
										  select="document(concat('uobject://',field[@name='delivery_id']/values/item[1]/@id))/udata/object/properties/group[@name='delivery_description_props']/property[@name='delivery_type_id']/value/item[1]/@guid"/>
							<xsl:variable name="apiShipGUID" select="'emarket-deliverytype-27958'"/>
							<xsl:apply-templates select="field[@name='delivery_id']" mode="form-modify"/>
							<xsl:apply-templates select="field[@name='delivery_name']" mode="form-modify"/>
							<xsl:apply-templates select="field[@name='delivery_status_id']" mode="form-modify"/>
							<xsl:apply-templates select="field[@name='delivery_address']" mode="form-modify"/>

							<xsl:if test="$deliveryGuid=$apiShipGUID">
								<xsl:apply-templates select="field[@name='delivery_date']" mode="form-modify"/>
								<xsl:apply-templates select="field[@name='pickup_date']" mode="form-modify"/>
								<xsl:apply-templates select="field[@name='pickup_type']" mode="form-modify"/>
								<xsl:apply-templates select="field[@name='delivery_point_in']"
													 mode="order_delivery_apiship_field"/>
								<xsl:apply-templates select="." mode="order_delivery_apiship"/>
							</xsl:if>

							<xsl:apply-templates select="field[@name='delivery_price']" mode="form-modify"/>
							<xsl:apply-templates select="field[@name='total_weight']" mode="form-modify"/>
							<xsl:apply-templates select="field[@name='total_width']" mode="form-modify"/>
							<xsl:apply-templates select="field[@name='total_height']" mode="form-modify"/>
							<xsl:apply-templates select="field[@name='total_length']" mode="form-modify"/>
						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="group-tip-hide"/>
						</div>
					</div>

				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон настроек доставки ApiShip-->
	<xsl:template match="group[@name = 'order_delivery_props']" mode="order_delivery_apiship">
		<div class="col-md-12" id="order_delivery_apiship">
			<div class="title-edit">&label-asw-field-title;</div>
			<xsl:apply-templates select="field[@name='delivery_provider']" mode="order_delivery_apiship_field"/>
			<xsl:apply-templates select="field[@name='delivery_tariff']" mode="order_delivery_apiship_field"/>
			<xsl:apply-templates select="field[@name='delivery_type']" mode="order_delivery_apiship_field"/>
			<xsl:apply-templates select="field[@name='delivery_point_out']" mode="order_delivery_apiship_field"/>
			<div id="order_delivery_apiship_type">
				<p>&label-asw-data-loading-message;</p>
			</div>
			<div class="row buttons">
				<a class="btn color-blue btn-small" id="showWidgetOrderDelivery">&label-asw-edit-button;</a>
				<a class="btn color-blue btn-small" id="sendApiShipOrderRequest">&label-asw-send-button;</a>
			</div>
			<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"/>
			<script src="/styles/skins/modern/design/js/initDeliveryWidgetEditPage.js?{$system-build}"/>
		</div>
	</xsl:template>

	<!-- Шаблон поля "точка приема" для доставки ApiShip-->
	<xsl:template match="field[@name='delivery_point_in']" mode="order_delivery_apiship_field">
		<xsl:variable name="pointId" select="."/>
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips"/>
					<xsl:value-of select="@title"/>
				</acronym>
				<xsl:apply-templates select="." mode="required_text"/>
			</div>
			<span>
				<input type="hidden" name="{@input_name}" value="{$pointId}" autocomplete="off"/>
				<div id="pointInInfo">
					<xsl:choose>
						<xsl:when test="$pointId > 0">
							<p>&label-asw-data-loading-message;</p>
						</xsl:when>
						<xsl:otherwise>
							<p>&label-point-not-select;</p>
						</xsl:otherwise>
					</xsl:choose>
				</div>
				<div class="buttons">
					<a class="btn color-blue btn-small" id="showPointInEditor">&label-button-delivery-point-in-select;</a>
				</div>
				<link type="text/css" rel="stylesheet" href="/styles/common/css/jquery.mCustomScrollbar.css?{$system-build}"/>
				<link type="text/css" rel="stylesheet" href="/styles/common/css/widget.Delivery.css?{$system-build}"/>
				<script src="/js/jquery.mCustomScrollbar.js?{$system-build}"/>
				<script src="/js/widget.Delivery.js?{$system-build}"/>
				<script src="/styles/skins/modern/design/js/initPointInEditor.js?{$system-build}"/>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон скрытого поля для доставки ApiShip-->
	<xsl:template match="field" mode="order_delivery_apiship_field">
		<input type="hidden" name="{@input_name}" value="{.}"/>
	</xsl:template>

</xsl:stylesheet>
