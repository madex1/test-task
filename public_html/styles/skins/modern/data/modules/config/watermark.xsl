<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				xmlns:umi="http://www.w3.org/1999/xhtml">

	<!-- Вкладка "Водяной знак" модуля "Конфигурация" -->
	<xsl:template match="/result[@method = 'watermark']/data">
		<script type="text/javascript" src="/styles/skins/modern/data/modules/config/watermark.js" />

		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
				<xsl:call-template name="watermark_preview" />

				<form id="{../@module}_{../@method}_form" action="do/" method="post">
					<xsl:apply-templates select="group" mode="watermark.settings.modify" />

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

	<!-- Предпросмотр водяного знака -->
	<xsl:template name="watermark_preview">
		<xsl:param name="image" select="'./styles/skins/modern/design/img/watermark_preview.jpg'" />
		<xsl:param name="width" select="533" />
		<xsl:param name="height" select="'auto'" />

		<!-- Сайд-эффект: меняется дата модификации файла, чтобы сгенерировать новую уменьшенную копию изображения -->
		<xsl:variable name="change_filemtime" select="php:function('touch', string($image))" />

		<div id="preview_wrapper">
			<h3>&watermark_preview;</h3>
			<div class="preview">
				<xsl:variable name="preview_src"
							  select="document(concat('udata://system/makeThumbnailFull/(', $image ,')/', $width ,'/', $height ,'/void/0/0/5/1/100'))/udata/src" />
				<img src="{$preview_src}" />
			</div>
		</div>
	</xsl:template>

	<!-- Группа настроек водяного знака, общих для всех сайтов -->
	<xsl:template match="/result[@method = 'watermark']//group[@name = 'watermark']" mode="watermark.settings.modify">
		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="@label" />
				</h3>
			</div>

			<div class="content">
				<xsl:apply-templates select="option" mode="settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Группа настроек водяного знака для конкретного сайта -->
	<xsl:template match="/result[@method = 'watermark']//group[@name != 'watermark']" mode="watermark.settings.modify">
		<xsl:variable name="domain" select="option[position() = 1]/value" />

		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="concat($domain, $lang-prefix)" />
				</h3>
			</div>

			<div class="content">
				<xsl:apply-templates select="option[position() > 1]" mode="watermark.settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Отдельная настройка водяного знака для конкретного сайта -->
	<xsl:template match="option" mode="watermark.settings.modify">
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

			<div class="col-md-4">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</div>
		</div>
	</xsl:template>

	<!-- Настройка водяного знака "Прозрачность" -->
	<xsl:template match="option[@type = 'int' and @name = 'alpha']" mode="settings.modify-option">
		<input type="number" class="default" name="{@name}" value="{value}" id="alpha" min="0" max="100" />
	</xsl:template>

	<!-- Настройка водяного знака "Накладываемое изображение" -->
	<xsl:template match="option[@type = 'string' and starts-with(@name, 'image')]" mode="settings.modify-option">
		<xsl:variable name="filemanager-id"
					  select="document(concat('uobject://',/result/@user-id))/udata//property[@name = 'filemanager']/value/item/@id" />
		<xsl:variable name="filemanager">
			<xsl:choose>
				<xsl:when test="not($filemanager-id)">
					<xsl:text>elfinder</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="document(concat('uobject://',$filemanager-id))/udata//property[@name = 'fm_prefix']/value" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="file watermark" id="{generate-id()}" umi:input-name="{@name}" umi:field-type="image_file"
			 umi:name="{@name}"
			 umi:lang="{/result/@interface-lang}"
			 umi:filemanager="{$filemanager}"
			 umi:file="{value}"
			 umi:folder="./images/cms/data"
			 umi:on_get_file_function="onChooseWaterMark">

			<label for="fileControlContainer_{generate-id()}">
				<span class="layout-row-icon" id="fileControlContainer_{generate-id()}" />
			</label>
		</div>
	</xsl:template>
</xsl:stylesheet>
