<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон вкладки "Яндекс.Метрика" -->
	<xsl:template match="/result[@module = 'stat' and @method = 'yandexMetric']/data[@type = 'list' and @action = 'view']">
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-new-table">
					<xsl:with-param name="controlParam">counters</xsl:with-param>
					<xsl:with-param name="configUrl">/admin/stat/flushCounterListConfig/.json</xsl:with-param>
					<xsl:with-param name="toolbarFunction">StatModule.getCounterListToolBarFunctions()</xsl:with-param>
					<xsl:with-param name="toolbarMenu">StatModule.getCounterListToolBarMenu()</xsl:with-param>
					<xsl:with-param name="showSelectButtons">0</xsl:with-param>
					<xsl:with-param name="showResetButtons">0</xsl:with-param>
					<xsl:with-param name="pageLimits">StatModule.getCounterListPageLimitList()</xsl:with-param>
					<xsl:with-param name="perPageLimit">500</xsl:with-param>
				</xsl:call-template>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>