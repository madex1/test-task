<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
>

	<!-- Вкладка "Почта" модуля "Конфигурация" -->
	<xsl:template match="/result[@method = 'mails']/data">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>

		<div id="mail-config" class="layout">
			<div class="column">
				<form id="{../@module}_{../@method}_form" action="do/" method="post" enctype="multipart/form-data" data-type-id="mail">
					<xsl:apply-templates select="." mode="settings.modify"/>
					<div class="row">
						<xsl:call-template name="std-form-buttons-settings" />
					</div>
				</form>

				<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Группа настроек общих для всех сайтов -->
	<xsl:template match="/result[@method = 'mails']//group[@name = 'mail']" mode="settings.modify">
		<div  class="panel-settings">
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle" />
					<h3>
						<xsl:value-of select="@label" />
					</h3>
				</div>
			</div>

			<div class="content">
				<xsl:apply-templates select="option" mode="settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Отображение настроек общих для всех сайтов -->
	<xsl:template match="option" mode="settings.modify">
		<div class="row">
			<xsl:choose>
				<xsl:when test="@name = 'smtp-settings-label'">
					<div class="col-md-4">
						<div class="title-edit">
							<h3><xsl:value-of select="@label" /></h3>
						</div>
					</div>
				</xsl:when>
				<xsl:otherwise>
					<div class="col-md-4">
						<div class="title-edit">
							<xsl:value-of select="@label" />
						</div>
					</div>
					<div class="col-md-4">
						<xsl:apply-templates select="." mode="settings.modify-option" />
					</div>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

	<!-- Группа настроек для конкретного сайта -->
	<xsl:template match="/result[@method = 'mails']//group[@name != 'mail']" mode="settings.modify">
		<xsl:variable name="domain" select="option[@name = 'domain']/value" />

		<div class="panel-settings">
			<div class="title">
				<div class="field-group-toggle">
					<div class="round-toggle switch" />
					<h3>
						&mail-site-settings; <xsl:value-of select="concat($domain, $lang-prefix)" />
					</h3>
				</div>
			</div>

			<div class="content settings-hide">
				<xsl:apply-templates select="option[@name != 'domain']" mode="mails.settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Отдельная настройка для конкретного сайта -->
	<xsl:template match="option" mode="mails.settings.modify">
		<!-- label без "-<id домена>" на конце -->
		<xsl:variable name="trimmedLabel">
			<xsl:value-of select="php:function('mb_substr', string(@label), 0, php:function('mb_strrpos', string(@label), '-'))" />
		</xsl:variable>

		<div class="row">
			<xsl:choose>
				<xsl:when test="@name = 'smtp-settings-label'">
					<div class="col-md-4">
						<div class="title-edit">
							<h3><xsl:value-of select="@label" /></h3>
						</div>
					</div>
				</xsl:when>
				<xsl:otherwise>
					<div class="col-md-4">
						<div class="title-edit">
							<xsl:value-of select="php:function('getLabel', $trimmedLabel)" />
						</div>
					</div>
					<div class="col-md-4">
						<xsl:apply-templates select="." mode="settings.modify-option" />
					</div>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

	<!-- Настройка для поля "Пароль"-->
	<xsl:template match="option[@type = 'smtp-password']" mode="settings.modify-option">
		<input class="default" type="password" name="{@name}" value="********" id="{@name}" />
	</xsl:template>

</xsl:stylesheet>
