<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
	<!ENTITY sys-module 'data'>
	<!ENTITY sys-module 'webforms'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон формы редактирования/создания страниц, объектов и типов модуля "Конструктор форм" -->
	<xsl:template match="/result[not(@method = 'form_edit' or @method = 'form_add')]/data[@type = 'form' and (@action = 'modify' or @action = 'create') and object]">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="saveSize"></div>
				</div>

				<div class="layout">
					<div class="column">
						<form  class="form_modify" data-type-id="{$object-type-id}" method="post" action="do/" enctype="multipart/form-data">
							<input type="hidden" name="referer" value="{/result/@referer-uri}" />
							<input type="hidden" name="domain" value="{$domain-floated}" />

							<xsl:apply-templates select="group" mode="form-modify" />

							<xsl:apply-templates select="object/properties/group" mode="form-modify">
								<xsl:with-param name="show-name">0</xsl:with-param>
							</xsl:apply-templates>

							<div class="row">
								<div id="buttons_wr" class="col-md-12">
									<xsl:choose>
										<xsl:when test="$data-action = 'create'">
											<xsl:call-template name="std-form-buttons-add" />
										</xsl:when>
										<xsl:otherwise>
											<xsl:call-template name="std-form-buttons" />
										</xsl:otherwise>
									</xsl:choose>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<xsl:call-template name="error-checker" />
		<script src="/styles/skins/modern/data/modules/webforms/initWebformsErrorChecker.js?{$system-build}" />
	</xsl:template>

	<!-- Шаблон группы полей объектов и страниц модуля "Конструктор форм" -->
	<xsl:template match="properties/group" mode="form-modify">
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
			<a data-name="{@name}" data-label="{$title}"/>
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
							<xsl:if test="position() = 1 and not(/result/@method='template_add') and not(/result/@method='template_edit')">
								<div class="col-md-6">
									<div class="title-edit">
										<xsl:text>&label-name;</xsl:text>
									</div>
									<span>
										<input class="default" type="text" name="name" value="{../../@name}" />
									</span>
								</div>
							</xsl:if>

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

	<xsl:template match="/result[@method = 'form_edit' or @method = 'form_add']/data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<form  class="form_modify" data-type-id="{$object-type-id}" action="do/" method="post" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}" />

			<xsl:apply-templates select="type" mode="fieldgroup-common" />

			<div class="row">
				<div id="buttons_wr" class="col-md-12">
					<xsl:choose>
						<xsl:when test="$data-action = 'create'">
							<xsl:call-template name="std-form-buttons-add" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="std-form-buttons" />
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</div>
		</form>

		<xsl:if test="$data-action = 'modify'">
			<xsl:apply-templates select="//fieldgroups" mode="fieldsgroups-other" />
		</xsl:if>
	</xsl:template>

	<!-- Шаблон группы полей "Форма" в форме редактирования типа -->
	<xsl:template match="type" mode="fieldgroup-common">
		<xsl:variable name="form_id">
			<xsl:choose>
				<xsl:when test="@id">
					<xsl:value-of select="@id" />
				</xsl:when>
				<xsl:when test="string(number(text())) != 'NaN'">
					<xsl:value-of select="text()" />
				</xsl:when>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'form')"/>
		<div name="g_form">
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
				<xsl:text>Основные настройки формы обратной связи.</xsl:text>
			</summary>
			<a data-name="form" data-label="form"/>
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
					<h3><xsl:text>&label-form;</xsl:text></h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'g_form'" />
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
							<div class="col-md-6">
								<div class="title-edit">
									<xsl:text>&label-form-name;</xsl:text>
								</div>
								<span>
									<input class="default" type="text" name="data[name]" value="{@title}"/>
								</span>
							</div>
							<xsl:apply-templates select="document(concat('udata://webforms/getAddresses/', $form_id))/udata"/>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'webforms'][@method = 'getAddresses']">
		<div class="col-md-6">
			<div class="title-edit">
				<xsl:text>&label-address-send;</xsl:text>
			</div>
			<div class="">
				<select class="default newselect" name="{@input_name}" id="relationSelect{generate-id()}">
					<xsl:apply-templates select="." mode="required_attr" />
					<xsl:if test="@multiple = 'multiple'">
						<xsl:attribute name="multiple">multiple</xsl:attribute>
						<xsl:attribute name="style">height: 62px;</xsl:attribute>
					</xsl:if>
					<option value=""></option>
					<xsl:apply-templates select="items/item" />
				</select>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'webforms' and @method = 'getAddresses']/items/item">
		<option value="{@id}">
			<xsl:value-of select="." />
		</option>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'webforms' and @method = 'getAddresses']/items/item[@selected = 'selected']">
		<option value="{@id}" selected="selected">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="base" mode="fieldsgroups-other">
		<div class="header">
			<span><xsl:value-of select="." /></span>
			<div class="l" /><div class="r" />
		</div>
	</xsl:template>

	<xsl:template match="group[@name = 'SendingData']" mode="fieldsgroups-other" />

	<xsl:template match="group[@name = 'Binding' or @name = 'binding']"     mode="form-modify" />

	<xsl:template match="group[@name = 'BindToForm']" mode="form-modify">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
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
			<a data-name="{@name}" data-label="{$title}"/>
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
				<div class="row">
					<div class="col-md-6">
						<div class="title-edit">
								<xsl:text>&label-form;</xsl:text>
						</div>
						<span>
							<select name="system_form_id" class="default newselect" id="system_form_id">
								<xsl:apply-templates
										select="document(concat('udata://webforms/getUnbindedForms/', //object/@id))/udata/items/item"
										mode="getUnbindedForms">
									<xsl:with-param name="selected_id" select="@selected_type"/>
								</xsl:apply-templates>
							</select>
						</span>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="getUnbindedForms">
		<xsl:param name="selected_id" />
		<option value="{@id}">
			<xsl:if test="@id = $selected_id">
				<xsl:attribute name="selected"><xsl:text>selected</xsl:text></xsl:attribute>
			</xsl:if>
			<xsl:value-of select="." />
		</option>
	</xsl:template>

</xsl:stylesheet>
