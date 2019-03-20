<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common/" [
	<!ENTITY sys-module 'dispatches'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'list' and @action = 'view']">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<xsl:if test="$method != 'messages'">
				<div class="imgButtonWrapper">
					<xsl:if test="$method = 'lists'">
						<a id="addDispatch" href="{$lang-prefix}/admin/&sys-module;/add/dispatch/" class="btn color-blue loc-left">&label-add-list;</a>
					</xsl:if>
					<xsl:if test="$method = 'subscribers'">
						<a id="addDispatch" href="{$lang-prefix}/admin/&sys-module;/add/subscriber/" class="btn color-blue loc-left">&label-add-subscriber;</a>
					</xsl:if>
				</div>
			</xsl:if>
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
				<xsl:choose>
					<xsl:when test="/result/@method = 'subscribers'">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="control-params" select="$method" />
							<xsl:with-param name="content-type">objects</xsl:with-param>
							<xsl:with-param name="enable-objects-activity" select="$method = 'lists'" />
							<xsl:with-param name="label_first_column" select="'&label-user-email;'" />
						</xsl:call-template>
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="control-params" select="$method" />
							<xsl:with-param name="content-type">objects</xsl:with-param>
							<xsl:with-param name="enable-objects-activity" select="$method = 'lists'" />
							<xsl:with-param name="js-ignore-props-edit">['news_relation']</xsl:with-param>
						</xsl:call-template>
					</xsl:otherwise>
				</xsl:choose>
			</div>

			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
		
	</xsl:template>

</xsl:stylesheet>