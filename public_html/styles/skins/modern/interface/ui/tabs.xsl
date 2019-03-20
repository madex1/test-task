<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:variable name="tabs" select="document(concat('udata://system/get_module_tabs/', $module, '/', $method))/udata" />

	<xsl:template match="result" mode="tabs">
		<xsl:choose>
			<xsl:when test="count($tabs/items/item)">
				<div class="tabs module">
					<xsl:apply-templates select="$tabs" />
				</div>
				<div class="tabs-content module">
					<div id="page" class="section selected">
						<xsl:apply-templates select="." />
					</div>
				</div>
			</xsl:when>
			
			<xsl:otherwise>
				<xsl:apply-templates select="." />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	
	<xsl:template match="udata[@module = 'system' and @method = 'get_module_tabs']">
		<xsl:apply-templates select="items/item" />
	</xsl:template>


	<xsl:template match="udata[@module = 'system' and @method = 'get_module_tabs']/items/item">
		<div>
			<xsl:attribute name="class">
				<xsl:text>section</xsl:text>
				<xsl:if test="@active"><xsl:text> selected</xsl:text></xsl:if>
			</xsl:attribute>

			<!--<xsl:choose>
				<xsl:when test="@active">
					<xsl:value-of select="@label" />
				</xsl:when>
				<xsl:otherwise>-->
					<a  href="{@link}" >
						<xsl:value-of select="@label" />
					</a>
				<!--</xsl:otherwise>
			</xsl:choose>-->
		</div>
	</xsl:template>

</xsl:stylesheet>