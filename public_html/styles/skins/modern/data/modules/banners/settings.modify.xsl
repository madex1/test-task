<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="option" mode="settings.modify">
		<div class="row">
			<div class="col-md-5">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</div>
			<div class="col-md-4">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>