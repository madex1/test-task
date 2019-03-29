<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Вкладка "Страницы с битыми ссылками" -->
	<xsl:template match="/result[@method = 'getBrokenLinks']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left ndc-buttons">
						<a id="findBadLinks" class="btn color-blue loc-left">&label-button-find-bad-links;</a>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<div id="tableWrapper"/>
						<script src="/js/underscore-min.js"/>
						<script src="/js/backbone-min.js"/>
						<script src="/js/twig.min.js"/>
						<script src="/js/backbone-relational.js"/>
						<script src="/js/backbone.marionette.min.js"/>
						<script src="/js/app.min.js"/>
						<script>
							<![CDATA[
							(function(){
								new umiDataController({
									container:'#tableWrapper',
									prefix:'/admin/seo',
									module:'seo',
									dataProtocol: 'json',
									configUrl:'/admin/seo/flushBrokenLinksDatasetConfiguration/.json',
									toolbarFunction: {
										view: {
											name: 'view',
											className: 'i-vision',
											hint: getLabel('js-label-view-button'),
											init : function(button,item) {
 												if (dc_application.toolbar.selectedItemsCount == 1) {
 													dc_application.toolbar.enableButtons(button);
												} else {
													dc_application.toolbar.disableButtons(button);
												}
											},
											release: function (button,item) {
												var selectedItem = dc_application.toolbar.selectedItems[0];
												var selectedItemId = dc_application.unPackId(selectedItem.attributes.id);
												SeoModule.showBadLinkSources(selectedItemId);
												return false;
											}
										}
									},
									toolbarMenu:['view']
								}).start();
							})()
							]]>
						</script>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
		<xsl:call-template name="seo.js.templates"/>
	</xsl:template>

	<!-- Шаблоны для js -->
	<xsl:template name="seo.js.templates">
		<script id="bad-link-sources-template" type="text/template">
			<![CDATA[
				<h2 class="sources-header">
					<%= header %>
				</h2>
				<ul class="sources">
					<% _.each(sources, function(source){ %>
						<li>
							<%= getLabel('js-label-place-type-' + source.type) %>

							<% if (source.type == "object") { %>
    							<a href="<%= source.place %>"><%= source.place %></a>
							<% } else { %>
								<%= source.place %>
							<% } %>
						</li>
					<% }); %>
				</ul>
			]]>
		</script>
		<script id="bad-links-search-template" type="text/template">
			<![CDATA[
				<h2 id="<%= id %>" class="links-search-header">
					<%= message %>
				</h2>
				<p class="loading-wrapper">
					<img src="/styles/skins/modern/design/img/process.gif" />
				</p>
			]]>
		</script>
	</xsl:template>

	<!-- Вкладка "Страницы с незаполненными meta тегами" -->
	<xsl:template match="/result[@method = 'emptyMetaTags']/data[@type = 'list' and @action = 'view']">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="control-params">emptyMetaTags</xsl:with-param>
					<xsl:with-param name="allow-drag">0</xsl:with-param>
					<xsl:with-param name="ignore-hierarchy">1</xsl:with-param>
					<xsl:with-param name="flat-mode">1</xsl:with-param>
					<xsl:with-param name="hide-csv-import-button">1</xsl:with-param>
					<xsl:with-param name="content-type">pages</xsl:with-param>
					<xsl:with-param name="show-toolbar">0</xsl:with-param>
					<xsl:with-param name="search-show">0</xsl:with-param>
					<xsl:with-param name="search-advanced-allow">0</xsl:with-param>
					<xsl:with-param name="menu"><![CDATA[
						var menu = [
							['view-page', 'view', ContextMenu.itemHandlers.viewElement],
							['edit-page', 'ico_edit', ContextMenu.itemHandlers.editItem],
							['copy-url', false, ContextMenu.itemHandlers.copyUrl]
						];
					]]></xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон вкладки "Яндекс.Вебмастер" -->
	<xsl:template match="/result[@module = 'seo' and @method = 'webmaster']/data[@type = 'list' and @action = 'view']">
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-new-table">
					<xsl:with-param name="controlParam">sites</xsl:with-param>
					<xsl:with-param name="configUrl">/admin/seo/flushSiteListConfig/.json</xsl:with-param>
					<xsl:with-param name="toolbarFunction">SeoModule.getSiteListToolBarFunctions()</xsl:with-param>
					<xsl:with-param name="toolbarMenu">SeoModule.getSiteListToolBarMenu()</xsl:with-param>
					<xsl:with-param name="showSelectButtons">0</xsl:with-param>
					<xsl:with-param name="showResetButtons">0</xsl:with-param>
					<xsl:with-param name="pageLimits">SeoModule.getSiteListPageLimitList()</xsl:with-param>
					<xsl:with-param name="perPageLimit">250</xsl:with-param>
				</xsl:call-template>
			</div>
			<div style="">&footer-webmaster-text;
				<a href="https://webmaster.yandex.ru/" target="_blank">&footer-webmaster-link;</a>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
