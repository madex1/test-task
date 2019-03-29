<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common" [
	<!ENTITY sys-module         'umiStub'>
	<!ENTITY sys-method-add     'add'>
	]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:umi="http://www.umi-cms.ru/TR/umi"
                xmlns:php="http://php.net/xsl">

	<xsl:template match="/result[@method = 'blackList' or @method = 'whiteList']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper">
						<xsl:choose>
							<xsl:when test="/result[@method = 'blackList']">
								<a id="addIp" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/ip-blacklist/"
								   class="btn color-blue loc-left">
									<xsl:text>&label-add-item;</xsl:text>
								</a>
							</xsl:when>
							<xsl:otherwise>
								<a id="addIp" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/ip-whitelist/"
								   class="btn color-blue loc-left">
									<xsl:text>&label-add-item;</xsl:text>
								</a>
							</xsl:otherwise>
						</xsl:choose>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="content-type">objects</xsl:with-param>
							<xsl:with-param name="control-params" select="/result/@method"/>
							<xsl:with-param name="flat-mode">1</xsl:with-param>
							<xsl:with-param name="label_first_column" select="'&label-name;'" />
						</xsl:call-template>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
