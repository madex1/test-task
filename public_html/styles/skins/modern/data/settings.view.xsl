<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'settings' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="group" mode="settings.view"/>
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
	</xsl:template>

	<xsl:param name="toggle" select="1" />

	<xsl:template match="group" mode="settings.view.table">
		<div class="panel-settings">
			<div class="title field-group-toggle">
				<xsl:if test="$toggle = 1 and (count(.) > 1)">
					<div class="round-toggle"></div>
				</xsl:if>

				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<xsl:apply-templates select="option" mode="settings.modify" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group" mode="settings.view">
		<div class="panel-settings">
			<div class="title field-group-toggle">
				<xsl:if test="$toggle = 1 and (count(.) > 1)">
					<div class="round-toggle "></div>
				</xsl:if>

				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<xsl:apply-templates select="option" mode="settings.view" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option" mode="settings.view">
		<div class="row">
			<div class="col-md-4">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</div>
			<div class="col-md-4">
				<xsl:apply-templates select="." mode="settings.view.option" />
			</div>
		</div>
	</xsl:template>


	<xsl:template match="option" mode="settings.view.table">
		<xsl:param name="title_column_width" select="'50%'" />
		<xsl:param name="value_column_width" select="'50%'"/>

		<tr>
			<td width="{$title_column_width}">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</td>

			<td width="{$value_column_width}">
				<xsl:apply-templates select="." mode="settings.view.option" />
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

	<xsl:template match="option[@type = 'status']" mode="settings.view.option">
		<xsl:value-of select="value" />
	</xsl:template>

	<xsl:template match="option[@type = 'string']" mode="settings.view.option">
		<input type="text" class="default" name="{@name}" value="{value}" id="{@name}" />
	</xsl:template>

	<xsl:template match="option[@type = 'email']" mode="settings.view.option">
		<input type="email" class="default" name="{@name}" value="{value}" id="{@name}"/>
	</xsl:template>

	<xsl:template match="option[@type = 'int']" mode="settings.view.option">
		<input type="number" class="default" name="{@name}" value="{value}" id="{@name}" min="0"/>
	</xsl:template>

	<xsl:template match="option[@type = 'bool']" mode="settings.view.option">
		<input type="hidden" name="{@name}" value="0" />
		<div class="checkbox">
			<input type="checkbox" name="{@name}" value="1" id="{@name}" class="check">
				<xsl:if test="value = '1'">
					<xsl:attribute name="checked">checked</xsl:attribute>
				</xsl:if>
			</input>
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

	<xsl:include href="udata://core/importSkinXsl/settings.view.xsl"/>
	<xsl:include href="udata://core/importSkinXsl/settings.view.custom.xsl"/>

	<xsl:include href="udata://core/importExtSkinXsl/settings.view"/>
</xsl:stylesheet>