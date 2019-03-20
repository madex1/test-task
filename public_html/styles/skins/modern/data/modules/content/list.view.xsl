<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:variable name="add-method" select="'add'" />
    <xsl:variable name="basic-type" select="'page'" />

	<xsl:template match="/result[@method = 'sitetree']/data[@type = 'list' and @action = 'view']">
        <div class="tabs-content module">
            <div class="section selected">
                <div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
                    <xsl:call-template name="entities.help.button" />
                </div>

                <div class="layout">
                    <div class="column">
                        <xsl:apply-templates select="domain" mode="list-view"/>
                    </div>
                    <div class="column">
                        <xsl:call-template name="entities.help.content" />
                    </div>
                </div>
            </div>
        </div>

	</xsl:template>

	<xsl:template match="domain" mode="list-view">
        <xsl:call-template name="ui-smc-tree">
            <xsl:with-param name="control-id" select="@id"/>
            <xsl:with-param name="init" select="position() = last()"/>
            <xsl:with-param name="domain-id" select="@id"/>
            <xsl:with-param name="host" select="@decoded-host"/>
            <xsl:with-param name="menu"><![CDATA[
                                            var menu = [
                                                ['view-page', 'view', ContextMenu.itemHandlers.viewElement],
                                                ['add-page', 'ico_add', ContextMenu.itemHandlers.addItem],
                                                ['edit-page', 'ico_edit', ContextMenu.itemHandlers.editItem],
                                                ['change-activity', 'ico_unblock', ContextMenu.itemHandlers.activeItem],
                                                ['copy-url', false, ContextMenu.itemHandlers.copyUrl],
                                                '-',
                                                ['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
                                            ]
                                        ]]></xsl:with-param>
            <xsl:with-param name="disableTooManyChildsNotification"
                            select="/result/@disableTooManyChildsNotification"/>
        </xsl:call-template>
	</xsl:template>

    <xsl:template match="/result[@method = 'tree']/data[@type = 'list' and @action = 'view']">
        <div class="location">
            <div class="imgButtonWrapper loc-left " xmlns:umi="http://www.umi-cms.ru/TR/umi">
                <a id="addPage" href="{$lang-prefix}admin/${module}/${add-method}/{id}/${basic-type}/"
                   class="btn color-blue" umi:type="content::page">
                    <xsl:text>&label-add-page;</xsl:text>
                </a>
            </div>
            <xsl:call-template name="entities.help.button" />
        </div>

        <div class="layout">
            <div class="column">
                <xsl:call-template name="ui-smc-table">
                    <xsl:with-param name="allow-drag">1</xsl:with-param>
                    <xsl:with-param name="control-params" select="'tree'" />
                    <xsl:with-param name="js-add-buttons">
                        createAddButton(
                        $('#addPage').get(0), oTable,
                        '<xsl:value-of select="concat($lang-prefix, '/admin/', $module, '/',  $add-method, '/{id}/', $basic-type, '/' )" />', ['*', true]
                        );
                    </xsl:with-param>
                </xsl:call-template>
            </div>
            <div class="column">
                <xsl:call-template name="entities.help.content" />
            </div>
        </div>
    </xsl:template>
	
</xsl:stylesheet>