<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/data" [
	<!ENTITY sys-module        'data'>
	<!ENTITY sys-method-type-view    'types'>
	<!ENTITY sys-method-type-add    'type_add'>
	<!ENTITY sys-method-type-edit    'type_edit'>
	<!ENTITY sys-method-type-del    'type_del'>
	<!ENTITY sys-method-del        'del'>
	<!ENTITY sys-method-guide-view    'guide_items'>
	<!ENTITY sys-method-guide-add    'guide_add'>
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
		<div class="section selected">
			<div class="location">
				<div class="imgButtonWrapper loc-left">
					<a class="btn color-blue" href="{$lang-prefix}/admin/&sys-module;/&sys-method-type-add;/{$param0}/"
					   id="addType">&label-type-add;</a>
				</div>
				<xsl:call-template name="entities.help.button" />
			</div>

			<div class="layout">
				<div class="column">
					<xsl:call-template name="ui-smc-table">
						<xsl:with-param name="domains-show">1</xsl:with-param>
						<xsl:with-param name="content-type">types</xsl:with-param>
						<xsl:with-param name="disable-name-filter">true</xsl:with-param>
						<xsl:with-param name="enable-edit">false</xsl:with-param>
						<xsl:with-param name="js-value-callback"><![CDATA[
				function (value, name, item) {
					var data = item.getData();
					return data.title;
				}
			]]></xsl:with-param>
						<xsl:with-param name="menu"><![CDATA[
				var menu = [
					['edit-item', 'ico_edit', ContextMenu.itemHandlers.editItem],
					['delete',    'ico_del',  ContextMenu.itemHandlers.deleteItem]
				]
			]]></xsl:with-param>
						<xsl:with-param name="js-add-buttons">
							createAddButton(
							$('#addType')[0], oTable,
							'<xsl:value-of select="$lang-prefix"/>/admin/&sys-module;/&sys-method-type-add;/{id}/',
							['*',true]
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

	<xsl:template match="/result[@method = 'guides']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left">
						<a class="btn color-blue" href="{$lang-prefix}/admin/&sys-module;/&sys-method-guide-add;/"
						   id="addType">&label-guide-add;</a>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="content-type">types</xsl:with-param>
							<xsl:with-param name="control-params">guides</xsl:with-param>
							<xsl:with-param name="js-value-callback"><![CDATA[
												function (value, name, item) {
													var data = item.getData();
													return data.title;
												}
											]]></xsl:with-param>
							<xsl:with-param name="menu"><![CDATA[
												var menu = [
													['view-guide-items', 'view',     ContextMenu.itemHandlers.guideViewItem],
													['edit-item',        'ico_edit', ContextMenu.itemHandlers.editItem],
													['delete',           'ico_del',  ContextMenu.itemHandlers.deleteItem]
												]
											]]></xsl:with-param>
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