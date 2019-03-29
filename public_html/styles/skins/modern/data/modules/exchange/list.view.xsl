<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/exchange" [
	<!ENTITY sys-module        'exchange'>
	<!ENTITY sys-method-add        'add'>
	<!ENTITY sys-method-edit    'edit'>
	<!ENTITY sys-method-del        'del'>

]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон вкладки "Импорт данных" -->
	<xsl:template match="result[@method = 'import']/data">
		<script src="/styles/skins/modern/design/js/{$module}.list.view.js" />

		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<a href="{$lang-prefix}/admin/&sys-module;/add/import/" class="btn color-blue">
					<xsl:text>&label-add-import;</xsl:text>
				</a>
				<a href="#" id="doImport" onclick="exchangeDoImport(); return false;" class="btn color-blue">
					<xsl:text>&label-import-do;</xsl:text>
				</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="control-params" select="$method" />
					<xsl:with-param name="content-type">objects</xsl:with-param>

					<xsl:with-param name="js-add-buttons">
						createAddButton(
							$('#doImport')[0],	oTable, '#', ['*']
						);
					</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Экспорт данных" -->
	<xsl:template match="result[@method = 'export']/data">
		<script type="text/javascript">
			var lang_prefix = '<xsl:value-of select="$lang-prefix" />';
		</script>

		<script src="/styles/skins/modern/design/js/{$module}.list.view.js" />

		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<a href="{$lang-prefix}/admin/&sys-module;/add/export/" class="btn color-blue">
					<xsl:text>&label-add-export;</xsl:text>
				</a>
				<a href="#" id="doExport" onclick="exchangeDoExport(); return false;" class="btn color-blue">
					<xsl:text>&label-export-do;</xsl:text>
				</a>
				<a href="#" id="prepareExport" onclick="prepareExport(); return false;" class="btn color-blue">
					<xsl:text>&js-exchange-prepare-export;</xsl:text>
				</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="control-params" select="$method" />
					<xsl:with-param name="content-type">objects</xsl:with-param>

					<xsl:with-param name="js-add-buttons">
						createAddButton(
							$('#doExport')[0],	oTable, '#', ['*']
						);
						createAddButton(
							$('#prepareExport')[0],	oTable, '#', ['*']
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
