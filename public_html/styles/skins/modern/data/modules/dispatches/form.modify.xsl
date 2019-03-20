<?xml version="1.0"	encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/" [
	<!ENTITY sys-module 'dispatches'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- Шаблон формы редактирования объекта модуля "Рассылки" -->
	<xsl:template match="data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<div class="editing-functions-wrapper">
			<div class="tabs editing">
			</div>
		</div>
		<div class="tabs-content module">
			<div class="section selected">
				<xsl:apply-templates select="$errors" />
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:if test="(../@method = 'edit') and (object/@type-guid = 'dispatches-dispatch')">
						<div class="">
							<a id="addDispatchMessage"  class="btn color-blue loc-left" href="{$lang-prefix}/admin/&sys-module;/add_message/{object/@id}/" >&label-add-message;</a>
							<a class="btn color-blue loc-left" href="{$lang-prefix}/admin/&sys-module;/releases/{object/@id}/">&label-releases-list;</a>
							<a class="btn color-blue loc-left" href="{$lang-prefix}/admin/&sys-module;/subscribers/{object/@id}/">&label-subscribers;</a>
						</div>
					</xsl:if>
					<div class="saveSize"/>
				</div>
				<div class="layout">
					<div class="column">
						<form class="form_modify" data-type-id="{$object-type-id}" method="post" action="do/" enctype="multipart/form-data">
							<input type="hidden" name="referer" value="{/result/@referer-uri}"/>
							<input type="hidden" name="domain" value="{$domain-floated}"/>
							<xsl:apply-templates mode="form-modify" />
							<xsl:apply-templates select="object/release" mode="form-modify" />

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
						<script type="text/javascript">
							var method = '<xsl:value-of select="/result/@method"/>';
							$('.form_modify').find('.select').each(function(){
							var current = $(this);
							buildSelect(current);
							});

							<![CDATA[
							jQuery('form.form_modify').submit(function(e) {
								//e.preventDefault();
								window.errorElements = [];
								var val = true;
								var str = '<div id="fields-errors"><ul>';
								var isTextArea = false;
								var editor = null;
								var editorValue = '';
								var isEmptyValue = false;
								var requiredFields = $('sup:contains(*)').parent().next().children()
								var errorElement = null;
								
								//jQuery(requiredFields, this).each(function(){
								for (var i = 0, cnt = requiredFields.length; i<cnt; i++){
									var item = $(requiredFields[i])
									isTextArea = item.tagName && item.tagName.toLowerCase() === 'textarea';
									if (isTextArea && typeof tinyMCE == 'object') {
										editor = tinyMCE.get(item.id);

										if (editor && typeof(editor.getContent) === 'function') {
											editorValue = editor.getContent({format: 'text'});
											isEmptyValue = (typeof(editorValue) === 'string' && editorValue.length === 0) ||
														   (editorValue.length === 1 && editorValue.charCodeAt(0) === "\n".charCodeAt(0));
										}
									} else {
										isEmptyValue = (item.value === '');
									}

									if (isEmptyValue) {
										if (val === true) {
											val = false;
										}

										if (isTextArea && editor) {
											window.errorElements.push($(editor.contentAreaContainer).closest('table').get(0));
										} else {
											window.errorElements.push(item);
										}


										var innerText = jQuery(item).parent().parent().find('acronym').eq(0).text();
										str += '<li>' + getLabel('js-error-required-field') + ' «<span class="field-name">' + innerText + '</span>».' + '</li>';
									}
								}
								str += '</ul></div>';

								if (val === false) {
									openDialog('', getLabel('js-label-errors-occurred'), {
										timer: 5000,
										width: 400,
										html: str,
										closeCallback: function() {
											var element = null;

											for (var i = 0; i < window.errorElements.length; i++) {
												element = window.errorElements[i];
												$(element).effect("highlight", {color:"#00a0dc"}, 5000);
											}
										}
									});
								}
								return val;
							});
						]]>
						</script>
					</div>
				</div>

			</div>
		</div>
	</xsl:template>



	<xsl:template match="properties/group" mode="form-modify">
		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>0</xsl:text></xsl:param>

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
							<xsl:apply-templates select="." mode="form-modify-group-fields">
								<xsl:with-param name="show-name" select="$show-name"/>
								<xsl:with-param name="show-type" select="$show-type"/>
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
	
	<xsl:variable name="first_column_title">
		<xsl:choose>
			<xsl:when test="/result/data/object/@type-guid = 'dispatches-subscriber' or //field[@name = 'subscribe_date']">
				<xsl:text>&label-user-email;</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>&label-name;</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:template match="properties/group[position() = 1]" mode="form-modify">
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
							<xsl:call-template name="std-form-name">
								<xsl:with-param name="value" select="../../@name" />
								<xsl:with-param name="label" select="$first_column_title" />
							</xsl:call-template>

							<xsl:if test="//field[@name = 'is_active']">
								<xsl:call-template name="std-form-is-active">
									<xsl:with-param name="value" select="//field[@name = 'is_active']" />
									<xsl:with-param name="form_name" select="//field[@name = 'is_active']/@input_name" />
								</xsl:call-template>
							</xsl:if>
							<xsl:apply-templates select="." mode="form-modify-group-fields">
								<xsl:with-param name="show-name" select="0"/>
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

	<xsl:template match="release[message]" mode="form-modify">
		<script type="text/javascript">
			var dispatchId = <xsl:value-of select="number($param0)" />;
			<![CDATA[
			function getFormData(sFormName) {
				 var sData = "";
				 //var arrObjForm  = document.getElementsByName(sFormName);
				 var oObjForm = jQuery("form").get(0);//arrObjForm[0];
				 if (oObjForm && oObjForm.elements && oObjForm.elements.length) {
					for (iI=0; iI<oObjForm.elements.length; iI++) {
						oElement = oObjForm.elements[iI];
						if (oElement.name && oElement.name.length) {
							var sVal = "";
							if(oElement.type=='checkbox') {
								sVal = (oElement.checked? 1: 0);
							} else if (oElement.tagName.toUpperCase()==='SELECT'  && oElement.multiple) {
								var iOpt=0;
								for (iOpt=0; iOpt<oElement.options.length; iOpt++) {
									var theOpt=oElement.options[iOpt];
								}
							} else {
								sVal = oElement.value;
							}
							sData += oElement.name + "=" + sVal + "&";
						}
					}
				}
				return sData;
			}

			function sendMessagesPacket(iDispatchId) {
				var sParams = getFormData('disp_edt_frm');

				if(window.session) {
				    window.session.startAutoActions();
				}

				var sDataSrc = "/admin/dispatches/release_send/"+iDispatchId+"?"+new Date().getTime();
				jQuery.ajax({
							url  : sDataSrc,
							type : "POST",
							//dataType : "xml",
							data : sParams,
							error: function(request, status, error) {
										if(window.session) {
											window.session.stopAutoActions();
										}
										sendAborted(getLabel('js-dispatch-server-error') + ". (status: " + status + ")", 'red');
									},
							success: function(data, status, request) {
										if (data) {
											if (jQuery("error", data).length) {
												sendAborted(jQuery("error", data).text(), true);
											} else {
												try {
													var iTotal = parseInt(jQuery("total", data).text());
													var iSended = parseInt(jQuery("sended", data).text());
													var iPercent = iTotal == 0 ? 0 : Math.floor(iSended * 100 / iTotal);
													var sPercent = iPercent + '%';
													if (iTotal != 0) {
														jQuery('#dlg-progress-bar').css({ width : sPercent});
														jQuery('#dlg-progress-lbl').html(sPercent);
														jQuery('#dlg-message').html(getLabel('js-dispatch-send1') + "<strong>" + iSended + "</strong>" +getLabel('js-dispatch-send2') +"<strong>" + iTotal + "</strong>" +getLabel('js-dispatch-send3'));
														if (iPercent < 100) {
															// ++Progress
															sendMessagesPacket(iDispatchId);
														} else {
															sendAborted(getLabel('js-dispatch-send-sucess') + iTotal, 'green', false);
														}
													} else {
														sendAborted(getLabel('js-dispatch-no-subscribers'));
													}
												} catch(theErr) {
													sendAborted(getLabel('js-dispatch-unknown-error') + ": " + theErr, 'red');
												}
											}
										} else {
											sendAborted(getLabel('js-dispatch-unknown-response'), 'red'); // TODO
										}
								}
				});
			}

			function sendAborted(sStatus, sColor, canRepeat) {
				if (typeof(canRepeat) == 'undefined') var canRepeat = true;
				if (typeof(sColor) === 'string') {
					jQuery('#dlg-message').css("color", sColor);
				}
				jQuery('#dlg-message').text(sStatus);
				jQuery('#dlg-ok-button').show();
				if (canRepeat) jQuery('#dlg-repeat-button').show();

				if(window.session) {
					 window.session.stopAutoActions();
				}
			}

			function repeatSend() {
				jQuery('#dlg-ok-button').hide();
				jQuery('#dlg-repeat-button').hide();
				jQuery('#dlg-message').html('&nbsp;');
				sendMessagesPacket(dispatchId);
			}

			function sendRelease(iDispatchId) {
				var but_ex = getLabel('js-dispatch-dialog-title');
				var sDialog =	'<div id="dlg-progress">' +]]>
				<xsl:if test="/result/@demo"><![CDATA['<div>В демо-режиме письма не отправляются.</div>' +]]></xsl:if>
				 <![CDATA['<span id="dlg-progress-lbl">0%</span>' +
										'<div id="dlg-progress-bar"></div>' +
										'<div id="dlg-message" style="margin-top:2px"></div>' +
										'<div class="eip_buttons custom">' +
											'<input type="button" class="back" style="display:none" onclick="document.location.href = document.location.href" id="dlg-ok-button" value="' + getLabel('js-dispatch-dialog-close') + '" />' +
											'<input type="button" class="primary ok" style="display:none" onclick="javascript:repeatSend()" id="dlg-repeat-button" value="' + getLabel('js-dispatch-dialog-repeat') + '" />' +
											'<div style="clear: both;"/>'+
										'</div>' +
									'</div>';
				openDialog('', but_ex, {
					html : sDialog,
					stdButtons : false
				});


				sendMessagesPacket(iDispatchId);
			}

		]]>
		</script>
		<div class="panel-settings" name="g_new-release-messages">
			<summary class="group-tip">
				<xsl:text>Управление неотправленными рассылками.</xsl:text>
			</summary>
			<a data-name="new-release-messages" data-label="&label-new-release-messages;"/>
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle"/>
					<h3>&label-new-release-messages;</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'new-release-messages'" />
					<xsl:with-param name="force-show" select="1" />
				</xsl:call-template>
			</div>
			<div class="col-md-6"/>
			<div class="content">
				<div class="layout">
					<div class="column">
						<div class="row">
							<table class="btable btable-striped">
								<thead>
									<tr>
										<th>&label-message-name;</th>
										<th class="col">&label-edit;</th>
										<th class="col">&label-delete;</th>
									</tr>
								</thead>
								<xsl:apply-templates select="message" mode="form-modify"/>
							</table>
							<div class="pull-right dispatch_messages">
								<xsl:if test="count(../properties/group/field[@name = 'news_relation']/values/item[@selected])">
									<a class="btn color-blue loc-left"
									   onclick="window.location='{$lang-prefix}/admin/dispatches/fill_release/{$param0}/';return false;">
										&label-fill-release;
									</a>
								</xsl:if>
								<a class="btn color-blue loc-left"
								   onclick="javascript:sendRelease('{../@id}');return false;">
									&label-send-release;
								</a>
							</div>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>

			</div>
		</div>
	</xsl:template>

	<xsl:template match="release" mode="form-modify">
		<xsl:if test="count(../properties/group/field[@name = 'news_relation']/values/item[@selected])">
			<div class="panel-settings" name="g_new-release-messages">
				<summary class="group-tip">
					<xsl:text>Управление неотправленными рассылками.</xsl:text>
				</summary>
				<a data-name="new-release-messages" data-label="&label-new-release-messages;"/>
				<div class="title">
					<div class="field-group-toggle">
						<div class="round-toggle"/>
						<h3>&label-new-release-messages;</h3>
					</div>
					<xsl:call-template name="group-tip">
						<xsl:with-param name="group" select="'new-release-messages'" />
						<xsl:with-param name="force-show" select="1" />
					</xsl:call-template>
				</div>
				<div class="content">
					<div class="layout">
						<div class="column">
							<div class="pull-right dispatch_messages">
								<a class="btn color-blue loc-left"
								   onclick="window.location='{$lang-prefix}/admin/dispatches/fill_release/{$param0}/';return false;">
									&label-fill-release;
								</a>
							</div>
						</div>
						<div class="column">
							<xsl:call-template name="entities.tip.content" />
						</div>
					</div>
				</div>
			</div>
		</xsl:if>

	</xsl:template>

	<xsl:template match="message" mode="form-modify">
		<tbody>
			<tr>
				<td>
					<a href="{$lang-prefix}/admin/&sys-module;/edit/{@id}/">
						<xsl:value-of select="@name"/>
					</a>
				</td>
				<td class="center">
					<a href="{$lang-prefix}/admin/&sys-module;/edit/{@id}/">
						<i class="small-ico i-edit"></i>
					</a>
				</td>
				<td class="center">
					<a href="{$lang-prefix}/admin/&sys-module;/del/{@id}/?csrf={/result/@csrf}">
						<i class="small-ico i-remove"></i>
					</a>
				</td>
			</tr>
		</tbody>
	</xsl:template>

	<xsl:template match="field[
		@name = 'uid' or
		@name = 'subscribe_date' or
		@name = 'release_reference' or
		@name = 'disp_last_release' or
		@name = 'forced_subscribers' or
		@name = 'new_relation' or
		@name = 'is_active'
	]" mode="form-modify"/>

	<!-- Шаблон поля рассылки "Связано с лентой новостей" -->
	<xsl:template match="field[@type = 'relation' and @name = 'news_relation']" mode="form-modify">
		<div class="col-md-6 clearfix default-empty-validation" id="{generate-id()}" umi:type="{@type-id}">
			<xsl:if test="not(@required = 'required')">
				<xsl:attribute name="umi:empty">
					<xsl:text>empty</xsl:text>
				</xsl:attribute>
			</xsl:if>

			<div class="title-edit">
				<span class="label">
					<acronym title="{@tip}">
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</span>
			</div>

			<xsl:variable name="rubric.list" select="document('udata://dispatches/getNewsRubricList/')/udata"/>

			<div class="layout-row-icon">
				<div class="layout-col-control selectize-container">
					<select class="default newselect" autocomplete="off" name="{@input_name}">
						<xsl:apply-templates select="." mode="required_attr" />

						<xsl:if test="not(values/item/@selected)">
							<option value=""/>
						</xsl:if>

						<xsl:apply-templates select="$rubric.list/item" mode="news-relation">
							<xsl:with-param name="selected" select="values/item[@selected]/@id"/>
						</xsl:apply-templates>
					</select>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон элемента списка лент новостей -->
	<xsl:template match="item" mode="news-relation">
		<xsl:param name="selected">0</xsl:param>
		<option value="{@id}">
			<xsl:if test="@id = $selected">
				<xsl:attribute name="selected">
					<xsl:text>selected</xsl:text>
				</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="." />
		</option>
	</xsl:template>

</xsl:stylesheet>
