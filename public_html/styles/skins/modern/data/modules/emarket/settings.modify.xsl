<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'mail_config']/data[@type = 'settings' and @action = 'modify']">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
				<form method="post" action="do/" enctype="multipart/form-data">
					<xsl:apply-templates select="." mode="settings-modify"/>
					<div class="row">
						<xsl:call-template name="std-form-buttons-settings" />
					</div>
				</form>
				<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo"/>
			</div>

			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'mail_config']//group[@name = 'status-notifications']" mode="settings-modify">
		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="@label" />
				</h3>
			</div>
			<div class="content">
				<table class="btable btable-striped">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify" />
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'mail_config']//group[@name != 'status-notifications']" mode="settings-modify">
		<xsl:variable name="from-email" select="option[position() = 2]" />
		<xsl:variable name="from-name" select="option[position() = 3]" />
		<xsl:variable name="manager-email" select="option[position() = 4]" />

		<table class="btable btable-striped">
			<thead>
				<tr>
					<th colspan="2" class="eq-col">
						<xsl:value-of select="option[@name = 'domain']/value" />
					</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td>
						<label for="{$from-email/@name}">
							<xsl:text>&option-email;</xsl:text>
						</label>
					</td>

					<td>
						<input class="default" type="text" name="{$from-email/@name}" value="{$from-email/value}" id="{$from-email/@name}" />
					</td>
				</tr>

				<tr>
					<td class="eq-col">
						<label for="{$from-name/@name}">
							<xsl:text>&option-name;</xsl:text>
						</label>
					</td>

					<td>
						<input class="default" type="text" name="{$from-name/@name}" value="{$from-name/value}" id="{$from-name/@name}" />
					</td>
				</tr>

				<tr>
					<td class="eq-col">
						<label for="{$manager-email/@name}">
							<xsl:text>&option-manageremail;</xsl:text>
						</label>
					</td>

					<td>
						<input class="default" type="text" name="{$manager-email/@name}" value="{$manager-email/value}" id="{$manager-email/@name}" />
					</td>
				</tr>
			</tbody>
		</table>
	</xsl:template>

	<xsl:template match="/result[@method = 'yandex_market_config']/data[@type = 'settings' and @action = 'modify']">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
		<div class="column">
		<form method="post" action="do/" enctype="multipart/form-data">
			<xsl:apply-templates select="." mode="settings-modify" />
			<div class="row">
				<xsl:call-template name="std-form-buttons-settings" />
			</div>
		</form>
		</div>
		<div class="column">
			<xsl:call-template name="entities.help.content" />
		</div>
		</div>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		<div id='createTokenPopup' style='display:none;'>
			<div class='eip_win_head popupHeader'>
				<div class='eip_win_close popupClose'></div>
				<div class='eip_win_title'>&label-yandex-market-generate;</div>
			</div>
			<div class='eip_win_body popupBody'>
				<form id="createTokenForm" action="{$lang-prefix}/admin/emarket/yandexMarketCreateToken/" method="POST">
					<input type="hidden" name="domain" value="" />
					<input type="hidden" name="clientId" value="" />
					<input type="hidden" name="password" value="" />
					<input type="hidden" name="token" value="" />
					<input type="hidden" name="login" value="" />
					<input type="hidden" name="marketToken" value="" />
					<input type="hidden" name="marketCampaignId" value="" />
					<input type="hidden" name="cashOnDelivery" value="" />
					<input type="hidden" name="cardOnDelivery" value="" />
					<input type="hidden" name="shopPrepaid" value="" />
					<div class='popupText'>&lable-yandex-market-confirm;</div>
					<div class='eip_buttons'>
						<input type='button' id="createTokenFormSend" class="ok" value='&label-proceed;' />
						<input type='button' id="createTokenFormCancel" value='&label-cancel;' class='stop'/>
						<div style='clear:both;'/>
					</div>
				</form>
			</div>
		</div>
		<xsl:if test="not(/result/@demo)">
			<script>
				<![CDATA[
					jQuery(function() {
						// Обработчик для получить токен
						jQuery(".emarket_yandex_market_config_btn[rel]").click(function() {
							jQuery('#createTokenForm input[name="domain"]').val(jQuery(this).attr('rel'));
							jQuery(this).closest('.content').find('input').each(function() {
								this.value = jQuery.trim(this.value);
								var name = jQuery(this).attr('name').split('-')[0];
								var value = this.value;
								switch(name) {
									case 'clientId': { jQuery('#createTokenForm input[name="clientId"]').val(value); break; }
									case 'password': { jQuery('#createTokenForm input[name="password"]').val(value); break; }
									case 'token': { jQuery('#createTokenForm input[name="token"]').val(value); break; }
									case 'login': { jQuery('#createTokenForm input[name="login"]').val(value); break; }
									case 'marketToken': { jQuery('#createTokenForm input[name="marketToken"]').val(value); break; }
									case 'marketCampaignId': { jQuery('#createTokenForm input[name="marketCampaignId"]').val(value); break; }
									case 'cashOnDelivery': { jQuery('#createTokenForm input[name="cashOnDelivery"]').val((value == 1 && jQuery(this).attr('checked'))?1:0); break; }
									case 'cardOnDelivery': { jQuery('#createTokenForm input[name="cardOnDelivery"]').val((value == 1 && jQuery(this).attr('checked'))?1:0); break; }
									case 'shopPrepaid': { jQuery('#createTokenForm input[name="shopPrepaid"]').val((value == 1 && jQuery(this).attr('checked'))?1:0); break; }
								}
							});

							if (jQuery('#createTokenForm input[name="clientId"]').val()=='' || jQuery('#createTokenForm input[name="password"]').val()=='') {
								jQuery.jGrowl(getLabel('js-lable-yandex-market-empty-field'), {
									'header': 'UMI.CMS',
									'life': 10000
								});
							} else if (jQuery('#createTokenForm input[name="token"]').val()!='') {
								jQuery.openPopupLayer({
									name   : 'createTokenPopup',
									target : 'createTokenPopup',
									width  : 400
								});
							} else {
								jQuery('#createTokenForm').submit();
							}
							return false;
						});

						// Обработка отказа от продолжения
						jQuery(document).on('click', '#popupLayer_createTokenPopup #createTokenFormCancel', function() {
							jQuery.closePopupLayer('createTokenPopup');
						});

						// Обработка продолжения генерации
						jQuery(document).on('click', '#popupLayer_createTokenPopup #createTokenFormSend', function() {
							jQuery('#createTokenForm').submit();
						});
					});
				]]>
			</script>
		</xsl:if>
	</xsl:template>

	<xsl:template match="/result[@method = 'yandex_market_config']//group" mode="settings-modify">
		<xsl:variable name="client-id" select="option[position() = 1]" />
		<xsl:variable name="client-password" select="option[position() = 2]" />
		<xsl:variable name="client-token" select="option[position() = 3]" />
		<xsl:variable name="client-login" select="option[position() = 4]" />
		<xsl:variable name="client-market-token" select="option[position() = 5]" />
		<xsl:variable name="client-campaign-id" select="option[position() = 6]" />
		<xsl:variable name="cash-on-delivery" select="option[position() = 7]" />
		<xsl:variable name="card-on-delivery" select="option[position() = 8]" />
		<xsl:variable name="shop-prepaid" select="option[position() = 9]" />
		<div class="panel-settings">
			<div class="title">
				<div>&group-settings-for;<xsl:value-of select="@name" /></div>
			</div>
			<div class="content">
				<table class="btable btable-striped btable-bordered">
					<tbody>
						<tr>
							<td width="50%"><label for="{$client-id/@name}"><xsl:text>&option-clientId;</xsl:text></label></td>
							<td colspan="2"><input type="text" class="default"  name="{$client-id/@name}" value="{$client-id/value}" id="{$client-id/@name}" /></td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$client-password/@name}"><xsl:text>&option-clientSecret;</xsl:text></label></td>
							<td colspan="2"><input type="text" class="default"  name="{$client-password/@name}" value="{$client-password/value}" id="{$client-password/@name}" /></td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$client-token/@name}"><xsl:text>&option-token;</xsl:text></label></td>
							<td colspan="2">
								<div class="layout-col-control" style="width:70%;">
									<input type="text" class="default" name="{$client-token/@name}"
										   value="{$client-token/value}" id="{$client-token/@name}" />
								</div>

								<div class="layout-col-icon">
									<a target="_blank" class="emarket_yandex_market_config_btn" rel="{@name}"
									   href="javascript:void();">Получить&nbsp;токен
									</a>
								</div>

							</td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$client-login/@name}"><xsl:text>&option-login;</xsl:text></label></td>
							<td colspan="2"><input type="text" class="default"  name="{$client-login/@name}" value="{$client-login/value}" id="{$client-login/@name}" /></td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$client-market-token/@name}"><xsl:text>&option-marketToken;</xsl:text></label></td>
							<td colspan="2"><input type="text" class="default"  name="{$client-market-token/@name}" value="{$client-market-token/value}" id="{$client-market-token/@name}" /></td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$client-campaign-id/@name}"><xsl:text>&option-marketCampaignId;</xsl:text></label></td>
							<td colspan="2"><input type="text" class="default"  name="{$client-campaign-id/@name}" value="{$client-campaign-id/value}" id="{$client-campaign-id/@name}" /></td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$cash-on-delivery/@name}"><xsl:text>&option-cashOnDelivery;</xsl:text></label></td>
							<td colspan="2">
								<input type="hidden" name="{$cash-on-delivery/@name}" value="0" />
								<div class="checkbox">
									<input type="checkbox" name="{$cash-on-delivery/@name}" value="1"
										   id="{$cash-on-delivery/@name}" class="check">
										<xsl:if test="$cash-on-delivery/value = '1'">
											<xsl:attribute name="checked">checked</xsl:attribute>
										</xsl:if>
									</input>
								</div>

							</td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$card-on-delivery/@name}"><xsl:text>&option-cardOnDelivery;</xsl:text></label></td>
							<td colspan="2">
								<input type="hidden" name="{$card-on-delivery/@name}" value="0" />
								<div class="checkbox">
									<input type="checkbox" name="{$card-on-delivery/@name}" value="1"
										   id="{$card-on-delivery/@name}" class="check">
										<xsl:if test="$card-on-delivery/value = '1'">
											<xsl:attribute name="checked">checked</xsl:attribute>
										</xsl:if>
									</input>
								</div>
							</td>
						</tr>
						<tr>
							<td class="eq-col"><label for="{$shop-prepaid/@name}"><xsl:text>&option-shopPrepaid;</xsl:text></label></td>
							<td colspan="2">
								<input type="hidden" name="{$shop-prepaid/@name}" value="0" />
								<div class="checkbox">
									<input type="checkbox" name="{$shop-prepaid/@name}" value="1"
										   id="{$shop-prepaid/@name}" class="check">
										<xsl:if test="$shop-prepaid/value = '1'">
											<xsl:attribute name="checked">checked</xsl:attribute>
										</xsl:if>
									</input>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<!--<xsl:call-template name="std-save-button" />-->
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'config']/data[@type = 'settings' and @action = 'modify']">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
		<div class="column">
		<form method="post" action="do/" enctype="multipart/form-data">
			<xsl:apply-templates select="." mode="settings.modify" >
				<xsl:with-param name="toggle" select="0"/>
			</xsl:apply-templates>
			<div class="row">
				<xsl:call-template name="std-form-buttons-settings" />
			</div>
		</form>
		<xsl:if test="/result[@method = 'config']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
		<xsl:if test="/result[@module = 'content' and @method = 'content_control']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
		<xsl:if test="/result[@module = 'emarket' and @method = 'social_networks']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
		<xsl:if test="/result[@module = 'search' and @method = 'index_control']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
		<xsl:if test="/result[@module = 'search' and @method = 'index_control']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>

		<div class="panel-settings">
			<div class="title">
				<h3>
					&js-emarket-reindex-header;
				</h3>
			</div>
			<div class="content">

				<script type="text/javascript">
					<![CDATA[
					var getReindexResult = function() {
						$.getJSON('/admin/emarket/getLastReindexDate/.json', function (e) {
							if (!e.data.reindexDate && !e.data.reindexResult) {
								$('#lastReindexDate').html(getLabel('js-emarket-reindex-no')).css('color','red');
							} else if (e.data.reindexResult) {
								$('#lastReindexDate').html(e.data.reindexDate).css('color','black');
								$('#lastReindexResult').html(getLabel('js-reindex-success')).css('color','green');
								$('#lastReindexResult').parent().css('display','block');
							} else {
								$('#lastReindexDate').html(e.data.reindexDate).css('color','black');
								$('#lastReindexResult').html(getLabel('js-reindex-fail')).css('color','red');
								$('#lastReindexResult').parent().css('display','block');
							}
						});
					};
					$(function() {
						getReindexResult();
					});
					]]>
					<xsl:if test="not(/result[@demo])">
					<![CDATA[
					$(function() {
						$('#rebuildTopItems').bind('click', function() {
							rebuildTopItems()
						});
					});
					function rebuildTopItems() {
						var partialQuery = function(page) {
							if(window.session) {
								window.session.startAutoActions();
							}

							$.get('/admin/emarket/partialRecalc.xml?page='+page, null, function (data) {
								var current = $('index-items', data).attr('current');
								var total = $('index-items', data).attr('total');
								var page = $('index-items', data).attr('page');

								if(parseInt(current) < parseInt(total)) {
									$('#emarket-reindex-log').html(getLabel('js-emarket-reindex-popular-items') + current);
									partialQuery(page);
								} else {
									$('.eip_buttons .ok').css('display','block');
									$('.eip_buttons').css('display','block');
									$('#emarket-reindex-log').html(getLabel('js-emarket-reindex-popular-items') + total);
									$('#processReindex').html(getLabel('js-emarket-reindex-finish')).addClass('textOk');
									getReindexResult();
									return;
								}
							});
						}

						partialQuery(0);

						openDialog('', getLabel('js-emarket-reindex-header'), {
							'html': '<span id="processReindex">' + getLabel('js-emarket-reindex') + '</span>' + '<p id="emarket-reindex-log" />'
						});

						$('.eip_buttons').css('display','none');
						$('.eip_buttons .back').css('display','none');
						return false;
					}
					]]>
					</xsl:if>
				</script>

				<p>&stat-date-reindex;: <span id="lastReindexDate"></span></p>
				<p style="display:none">&stat-result-reindex;: <span id="lastReindexResult"></span></p>

				<div class="buttons emarket_config_btn" id="rebuildTopItems" style="float:right">
					<div>
						<input class="btn color-blue" type="button" value="&order-reindex;"/>
					</div>
				</div>

			</div>
		</div>
		</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group[@name = 'fields-settings']" mode="settings.modify">
		<xsl:param name="toggle" select="1" />
		<div class="panel-settings">
			<div class="title field-group-toggle">
				<xsl:if test="$toggle = 1 and (count(.) > 1)">
					<div class="round-toggle " />
				</xsl:if>

				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<div class="row">
					<div class="col-md-4 group-caption">&label-group-caption-item-property;</div>
					<div class="col-md-4 group-caption">&label-group-caption-item-field;</div>
				</div>
				<xsl:apply-templates select="option" mode="settings.modify" />
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
