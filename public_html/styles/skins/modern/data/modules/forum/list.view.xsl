<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/forum" [
	<!ENTITY sys-module        'forum'>
	<!ENTITY sys-method-add        'add'>
	<!ENTITY sys-method-edit    'edit'>
	<!ENTITY sys-method-del        'del'>
	<!ENTITY sys-method-list    'lists'>
	<!ENTITY sys-method-acivity    'activity'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'lists']/data">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<div class="imgButtonWrapper loc-left">
				<a href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/conf/" id="addConf"
				   class="btn color-blue loc-left" umi:type="forum::conf">
					<xsl:text>&label-add-conf;</xsl:text>
				</a>

				<a href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/topic/" id="addTopic"
				   class="btn color-blue loc-left" umi:type="forum::topic">
					<xsl:text>&label-add-topic;</xsl:text>
				</a>

				<a href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/message/" id="addMessage"
				   class="btn color-blue loc-left" umi:type="forum::message">
					<xsl:text>&label-add-message;</xsl:text>
				</a>
			</div>

			<xsl:call-template name="entities.help.button" />
		</div>


		<div class="layout">
			<div class="column">

				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="allow-drag">1</xsl:with-param>
					<xsl:with-param name="js-add-buttons">
						createAddButton($('#addConf')[0], oTable, '{$pre_lang}/admin/&sys-module;/&sys-method-add;/{$param0}/conf/', [true]);
						createAddButton($('#addTopic')[0], oTable, '{$pre_lang}/admin/&sys-module;/&sys-method-add;/{$param0}/topic/', ['conf']);
						createAddButton($('#addMessage')[0], oTable, '{$pre_lang}/admin/&sys-module;/&sys-method-add;/{$param0}/message/', ['topic']);
					</xsl:with-param>
				</xsl:call-template>

			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>
	
	<xsl:template match="/result[@method = 'last_messages']/data">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="control-params">last_messages</xsl:with-param>
					<xsl:with-param name="flat-mode">1</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>


</xsl:stylesheet>