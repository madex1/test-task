<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common/catalog" [
	<!ENTITY sys-module		   'catalog'>
	<!ENTITY sys-method-add		   'add'>
	<!ENTITY sys-method-edit	'edit'>
	<!ENTITY sys-method-del		   'del'>
	<!ENTITY sys-method-list	'tree'>

	<!ENTITY sys-type-list		  'category'>
	<!ENTITY sys-type-item		  'object'>
	<!ENTITY sys-method-acivity	   'activity'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="data" priority="1">
		<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<a id="addCategory" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-list;/" class="type_select_gray" umi:type="catalog::category">
				<xsl:text>&label-add-list;</xsl:text>
			</a>
			<a id="addObject" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-item;/" class="type_select_gray" umi:type="catalog::object">
				<xsl:text>&label-add-item;</xsl:text>
			</a>
		</div>

		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="allow-drag">1</xsl:with-param>
			<xsl:with-param name="js-add-buttons">
				createAddButton(
					$('#addCategory')[0], oTable,
					'<xsl:value-of select="$lang-prefix" />/admin/&sys-module;/&sys-method-add;/{id}/&sys-type-list;/', ['category', true]
				);
				
				createAddButton(
					$('#addObject')[0],	oTable,
					'<xsl:value-of select="$lang-prefix" />/admin/&sys-module;/&sys-method-add;/{id}/&sys-type-item;/', ['category']
				);
			</xsl:with-param>
			
		</xsl:call-template>

	</xsl:template>
	
	<xsl:template match="result[@method = 'filters']">
		<h2>&indexing-header;</h2>
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="flat-mode" select="1" />
			<xsl:with-param name="content-type" select="'objects'" />
			<xsl:with-param name="search-show" select="0" />
			<xsl:with-param name="ignore-hierarchy" select="1" />
			<xsl:with-param name="control-type-id" select="85" />
			<xsl:with-param name="disable-csv-buttons" select="1" />
			<xsl:with-param name="control-params" select="'filters'" />
			<xsl:with-param name="show-toolbar" select="0"/>
			<xsl:with-param name="on-render-complete">
				<xsl:text><![CDATA[
					AdminIndexing.Controller.onRenderComplete
				]]></xsl:text>
			</xsl:with-param>
			<xsl:with-param name="data-set-init-end-requests">
				<xsl:text><![CDATA[
					[{  
						url: AdminIndexing.Settings.buttons.indexIt.resource.fullPath,
						callback: AdminIndexing.Settings.buttons.indexIt.template.onLoad

					},
					{
						url: AdminIndexing.Settings.module.server.path,
						callback: AdminIndexing.Settings.module.server.onLoad
					}]
				]]></xsl:text>
			</xsl:with-param>
			<xsl:with-param name="enable-edit">false</xsl:with-param>
			<xsl:with-param name="menu">
				<xsl:text>var menu = </xsl:text>
				<xsl:text>AdminIndexing.Settings.table.rows.menu</xsl:text>
			</xsl:with-param>
			
			<xsl:with-param name="js-value-callback">
				<xsl:text><![CDATA[
					AdminIndexing.Controller.onLoadPropValue
				]]></xsl:text>
			</xsl:with-param>
			
		</xsl:call-template>
		
		<div class="buttons">
			<div style="float:left;">
				<input type="submit" value="&indexing-add-category;" class="primary" id="add_to_index"/>
			</div>
		</div>

		<script type="text/javascript" src="/styles/skins/mac/data/modules/catalog/filters/admin.indexing.js?{$system-build}"></script>
		<script type="text/javascript" src="/styles/skins/mac/data/modules/catalog/filters/jquery.tmpl.js?{$system-build}"></script>
		<script type="text/javascript" src="/styles/skins/mac/data/modules/catalog/filters/loadingoverlay/js/loadingoverlay.min.js?{$system-build}"></script>
	</xsl:template>



</xsl:stylesheet>