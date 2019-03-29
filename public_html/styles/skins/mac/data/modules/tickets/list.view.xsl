<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'tickets']/data[@type = 'list' and @action = 'view']">
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="content-type">objects</xsl:with-param>
			<xsl:with-param name="control-params">tickets</xsl:with-param>
			<xsl:with-param name="js-ignore-props-edit">['message', 'url', 'user_id', 'create_time']</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

</xsl:stylesheet>