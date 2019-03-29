<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>

	<!-- Вкладка "Глобальные" модуля "Конфигурация" -->
	<xsl:template match="/result[@method = 'main']//group" mode="settings.modify">
		<table class="btable btable-striped config-main" style="margin-bottom:200px;">
			<tbody>
				<xsl:apply-templates select="option" mode="settings.modify.table">
					<xsl:with-param name="title_column_width" select="'65%'" />
					<xsl:with-param name="value_column_width" select="'35%'" />
				</xsl:apply-templates>
			</tbody>
		</table>

		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>
</xsl:stylesheet>
