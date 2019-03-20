<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common/config">

<xsl:stylesheet version="1.0" exclude-result-prefixes="xlink"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:xlink="http://www.w3.org/TR/xlink">
	<xsl:output	encoding="utf-8" indent="yes" method="html" />

	<!-- Шаблон списка решений с фильтрами по категориям и типам с поиском -->
	<xsl:template match="/result[@module = 'config' and @method = 'getFullSolutionList']/data[@type = 'list' and @action = 'view']">
		<link rel="stylesheet" type="text/css" href="https://install.umi-cms.ru/style.css?{../@system-build}" />
		<link rel="stylesheet" type="text/css" href="/styles/skins/solutions/css/solutions.css?{../@system-build}" />
		<script type="text/javascript" charset="utf-8" src="/ulang/{../@lang}/common/content/date/{../@module}?js;{../@system-build}" />
		<script type="text/javascript" charset="utf-8" src="/js/jquery/jquery.js?{../@system-build}" />
		<script type="text/javascript" charset="utf-8" src="/js/underscore-min.js?{../@system-build}" />
		<script type="text/javascript" charset="utf-8" src="/styles/skins/solutions/js/solutionList.js?{../@system-build}" />
		<div class="fourth_block shadow_some select_umi_demosite">
			<div class="choose_umisite">
				<div class="category">
					<xsl:apply-templates select="categories" />
				</div>
				<div class="umiru_body">
					<xsl:apply-templates select="types" />
					<div class="doc">
						<input class="search" placeholder="&search;" value="" type="text"/>
						<input value="&find;" class="next_step_submit run_search" type="submit"/>
						<input value="&reset-all-filters;" class="next_step_submit reset_filter" type="button"/>
						<a href="{market_link}" class="back_step_submit next_step_submit premium" target="_blank">
							<xsl:text>&label-premium-solutions;</xsl:text>
						</a>
					</div>
					<xsl:apply-templates select="solutions" />
				</div>
				<div class="clear"/>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон списка фильтров по категориям -->
	<xsl:template match="categories">
		<ul>
			<xsl:apply-templates select="category" />
		</ul>
	</xsl:template>

	<!-- Шаблон категории -->
	<xsl:template name="category" match="category">
		<li>
			<a href="#" rel="{@id}">
				<xsl:value-of select="@title"/>
			</a>
		</li>
	</xsl:template>

	<!-- Шаблон списка фильтров по типам -->
	<xsl:template match="types">
		<div>
			<table class="parts_nav navigation">
				<tbody>
					<tr>
						<xsl:apply-templates select="type" />
					</tr>
				</tbody>
			</table>
		</div>
	</xsl:template>

	<!-- Шаблон типа -->
	<xsl:template match="type">
		<td rel="{@id}">
			<div class="drop_down">
				<a href="#">
					<xsl:value-of select="@title"/>
				</a>
			</div>
		</td>
	</xsl:template>

	<!-- Шаблон списка решений -->
	<xsl:template match="solutions">
		<div class="site_holder">
			<xsl:apply-templates select="solution" />
			<xsl:call-template name="empty.solution.list" />
		</div>
	</xsl:template>

	<!-- Шаблон сообщения о том, что решения не найдены -->
	<xsl:template name="empty.solution.list">
		<div id="empty_solution_list" class="disabled">
			<p>&label-empty-solution-list;</p>
		</div>
	</xsl:template>

	<!-- Шаблон решения -->
	<xsl:template match="solution">
		<div class="site enabled" data-type-id="{@typeId}" data-category-id-list="{@categoryIdList}" data-id="{@id}" data-title="{@lowerCaseTitle}"
			 data-name="{@name}" data-keywords="{@keywords}">
			<div>
				<img src="{@thumb}"/>
				<a href="#" class="choose next_step_submit" data-alt-text="&cancel;">&select;</a>
				<xsl:apply-templates select="." mode="demo"/>
			</div>
			<p>&site-number; <xsl:value-of select="@id" /><br/><span><xsl:value-of select="@title" /></span></p>
		</div>
	</xsl:template>

	<!-- Шаблон кнопки "В демо центр" -->
	<xsl:template match="solution[@isPartner = '0' and @isDemoSite = '0']" mode="demo">
		<a href="https://demo.umi-cms.ru/#!/create/template:{@name}" class="goto_demo back_step_submit" target="_blank">&to-demo-center;</a>
	</xsl:template>

</xsl:stylesheet>