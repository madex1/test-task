<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
		<!ENTITY sys-module		   'content'>
		<!ENTITY sys-method-add		   'add'>
		<!ENTITY sys-type		   'page'>
		<!ENTITY sys-method-edit	'edit'>
		<!ENTITY sys-method-del		   'del'>
		<!ENTITY sys-method-list	'tree'>
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'sitetree']/data[@type = 'list' and @action = 'view']">
		<xsl:apply-templates select="domain" mode="list-view"/>
	</xsl:template>


	<xsl:template match="domain" mode="list-view">
		<xsl:call-template name="ui-smc-tree">
			<xsl:with-param name="control-id" select="@id" />
			<xsl:with-param name="init" select="position() = last()" />
			<xsl:with-param name="domain-id" select="@id" />
			<xsl:with-param name="host" select="@host" />
			<xsl:with-param name="menu"><![CDATA[
				var menu = [
					['view-page', 'view', ContextMenu.itemHandlers.viewElement],
					['add-page', 'ico_add', ContextMenu.itemHandlers.addItem],
					['edit-page', 'ico_edit', ContextMenu.itemHandlers.editItem],
					['change-activity', 'ico_unblock', ContextMenu.itemHandlers.activeItem],
					'-',
					['copy-url', false, ContextMenu.itemHandlers.copyUrl],
					['filter-by-node', false, ContextMenu.itemHandlers.filterItem],
					['change-template', false, ContextMenu.itemHandlers.templatesItem],
					['crossdomain-copy', false, ContextMenu.itemHandlers.copyItemOld],
					['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
				]
			]]></xsl:with-param>
			<xsl:with-param name="disableTooManyChildsNotification" select="/result/@disableTooManyChildsNotification" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="/result[@method = 'tree']/data[@type = 'list' and @action = 'view']">
		<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<a id="addPage"
			   href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type;/"
			   class="type_select_gray" umi:type="content::page">
				<xsl:text>&label-add-page;</xsl:text>
			</a>
		</div>

		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="allow-drag">1</xsl:with-param>
			<xsl:with-param name="control-params" select="'tree'" />
			<xsl:with-param name="js-add-buttons">
				createAddButton(
				$('#addPage').get(0), oTable,
				'<xsl:value-of select="$lang-prefix" />/admin/&sys-module;/&sys-method-add;/{id}/&sys-type;/', ['*', true]
				);
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	
</xsl:stylesheet>