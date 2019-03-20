<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet
		version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:umi="http://www.umi-cms.ru/TR/umi"
		xmlns:php="http://php.net/xsl">

	<xsl:param name="expandextendedfields" />
		
	<xsl:template match="data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<xsl:variable name="pid" select="/result/data/page/@id" />
		<xsl:variable name="oid" select="/result/data/object/@id" />
		<xsl:variable name="link" select="document(concat('upage://', $pid))//@link" />
		<xsl:variable name="isPage" select="not(not(/result/data/page))" />

		<xsl:variable name="value">
			<xsl:choose>
				<xsl:when test="$isPage">
					<xsl:value-of select="/result/data/page/@type-id" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="/result/data/object/@type-id" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="editing-functions-wrapper">
			<div class="slider_container">
				<div class="left_button"></div>
				<div class="right_button"></div>
				<div class="container_tabs">
					<div class="tabs editing"></div>
				</div>
			</div>

			<div class="toolbar clearfix">
				<xsl:apply-templates select="." mode="form-modify-toolbar-buttons">
					<xsl:with-param name="isPage">
						<xsl:value-of select="$isPage"/>
					</xsl:with-param>
					<xsl:with-param name="pid">
						<xsl:value-of select="$pid"/>
					</xsl:with-param>
					<xsl:with-param name="value">
						<xsl:value-of select="$value"/>
					</xsl:with-param>
					<xsl:with-param name="link">
						<xsl:value-of select="$link" />
					</xsl:with-param>
				</xsl:apply-templates>

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
					remove_confurme();
					});

					/*$(window).bind('scroll',function (){
					var editingtollbar = $('div.toolbar'),
					parent = editingtollbar.parent(),
					top = $(window).scrollTop();

					if (top > parent.position().top+60) {
					editingtollbar.addClass('fixed');
					} else {
					editingtollbar.removeClass('fixed');
					}
					});*/

					});

					function remove_confurme(){
						if (del_func_name === null) {
							return false;
						}

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
									success: function (data) {
										closeDialog(popupName);
										window.location = '/admin/'+curent_module+'/';
									},
									error: function(data) {
										closeDialog(popupName);
										var message = getLabel('js-server_error');

										if (data.status === 403) {
											var messageNode = $('error', data.responseXML);

											if (messageNode) {
												message = messageNode.text();
											}
										}

										alert(message);
									},
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
				<form class="form_modify" data-type-id="{$value}" method="post" action="do/" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer" />
					<input type="hidden" name="domain" value="{$domain-floated}" />
					<input type="hidden" name="permissions-sent" value="1" />

					<script type="text/javascript">
						//Проверка
						var treeLink = function(key, value){
						var settings = SettingsStore.getInstance();

						return settings.set(key, value, 'expanded');
						}
					</script>

					<xsl:apply-templates mode="form-modify" />
					<xsl:apply-templates select="page" mode="permissions" />

					<xsl:if test="@action = 'modify' and count(page) = 1">
						<xsl:apply-templates select="document(concat('udata://backup/backup_panel/', page/@id))/udata" />
					</xsl:if>

					<div class="row">
						<xsl:choose>
							<xsl:when test="$data-action = 'create'">
								<xsl:call-template name="std-form-buttons-add" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:call-template name="std-form-buttons" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</form>

				<script type="text/javascript">
					var method = '<xsl:value-of select="/result/@method" />';
					$('.form_modify').find('.select').each(function(){
					var current = $(this);
					buildSelect(current);
					});
				</script>
			</div>
		</div>

		<xsl:call-template name="error-checker">
			<xsl:with-param name="launch" select="1" />
		</xsl:call-template>
	</xsl:template>

	<!-- Шаблон кнопок туллбара формы редактирования и создания сущностей -->
	<xsl:template match="data[@type = 'form' and (@action = 'modify' or @action = 'create')]" mode="form-modify-toolbar-buttons">
		<xsl:param name="isPage"/>
		<xsl:param name="value"/>
		<xsl:param name="link"/>
		<xsl:param name="pid"/>

		<xsl:if test="$isPage = 'true'">
			<xsl:call-template name="std-form-active-control">
				<xsl:with-param name="value" select="/result/data/page/@active = 'active'" />
			</xsl:call-template>

			<xsl:apply-templates select="document(concat('upage://',/result/data/page/@parentId))" mode="add-button">
				<xsl:with-param name="module_name" select="/result/@module" />
			</xsl:apply-templates>

			<a href="{$link}" target="_blank" class="go_view icon-action" title="&label-view-on-site;">
				<i class="small-ico i-see"></i>
			</a>
		</xsl:if>

		<a href="javascript:void(0);" class="icon-action extended_fields_expander"
		   data-expand-text="&js-fields-expand;" data-collapse-text="&js-fields-collapse;">
		   
			<xsl:attribute name="title">
				<xsl:choose>
					<xsl:when test="$expandextendedfields = 1">
						<xsl:text>&js-fields-collapse;</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>&js-fields-expand;</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
		   
			<i class="small-ico i-slideup"></i>
		</a>

		<a href="/admin/data/type_edit/{$value}" class="icon-action" id="edit" title="&label-edit-type;">
			<i class="small-ico i-edit"></i>
		</a>

		<xsl:if test="not(/result/data/object/@type-guid = 'emarket-order') and not(/result/data/@action = 'create')">
			<a id="remove-object" title="&label-delete;" class="icon-action">
				<i class="small-ico i-remove"></i>
			</a>
		</xsl:if>

		<xsl:if test="$isPage = 'true'">
			<script src="/styles/skins/modern/design/js/initYandexShare.js?{$system-build}" />

			<div class="pull-right">
				<span id="ya_share1" style="display:none;"></span>
				<a class="icon-action pull-right" id="share-toggler">
					<i class="small-ico i-share"></i>
				</a>
			</div>
		</xsl:if>
	</xsl:template>

	<xsl:template match="dataset" mode="edit-toolbar">
		var del_method_name = '<xsl:value-of select="dataset/methods/method[title='Удалить']" />';
	</xsl:template>

	<xsl:template match="udata" mode="add-button">
		<xsl:param name="module_name" />
		<xsl:apply-templates
				select="document(concat('udata://core/getEditLinkWrapper/', $module_name, '/', page/@id, '/', page/basetype/@method))/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'core'][@method='getEditLinkWrapper']">
		<xsl:if test="string-length(item/@add) > 0">
			<a href="{item/@add}" title="&label-add;" class="icon-action">
				<i class="small-ico i-add"></i>
			</a>
		</xsl:if>
	</xsl:template>

	<xsl:template match="page|object" mode="form-modify">
		<xsl:apply-templates select="properties/group" mode="form-modify" />
	</xsl:template>

	<xsl:template match="page[count(properties/group) = 0]|object[count(properties/group) = 0]" mode="form-modify">
		<xsl:param name="show-name">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="show-type">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="group-title">
			<xsl:text>&label-group-common;</xsl:text>
		</xsl:param>

		<div class="panel-settings" name="g_{@name}">
			<a data-name="{@name}" data-label="{$group-title}"/>
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle"/>
					<h3>
						<xsl:value-of select="$group-title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="@name" />
				</xsl:call-template>
			</div>
			<div class="content">
				<div class="layout">
					<div class="column">
						<xsl:if test="$show-name = '1'">
							<xsl:call-template name="std-form-name">
								<xsl:with-param name="value" select="@name" />
								<xsl:with-param name="show-tip">
									<xsl:text>0</xsl:text>
								</xsl:with-param>
							</xsl:call-template>
						</xsl:if>

						<xsl:choose>
							<xsl:when test="$show-type = '1'">
								<xsl:call-template name="std-form-data-type">
									<xsl:with-param name="typeId" select="@type-id" />
									<xsl:with-param name="domainId" select="@domain-id" />
								</xsl:call-template>
							</xsl:when>
							<xsl:otherwise>
								<input type="hidden" name="type-id" value="{@type-id}" />
							</xsl:otherwise>
						</xsl:choose>
					</div>

					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Выводит кнопку "Подсказка" для текущей группы полей или для группы с именем $group -->
	<!-- @param group название группы полей-->
	<xsl:template name="group-tip">
		<xsl:param name="group" />
		<xsl:param name="force-show" select="0" />
		<xsl:param name="isHidden" select="false()"/>

		<xsl:choose>
			<xsl:when test="$force-show">
				<xsl:call-template name="group-tip-button">
					<xsl:with-param name="isHidden" select="$isHidden" />
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="$group and .//group[@name = $group]/tip">
				<xsl:call-template name="group-tip-button">
					<xsl:with-param name="isHidden" select="$isHidden" />
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="./tip">
				<xsl:call-template name="group-tip-button">
					<xsl:with-param name="isHidden" select="$isHidden" />
				</xsl:call-template>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон кнопки "Подсказка" -->
	<xsl:template name="group-tip-button">
		<xsl:param name="isHidden" select="false()"/>
		<a class="btn-action group-tip-show">
			<xsl:if test="$isHidden">
				<xsl:attribute name="style">
					<xsl:text>display:none;</xsl:text>
				</xsl:attribute>
			</xsl:if>
			<i class="small-ico i-info"/>
			<xsl:text>&type-edit-tip;</xsl:text>
		</a>
	</xsl:template>

	<!-- Выводит подсказку для поля -->
	<!-- @param tip подсказка для поля -->
	<!-- @param class добавляемый класс -->
	<xsl:template name="field-tip">
		<xsl:param name="tip" />
		<xsl:param name="class" select="''" />

		<xsl:variable name="title">
			<xsl:choose>
				<xsl:when test="$tip">
					<xsl:value-of select="$tip" />
				</xsl:when>
				<xsl:when test="@tip">
					<xsl:value-of select="@tip" />
				</xsl:when>
			</xsl:choose>
		</xsl:variable>

		<xsl:if test="$title">
			<xsl:attribute name="class">
				<xsl:value-of select="$class" /><xsl:text> tip</xsl:text>
			</xsl:attribute>
			<xsl:attribute name="title">
				<xsl:value-of select="$title" />
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон группы полей по умолчанию -->
	<xsl:template match="properties/group" mode="form-modify">
		<xsl:param name="show-name">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="show-type">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:apply-templates select="." mode="form-modify-group">
			<xsl:with-param name="show-name">
				<xsl:value-of select="$show-name"/>
			</xsl:with-param>
			<xsl:with-param name="show-type">
				<xsl:value-of select="$show-type"/>
			</xsl:with-param>
		</xsl:apply-templates>
	</xsl:template>

	<!-- Шаблон группы полей "Торговые предложения" -->
	<xsl:template match="properties/group[@name = 'trade_offers']" mode="form-modify">
		<xsl:param name="show-name">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="show-type">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:if test="$data-action = 'modify'">
			<xsl:apply-templates select="." mode="form-modify-group">
				<xsl:with-param name="show-name">
					<xsl:value-of select="$show-name"/>
				</xsl:with-param>
				<xsl:with-param name="show-type">
					<xsl:value-of select="$show-type"/>
				</xsl:with-param>
			</xsl:apply-templates>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон группы полей объекта или страницы -->
	<xsl:template match="properties/group" mode="form-modify-group">
		<xsl:param name="show-name">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="show-type">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
		<div name="g_{@name}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden and @name = 'more_params'">
						<xsl:text>panel-settings has-border extended_fields</xsl:text>
					</xsl:when>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:when test="@name = 'more_params'">
						<xsl:text>panel-settings extended_fields</xsl:text>
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
								<xsl:with-param name="show-name" select="$show-name" />
								<xsl:with-param name="show-type" select="$show-type" />
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

	<xsl:template match="properties/group[@name = 'more_params']" mode="form-modify-more-params">
		<xsl:param name="show-name">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="show-type">
			<xsl:text>1</xsl:text>
		</xsl:param>

		<div class="panel-settings extended_fields" name="g_{@name}">
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle"></div>
					<h3>
						<xsl:value-of select="@title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip" />
			</div>
			<div class="content">
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:apply-templates select="." mode="form-modify-group-fields">
								<xsl:with-param name="show-name" select="$show-name" />
								<xsl:with-param name="show-type" select="$show-type" />
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

	<xsl:template match="group" mode="form-modify-group-fields">
		<xsl:apply-templates select="field" mode="form-modify" />
	</xsl:template>

	<xsl:template match="group[position() = 1 and count(../../basetype)]" mode="form-modify-group-fields">
		<xsl:param name="show-name">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="show-type">
			<xsl:text>1</xsl:text>
		</xsl:param>

		<xsl:variable name="pid" select="/result/data/page/@id" />

		<xsl:if test="$show-name = '1'">
			<xsl:call-template name="std-form-is-active">
				<xsl:with-param name="value" select="../../@active = 'active'" />
			</xsl:call-template>
			<xsl:call-template name="std-form-name">
				<xsl:with-param name="value" select="../../name" />
			</xsl:call-template>
			<xsl:call-template name="std-form-alt-name">
				<xsl:with-param name="value" select="../../@alt-name" />
			</xsl:call-template>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="$show-type = '1'">
				<xsl:call-template name="std-form-data-type">
					<xsl:with-param name="typeId" select="../../@type-id" />
					<xsl:with-param name="domainId" select="../../@domain-id" />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<input type="hidden" name="type-id" value="{../../@type-id}" />
			</xsl:otherwise>
		</xsl:choose>

		<xsl:apply-templates select="field[not(@type='wysiwyg' or @type='text')]" mode="form-modify" />

		<xsl:if test="/result/@module = 'catalog'">
			<xsl:call-template name="changePath">
				<xsl:with-param name="pid" select="$pid" />
			</xsl:call-template>
			<xsl:call-template name="virtuals">
				<xsl:with-param name="pid" select="$pid" />
			</xsl:call-template>
		</xsl:if>

		<xsl:apply-templates select="field[@type='wysiwyg' or @type='text']" mode="form-modify" />
	</xsl:template>

	<xsl:template name="changePath">
		<xsl:param name="type" select="'catalog-category'" />

		<xsl:if test="../../.././@action != 'create'">
			<div class="field changeParent col-md-6" umi:type="{$type},content-page" id="{generate-id(//copies/copy)}">
				<div class="title-edit">
					<acronym>
						<xsl:attribute name="title">
							<xsl:text>&tip-change-parent;</xsl:text>
						</xsl:attribute>
						<xsl:attribute name="class">
							<xsl:text>acr</xsl:text>
						</xsl:attribute>
						<xsl:text>&label-move-to;</xsl:text>
					</acronym>
				</div>

				<span id="changeParentInput{generate-id(//copies/copy)}">
					<ul class="items-list">
						<xsl:apply-templates select="//copies/copy[position()=1]" mode="symlink" />
					</ul>
				</span>
			</div>
		</xsl:if>
	</xsl:template>

	<xsl:template name="virtuals">
		<xsl:param name="type" select="'catalog-category'" />

		<xsl:if test="../../.././@action != 'create'">
			<div class="field changeParent virtuals col-md-6" id="{generate-id()}" umi:type="{$type},content-page">
				<div class="title-edit">
					<acronym>
						<xsl:attribute name="title">
							<xsl:text>&tip-virtuals;</xsl:text>
						</xsl:attribute>
						<xsl:attribute name="class">
							<xsl:text>acr</xsl:text>
						</xsl:attribute>
						<xsl:text>&label-virtual-copies;</xsl:text>
					</acronym>
				</div>

				<span id="changeParentInput{generate-id()}">
					<ul class="items-list">
						<xsl:apply-templates select="//copies/copy[position()!=1]" mode="virtual.symlink" />
					</ul>
				</span>
			</div>
		</xsl:if>
	</xsl:template>

	<xsl:template match="copies/copy" mode="virtual.symlink">
		<li umi:id="{@id}" umi:module="{./basetype/@module}" umi:method="{./basetype/@method}" umi:href="{@link}">
			<i class="small-ico i-remove virtual-copy-delete" />
			<span>
				<xsl:apply-templates select="parents/item" mode="symlink" />
				<xsl:choose>
					<xsl:when test="//page/@id != @id">
						<a>
							<xsl:attribute name="href">
								<xsl:value-of select="@edit-link" />
							</xsl:attribute>
							<xsl:attribute name="title">
								<xsl:value-of select="@url" />
							</xsl:attribute>
							<xsl:value-of select="@name" />
						</a>
					</xsl:when>
					<xsl:otherwise>
						<span>
							<xsl:attribute name="title">
								<xsl:value-of select="@url" />
							</xsl:attribute>
							<xsl:value-of select="@name" />
						</span>
					</xsl:otherwise>
				</xsl:choose>
			</span>
		</li>
	</xsl:template>

	<xsl:template match="copies/copy" mode="symlink">
		<li umi:id="{@id}" umi:module="{./basetype/@module}" umi:method="{./basetype/@method}" umi:href="{@link}">
			<span>
				<xsl:apply-templates select="parents/item" mode="symlink" />
				<xsl:choose>
					<xsl:when test="//page/@id != @id">
						<a>
							<xsl:attribute name="href">
								<xsl:value-of select="@edit-link" />
							</xsl:attribute>
							<xsl:attribute name="title">
								<xsl:value-of select="@url" />
							</xsl:attribute>
							<xsl:value-of select="@name" />
						</a>
					</xsl:when>
					<xsl:otherwise>
						<span>
							<xsl:attribute name="title">
								<xsl:value-of select="@url" />
							</xsl:attribute>
							<xsl:value-of select="@name" />
						</span>
					</xsl:otherwise>
				</xsl:choose>
			</span>
		</li>
	</xsl:template>

	<xsl:template match="parents/item" mode="symlink">
		<a href="/admin/{@module}/{@method}/" target="_blank" class="tree_link"
			 onclick="javascript:return treeLink('{@settingsKey}', '{@treeLink}');" title="{@url}">
			<xsl:value-of select="./@name" />
		</a>
		<xsl:text>&nbsp;/&nbsp;</xsl:text>
	</xsl:template>

	<xsl:template match="group[position() = 1 and count(../../basetype) = 0]" mode="form-modify-group-fields">
		<xsl:param name="show-name">
			<xsl:text>1</xsl:text>
		</xsl:param>
		<xsl:param name="show-type">
			<xsl:text>1</xsl:text>
		</xsl:param>

		<xsl:if test="$show-name = '1'">
			<xsl:call-template name="std-form-name">
				<xsl:with-param name="value" select="../../@name" />
				<xsl:with-param name="show-tip">
					<xsl:text>0</xsl:text>
				</xsl:with-param>
			</xsl:call-template>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="$show-type = '1'">
				<xsl:call-template name="std-form-data-type">
					<xsl:with-param name="typeId" select="../../@type-id" />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<input type="hidden" name="type-id" value="{../../@type-id}" />
			</xsl:otherwise>
		</xsl:choose>

		<xsl:apply-templates select="field[not(@type='tags' or @type='wysiwyg' or @type='text')]" mode="form-modify" />
		<xsl:apply-templates select="field[@type='tags' or @type='wysiwyg' or @type='text']" mode="form-modify" />
	</xsl:template>

	<xsl:template match="group[@name = 'more_params']" mode="form-modify-group-fields">
		<xsl:apply-templates select="../group[@name = 'menu_view']/field" mode="form-modify" />

		<xsl:call-template name="std-form-template-id">
			<xsl:with-param name="value" select="../../@tpl-id" />
		</xsl:call-template>

		<div style="clear: left;" />

		<xsl:call-template name="std-form-is-visible">
			<xsl:with-param name="value" select="../../@visible = 'visible'" />
		</xsl:call-template>

		<xsl:call-template name="std-form-is-default">
			<xsl:with-param name="value" select="../../@default = 'default'" />
		</xsl:call-template>

		<xsl:apply-templates select="field" mode="form-modify" />
	</xsl:template>

	<!-- Шаблон списка прав доступа к странице -->
	<xsl:template match="page" mode="permissions">

		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'permissions')"/>

		<div name="g_permissions">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border extended_fields</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings extended_fields</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>

			<summary class="group-tip">
				<xsl:text>Настройка прав доступа пользователей к данной странице (на дочерние страницы права не распространяются).</xsl:text>
			</summary>

			<a data-name="permissions" data-label="&permissions-panel;"/>

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
						<xsl:text>&permissions-panel;</xsl:text>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'permissions'" />
					<xsl:with-param name="force-show" select="1" />
					<xsl:with-param name="isHidden" select="$groupIsHidden" />
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
							<xsl:call-template name="std-page-permissions">
								<xsl:with-param name="page-id" select="@id" />
							</xsl:call-template>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group[@name = 'menu_view']" mode="form-modify" />

	<xsl:template
			match="field[@type = 'string' or @type = 'int' or @type = 'price' or @type = 'float' or @type = 'counter']"
			mode="form-modify">
		<xsl:param name="class">col-md-6</xsl:param>
		<div class="{$class} default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>
			<span>
				<input class="default" type="text" name="{@input_name}" value="{.}" id="{generate-id()}">
					<xsl:apply-templates select="@type" mode="number" />
				</input>
			</span>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'link_to_object_type']" mode="form-modify">
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>
			<span>
				<input class="default" type="text" name="{@input_name}" value="{.}" id="{generate-id()}">
					<xsl:apply-templates select="@type" mode="number" />
				</input>
			</span>
			<xsl:apply-templates select="." mode="link-to-guide-items" />
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'link_to_object_type']" mode="link-to-guide-items">
			<xsl:variable name="type" select="document(concat('utype://', .))/udata/type" />

			<xsl:if test="$type/@guide = 'guide' and $type/@public = 'public'">
				<xsl:call-template name="edit-guide-items-link">
					<xsl:with-param name="typeId" select="$type/@id" />
				</xsl:call-template>
			</xsl:if>
	</xsl:template>

	<!-- Шаблон ссылки "Редактировать элементы справочника" -->
	<xsl:template name="edit-guide-items-link">
		<xsl:param name="typeId" />
		<div>
			<a href="{$lang-prefix}/admin/data/guide_items/{$typeId}/" target="_blank">
				<xsl:text>&label-edit-guide-items;</xsl:text>
			</a>
		</div>
	</xsl:template>

	<!-- Шаблон значения поля типа "Ссылка на домен" или "Ссылка на список доменов" -->
	<xsl:template match="field[@type = 'domain_id' or @type = 'domain_id_list']" mode="form-modify">
		<xsl:param name="class">col-md-6</xsl:param>
		<div class="{$class} domain_field default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>
			<div class="layout-row-icon">
				<xsl:apply-templates select="value" mode="field-domain-id"/>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон списка доменов поля типа "Ссылка на домен" или "Ссылка на список доменов" -->
	<xsl:template match="value" mode="field-domain-id">
		<div class="layout-col-control selectize-container">
			<select class="default newselect" autocomplete="off" name="{../@input_name}">
				<xsl:if test="../type/@multiple = 'multiple'">
					<xsl:attribute name="multiple">multiple</xsl:attribute>
					<xsl:attribute name="style">height: 62px;</xsl:attribute>
				</xsl:if>
				<xsl:apply-templates select="." mode="required_attr" />
				<option value=""/>
				<xsl:apply-templates select="domain" mode="field-domain-id"/>
			</select>
		</div>
	</xsl:template>

	<!-- Шаблон варианта значения поля типа "Ссылка на домен" или "Ссылка на список доменов"-->
	<xsl:template match="domain" mode="field-domain-id">
		<option value="{@id}">
			<xsl:value-of select="@decoded-host" />
		</option>
	</xsl:template>

	<!-- Шаблон выбранного варианта значения поля типа "Ссылка на домен" или "Ссылка на список доменов" -->
	<xsl:template match="domain[@selected = '1']" mode="field-domain-id">
		<option value="{@id}" selected="selected">
			<xsl:value-of select="@decoded-host" />
		</option>
	</xsl:template>

	<!-- Для полей, в которых хранятся числовые значения добавляет класс -->
	<xsl:template match="@type" mode="number">
		<xsl:if test="(. = 'int' or . = 'float' or . = 'price' or . = 'counter' or . = 'offer_id') and (../@name != 'domain_id')">
			<xsl:attribute name="class">
				<xsl:text>default number-field</xsl:text>
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

	<xsl:template match="field[@type = 'multiple_image']" mode="form-modify">
		<div class="col-md-6 multiimage" id="{generate-id()}">
			<xsl:attribute name="data-prefix">
				<xsl:value-of select="@input_name" />
			</xsl:attribute>

			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<div class="mimage_wrapper ui-sortable">
				<input type="hidden" name="{@input_name}" value="" />
				<xsl:for-each select="values/value">
					<xsl:if test="@relative-path">
						<div class="multi_image"
								 umi:file="{@relative-path}"
								 umi:alt="{@alt}"
								 umi:title="{@title}"
								 umi:order="{@order}"
								 umi:id="{@relative-path}"
								 id="mifile_{@id}">
						</div>
					</xsl:if>
				</xsl:for-each>
				<div class="emptyfield"/>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'color']" mode="form-modify">
		<div class="field color col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<span>
				<input type="text" name="{@input_name}" value="{.}" id="{generate-id()}" class="default color" />
			</span>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'password']" mode="form-modify">
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<span>
				<input type="password" name="{@input_name}" value="{.}" class="default" id="{generate-id()}">
				</input>
			</span>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'tags']" mode="form-modify">
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<div class="layout-row-icon">
				<div class="layout-col-control">
					<input type="text" class="default tags" name="{@input_name}" value="{.}" id="{generate-id()}">
					</input>
				</div>

				<xsl:if test="count($modules-menu/items/item[@name='stat'])">
					<div class="layout-col-icon">
						<a href="javascript:void('0');" id="link{generate-id()}" class="icon-action tagPicker">
							<img title="&label-tags-cloud;" alt="&label-tags-cloud;" height="13" src="/images/cms/admin/mac/icons/tags.gif" />
						</a>
					</div>
				</xsl:if>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'date']" mode="form-modify">
		<div class="col-md-6 datePicker default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<input class="default" type="text" name="{@input_name}" id="{generate-id()}">
				<xsl:apply-templates select="." mode="required_attr" />
				<xsl:attribute name="value">
					<xsl:choose>
						<xsl:when test="@name='publish_time' and string-length(text()) = 0">
							<xsl:value-of select="document('udata://system/convertDate/now/Y-m-d%20H:i')/udata" />
						</xsl:when>
						<xsl:when test="@name='show_start_date' and @timestamp = 0">
							<xsl:value-of select="document('udata://system/convertDate/now/Y-m-d%20H:i')/udata" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="." />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
			</input>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'text' or @type = 'wysiwyg']" mode="form-modify">
		<div class="col-md-12 wysiwyg-field default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<span>
				<textarea name="{@input_name}" id="{generate-id()}">
					<xsl:apply-templates select="." mode="required_attr">
						<xsl:with-param name="old_class" select="@type" />
					</xsl:apply-templates>
					<xsl:value-of select="." />
				</textarea>
			</span>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'boolean']" mode="form-modify">
		<xsl:if test="preceding-sibling::field/@type != 'boolean'">
			<div style="clear: left;" />
		</xsl:if>

		<div class="col-md-6 title-edit" for="{generate-id()}" style="min-height:30px;">
			<input type="hidden" name="{@input_name}" value="0" />
			<label>
				<div class="checkbox">
					<input type="checkbox" name="{@input_name}" value="1" id="{generate-id()}">
						<xsl:apply-templates select="." mode="required_attr">
							<xsl:with-param name="old_class" select="'checkbox'" />
						</xsl:apply-templates>
						<xsl:if test=". = '1'">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
				</div>

				<span class="label">
					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</span>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'relation']" mode="form-modify">
		<div class="col-md-6 relation clearfix default-empty-validation" id="{generate-id()}" umi:type="{@type-id}">
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
				<span>
				</span>
			</div>

			<div class="layout-row-icon">
				<div class="layout-col-control selectize-container">
					<select autocomplete="off" name="{@input_name}" id="relationSelect{generate-id()}">
						<xsl:apply-templates select="." mode="required_attr" />

						<xsl:if test="@multiple = 'multiple'">
							<xsl:attribute name="multiple">multiple</xsl:attribute>
							<xsl:attribute name="style">height: 62px;</xsl:attribute>
						</xsl:if>

						<xsl:if test="not(values/item/@selected)">
							<option value=""></option>
						</xsl:if>

						<xsl:apply-templates select="values/item" />
					</select>
				</div>

				<xsl:if test="@public-guide = '1'">
					<div class="layout-col-icon">
						<a class="icon-action relation-add">
							<i class="small-ico i-add" title="&js-add-relation-item;"></i>
						</a>
					</div>
				</xsl:if>
			</div>

			<xsl:if test="@public-guide = '1'">
				<xsl:call-template name="edit-guide-items-link">
					<xsl:with-param name="typeId" select="@type-id" />
				</xsl:call-template>
			</xsl:if>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'symlink']" mode="form-modify">
		<div class="col-md-6 symlink" id="{generate-id()}" name="{@input_name}">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<span id="symlinkInput{generate-id()}">
				<ul>
					<xsl:apply-templates select="values/item" mode="symlink" />
				</ul>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон группы полей типа "Торговые предложения" -->
	<xsl:template match="group[@name = 'trade_offers']" mode="form-modify-group-fields">
		<xsl:apply-templates select="field[@name = 'trade_offer_list']" mode="form-modify" />
		<xsl:apply-templates select="field[@name = 'trade_offer']" mode="form-modify" />
	</xsl:template>

	<!-- Шаблон поля типа "Ссылка на список торговых предложений" -->
	<xsl:template match="field[@type = 'offer_id_list']" mode="form-modify">
		<xsl:param name="pid" select="../../../@id" />
		<xsl:if test="$pid">
			<div class="col-md-12" id="{generate-id()}" name="{@input_name}">
				<div class="title-edit">
					<acronym title="{@tip}">
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</div>
				<xsl:call-template name="ui-new-table">
					<xsl:with-param name="controlParam"><xsl:value-of select="@name"/></xsl:with-param>
					<xsl:with-param name="configUrl">
						<xsl:value-of select="concat('/admin/catalog/flushTradeOfferListConfig/', $pid, '/', @name, '.json')" />
					</xsl:with-param>
					<xsl:with-param name="toolbarFunction">CatalogModule.getTradeOfferListToolBarFunctions()</xsl:with-param>
					<xsl:with-param name="toolbarMenu">CatalogModule.getTradeOfferListToolBarMenu()</xsl:with-param>
					<xsl:with-param name="perPageLimit">20</xsl:with-param>
					<xsl:with-param name="dragAllowed">1</xsl:with-param>
					<xsl:with-param name="dropValidator">CatalogModule.getDragAndDropValidator</xsl:with-param>
				</xsl:call-template>
			</div>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон поля типа "Ссылка на торговое предложение" -->
	<xsl:template match="field[@type = 'offer_id']" mode="form-modify">
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>
			<span>
				<input class="default" type="text" name="{@input_name}" value="{values/offer/@id}" id="{generate-id()}" readonly="readonly">
					<xsl:apply-templates select="@type" mode="number" />
				</input>
			</span>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'video_file' or @type = 'swf_file' or @type = 'file']" mode="form-modify">
		<xsl:variable name="filemanager-id"
									select="document(concat('uobject://', /result/@user-id))/udata//property[@name = 'filemanager']/value/item/@id" />
		<xsl:variable name="filemanager">
			<xsl:choose>
				<xsl:when test="not($filemanager-id)">
					<xsl:text>elfinder</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="document(concat('uobject://', $filemanager-id))/udata//property[@name = 'fm_prefix']/value" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="col-md-6 file default-empty-validation" id="{generate-id()}" umi:input-name="{@input_name}"
				 umi:field-type="{@type}"
				 umi:name="{@name}"
				 umi:folder="{@destination-folder}"
				 umi:file="{@relative-path}"
				 umi:folder-hash="{php:function('elfinder_get_hash', string(@destination-folder))}"
				 umi:file-hash="{php:function('elfinder_get_hash', string(@relative-path))}"
				 umi:lang="{/result/@interface-lang}"
				 umi:filemanager="{$filemanager}">

			<label for="fileControlContainer_{generate-id()}">
				<div class="title-edit">
					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</div>
				<span class="layout-row-icon" id="fileControlContainer_{generate-id()}"></span>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'img_file']" mode="form-modify">
		<xsl:variable name="filemanager-id"
									select="document(concat('uobject://',/result/@user-id))/udata//property[@name = 'filemanager']/value/item/@id" />
		<xsl:variable name="filemanager">
			<xsl:choose>
				<xsl:when test="not($filemanager-id)">
					<xsl:text>elfinder</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of
							select="document(concat('uobject://',$filemanager-id))/udata//property[@name = 'fm_prefix']/value" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="col-md-6 img_file default-empty-validation" id="{generate-id()}" umi:input-name="{@input_name}"
				 umi:field-type="{@type}"
				 umi:name="{@name}"
				 umi:alt="{@image_alt}"
				 umi:title="{@image_title}"
				 umi:field-id="{@field_id}"
				 umi:folder="{@destination-folder}"
				 umi:file="{@relative-path}"
				 umi:folder-hash="{php:function('elfinder_get_hash', string(@destination-folder))}"
				 umi:file-hash="{php:function('elfinder_get_hash', string(@relative-path))}"
				 umi:lang="{/result/@interface-lang}"
				 umi:filemanager="{$filemanager}">

			<label for="imageField_{generate-id()}">
				<div class="title-edit">
					<acronym title="{@tip}">
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</div>
				<div class="layout-row-icon" id="imageField_{generate-id()}"></div>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'optioned']" mode="form-modify">
		<div class="col-md-6 optioned" id="{generate-id()}">
			<xsl:call-template name="std-optioned-control" />
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'optioned' and @name = 'stores_state']" mode="form-modify">
		<div class="col-md-6 optioned stores default-empty-validation" id="{generate-id()}">
			<xsl:call-template name="std-optioned-control">
				<xsl:with-param name="type" select="'int'" />
			</xsl:call-template>
		</div>
	</xsl:template>

	<xsl:template match="field/values/item">
		<option value="{@id}">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="field/values/item[@selected = 'selected']">
		<option value="{@id}" selected="selected">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="field/values/item" mode="symlink">
		<li umi:id="{@id}" umi:module="{./basetype/@module}" umi:method="{./basetype/@method}" umi:href="{@link}">
			<xsl:value-of select="./name" />
		</li>
	</xsl:template>

	<xsl:template match="page" mode="symlink">
		<li umi:id="{@id}" umi:module="{./basetype/@module}" umi:method="{./basetype/@method}" umi:href="{@link}">
			<xsl:value-of select="./name" />
		</li>
	</xsl:template>

	<xsl:template match="field" mode="sys-tips" />

	<xsl:template match="field[@tip]" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:value-of select="@tip" />
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'title']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-title;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'meta_keywords']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-keywords;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'meta_descriptions']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-description;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'h1']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-h1;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'publish_time']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-publish-time;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'is_unindexed']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-is-unindexed;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'robots_deny']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-robots-deny;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'is_expanded']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-expanded;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'show_submenu']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-show-submenu;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'menu_pic_a']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-menu_a;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'menu_pic_ua']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-menu_ua;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'header_pic']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-headers;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'tags']" mode="sys-tips">
		<xsl:attribute name="title">
			<xsl:text>&tip-tags;</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:text>acr</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'date_create_object']" mode="form-modify" />

	<xsl:template match="field" mode="required_text" />

	<xsl:template match="field[@required = 'required']" mode="required_text">
		<sup>
			<xsl:text>*</xsl:text>
		</sup>
	</xsl:template>

	<xsl:template match="field" mode="required_attr">
		<xsl:param name="old_class" />
		<xsl:if test="$old_class">
			<xsl:attribute name="class">
				<xsl:value-of select="$old_class" />
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

	<xsl:template match="field[@required = 'required']" mode="required_attr">
		<xsl:param name="old_class" />
		<xsl:attribute name="class">
			<xsl:if test="$old_class">
				<xsl:value-of select="$old_class" />
				<xsl:text> </xsl:text>
			</xsl:if>
			<xsl:text>required</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="field[@name = 'proc']" mode="required_attr">
		<xsl:param name="old_class" />
		<xsl:attribute name="id">
			<xsl:text>sale_borders</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="class">
			<xsl:if test="$old_class">
				<xsl:value-of select="$old_class" />
				<xsl:text> </xsl:text>
			</xsl:if>
			<xsl:text>required</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<!-- Шаблон списка последних изменений страницы -->
	<xsl:template match="udata[@module = 'backup' and @method = 'backup_panel']">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'backup')"/>

		<div name="g_backup">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border extended_fields</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings extended_fields</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>

			<a data-name="backup" data-label="&backup-changelog;"/>
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
						<xsl:text>&backup-changelog;</xsl:text>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'backup'" />
					<xsl:with-param name="isHidden" select="$groupIsHidden" />
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
						<xsl:choose>
							<xsl:when test="count(revision) &gt; 0">
								<table class="tableContent tbl" style="width:100%;">
									<thead>
										<tr>
											<th>
												<xsl:text>&backup-change-number;</xsl:text>
											</th>
											<th>
												<xsl:text>&backup-change-time;</xsl:text>
											</th>
											<th>
												<xsl:text>&backup-change-author;</xsl:text>
											</th>
											<th>
												<xsl:text>&backup-change-rollback;</xsl:text>
											</th>
										</tr>
									</thead>
									<tbody>
										<xsl:apply-templates select="revision" />
									</tbody>
								</table>
							</xsl:when>
							<xsl:otherwise>
								<div class="row">
									<div class="col-md-12" style="text-align:center;">
										<xsl:text>&backup-no-changes-found;</xsl:text>
									</div>
								</div>
							</xsl:otherwise>
						</xsl:choose>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
				<br />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="revision">
		<xsl:variable name="editor-info" select="document(concat('uobject://',@user-id))/udata" />
		<tr>
			<td>
				<xsl:value-of select="position()" />
			</td>

			<td>
				<xsl:value-of select="document(concat('udata://system/convertDate/',@changetime,'/Y-m-d%20%7C%20H:i'))/udata" />
			</td>

			<td>
				<xsl:value-of select="$editor-info//property[@name = 'lname']/value" />
				<xsl:text>&nbsp;</xsl:text>
				<xsl:value-of select="$editor-info//property[@name = 'fname']/value" />
			</td>

			<td class="center">
				<xsl:apply-templates select="." mode="button" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="revision" mode="button">
		<a onclick="window.location = '{link}?referer=' + window.location.pathname;">
			<i class="small-ico i-rollback"></i>
		</a>
	</xsl:template>

	<xsl:template match="revision[@is-void = 1]" mode="button">
		<xsl:text>&backup-entry-is-void;</xsl:text>
	</xsl:template>

	<xsl:template match="revision[@active = 'active']" mode="button" />

	<!-- Шаблон списка полей формы редактирования сущности -->
	<xsl:template match="fields" mode="ndc.fields">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div class="column">
			<div class="row">
				<xsl:apply-templates select="field[@required = 1]" mode="ndc.field">
					<xsl:with-param name="entity.id">
						<xsl:value-of select="$entity.id"/>
					</xsl:with-param>
				</xsl:apply-templates>
			</div>
			<div class="row">
				<xsl:apply-templates select="field[position() mod 2 = 1 and not(@required)]" mode="ndc.field">
					<xsl:with-param name="entity.id">
						<xsl:value-of select="$entity.id"/>
					</xsl:with-param>
				</xsl:apply-templates>
			</div>
			<div class="row">
				<xsl:apply-templates select="field[position() mod 2 = 0 and not(@required)]" mode="ndc.field">
					<xsl:with-param name="entity.id">
						<xsl:value-of select="$entity.id"/>
					</xsl:with-param>
				</xsl:apply-templates>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон поля типа "Строка" формы редактирования сущности -->
	<xsl:template match="field[@type = 'string']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="./@required">
						<xsl:text>col-md-6 default-empty-validation</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>col-md-6</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates select="." mode="ndc.field.title"/>
			<span>
				<input class="default" type="text" name="{concat('data[', $entity.id, '][', ./@name, ']')}"
					   value="{.}" id="{generate-id()}"/>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон поля типа "html" формы редактирования сущности -->
	<xsl:template match="field[@type = 'html']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="./@required">
						<xsl:text>col-md-12 wysiwyg-field default-empty-validation</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>col-md-12 wysiwyg-field</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates select="." mode="ndc.field.title"/>
			<span>
				<textarea class="wysiwyg" name="{concat('data[', $entity.id, '][', ./@name, ']')}" id="{generate-id()}">
					<xsl:value-of select="." />
				</textarea>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон поля типа "Число" формы редактирования сущности -->
	<xsl:template match="field[@type = 'integer']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="./@required">
						<xsl:text>col-md-6 default-empty-validation</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>col-md-6</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates select="." mode="ndc.field.title"/>
			<span>
				<input class="default" type="number" name="{concat('data[', $entity.id, '][', ./@name, ']')}"
					   value="{.}" id="{generate-id()}"/>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон поля типа "Булевое" формы редактирования сущности -->
	<xsl:template match="field[@type = 'bool']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="./@required">
						<xsl:text>col-md-6 default-empty-validation</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>col-md-6</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<label>
				<div class="checkbox">
					<input class="default" type="checkbox"
						   name="{concat('data[', $entity.id, '][', ./@name, ']')}" id="{generate-id()}">
						<xsl:if test=". = 1">
							<xsl:attribute name="checked">
								<xsl:text>checked</xsl:text>
							</xsl:attribute>
						</xsl:if>
					</input>
				</div>
				<span>
					<xsl:apply-templates select="." mode="ndc.field.title"/>
				</span>
			</label>
		</div>
	</xsl:template>

	<!-- Шаблон поля типа "Изображение" формы редактирования сущности -->
	<xsl:template match="field[@type = 'image']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div>
			<div id="{generate-id()}" umi:input-name="{concat('data[', $entity.id, '][', ./@name, ']')}"
				 umi:field-type="umiImageType"
				 umi:name="{./name}"
				 umi:file="{.}"
				 umi:file-hash="{php:function('elfinder_get_hash', string(.))}"
				 umi:lang="{/result/@interface-lang}"
				 umi:filemanager="elfinder">
				<xsl:attribute name="class">
					<xsl:choose>
						<xsl:when test="./@required">
							<xsl:text>col-md-6 img_file default-empty-validation</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>col-md-6 img_file</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
				<label for="imageField_{generate-id()}">
					<xsl:apply-templates select="." mode="ndc.field.title"/>
					<div class="layout-row-icon" id="imageField_{generate-id()}"/>
				</label>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон заголовка поля формы редактирования сущности -->
	<xsl:template match="field" mode="ndc.field.title">
		<div class="title-edit">
			<acronym>
				<xsl:if test="./@hint">
					<xsl:attribute name="title">
						<xsl:value-of select="./@hint" />
					</xsl:attribute>
					<xsl:attribute name="class">
						<xsl:text>acr</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="./@title"/>
			</acronym>
			<xsl:if test="./@required">
				<sup>
					<xsl:text>*</xsl:text>
				</sup>
			</xsl:if>
		</div>
	</xsl:template>

	<xsl:template match="domains/domain" mode="domain_id">
		<xsl:param name="selected.id"/>
		<xsl:if test="$selected.id != @id">
			<option value="{@id}">
				<xsl:value-of select="@decoded-host" />
			</option>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон кнопок сохранения/создания сущности -->
	<xsl:template name="entity.edit.form.buttons">
		<!-- Включен ли режим создания -->
		<xsl:param name="create.mode"/>
		<xsl:choose>
			<xsl:when test="$create.mode = 1">
				<xsl:call-template name="std-form-buttons-add">
					<xsl:with-param name="disable.view.button">1</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="std-form-buttons">
					<xsl:with-param name="disable.view.button">1</xsl:with-param>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон панели инструментов формы редактирования сущности -->
	<xsl:template name="entity.edit.form.toolbar">
		<!-- Включено ли удаление -->
		<xsl:param name="delete.enabled"/>
		<!-- Модуль, который отвечает за удаление-->
		<xsl:param name="delete.module"/>
		<!-- Метод, который отвечает за удаление-->
		<xsl:param name="delete.method"/>
		<!-- Идентификатор удаляемой сущности -->
		<xsl:param name="delete.entity.id"/>
		<!-- Тип удаляемой сущности -->
		<xsl:param name="delete.entity.type"/>

		<script src="/styles/skins/modern/design/js/form.modify.toolbar.js" />

		<div class="editing-functions-wrapper">
			<div class="tabs editing"/>
			<div class="toolbar clearfix">
				<xsl:if test="$delete.enabled = 1">
					<a id="delete-entity" title="&js-edit-form-delete-button-title;" class="icon-action">
						<xsl:attribute name="data-delete-module">
							<xsl:value-of select="$delete.module"/>
						</xsl:attribute>
						<xsl:attribute name="data-delete-method">
							<xsl:value-of select="$delete.method"/>
						</xsl:attribute>
						<xsl:attribute name="data-delete-entity-id">
							<xsl:value-of select="$delete.entity.id"/>
						</xsl:attribute>
						<xsl:attribute name="data-delete-entity-type">
							<xsl:value-of select="$delete.entity.type"/>
						</xsl:attribute>
						<i class="small-ico i-remove"/>
					</a>
				</xsl:if>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон топа -->
	<xsl:template match="top" mode="bar.chart">
		<xsl:call-template name="bar.chart">
			<xsl:with-param name="id" select="@id" />
			<xsl:with-param name="text" select="@label" />
			<xsl:with-param name="datasets" select="dataset" />
		</xsl:call-template>
	</xsl:template>

	<!-- http://www.chartjs.org/samples/latest/charts/bar/vertical.html -->
	<xsl:template name="bar.chart">
		<xsl:param name="id">canvas</xsl:param>
		<xsl:param name="text">simple bar chart</xsl:param>
		<xsl:param name="datasets">[]</xsl:param>
		<script>
			var <xsl:value-of select="$id" />config = {
				type: 'bar',
				data: {
					datasets: [
						<xsl:apply-templates select="$datasets" mode="chart.dataset"/>
					]
				},
				options: {
					responsive: true,
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text:'<xsl:value-of select="$text" />'
					}
				}
			};
		</script>
		<div class="seo_chart">
			<canvas id="{$id}"/>
		</div>
	</xsl:template>

	<!-- http://www.chartjs.org/samples/latest/charts/pie.html -->
	<xsl:template name="pie.chart">
		<xsl:param name="id">canvas</xsl:param>
		<xsl:param name="text">simple pie chart</xsl:param>
		<xsl:param name="datasets">[]</xsl:param>
		<script>
			var <xsl:value-of select="$id" />config = {
				type: 'pie',
				data: {
					labels: [
						<xsl:apply-templates select="$datasets" mode="pie.chart.labelset"/>
					],
					datasets: [{
						data: [
							<xsl:apply-templates select="$datasets" mode="pie.chart.dataset"/>
						],
						backgroundColor: [
							<xsl:apply-templates select="$datasets" mode="pie.chart.colorset"/>
						]
					}]
				},
				options: {
					responsive: true,
					legend: {
						position: 'bottom',
					},
					title: {
						display: true,
						text:'<xsl:value-of select="$text" />'
					}
				}
			};
		</script>
		<div class="seo_chart">
			<canvas id="{$id}" />
		</div>
	</xsl:template>

	<!-- Шаблон заголовка для pie chart -->
	<xsl:template match="dataset" mode="pie.chart.labelset">
		<xsl:call-template name="chart.value">
			<xsl:with-param name="value" select="@label"/>
		</xsl:call-template>
	</xsl:template>

	<!-- Шаблон значения для pie chart -->
	<xsl:template match="dataset" mode="pie.chart.dataset">
		<xsl:call-template name="chart.value">
			<xsl:with-param name="value" select="@value"/>
		</xsl:call-template>
	</xsl:template>

	<!-- Шаблон цвета для pie chart -->
	<xsl:template match="dataset" mode="pie.chart.colorset">
		<xsl:call-template name="chart.value">
			<xsl:with-param name="value" select="@color"/>
		</xsl:call-template>
	</xsl:template>

	<!-- Шаблон значения из списка для pie chart -->
	<xsl:template name="chart.value">
		<xsl:param name="value"/>
		'<xsl:value-of select="$value" />',
	</xsl:template>

	<!-- Шаблон истории -->
	<xsl:template match="history" mode="line.chart">
		<xsl:call-template name="line.chart">
			<xsl:with-param name="id" select="@id" />
			<xsl:with-param name="text" select="@label" />
			<xsl:with-param name="labels" select="dataset[1]/date_list" />
			<xsl:with-param name="datasets" select="dataset" />
			<xsl:with-param name="xLabel" select="@x-label" />
			<xsl:with-param name="yLabel" select="@y-label" />
		</xsl:call-template>
	</xsl:template>

	<!-- http://www.chartjs.org/samples/latest/charts/line/basic.html -->
	<xsl:template name="line.chart">
		<xsl:param name="id">canvas</xsl:param>
		<xsl:param name="text">simple line chart</xsl:param>
		<xsl:param name="labels">[]</xsl:param>
		<xsl:param name="datasets">[]</xsl:param>
		<xsl:param name="xLabel">date</xsl:param>
		<xsl:param name="yLabel">value</xsl:param>
		<script>
			var <xsl:value-of select="$id" />config = {
				type: 'line',
				data: {
					labels: <xsl:value-of select="$labels" />,
					datasets: [
						<xsl:apply-templates select="$datasets" mode="chart.dataset"/>
					]
				},
				options: {
					responsive: true,
					title:{
						display:true,
						text:'<xsl:value-of select="$text" />'
					},
					tooltips: {
						mode: 'index',
						intersect: false,
					},
					hover: {
						mode: 'nearest',
						intersect: true
					},
					scales: {
						xAxes: [{
							display: true,
							scaleLabel: {
							display: true,
								labelString: '<xsl:value-of select="$xLabel"/>'
							}
						}],
						yAxes: [{
							display: true,
							scaleLabel: {
							display: true,
								labelString: '<xsl:value-of select="$yLabel"/>'
							}
						}]
					}
				}
			};
		</script>
		<div class="seo_chart">
			<canvas id="{$id}"/>
		</div>
	</xsl:template>

	<!-- Шаблон непоследнего набора данных линейной диаграммы -->
	<xsl:template match="dataset[position() != last()]" mode="chart.dataset">
		<xsl:call-template name="dataset">
			<xsl:with-param name="label" select="@label"/>
			<xsl:with-param name="color" select="@color"/>
			<xsl:with-param name="data" select="value_list"/>
		</xsl:call-template>,
	</xsl:template>

	<!-- Шаблон последнего набора данных линейной диаграммы -->
	<xsl:template match="dataset[position() = last()]" mode="chart.dataset">
		<xsl:call-template name="dataset">
			<xsl:with-param name="label" select="@label"/>
			<xsl:with-param name="color" select="@color"/>
			<xsl:with-param name="data" select="value_list"/>
		</xsl:call-template>
	</xsl:template>

	<!-- Шаблон набора данных линейной диаграммы -->
	<xsl:template name="dataset" >
		<xsl:param name="label"/>
		<xsl:param name="color"/>
		<xsl:param name="data"/>
		{
			label: "<xsl:value-of select="$label" />",
			backgroundColor: '<xsl:value-of select="$color" />',
			borderColor: '<xsl:value-of select="$color" />',
			data: <xsl:value-of select="$data" />,
			fill: false,
		}
	</xsl:template>

	<!-- Шаблон кнопки вызова подсказки -->
	<xsl:template name="entities.help.button">
		<a class="btn-action loc-right infoblock-show">
			<i class="small-ico i-info"/>
			<xsl:text>&help;</xsl:text>
		</a>
	</xsl:template>

	<!-- Шаблон контента подсказки -->
	<xsl:template name="entities.help.content">
		<div class="infoblock">
			<h3>
				<xsl:text>&label-quick-help;</xsl:text>
			</h3>
			<div class="content" title="{$context-manul-url}"/>
			<div class="infoblock-hide"/>
		</div>
	</xsl:template>

	<!-- Шаблон контента совета -->
	<xsl:template name="entities.tip.content">
		<div class="infoblock">
			<h3>
				<xsl:text>&type-edit-tip;</xsl:text>
			</h3>
			<div class="content">
			</div>
			<div class="group-tip-hide"></div>
		</div>
	</xsl:template>

	<!-- Флаг игнорирования блокировки изменений -->
	<xsl:param name="skip-lock"/>

	<!-- Шаблон контрола списка групп полей -->
	<xsl:template match="fieldgroups" mode="fieldsgroups-other" >
		<script src="/styles/skins/modern/design/js/type.control.js?{$system-build}"/>
		<div id="group-fields">
			<div id="groupsContainer" class="content">
				<div class="buttons row">
					<div class="col-md-12">
						<a href='#' class='add_group btn color-blue'>
							<xsl:text>&type-edit-add_group;</xsl:text>
						</a>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<xsl:apply-templates select="group" mode="fieldsgroups-tpl"/>
					</div>
				</div>
			</div>
			<script type="text/javascript">
				var modernCurentType = <xsl:value-of select="//type/@id" />; // обратная совместимость
				var currentTypeId = <xsl:value-of select="//type/@id" />;
				modernTypeController.init(currentTypeId,{
					<xsl:apply-templates select="group[not(@locked)]" mode="groupsmodel-tpl"/>
				});
			</script>
		</div>
		<script id="field-line-view" type="text/template">
			<![CDATA[
				<li class="f_container<%= (field.visible == 'visible' ? '' : ' finvisible') %>" umifieldid="<%= field.id %>">
					<div class="row">
						<div class="view col-md-12">
							<span id="headf<%= field.id %>title" class="col-md-3 field-title" title="<%= field.title %>">
								<%= field.title %>
								<%= field.required == "required" ? " *" : "" %>
							</span>
							<span id="headf<%= field.id %>name" class="col-md-3 field-name" title="<%= field.name %>">
								[<%= field.name %>]
							</span>
							<% var typeName = (field.type ? field.type.name : ''); %>
							<span id="headf<%= field.id %>type" class="col-md-4 field-type" title="<%= typeName %>">
								(<%= typeName %>)
							</span>
							<span id="f<%= field.id %>control" class="pull-right">
								<a class="fedit" data="<%= field.id %>" title="<%= getLabel('label-edit') %>">
									<i class="small-ico i-edit"></i>
								</a>
								<a class="fremove" data="<%= field.id %>" title="<%= getLabel('label-delete') %>">
									<i class="small-ico i-remove"></i>
								</a>
							</span>
						</div>
					</div>
				</li>
			 ]]>
		</script>
	</xsl:template>

	<!-- Шаблон отображения группы полей внутри типа данных -->
	<xsl:template match="group" mode="fieldsgroups-tpl">
		<div umigroupid="{@id}">
			<xsl:attribute name="class">
				<xsl:text>fg_container</xsl:text>
				<xsl:if test="@locked = 'locked' and not($skip-lock = 1)">
					<xsl:text> locked</xsl:text>
				</xsl:if>
				<xsl:if test="not(@visible = 'visible')">
					<xsl:text> finvisible</xsl:text>
				</xsl:if>
			</xsl:attribute>
			<div class="fg_container_header">
				<span id="headg{@id}title" class="left">
					<xsl:value-of select="@title" /> [<xsl:value-of select="@name" />]
				</span>
				<span id="g{@id}control">
					<xsl:if test="not(@locked = 'locked'  and not($skip-lock))">
						<a class="gedit" data="{@id}"  title="&label-edit;">
							<i class="small-ico i-edit"/>
						</a>
						<a class="gremove" data="{@id}" title="&label-delete;">
							<i class="small-ico i-remove"/>
						</a>
					</xsl:if>
				</span>
			</div>
			<div class="fg_container_body content">
				<ul class="fg_container ui-sortable" umigroupid="{@id}">
					<xsl:if test="not(@locked = 'locked') or $skip-lock">
						<div class="buttons">
							<a data="{@id}" class='fadd btn color-blue'>
								<xsl:text>&type-edit-add_field;</xsl:text>
							</a>
						</div>
					</xsl:if>
					<xsl:apply-templates select="field" mode="field-tpl" />
				</ul>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон группы полей для добавления его данных в контрол управления типа данных -->
	<xsl:template match="group" mode="groupsmodel-tpl">
		<xsl:variable name="tip">
			<xsl:call-template name="string-replace-all">
				<xsl:with-param name="text" select="tip" />
				<xsl:with-param name="replace" select="'&quot;'" />
				<xsl:with-param name="by" select="'\&quot;'" />
			</xsl:call-template>
		</xsl:variable>

		"<xsl:value-of select="@id"/>": {
			"id": <xsl:value-of select="@id"/>,
			"title": "<xsl:value-of select="php:function('htmlspecialchars', string(./@title))" />",
			"name": "<xsl:value-of select="@name" />",

			"visible": "<xsl:choose>
							<xsl:when test="@visible = 'visible'">true</xsl:when>
							<xsl:otherwise>false</xsl:otherwise>
						</xsl:choose>"
		}
		<xsl:if test="not(position() = last())">,</xsl:if>
	</xsl:template>

	<!-- Шаблон для для замены символов в строке -->
	<xsl:template name="string-replace-all">
		<xsl:param name="text" />
		<xsl:param name="replace" />
		<xsl:param name="by" />
		<xsl:choose>
			<xsl:when test="$text = '' or $replace = '' or not($replace)" >
				<!-- Prevent this routine from hanging -->
				<xsl:value-of select="$text" />
			</xsl:when>
			<xsl:when test="contains($text, $replace)">
				<xsl:value-of select="substring-before($text,$replace)" />
				<xsl:value-of select="$by" />
				<xsl:call-template name="string-replace-all">
					<xsl:with-param name="text" select="substring-after($text,$replace)" />
					<xsl:with-param name="replace" select="$replace" />
					<xsl:with-param name="by" select="$by" />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон отображения поля внутри группы полей -->
	<xsl:template match="field" mode="field-tpl">
		<li umifieldid="{@id}">
			<xsl:attribute name="class">
				<xsl:text>f_container</xsl:text>
				<xsl:if test="not(@visible = 'visible')">
					<xsl:text> finvisible</xsl:text>
				</xsl:if>
				<xsl:if test="@locked = 'locked'">
					<xsl:text> locked</xsl:text>
				</xsl:if>
			</xsl:attribute>
			<div class="row">
				<div class="view col-md-12">
					<span id="headf{@id}title" class="col-md-3 field-title" title="{@title}">
						<xsl:value-of select="@title"/>
						<xsl:if test="@required = 'required'"> *</xsl:if>
					</span>
					<span id="headf{@id}name" class="col-md-3 field-name" title="{@name}">
						[<xsl:value-of select="@name"/>]
					</span>
					<span id="headf{@id}type" class="col-md-4 field-type" title="{./type/@name}">
						(<xsl:value-of select="./type/@name"/>)
					</span>
					<span id="f{@id}control" class="pull-right">
						<xsl:if test="not(@locked = 'locked' and not($skip-lock))">
							<a class="fedit" data="{@id}" title="&label-edit;">
								<i class="small-ico i-edit"/>
							</a>
							<a class="fremove" data="{@id}" title="&label-delete;">
								<i class="small-ico i-remove"/>
							</a>
						</xsl:if>
					</span>
				</div>
			</div>
		</li>
	</xsl:template>

	<xsl:include href="udata://core/importSkinXsl/form.modify.xsl" />
	<xsl:include href="udata://core/importSkinXsl/form.modify.custom.xsl" />
	<xsl:include href="udata://core/importExtSkinXsl/form.modify" />
</xsl:stylesheet>
