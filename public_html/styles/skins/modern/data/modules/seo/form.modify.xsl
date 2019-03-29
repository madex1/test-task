<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


	<!-- Шаблон страницы с информацией о сайте, добавленном в Яндекс.Вебмастер -->
	<xsl:template match="/result[@method = 'getSiteInfo']/data[@type = 'form' and @action = 'modify']">
		<div class="tabs-content module {$module}-module">
			<div class="section selected">

				<xsl:apply-templates select="$errors" />

				<div class="layout">
					<div class="column">

						<xsl:apply-templates select="data/section" />

					</div>
				</div>
			</div>
		</div>
		<script src="/styles/skins/modern/design/js/chart/Chart.min.js"/>
	</xsl:template>

	<!-- Шаблон секции -->
	<xsl:template match="section[
		history[@need-to-show = '1'] or top[@need-to-show = '1'] or external_link_list[@need-to-show = '1']
	]">
		<div class="panel-settings">
			<div class="title">
				<h3><xsl:value-of select="@label"/></h3>
			</div>
			<div class="content">
				<xsl:apply-templates select="history[@need-to-show = '1']" mode="line.chart" />
				<xsl:apply-templates select="top[@need-to-show = '1']" mode="bar.chart" />
				<xsl:apply-templates select="external_link_list[@need-to-show = '1']" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон списка внешних ссылок -->
	<xsl:template match="external_link_list">
		<xsl:call-template name="ui-new-table">
			<xsl:with-param name="controlParam">external_links</xsl:with-param>
			<xsl:with-param name="configUrl"
							select="concat('/admin/seo/flushExternalLinksListConfig/', ../../@site_id, '/.json')"/>
			<xsl:with-param name="toolbarMenu">[]</xsl:with-param>
			<xsl:with-param name="showSelectButtons">0</xsl:with-param>
			<xsl:with-param name="showResetButtons">0</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

</xsl:stylesheet>