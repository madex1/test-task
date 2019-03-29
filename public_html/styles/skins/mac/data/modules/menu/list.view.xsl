<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="result[@method = 'lists']/data">
		<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<a href="{$lang-prefix}/admin/menu/add/item_element" class="type_select">
				<xsl:text>&label-menu-add-menu;</xsl:text>
			</a>
		</div>
		
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="control-params" select="$method" />
			<xsl:with-param name="content-type">objects</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	
	
</xsl:stylesheet>