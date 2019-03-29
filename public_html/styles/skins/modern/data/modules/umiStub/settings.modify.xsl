<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				xmlns:php="http://php.net/xsl">

	<!-- Настройки доступа к сайту -->
	<xsl:template match="data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout stub-module">
					<div class="column">
						<form id="module-stub" method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.modify"/>
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
		<xsl:call-template name="wysiwyg-init" />
	</xsl:template>

	<!-- Группа настроек доступа к сайту, общих для всех сайтов -->
	<xsl:template match="/result[@method = 'stub']//group[@name = 'stub']" mode="settings.modify">
		<div class="panel-settings">
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle" />
					<h3><xsl:value-of select="@label" /></h3>
				</div>
			</div>

			<div class="content">
				<xsl:apply-templates select="option" mode="settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Отображение настроек доступа к сайту, общих для всех сайтов -->
	<xsl:template match="option" mode="settings.modify">
		<div class="row">
			<div class="col-md-4">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</div>
			<xsl:choose>
				<xsl:when test="@type = 'wysiwyg'">
					<div class="col-md-12 wysiwyg-field default-empty-validation">
						<xsl:apply-templates select="." mode="settings.modify-option" />
					</div>
				</xsl:when>
				<xsl:otherwise>
					<div class="col-md-4">
						<xsl:apply-templates select="." mode="settings.modify-option" />
					</div>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

	<!-- Группа настроек доступа к сайту для конкретного сайта -->
	<xsl:template match="/result[@method = 'stub']//group[@name != 'stub']" mode="settings.modify">
		<xsl:variable name="domain" select="option[position() = 1]/value" />

		<div class="panel-settings">
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle switch" />
					<h3>
						&stub-site-settings; <xsl:value-of select="concat($domain, $lang-prefix)" />
					</h3>
				</div>
			</div>

			<div class="content settings-hide">
				<xsl:apply-templates select="option[position() > 1]" mode="stub.settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Отдельная настройка доступа к сайту для конкретного сайта -->
	<xsl:template match="option" mode="stub.settings.modify">
		<!-- label без "-<id домена>" на конце -->
		<xsl:variable name="trimmedLabel">
			<xsl:value-of select="php:function('mb_substr', string(@label), 0, php:function('mb_strrpos', string(@label), '-'))" />
		</xsl:variable>

		<div class="row">
			<div class="col-md-4">
				<div class="title-edit">
					<xsl:value-of select="php:function('getLabel', $trimmedLabel)" />
				</div>
			</div>
			<xsl:choose>
				<xsl:when test="@type = 'wysiwyg'">
					<div class="col-md-12 wysiwyg-field default-empty-validation">
						<xsl:apply-templates select="." mode="settings.modify-option" />
					</div>
				</xsl:when>
				<xsl:otherwise>
					<div class="col-md-4">
						<xsl:apply-templates select="." mode="settings.modify-option" />
					</div>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

	<!-- Шаблон wysiwyg-редактора заглушки -->
	<xsl:template match="option[@type = 'wysiwyg']" mode="settings.modify-option">
		<textarea class="wysiwyg" id="{generate-id()}" name="{@name}" >
			<xsl:value-of select="value" disable-output-escaping="yes" />
		</textarea>
	</xsl:template>

</xsl:stylesheet>