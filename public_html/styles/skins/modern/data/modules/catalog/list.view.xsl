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

	<!-- Шаблон вкладки "Разделы и товары" -->
	<xsl:template match="data" priority="1">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
				<a umi:type="catalog::category" class="btn color-blue loc-left" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-list;/" id="addCategory">
					<xsl:text>&label-add-list;</xsl:text>
				</a>
				<a umi:type="catalog::object" class="btn color-blue loc-left" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-item;/" id="addObject">
					<xsl:text>&label-add-item;</xsl:text>
				</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
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
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Индексация" -->
	<xsl:template match="result[@method = 'filters']">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column indexing_content">
				<h2>&indexing-header;</h2>
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="flat-mode" select="1" />
					<xsl:with-param name="search-show" select="0" />
					<xsl:with-param name="ignore-hierarchy" select="1" />
					<xsl:with-param name="control-type-id" select="85" />
					<xsl:with-param name="disable-csv-buttons" select="1" />
					<xsl:with-param name="control-params" select="'filters'" />
					<xsl:with-param name="show-toolbar" select="1"/>
					<xsl:with-param name="js-has-checkboxes" select="'true'"/>
					<xsl:with-param name="toolbarmenu">
						<![CDATA[
							toolbarMenu = ['delIndex'];
						]]>
					</xsl:with-param>
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
					<div style="pull-left">
						<a class="btn color-blue loc-left" id="add_to_index">&indexing-add-category;</a>
					</div>
				</div>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>

		<script type="text/javascript" src="/styles/skins/modern/data/modules/catalog/filters/admin.indexing.js?{$system-build}"/>
		<script type="text/javascript" src="/styles/skins/modern/data/modules/catalog/filters/loadingoverlay/js/loadingoverlay.min.js?{$system-build}"/>
	</xsl:template>

	<!-- Шаблон вкладки настроек "Типы цен торговых предложений" -->
	<xsl:template match="result[@method = 'tradeOfferPriceTypes']">
		<xsl:call-template name="ui-new-table">
			<xsl:with-param name="configUrl">/admin/catalog/flushTradeOfferPriceTypeListConfig/.json</xsl:with-param>
			<xsl:with-param name="toolbarFunction">CatalogModule.getTradeOfferPriceTypeListToolBarFunction()</xsl:with-param>
			<xsl:with-param name="toolbarMenu">CatalogModule.getTradeOfferPriceTypeListToolBarMenu()</xsl:with-param>
			<xsl:with-param name="perPageLimit">20</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

</xsl:stylesheet>