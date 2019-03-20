<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="udata[@module = 'system' and @method = 'getSubNavibar']">
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'getSubNavibar']/module">
		<h1>
			<a href="{$lang-prefix}/admin/{.}/">
				<xsl:value-of select="@label" />
			</a>
		</h1>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'getSubNavibar']/method">
		<h2>
			<xsl:value-of select="@label" />
		</h2>
	</xsl:template>
</xsl:stylesheet>