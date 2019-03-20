<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/" [
	<!ENTITY sys-module         'vote'>
	<!ENTITY sys-method-add     'add'>
	<!ENTITY sys-method-edit    'edit'>
	<!ENTITY sys-method-del     'del'>
	<!ENTITY sys-method-list    'tree'>
	<!ENTITY sys-method-acivity 'activity'>
	<!ENTITY sys-type-item      'poll'>
]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:template match="/result[@module = 'vote' and @method = 'lists']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
						<a id="addVote" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/0/"
						   class="type_select btn color-blue loc-left" umi:type="vote::poll">
							<xsl:text>&label-add-item;</xsl:text>
						</a>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="content-type">pages</xsl:with-param>
							<xsl:with-param name="control-params">poll</xsl:with-param>
							<xsl:with-param name="flat-mode">1</xsl:with-param>
							<xsl:with-param name="js-add-buttons">
								createAddButton(
								$('#addVote')[0], oTable,
								'<xsl:value-of select="$lang-prefix"/>/admin/&sys-module;/&sys-method-add;/0/',
								[true, '*']
								);
							</xsl:with-param>
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
