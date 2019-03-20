<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi"
	xmlns:php="http://php.net/xsl">


	<xsl:param name="param0" />

	<!-- Шаблон первой группы полей для сценариев импорта и экспорта -->
	<xsl:template match="group[position() = 1 and count(../../basetype) = 0 and ../../../../@module='exchange']"
				  mode="form-modify-group-fields">

		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>1</xsl:text></xsl:param>

		<xsl:if test="$show-name = '1'">
			<xsl:call-template name="std-form-name">
				<xsl:with-param name="value" select="../../@name" />
				<xsl:with-param name="show-tip"><xsl:text>0</xsl:text></xsl:with-param>
			</xsl:call-template>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="$show-type = '1'">
				<xsl:call-template name="std-form-data-type">
					<xsl:with-param name="typeId" select="../../@type-id" />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<input type="hidden" name="type-id" value="{../../@type-id}" />
			</xsl:otherwise>
		</xsl:choose>

		<xsl:apply-templates select="field[not(@type='tags' or @type='wysiwyg' or @type='text')]"
							 mode="form-modify" />
		<xsl:apply-templates select="field[@type='tags' or @type='wysiwyg' or @type='text']" mode="form-modify" />
		<script src="/styles/skins/modern/design/js/exchange.js" />
	</xsl:template>

	<xsl:template match="/result/data/default-encoding" mode="form-modify"/>
	<xsl:template match="/result/data/object-type" mode="form-modify"/>
	<xsl:template match="/result/data/csv-format-id" mode="form-modify"/>

</xsl:stylesheet>