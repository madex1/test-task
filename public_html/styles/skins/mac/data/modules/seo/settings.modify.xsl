<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="option[@name = 'megaindex-password']" mode="settings.modify-option">
		<input type="password" name="{@name}" value="{value}" id="{@name}" />
	</xsl:template>

	<xsl:template match="/result[@method = 'config']/data[@type = 'settings' and @action = 'modify']">
		<form method="post" action="do/" enctype="multipart/form-data">
			<div class="panel properties-group">
				<div class="header">
					<span><xsl:text>&header-seo-domains;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content">
					<xsl:apply-templates select="group[@name != 'yandex']" mode="settings-modify" />
					<xsl:call-template name="std-save-button" />
				</div>
			</div>
		</form>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="/result[@method = 'config']//group" mode="settings-modify">
		<xsl:variable name="seo-title" select="option[position() = 2]" />
		<xsl:variable name="seo-keywords" select="option[position() = 3]" />
		<xsl:variable name="seo-description" select="option[position() = 4]" />

		<table class="tableContent">
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
						<label for="{$seo-title/@name}">
							<xsl:text>&option-seo-title;</xsl:text>
						</label>
					</td>

					<td>
						<input type="text" name="{$seo-title/@name}" value="{$seo-title/value}" id="{$seo-title/@name}" />
					</td>
				</tr>

				<tr>
					<td class="eq-col">
						<label for="{$seo-keywords/@name}">
							<xsl:text>&option-seo-keywords;</xsl:text>
						</label>
					</td>

					<td>
						<input type="text" name="{$seo-keywords/@name}" value="{$seo-keywords/value}" id="{$seo-keywords/@name}" />
					</td>
				</tr>

				<tr>
					<td class="eq-col">
						<label for="{$seo-description/@name}">
							<xsl:text>&option-seo-description;</xsl:text>
						</label>
					</td>

					<td>
						<input type="text" name="{$seo-description/@name}" value="{$seo-description/value}" id="{$seo-description/@name}" />
					</td>
				</tr>
			</tbody>
		</table>
	</xsl:template>


	<xsl:template match="/result[@method = 'yandex']/data[@type = 'settings' and @action = 'modify']">
		<form method="post" action="do/" enctype="multipart/form-data">
			<div class="panel properties-group">
				<div class="header">
					<span><xsl:text>&header-seo-yandex;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content">
					<table class="tableContent">
						<tbody>
							<tr>
								<xsl:apply-templates select="group[@name = 'yandex']" mode="settings-modify" />
							</tr>
						</tbody>
					</table>
					<xsl:call-template name="std-save-button" />
				</div>
			</div>
		</form>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="group[@name = 'yandex']/option[@name = 'code']" mode="settings-modify">
		<td class="eq-col" style="vertical-align: top;">
			<label for="{@name}">
				<xsl:value-of select="@label" />
			</label>
		</td>
		<td>
			<input type="text" name="{@name}" id="{@name}" />
		</td>
		<td class="token_button">
			<a href="https://oauth.yandex.ru/authorize?response_type=code&#38;client_id=47fc30ca18e045cdb75f17c9779cfc36" target="_blank">Получить код</a>
		</td>
	</xsl:template>

	<xsl:template match="group[@name = 'yandex']/option[@name = 'token' and value]" mode="settings-modify">
		<td class="eq-col" style="vertical-align: top;">
			<label for="{@name}">
				<xsl:value-of select="@label" />
			</label>
		</td>
		<td>
			<input type="text" name="{@name}" value="{value}" id="{@name}" />
		</td>
	</xsl:template>

</xsl:stylesheet>