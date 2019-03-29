<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон табличного контрола -->
	<xsl:template match="data[@type = 'list' and @action = 'view']">
		<xsl:call-template name="ui-smc-table" />
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

	<xsl:include href="udata://core/importSkinXsl/list.view.xsl"/>
	<xsl:include href="udata://core/importSkinXsl/list.view.custom.xsl"/>
	<xsl:include href="udata://core/importExtSkinXsl/list.view"/>

</xsl:stylesheet>