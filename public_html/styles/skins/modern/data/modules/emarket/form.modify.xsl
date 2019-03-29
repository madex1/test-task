<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:include href="order-edit.xsl" />

	<!-- Шаблон формы создания скидки -->
	<xsl:template match="/result[@method = 'discount_add' or @method = 'discount_modify']/data" priority="1">
		<form class="form_modify" data-type-id="{$object-type-id}" method="post" action="do/" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}"/>
			<input type="hidden" name="domain" value="{$domain-floated}"/>

			<xsl:apply-templates mode="form-modify" />

			<xsl:apply-templates select=".//field[@name = 'discount_type_id']/values/item" />
			<div class="row">
				<div id="buttons_wr" class="col-md-12">
					<xsl:choose>
						<xsl:when test="@method = 'discount_add'">
							<xsl:call-template name="std-form-buttons-add"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="std-form-buttons"/>
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</div>
		</form>
		<script>
			$(document).ready(function (){
				jQuery('input.discount-type-id').bind('click', function () {
					var discountTypeId = jQuery(this).attr('value');

					jQuery('div.discount-params input').attr('disabled', true);
					jQuery('div.discount-params').css('display', 'none');

					jQuery('div.discount-params#' + discountTypeId + ' input').attr('disabled', false);
					jQuery('div.discount-params#' + discountTypeId + '').css('display', '');
				});
			})
		</script>
	</xsl:template>

	<xsl:template match="/result[not(@method = 'discount_edit')]//field[@name = 'is_active']" mode="form-modify" />
	<xsl:template match="field[@name = 'discount_modificator_id']" mode="form-modify" />
	<xsl:template match="field[@name = 'discount_rules_id']" mode="form-modify" />

	<xsl:template match="field[@name = 'discount_type_id']" mode="form-modify">
		<div class="col-md-12">
				<span class="title-edit">
					<acronym title="{@tip}">
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
				</span>

				<xsl:apply-templates select="values/item" mode="discount-type" />
		</div>
	</xsl:template>

	<xsl:template match="values/item" mode="discount-type">
		<xsl:variable name="description" select="document(concat('uobject://', @id, '.description'))/udata/property" />
		<p>
			<div class="inline" style="padding:5px 0;">
				<input type="radio" class="checkbox discount-type-id" name="{../../@input_name}" value="{@id}">
					<xsl:if test="@selected = 'selected'">
						<xsl:attribute name="checked" select="'checked'" />
					</xsl:if>
				</input>
				<acronym>
					<xsl:value-of select="." />
					<xsl:apply-templates select="$description" mode="desc" />
				</acronym>
			</div>
		</p>
	</xsl:template>

	<xsl:template match="property[@name = 'description']" mode="desc">
		<em>
			<xsl:value-of select="concat(' (', value, ')')" />
		</em>
	</xsl:template>

	<xsl:template match="field[@name = 'discount_type_id']/values/item">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @id)"/>
		<div id="{@id}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings discount discount-params has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings discount discount-params</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:if test="not(@selected = 'selected')">
				<xsl:attribute name="style"><xsl:text>display: none;</xsl:text></xsl:attribute>
			</xsl:if>
			<a data-name="{@id}" data-label="{@title}"/>
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
						<xsl:value-of select="." />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="param" select="@name" />
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
							<xsl:apply-templates select="../../../field[@name = 'discount_modificator_id']" mode="modify-modificators">
								<xsl:with-param name="discount-type-id" select="@id" />
							</xsl:apply-templates>

							<xsl:apply-templates select="../../../field[@name = 'discount_rules_id']" mode="modify-rules">
								<xsl:with-param name="discount-type-id" select="@id" />
							</xsl:apply-templates>

						</div>
				</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field[@name = 'discount_modificator_id']" mode="modify-modificators">
		<xsl:param name="discount-type-id" />

		<xsl:variable name="modificators"
			select="document(concat('udata://emarket/getModificators/', $discount-type-id, '/', $param0))/udata" />
		<div class="col-md-6">
			<div class="title-edit">
				<acronym>
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
			</div>

			<xsl:apply-templates select="$modificators/items/item">
				<xsl:with-param name="input-name" select="@input_name" />
			</xsl:apply-templates>
		</div>
	</xsl:template>

	<xsl:template match="udata[@method = 'getModificators']//item">
		<xsl:param name="input-name" />
		<xsl:variable name="description" select="document(concat('uobject://', @id, '.description'))/udata/property" />
		<xsl:variable name="item-id" select="@id" />

		<p>
			<label class="inline">
				<input type="radio" class="checkbox" name="{$input-name}" value="{@id}">
					<xsl:if test="@selected = 'selected'">
						<xsl:attribute name="checked"><xsl:text>checked</xsl:text></xsl:attribute>
					</xsl:if>
				</input>
				<acronym>
					<xsl:value-of select="@name" />
					<xsl:apply-templates select="$description" mode="desc" />
				</acronym>
			</label>
		</p>
	</xsl:template>

	<xsl:template match="field[@name = 'discount_rules_id']" mode="modify-rules">
		<xsl:param name="discount-type-id" />
		<xsl:variable name="rules"
			select="document(concat('udata://emarket/getRules/', $discount-type-id, '/', $param0))/udata" />

		<div class="col-md-6">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
			</div>

			<xsl:apply-templates select="$rules/items/item">
				<xsl:with-param name="input-name" select="@input_name" />
			</xsl:apply-templates>
		</div>
	</xsl:template>

	<xsl:template match="udata[@method = 'getRules']//item">
		<xsl:param name="input-name" />
		<xsl:variable name="description" select="document(concat('uobject://', @id, '.description'))/udata/property" />
		<p>
			<label class="inline">
				<input type="checkbox" class="checkbox" name="{$input-name}" value="{@id}">
					<xsl:if test="@selected = 'selected'">
						<xsl:attribute name="checked"><xsl:text>checked</xsl:text></xsl:attribute>
					</xsl:if>
				</input>

				<acronym>
					<xsl:value-of select="@name" />
					<xsl:apply-templates select="$description" mode="desc" />
				</acronym>
			</label>
		</p>
	</xsl:template>

	<!-- Шаблон формы редактирования скидки -->
	<xsl:template match="/result[@method = 'discount_edit']/data" priority="1">
		<form class="form_modify" data-type-id="{$object-type-id}" action="do/" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}"/>
			<input type="hidden" name="domain" value="{$domain-floated}"/>

			<xsl:apply-templates mode="form-modify" />

			<!-- Select modificator and apply data::getEditForm -->
			<xsl:apply-templates select=".//field[@name = 'discount_modificator_id']/values/item" mode="discount-edit" />

			<!-- Select rules and apply data::getEditForm -->
			<xsl:apply-templates select=".//field[@name = 'discount_rules_id']/values/item" mode="discount-edit" />

			<div class="row">
				<xsl:call-template name="std-form-buttons"/>
			</div>

		</form>
	</xsl:template>

	<xsl:template match="field/values/item" mode="discount-edit">
		<xsl:apply-templates select="document(concat('udata://data/getEditForm/', @id))/udata">
			<xsl:with-param name="item-id" select="@id" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="udata[@method = 'getEditForm']">
		<xsl:param name="item-id" />

		<xsl:apply-templates select="group" mode="form-modify">
			<xsl:with-param name="item-id" select="$item-id" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="udata[@method = 'getEditForm']/group" mode="form-modify">
		<xsl:param name="item-id" />
		<xsl:variable name="label" select="document(concat('uobject://', $item-id))/udata//property[@name = 'rule_type_id' or @name = 'modificator_type_id']//item/@name" />
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
			<summary class="group-tip">
				<xsl:text>Тип модификатора скидки</xsl:text>
			</summary>
			<a data-name="{@name}" data-label="{$label}"/>
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
						<xsl:value-of select="$label" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="param" select="@name" />
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
							<xsl:apply-templates select="field" mode="form-modify" />

						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata//field[@name = 'modificator_type_id' or @name = 'rule_type_id']" mode="form-modify" priority="1" />

	<xsl:template match="/result[@method = 'discount_edit']//field[@name = 'discount_type_id']" mode="form-modify" />

	<!-- Шаблон формы создания способов оплаты и доставки -->
	<xsl:template mode="form-modify"
		match="/result[@method = 'payment_add' or @method = 'payment_edit' or @method = 'delivery_add']/data/object">
		<xsl:apply-templates mode="form-modify">
			<xsl:with-param name="show-type"><xsl:text>0</xsl:text></xsl:with-param>
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="object[./properties/group[@name = 'delivery_description_props']]" mode="form-modify">
		<xsl:choose>
			<xsl:when test="../@action = 'create'">
				<xsl:apply-templates select="properties/group[@name = 'delivery_description_props']" mode="form-modify" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="properties/group" mode="form-modify" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон поля "Ставка НДС" типа доставки "Почта России" -->
	<xsl:template
		match="object[@type-guid='emarket-delivery-808']//group[@name = 'delivery_description_props']/field[@name = 'tax_rate_id']"
		mode="form-modify"
	>
		<div class="col-md-6">
			<div class="title-edit">
				<acronym title="{@tip}" class="acr">
					<xsl:value-of select="@title"/>
				</acronym>
			</div>
			<span>
				<input class="default" type="text" name="{@input_name}" value="&label-vat-20-percent;" disabled="disabled"/>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон формы редактирования способа доставки "ApiShip" -->
	<xsl:template match="data[@type = 'form' and (@action = 'modify' or @action = 'create') and object/@type-guid = 'emarket-delivery-842']">
		<xsl:variable name="pid" select="/result/data/page/@id" />
		<xsl:variable name="oid" select="/result/data/object/@id" />
		<xsl:variable name="isPage" select="not(not(/result/data/page))" />

		<xsl:variable name="value" >
			<xsl:choose>
				<xsl:when test="$isPage">
					<xsl:value-of select="/result/data/page/@type-id" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="/result/data/object/@type-id" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="login" select="./object/properties/group[@name = 'settings']/field[@name = 'login']"/>

		<xsl:choose>
			<xsl:when test="$login = ''">
				<div class="tabs-content module {$module}-module">
					<div class="section selected">
						<xsl:apply-templates select="$errors" />
						<form class="form_modify" data-type-id="{$object-type-id}"  method="post" action="/admin/emarket/saveApiShipUser/{$oid}/" enctype="multipart/form-data">
							<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer"/>
							<xsl:apply-templates mode="form-modify"/>
						</form>
					</div>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<div class="editing-functions-wrapper">
					<div class="tabs editing"></div>
					<div class="toolbar clearfix">
						<a href="javascript:void(0);" class="icon-action extended_fields_expander"
						   title="&js-fields-expand;" data-expand-text="&js-fields-expand;"
						   data-collapse-text="&js-fields-collapse;">
							<i class="small-ico i-slideup"></i>
						</a>
						<a href="/admin/data/type_edit/{$value}" class="icon-action" id="edit" title="&label-edit-type;">
							<i class="small-ico i-edit"></i>
						</a>
						<a id="remove-object" title="&label-delete;" class="icon-action">
							<i class="small-ico i-remove"></i>
						</a>
						<script>
							var del_func_name = null;
							var obj_id = '<xsl:choose>
							<xsl:when test="$pid">
								<xsl:value-of select="$pid"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$oid"/>
							</xsl:otherwise>
						</xsl:choose>';
							$(document).ready(function (){
								$('#share-toggler').click(function (){
									$( "#ya_share1" ).toggle( 'display' );
								});

								$.ajax({
									url:'/admin/'+curent_module+'/dataset_config.xml',
									dataType:'xml',
									success: function (data){
										var el = $(data).find('method[title="Удалить"]').text();
										del_func_name = el ? el : null;
									}
								})

								$('#remove-object').on('click',function (){
									remove_confirm();
								});
							});

							function remove_confirm(){
								if (del_func_name === null) return false;

								var csrf = window.parent.csrfProtection.token;
								openDialog('', getLabel('js-del-object-title-short'), {
									cancelButton: true,
									html: getLabel('js-del-shured'),
									confirmText: getLabel('js-delete'),
									cancelText: getLabel('js-cancel'),
									confirmCallback: function(popupName) {
										$.ajax({
											url:'/admin/'+curent_module+'/'+del_func_name+'.xml?childs=1&amp;element='+obj_id+'&amp;allow=true&amp;csrf=' + csrf,
											dataType:'xml',
											success:function (data){
												closeDialog(popupName);
												window.location = '/admin/'+curent_module+'/';
											}
										});
									}
								});
							}
						</script>
					</div>
				</div>
				<div class="tabs-content module {$module}-module">
					<div class="section selected">
						<xsl:apply-templates select="$errors" />
						<form class="form_modify" data-type-id="{$object-type-id}"  method="post" action="do/" enctype="multipart/form-data">
							<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer"/>
							<input type="hidden" name="domain" value="{$domain-floated}"/>
							<input type="hidden" name="permissions-sent" value="1"/>
							<script type="text/javascript">
								//Проверка
								var treeLink = function(key, value){
									var settings = SettingsStore.getInstance();
									return settings.set(key, value, 'expanded');
								}
							</script>
							<xsl:apply-templates mode="form-modify"/>
							<div class="row">
								<xsl:choose>
									<xsl:when test="@action = 'create'">
										<xsl:call-template name="std-form-buttons-add"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:call-template name="std-form-buttons"/>
									</xsl:otherwise>
								</xsl:choose>
							</div>
						</form>
						<script type="text/javascript">
							var method = '<xsl:value-of select="/result/@method"/>';
							$('.form_modify').find('.select').each(function(){
								var current = $(this);
								buildSelect(current);
							});
						</script>
					</div>
				</div>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:call-template name="error-checker" >
			<xsl:with-param name="launch" select="1" />
		</xsl:call-template>
	</xsl:template>

	<!-- Шаблон полей формы редактирования способа доставки "ApiShip" -->
	<xsl:template mode="form-modify" match="/result[@method = 'delivery_edit']/data/object[@type-guid = 'emarket-delivery-842']">
		<xsl:variable name="login" select="./properties/group[@name = 'settings']/field[@name = 'login']"/>
		<xsl:choose>
			<xsl:when test="$login = ''">
				<div class="panel-settings">
					<a data-name="orders" data-label="&label-register-in-api-ship;"/>
					<div class="title">
						<h3>&label-register-in-api-ship;</h3>
					</div>
					<div class="content">
						<div class="layout">
							<div class="column">
								<div class="row">
									<div class="col-md-6">
										<div class="title-edit"><acronym>&label-login;</acronym></div>
										<input class="default" type="text" name="login" value=""/>
									</div>
									<div class="col-md-6">
										<div class="title-edit"><acronym>&label-password;</acronym></div>
										<input class="default" type="password" name="password" value=""/>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div id="buttons_wr" class="col-md-12">
						<div class="btn-select color-blue pull-right" style="width:220px;  margin-top:10px;">
							<div class="selected">
								<input type="submit" value="&label-to-register;" name="reg-mode" />
							</div>
							<ul class="list">
								<li>
									<input type="button" value="&label-cancel;" onclick="javascript: window.location = '{/result/@referer-uri}';"/>
								</li>
								<li>
									<input type="submit" value="&label-to-auth;" name="auth-mode" />
								</li>
							</ul>
						</div>
					</div>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates mode="form-modify">
					<xsl:with-param name="show-type">
						<xsl:text>0</xsl:text>
					</xsl:with-param>
				</xsl:apply-templates>
				<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'orders')"/>
				<div>
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
					<a data-name="orders" data-label="&header-emarket-orders;"/>
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
							<h3>&header-emarket-orders;</h3>
						</div>
						<xsl:call-template name="group-tip">
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
								<div id="tableWrapper"/>
								<script src="/js/underscore-min.js"/>
								<script src="/js/backbone-min.js"/>
								<script src="/js/twig.min.js"/>
								<script src="/js/backbone-relational.js"/>
								<script src="/js/backbone.marionette.min.js"/>
								<script src="/js/app.min.js"/>
								<script>
									(function(){
										var deliveryId = '<xsl:value-of select="./@id"/>';
										<![CDATA[
										var tableControl = new umiDataController({
											container:'#tableWrapper',
											prefix:'/admin/emarket',
											module:'emarket',
											configUrl:'/admin/emarket/getApiShipDataSetConfiguration/.json',
											dataProtocol: 'json',
											domain:1,
											lang:1,
											toolbarFunction: {
												refresh: {
													name: 'refresh',
													className: 'i-restore',
													hint: getLabel('js-refresh-orders-status'),
													init : function(button,item) {
														if (dc_application.toolbar.selectedItemsCount > 0 && dc_application.toolbar.selectedItemsCount < 20) {
															dc_application.toolbar.enableButtons(button);
														} else {
															dc_application.toolbar.disableButtons(button);
														}
													},
													release: function (button,item) {
														var orderIds = [];

														dc_application.toolbar.selectedItems.forEach(function(item, i, arr) {
															orderIds.push(dc_application.unPackId(item.id));
														});

														$.ajax({
															type: "POST",
															url: "/admin/emarket/refreshApiShipOrdersStatuses/.json",
															dataType: "json",
															data: 	{
																param0: deliveryId,
																param1: orderIds
															}
														}).done(function(json){
															if (typeof json.data != 'undefined' && typeof json.data.error != 'undefined') {
																$.jGrowl(json.data.error);
															} else {
																location.reload();
															}
														})

														return false;
													}
												},
												cancel: {
													name: 'cancel',
													className: 'i-remove',
													hint: getLabel('js-cancel-order'),
													init : function(button,item) {
														if (dc_application.toolbar.selectedItemsCount == 1) {
															dc_application.toolbar.enableButtons(button);
														} else {
															dc_application.toolbar.disableButtons(button);
														}
													},
													release: function (button,item) {
														var orderId = dc_application.toolbar.selectedItems[0].id;
														orderId = dc_application.unPackId(orderId);

														$.ajax({
															type: "POST",
															url: "/admin/emarket/cancelApiShipOrder/.json",
															dataType: "json",
															data: 	{
																param0: deliveryId,
																param1: orderId
															}
														}).done(function(json){
															if (typeof json.data != 'undefined' && typeof json.data.error != 'undefined') {
																$.jGrowl(json.data.error);
															} else {
																location.reload();
															}
														})

														return false;
													}
												},
												waybill: {
													name: 'waybill',
													className: 'i-copy-other',
													hint: getLabel('js-download-way-bill'),
													init : function(button,item) {
														if (dc_application.toolbar.selectedItemsCount == 1) {
															dc_application.toolbar.enableButtons(button);
														} else {
															dc_application.toolbar.disableButtons(button);
														}
													},
													release: function (button,item) {
														var orderId = dc_application.toolbar.selectedItems[0].id;
														orderId = dc_application.unPackId(orderId);

														$.ajax({
															type: "POST",
															url: "/admin/emarket/getApiShipWayBill/.json",
															dataType: "json",
															data: 	{
																param0: deliveryId,
																param1: orderId
															}
														}).done(function(json){
															if (typeof json.data != 'undefined' && typeof json.data.error != 'undefined') {
																$.jGrowl(json.data.error);
															} else {
																location.href = json.file;
															}
														})

														return false;
													}
												},
												label: {
													name: 'label',
													className: 'i-amend',
													hint: getLabel('js-download-label'),
													init : function(button,item) {
														if (dc_application.toolbar.selectedItemsCount == 1) {
															dc_application.toolbar.enableButtons(button);
														} else {
															dc_application.toolbar.disableButtons(button);
														}
													},
													release: function (button,item) {
														var orderId = dc_application.toolbar.selectedItems[0].id;
														orderId = dc_application.unPackId(orderId);

														$.ajax({
															type: "POST",
															url: "/admin/emarket/getApiShipLabel/.json",
															dataType: "json",
															data: 	{
																param0: deliveryId,
																param1: orderId
															}
														}).done(function(json){
															if (typeof json.data != 'undefined' && typeof json.data.error != 'undefined') {
																$.jGrowl(json.data.error);
															} else {
																location.href = json.url;
															}
														})

														return false;
													}
												}
											},
											toolbarMenu:['refresh', 'waybill', 'label', 'cancel']
										});
										]]>
										tableControl.start();
									})()
								</script>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<xsl:choose>
						<xsl:when test="../@action = 'create'">
							<xsl:call-template name="std-form-buttons-add"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="std-form-buttons"/>
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон форм редактирования и создания складов -->
	<xsl:template match="/result[@method = 'store_add' or @method = 'store_edit']/data">
		<form class="form_modify" data-type-id="{$object-type-id}" method="post" action="do/" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer" />
			<input type="hidden" name="domain" value="{$domain-floated}"/>

			<xsl:apply-templates mode="form-modify">
				<xsl:with-param name="group-title"><xsl:text>&label-store-common;</xsl:text></xsl:with-param>
			</xsl:apply-templates>
			<div class="row">
				<xsl:choose>
					<xsl:when test="$data-action = 'create'">
						<xsl:call-template name="std-form-buttons-add"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="std-form-buttons"/>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</form>
	</xsl:template>

	<!-- Шаблон формы редактирования адреса доставки -->
	<xsl:template match="/result[@method = 'delivery_address_edit']/data/object" mode="form-modify">
		<xsl:apply-templates select="properties/group" mode="form-modify">
			<xsl:with-param name="show-name"><xsl:text>0</xsl:text></xsl:with-param>
		</xsl:apply-templates>
	</xsl:template>

	<!-- Payment -->
	<xsl:template match="field[@name = 'payment_type_id' or @name='delivery_type_id']" mode="form-modify">
		<div class="col-md-6" id="{generate-id()}" xmlns:umi="http://www.umi-cms.ru/TR/umi" umi:type="{@type-id}">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>
			<div>
				<select name="{@input_name}" id="relationSelect{generate-id()}" class="default newselect type_select" autocomplete="off">
					<xsl:apply-templates select="." mode="required_attr" >
						<xsl:with-param name="old_class" >default newselect</xsl:with-param>
					</xsl:apply-templates>
					<xsl:apply-templates select="values/item" mode="type-select">
						<xsl:with-param name="object-type-guid" select="document(concat('utype://', /result/data/object/@type-id))/udata/type/@guid" />
					</xsl:apply-templates>
				</select>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field/values/item" mode="type-select">
		<xsl:param name="object-type-guid" select="0" />
		<xsl:variable name="type-guid" select="document(concat('uobject://', @id))/udata/object/properties/group/property[@name='payment_type_guid' or @name='delivery_type_guid']/value" />
		<option value="{@id}">
			<xsl:if test="$type-guid=$object-type-guid">
				<xsl:attribute name="selected"><xsl:text>selected</xsl:text></xsl:attribute>
			</xsl:if>
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="properties/group[@name = 'statistic_info']" mode="form-modify">
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
						<xsl:value-of select="@title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="param" select="@name" />
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
							<xsl:apply-templates select="field" mode="form-modify" />
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="properties/group[@name = 'statistic_info']/field" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<acronym title="{@tip}"><xsl:value-of select="./@title" /></acronym>
			</div>
			<xsl:apply-templates select="." />
		</div>
	</xsl:template>

	<xsl:template match="properties/group[@name = 'statistic_info']/field[@name = 'http_referer' or @name = 'http_target']" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<acronym title="{@tip}"><xsl:value-of select="./@title" /></acronym>
			</div>
			<a href="{.}" id="{generate-id()}" class="text container_with_link" name="{@input_name}"><xsl:apply-templates select="." mode="value" /></a>
		</div>
	</xsl:template>

	<xsl:template match="properties/group[@name = 'statistic_info']/field" mode="value">
		<xsl:text>/</xsl:text>
	</xsl:template>

	<xsl:template match="properties/group[@name = 'statistic_info']/field[. != '']" mode="value">
		<xsl:value-of select="." disable-output-escaping="yes" />
	</xsl:template>

	<!-- Шаблон настроек способа доставки "ApiShip" -->
	<xsl:template match="group[@name='settings' and ../../@type-guid='emarket-delivery-842']" mode="form-modify">
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
						<xsl:value-of select="@title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="@name" />
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
							<script src="/styles/skins/modern/design/js/control.JsonSelect.js?{$system-build}"></script>
							<script src="/styles/skins/modern/design/js/initApiShipEditControls.js?{$system-build}"></script>
							<xsl:apply-templates select="field[@name = 'login']" mode="form-modify" />
							<xsl:apply-templates select="field[@name = 'password']" mode="form-modify" />
							<div class="col-md-6">
								<a id="change-api-ship-user" class="btn color-blue btn-small">
									<xsl:text>&label-api-ship-change-user;</xsl:text>
								</a>
							</div>
							<xsl:apply-templates select="field[@name = 'dev_mode']" mode="form-modify" />
							<xsl:apply-templates select="field[@name = 'keep_log']" mode="form-modify" />
							<xsl:apply-templates select="field[@name = 'providers']" mode="form-modify" />
							<xsl:apply-templates select="field[@name = 'delivery_types']" mode="form-modify" />
							<xsl:apply-templates select="field[@name = 'pickup_types']" mode="form-modify" />
							<xsl:apply-templates select="field[@name = 'settings']" mode="form-modify" />
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field[@name = 'providers' or @name='pickup_types' or @name='delivery_types']" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<xsl:value-of select="@title"/>
			</div>
			<div>
				<input type="hidden" name="{@input_name}" value="{.}"/>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field[@name='settings']" mode="form-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<xsl:value-of select="@title"/>
			</div>
			<div>
				<input type="hidden" name="{@input_name}" value="{.}"/>
				<link href="/styles/skins/modern/design/css/widget.ApiShipProvidersSettings.css?{$system-build}" rel="stylesheet"/>
				<div id="asps_wrapper"/>
				<script src="/styles/skins/modern/design/js/widget.ApiShipProvidersSettings.js?{$system-build}"/>
				<template id="layoutsTpl">
					<![CDATA[
						<div class="asps_tab_wrapper">
							<ul id="asps_tabs"></ul>
						</div>
						<a class="asps_tabs_nav prev"/>
						<a class="asps_tabs_nav next"/>
						<div id="asps_form_wrapper">
							<img src="/images/cms/loading.gif" />
						</div>
						<div class="asps_buttons" style="display: none;">
							<a class="btn color-blue btn-small pull-right">Подключить настроенные службы доставки</a>
						</div>
					]]>
				</template>
				<template id="connectDialogTpl">
					<![CDATA[
						<div id="asps_dialog" >
						<div class="progress">
							<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemax="<%= total%>" style="width: 0%">
							<span>0%</span>
							</div>
						</div>
						<div class="log"></div>
						</div>
					]]>
				</template>
				<template id="tabTpl">
					<![CDATA[
						<li class="tab" data-provider="<%= title %>">
						<img src="/styles/skins/modern/design/img/delivery_providers/<%= title %>.png" alt="<%= title %>"/>
						</li>
					]]>
				</template>
				<template id="stringFieldTpl">
					<![CDATA[
						<div class="col-md-12 providerSettingsField">
							<div class="title-edit"><%= description%><% if (required) { %> * <% } %></div>
							<span>
								<input class="default" asps-id="<%= name %>" type="text" value="<%= value %>" asps-id="<%= name %>"/>
							</span>
						</div>
					]]>
				</template>
				<template id="booleanFieldTpl">
					<![CDATA[
						<div class="col-md-12 providerSettingsField">
							<div class="title-edit"><%= description%></div>
							<span>
								<input type="checkbox" <% if (value) { %> checked <% } %> asps-id="<%= name %>"/>
							</span>
						</div>
					]]>
				</template>
				<template id="tariffsFieldTpl">
					<![CDATA[
					<div class="col-md-6 providerSettingsField">
						<div class="title-edit"><%= description%></div>
						<span>
							<input class="" type="hidden" value=""/>
							<select class="asps_select_<%= type %>" multiple asps-id="<%= name %>"></select>
						</span>
					</div>
					]]>
				</template>
				<template id="pointFieldTpl">
					<![CDATA[
						<div class="col-md-6 providerSettingsField">
							<div class="title-edit"><%= description%></div>
							<span>
								<input class="" type="hidden" value=""/>
								<select class="asps_select_<%= type %>" multiple asps-id="<%= name %>"></select>
							</span>
						</div>
					]]>
				</template>
				<template id="tariffOptionTpl">
					<![CDATA[
					<div>
						<h3><%= name%></h3>
						<p><%= description%></p>
					</div>
					]]>
				</template>
				<template id="pointOptionTpl">
					<![CDATA[
						<div>
						<h3><%= name%></h3>
						<p><%= region %>, г. <%= city %>,</br> <%= streetType%>. <%= street%>, <%= house %></p>
					</div>
					]]>
				</template>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
