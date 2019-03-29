<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон страницы со статистикой счетчика из "Яндекс.Метрика" -->
	<xsl:template match="/result[@method = 'getCounterStat']/data[@type = 'form' and @action = 'modify']">
		<div class="tabs module">
			<xsl:apply-templates select="data/section"  mode="header"/>
		</div>
		<div class="tabs-content module {$module}-module">
			<div class="section selected">
				<xsl:apply-templates select="$errors" />
				<div class="tabs editing notextselect">
					<xsl:apply-templates select="data/section[@selected = '1']/history"  mode="header"/>
					<xsl:apply-templates select="data/section[@selected = '1']/table"  mode="header"/>
					<xsl:apply-templates select="data/section[@selected = '1']/pie-chart"  mode="header"/>
				</div>
				<div class="location">
					<div class="loc-left">
						<form id="statdate_settings" method="post">
							<div class="buttons">
								<div>
									<input type="submit" value="&label-action-filter;" class="btn color-blue btn-small"/>
								</div>
							</div>
							<div class="datePicker">
								<span>&label-period;:</span>
								<label for="start_date">
									<acronym>&label-from;</acronym>
									<input type="text" class="stat-date-filter default" value="{data/@date_from}" name="fromDate" umi:date-only="1"/>
								</label>
							</div>
							<div class="datePicker">
								<label for="end_date">
									<acronym>&label-to;</acronym>
									<input type="text" class="stat-date-filter default" value="{data/@date_to}" name="toDate" umi:date-only="1"/>
								</label>
							</div>
						</form>
					</div>
				</div>
				<div class="layout">
					<div class="column">
						<div class="tabsStat">
							<xsl:apply-templates select="data/section[@selected = '1']" mode="content"/>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="/styles/skins/modern/design/js/chart/Chart.min.js"/>
	</xsl:template>

	<!-- Шаблон заголовка группы статистической информации -->
	<xsl:template match="section" mode="header">
		<div class="section">
			<a href="{concat($lang-prefix, '/admin/stat/getCounterStat/', ../@counter_id, '/', @id, '/', @default-subsection)}">
				<xsl:value-of select="@label"/>
			</a>
		</div>
	</xsl:template>

	<!-- Шаблон заголовка выбранной группы статистической информации -->
	<xsl:template match="section[@selected = '1']" mode="header">
		<div class="section selected">
			<a href="{concat($lang-prefix, '/admin/stat/getCounterStat/', ../@counter_id, '/', @id, '/', @default-subsection)}">
				<xsl:value-of select="@label"/>
			</a>
		</div>
	</xsl:template>

	<!-- Шаблон секции -->
	<xsl:template match="section[history or table or pie-chart]" mode="content">
		<div class="panel-settings">
			<div class="content">
				<xsl:choose>
					<xsl:when test="history[@need-to-show = '1' and @selected = '1']">
						<xsl:apply-templates select="history[@need-to-show = '1' and @selected = '1']" mode="line.chart" />
					</xsl:when>
					<xsl:when test="table[@need-to-show = '1' and @selected = '1']">
						<xsl:apply-templates select="table[@need-to-show = '1' and @selected = '1']" mode="stat.table" />
					</xsl:when>
					<xsl:when test="pie-chart[@need-to-show = '1' and @selected = '1']">
						<div>
							<xsl:apply-templates select="pie-chart[@need-to-show = '1' and @selected = '1']" mode="pie.chart" />
						</div>
					</xsl:when>
					<xsl:otherwise>
						<div class="title">
							<h3>Нет информации за указанный период</h3>
						</div>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон заголовка подгруппы статистической информации -->
	<xsl:template match="history|table|pie-chart" mode="header">
		<div class="section">
			<a href="{concat($lang-prefix, '/admin/stat/getCounterStat/', ../../@counter_id, '/', ../@id, '/', @id)}">
				<xsl:value-of select="@label"/>
			</a>
		</div>
	</xsl:template>

	<!-- Шаблон заголовка подгруппы статистической информации -->
	<xsl:template match="history[@selected = '1']|table[@selected = '1']|pie-chart[@selected = '1']" mode="header">
		<div class="section selected">
			<a href="{concat($lang-prefix, '/admin/stat/getCounterStat/', ../../@counter_id, '/', ../@id, '/', @id)}">
				<xsl:value-of select="@label"/>
			</a>
		</div>
	</xsl:template>

	<!-- Шаблон таблицы со статистической информацией -->
	<xsl:template match="table" mode="stat.table">
		<table class="btable btable-striped bold-head">
			<thead>
				<xsl:apply-templates select="row[position() = 1]" mode="stat.table"/>
			</thead>
			<tbody>
				<xsl:apply-templates select="row[position() != 1]" mode="stat.table"/>
			</tbody>
		</table>
	</xsl:template>

	<!-- Шаблон заголовка таблицы со статистической информацией -->
	<xsl:template match="row[position() = 1]" mode="stat.table">
		<tr>
			<xsl:apply-templates select="cell" mode="stat.table.cell.header"/>
		</tr>
	</xsl:template>

	<!-- Шаблон строки таблицы со статистической информацией -->
	<xsl:template match="row[position() != 1]" mode="stat.table">
		<tr>
			<xsl:apply-templates select="cell" mode="stat.table.cell"/>
		</tr>
	</xsl:template>

	<!-- Шаблон ячейки заголовка таблицы со статистической информацией -->
	<xsl:template match="cell" mode="stat.table.cell.header">
		<th>
			<xsl:value-of select="@value"/>
		</th>
	</xsl:template>

	<!-- Шаблон ячейки строки таблицы со статистической информацией -->
	<xsl:template match="cell" mode="stat.table.cell">
		<td>
			<xsl:value-of select="@value"/>
		</td>
	</xsl:template>

	<!-- Шаблон группы круговых диаграмм-->
	<xsl:template match="pie-chart" mode="pie.chart">
		<xsl:apply-templates select="chart" mode="pie.chart">
			<xsl:with-param name="id" select="@id"/>
		</xsl:apply-templates>
	</xsl:template>

	<!-- Шаблон круговой диаграммы -->
	<xsl:template match="chart" mode="pie.chart">
		<xsl:param name="id" />
		<div class="col-md-6">
			<xsl:call-template name="pie.chart">
				<xsl:with-param name="id" select="concat($id, generate-id())"/>
				<xsl:with-param name="text" select="@header"/>
				<xsl:with-param name="datasets" select="dataset"/>
			</xsl:call-template>
		</div>
	</xsl:template>

</xsl:stylesheet>