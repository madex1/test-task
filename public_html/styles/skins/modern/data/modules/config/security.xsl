<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>

	<!-- Вкладка "Безопасность" модуля "Конфигурация" -->
	<xsl:template match="/result[@method = 'security']/data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.modify" />
						</form>
					</div>

					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'security']/data/group[@name='security-audit']" mode="settings.modify">
		<script type="text/javascript" src="/styles/skins/modern/design/js/common/security.js" />

		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="@label" />
				</h3>
			</div>

			<div class="content">
				<table class="btable btable-striped" id="testsTable">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify" />
					</tbody>
				</table>
			</div>

			<div class="pull-right">
				<input type="button" class="btn color-blue" id="startSecurityTests"
					   value="&js-check-security;" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group[@name='security-audit']/option" mode="settings.modify">
		<tr class="test">
			<xsl:attribute name="data-id">
				<xsl:value-of select="@type" />
			</xsl:attribute>

			<td class="test-name">
				<xsl:value-of select="@label" />
			</td>
			<td class="test-value">&js-index-security-no;</td>
		</tr>
	</xsl:template>
</xsl:stylesheet>
