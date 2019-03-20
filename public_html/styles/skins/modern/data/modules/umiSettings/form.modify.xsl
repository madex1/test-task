<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Права на функционал модуля -->
	<xsl:variable name="permissions" select="document('udata://umiSettings/permissions/')/udata/data" />

	<!-- Шаблон вывода туллбара форм редактирования и создания настроек -->
	<xsl:template match="data[@type = 'form' and (@action = 'modify' or @action = 'create')]" mode="form-modify-toolbar-buttons">
		<xsl:param name="value"/>

		<a href="javascript:void(0);" class="icon-action extended_fields_expander"
		   title="&js-fields-expand;" data-expand-text="&js-fields-expand;"
		   data-collapse-text="&js-fields-collapse;">
			<i class="small-ico i-slideup"/>
		</a>
		<xsl:if test="$permissions/delete = 1 and not(/result/data/@action = 'create')">
			<a id="remove-object" title="&label-delete;" class="icon-action">
				<i class="small-ico i-remove"/>
			</a>
		</xsl:if>
		<xsl:if test="$permissions/update = 1">
			<a href="/admin/data/type_edit/{$value}" class="icon-action" id="edit" title="&label-edit-type;">
				<i class="small-ico i-edit"/>
			</a>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон вывода поля "Идентификатор" -->
	<xsl:template match="field[@name = 'custom_id' and @type = 'string']" mode="form-modify">
		<xsl:variable name="settings.id">
			<xsl:choose>
				<xsl:when test="../../../@id">
					<xsl:value-of select="../../../@id"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>new</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
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
					<xsl:if test="$settings.id != 'new' and string-length(string(.)) > 0">
						<xsl:attribute name="disabled">
							<xsl:text>disabled</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<xsl:apply-templates select="@type" mode="number" />
				</input>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон вывода поля "Домен" -->
	<xsl:template match="field[@name = 'domain_id' and @type = 'int']" mode="form-modify">
		<xsl:param name="selected.id" select="."/>
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>
			<div class="layout-row-icon">
				<div class="layout-col-control selectize-container">
					<select class="default newselect required" autocomplete="off" name="{@input_name}">
						<xsl:if test="$selected.id">
							<option value="{$selected.id}" selected="selected">
								<xsl:value-of select="$domains-list/domain[@id = $selected.id]/@host" />
							</option>
						</xsl:if>
						<xsl:apply-templates select="$domains-list" mode="domain_id">
							<xsl:with-param name="selected.id" select="$selected.id" />
						</xsl:apply-templates>
					</select>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вывода поля "Язык" -->
	<xsl:template match="field[@name = 'lang_id' and @type = 'int']" mode="form-modify">
		<xsl:param name="selected.id" select="."/>
		<div class="col-md-6 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:apply-templates select="." mode="sys-tips" />
					<xsl:value-of select="@title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>
			<div class="layout-row-icon">
				<div class="layout-col-control selectize-container">
					<select class="default newselect required" autocomplete="off" name="{@input_name}">
						<xsl:if test="$selected.id">
							<option value="{$selected.id}" selected="selected">
								<xsl:value-of select="$site-langs/items/item[@id = $selected.id]" />
							</option>
						</xsl:if>
						<xsl:apply-templates select="$site-langs" mode="lang_id">
							<xsl:with-param name="selected.id" select="$selected.id" />
						</xsl:apply-templates>
					</select>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'getLangsList']" mode="lang_id">
		<xsl:param name="selected.id"/>
		<xsl:apply-templates select="items/item" mode="lang_id">
			<xsl:with-param name="selected.id" select="$selected.id" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="item" mode="lang_id">
		<xsl:param name="selected.id"/>
		<xsl:if test="$selected.id != @id">
			<option value="{@id}">
				<xsl:value-of select="." />
			</option>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>