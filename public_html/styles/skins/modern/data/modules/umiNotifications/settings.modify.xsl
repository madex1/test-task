<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
								xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
								xmlns:php="http://php.net/xsl"
								extension-element-prefixes="php"
								xmlns:umi="http://www.w3.org/1999/xhtml">

	<xsl:template match="option[@type = 'mail-template']" mode="settings.modify">
		<div class="row">
			<div class="col-md-12">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</div>
			<div class="col-md-12">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
