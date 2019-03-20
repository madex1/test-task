<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:template match="data/error">
		<p class="error">
			<strong>
				<xsl:text>&styles-skins-eip-ui-errors-error; </xsl:text>
			</strong>
			<xsl:value-of select="." />
		</p>
	</xsl:template>
	
	
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages']/items">
		<p class="error">
			<strong>
				<xsl:text>&styles-skins-eip-ui-errors-following_errors_occurred;</xsl:text>
			</strong>
		</p>
		<ol class="error">
			<xsl:apply-templates />
		</ol>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages']/items/item">
		<li>
			<xsl:value-of select="." />
		</li>
	</xsl:template>
</xsl:stylesheet>