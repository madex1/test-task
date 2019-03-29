<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/umiSetting">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Права на функционал модуля -->
	<xsl:variable name="permissions" select="document('udata://umiSettings/permissions/')/udata/data" />

	<!-- Шаблон вкладки "Список" -->
	<xsl:template match="/result[@module = 'umiSettings' and @method = 'read']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:if test="$permissions/create = 1">
						<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
							<a umi:type="umiSettings::settings"
							   class="btn color-blue loc-left"
							   href="{$lang-prefix}/admin/{../@module}/create/settings/">
								<xsl:text>&label-add-settings;</xsl:text>
							</a>
						</div>
					</xsl:if>
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<xsl:variable name="menu">
							<xsl:choose>
								<xsl:when test="$permissions/update = 1 and $permissions/delete = 1">
									<xsl:text>
										menu = [
											['edit-item', 'ico_edit', ContextMenu.itemHandlers.editItem],
											['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
										];
									</xsl:text>
								</xsl:when>
								<xsl:when test="$permissions/update = 1 and $permissions/delete = 0">
									<xsl:text>
										menu = [
											['edit-item', 'ico_edit', ContextMenu.itemHandlers.editItem]
										];
									</xsl:text>
								</xsl:when>
								<xsl:when test="$permissions/update = 0 and $permissions/delete = 1">
									<xsl:text>
										menu = [
											['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
										];
									</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>
										menu = [];
									</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:variable>
						<xsl:variable name="toolbarmenu">
							<xsl:choose>
								<xsl:when test="$permissions/update = 1 and $permissions/delete = 1">
									<xsl:text>
										toolbarMenu = ['editButton', 'delButton'];
									</xsl:text>
								</xsl:when>
								<xsl:when test="$permissions/update = 1 and $permissions/delete = 0">
									<xsl:text>
										toolbarMenu = ['editButton'];
									</xsl:text>
								</xsl:when>
								<xsl:when test="$permissions/update = 0 and $permissions/delete = 1">
									<xsl:text>
										toolbarMenu = ['delButton'];
									</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>
										toolbarMenu = [];
									</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:variable>
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="content-type">objects</xsl:with-param>
							<xsl:with-param name="control-params">settings</xsl:with-param>
							<xsl:with-param name="domains-show">1</xsl:with-param>
							<xsl:with-param name="enable-edit">false</xsl:with-param>
							<xsl:with-param name="menu">
								<xsl:value-of select="$menu"/>
							</xsl:with-param>
							<xsl:with-param name="toolbarmenu">
								<xsl:value-of select="$toolbarmenu"/>
							</xsl:with-param>
						</xsl:call-template>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content"/>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>