<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
>

	<xsl:template match="option[@name = 'megaindex-password']" mode="settings.modify-option">
		<input class="default" type="password" name="{@name}" value="{value}" id="{@name}" />
	</xsl:template>

	<xsl:template match="/result[@method = 'config']/data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
		<div class="section selected">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
		<div class="column">
		<form method="post" action="do/" enctype="multipart/form-data">
			<div class="panel-settings">
				<div class="title">
					<h3><xsl:text>&header-seo-domains;</xsl:text></h3>
				</div>
				<div class="content">
					<xsl:apply-templates select="group[@name != 'yandex']" mode="settings-modify" />
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

		<xsl:call-template name="error-checker" />
		<script>
			var form = $('form').eq(0);
			jQuery(form).submit(function() {
				return checkErrors({
					form: form,
					check: {
						empty: 'input.required',
					}
				});
			});
		</script>
	</xsl:template>

	<!-- Группа настроек доступа к сайту для конкретного сайта -->
	<xsl:template match="/result[@method = 'config']//group" mode="settings-modify">
		<xsl:variable name="domain" select="option[position() = 1]/value" />

		<div class="panel-settings">
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle" />
					<h3>
						&seo-site-settings; <xsl:value-of select="concat($domain, $lang-prefix)" />
					</h3>
				</div>
			</div>

			<div class="content">
				<xsl:apply-templates select="option[position() > 1]" mode="seo.settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Отдельная настройка доступа к сайту для конкретного сайта -->
	<xsl:template match="option" mode="seo.settings.modify">
		<!--&lt;!&ndash; label без "-<id домена>" на конце &ndash;&gt;-->
		<xsl:variable name="trimmedLabel">
			<xsl:value-of select="php:function('mb_substr', string(@label), 0, php:function('mb_strrpos', string(@label), '-'))" />
		</xsl:variable>


		<div class="row">
			<div class="col-md-4">
				<div class="title-edit">
					<xsl:value-of select="php:function('getLabel', $trimmedLabel)" />
				</div>
			</div>
			<div class="col-md-4">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
