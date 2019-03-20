<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.modify"/>
							<div class="row">
								<xsl:call-template name="std-form-buttons-settings"/>
							</div>
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>

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
	</xsl:template>

	<xsl:template match="group" mode="settings.modify">
		<xsl:param name="toggle" select="1"></xsl:param>
		<div class="panel-settings">
			<div class="title field-group-toggle">
				<xsl:if test="$toggle = 1 and (count(.) > 1)">
					<div class="round-toggle "></div>
				</xsl:if>

				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<xsl:apply-templates select="option" mode="settings.modify" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option" mode="settings.modify">
		<div class="row">
			<div class="col-md-4">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</div>
			<div class="col-md-4">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group" mode="settings.modify.table">
		<div class="panel-settings">
			<div class="title">
				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<table class="btable btable-striped middle-align">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify.table" />
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option" mode="settings.modify.table">
		<xsl:param name="title_column_width" select="'50%'" />
		<xsl:param name="value_column_width" select="'50%'"/>

		<tr>
			<td width="{$title_column_width}">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</td>

			<td width="{$value_column_width}">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="option" mode="settings.modify-option">
		<xsl:text>Put here "</xsl:text>
		<xsl:value-of select="@type" />
		<xsl:text>" and code for other options (</xsl:text>
		<a href="/styles/skins/modern/data/settings.modify.xsl">
			<xsl:text>~/styles/skins/modern/data/settings.modify.xsl</xsl:text>
		</a>
		<xsl:text>)</xsl:text>
	</xsl:template>

	<xsl:template match="option[@type = 'status']" mode="settings.modify-option">
		<xsl:value-of select="value" />
	</xsl:template>

	<xsl:template match="option[@type = 'string']" mode="settings.modify-option">
		<input type="text" class="default" name="{@name}" value="{value}" id="{@name}" />
	</xsl:template>

    <xsl:template match="option[@type = 'email']" mode="settings.modify-option">
        <input type="email" class="default" name="{@name}" value="{value}" id="{@name}"/>
	</xsl:template>

    <xsl:template match="option[@type = 'int']" mode="settings.modify-option">
        <input type="number" class="default" name="{@name}" value="{value}" id="{@name}"/>
	</xsl:template>

	<xsl:template match="option[@type = 'ufloat']" mode="settings.modify-option">
		<input type="number" class="default" name="{@name}" value="{value}" id="{@name}" min="0" step="0.000001" />
	</xsl:template>
	
	<xsl:template match="option[@type = 'bool']" mode="settings.modify-option">
		<input type="hidden" name="{@name}" value="0" />
		<div class="checkbox">
		<input type="checkbox" name="{@name}" value="1" id="{@name}" class="check">
			<xsl:if test="value = '1'">
				<xsl:attribute name="checked">checked</xsl:attribute>
			</xsl:if>
		</input>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'password']" mode="settings.modify-option">
		<input class="default" type="password" name="{@name}" value="********" id="{@name}" />
	</xsl:template>

	<xsl:template match="option[@type = 'boolean']" mode="settings.modify-option">
		<input type="hidden" value="0" name="{@name}" />
		<div class="checkbox">
		<input type="checkbox" value="1" id="{@name}" name="{@name}" class="check">
			<xsl:if test="value &gt; 0">
				<xsl:attribute name="checked"><xsl:text>checked</xsl:text></xsl:attribute>
			</xsl:if>
		</input>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'select']" mode="settings.modify-option">
		<div >
			<select class="default newselect" id="{@name}" name="{@name}">
				<xsl:apply-templates select="value/item" mode="settings.modify-option.select">
					<xsl:with-param name="value" select="value/@id"/>
				</xsl:apply-templates>
			</select>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'guide' or @type = 'weak_guide']" mode="settings.modify-option">
		<div>
			<select class="default newselect" name="{@name}" id="{@name}">
				<xsl:apply-templates
						select="document(concat('udata://content/getObjectsByTypeList/', value/type-id))/udata//item"
						mode="settings.modify-option.select">
					<xsl:with-param name="value" select="value/value"/>
				</xsl:apply-templates>
			</select>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'templates']" mode="settings.modify-option">
		<div >
			<select class="default newselect" name="{@name}" id="{@name}">
				<xsl:apply-templates
						select="document('udata://system/getTemplatesList/')/udata//item"
						mode="settings.modify-option.select">
					<xsl:with-param name="value" select="value" />
				</xsl:apply-templates>
			</select>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'select-multi']" mode="settings.modify-option">
		<div>
			<select class="default newselect" name="{@name}[]" id="{@name}" multiple="multiple">
				<xsl:apply-templates select="value/item" mode="settings.modify-option.select" />
			</select>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="settings.modify-option.select">
		<xsl:param name="value"/>
		<option value="{@id}">
			<xsl:if test="(not($value) and @selected) or $value = @id">
				<xsl:attribute name="selected"><xsl:text>selected</xsl:text></xsl:attribute>
			</xsl:if>
			<xsl:value-of select="."/>
		</option>
	</xsl:template>

	<!-- Шаблон вкладки настроек Яндекс авторизациия (OAuth) -->
	<xsl:template match="/result[@method = 'yandex']/data[@type = 'settings' and @action = 'modify']">
		<xsl:apply-templates select="$errors" />
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<div class="panel-settings properties-group">
								<div class="round-toggle"/>
								<div class="title field-group-toggle">
									<h3><xsl:text>&header-yandex-oauth;</xsl:text></h3>
								</div>
								<div class="content">
									<div class="row">
										<xsl:apply-templates select="group[@name = 'yandex']" mode="settings-modify" />
									</div>
								</div>
							</div>
							<div class="row">
								<xsl:call-template name="std-form-buttons-settings" />
							</div>
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<!-- Шаблон для вывода идентификатора приложения -->
	<xsl:template match="group[@name = 'yandex']/option[@name = 'client_id']" mode="settings-modify" />

	<!-- Шаблон для ввода кода подтверждения Яндекс.OAuth -->
	<xsl:template match="group[@name = 'yandex']/option[@name = 'code']" mode="settings-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<xsl:value-of select="@label" />
			</div>
			<input class="default" type="text" name="{@name}" id="{@name}" />
			<xsl:variable name="clientId" select="../option[@name = 'client_id']/value"/>
			<a href="https://oauth.yandex.ru/authorize?response_type=code&#38;client_id={$clientId}" target="_blank">&label-yandex-oauth-get-code;</a>
		</div>
	</xsl:template>

	<!-- Шаблон для ввода авторизационного токена Яндекс.OAuth -->
	<xsl:template match="group[@name = 'yandex']/option[@name = 'token' and value]" mode="settings-modify">
		<div class="col-md-6">
			<div class="title-edit">
				<xsl:value-of select="@label" />
			</div>
			<input class="default" type="text" name="{@name}" value="{value}" id="{@name}" />
		</div>
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

	<xsl:include href="udata://core/importSkinXsl/settings.modify.xsl"/>
	<xsl:include href="udata://core/importSkinXsl/settings.modify.custom.xsl"/>
	<xsl:include href="udata://core/importExtSkinXsl/settings.modify"/>

</xsl:stylesheet>
