<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:include href="settings-sphinx.xsl" />

	<xsl:template match="data[@type = 'settings' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.view"/>
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>

		<xsl:if test="/result[@method = 'config']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
		<xsl:if test="/result[@module = 'content' and @method = 'content_control']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
		<xsl:if test="/result[@module = 'emarket' and @method = 'social_networks']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
		<xsl:if test="/result[@module = 'search' and @method = 'index_control']">
			<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		</xsl:if>
	</xsl:template>

	<xsl:template match="group[@name = 'info']" mode="settings.view">
		<script type="text/javascript">
			function rebuildSearchIndex() {
				var partialQuery = function (lastId) {
					if(window.session) {
						window.session.startAutoActions();
					}
					
					$.get('/admin/search/partialReindex.xml?lastId=' + lastId, null, function (data) {
						
						var current = $('index-status', data).attr('current');
						var total = $('index-status', data).attr('total');
						var lastId = $('index-status', data).attr('lastId');

						changeProgress(current, total)

						if (current) {
							partialQuery(lastId);
						} else {
							setTimeout(function() {window.location.reload();}, 500);
						}
					});
				};
				
				partialQuery(0);
			
				openDialog('', getLabel('js-search-reindex-header'), {
					'html': <![CDATA['<div class="search-reindex">' + getLabel('js-search-reindex') + '<p id="search-reindex-log" />' +
							'<div class="progress">' +
								'<div class="progress-bar progress-bar-info progress-bar-striped"' +
									  'role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 0%">' +
									'<span>0%</span>' +
								'</div>' +
							'</div></div></div>',]]>
					'stdButtons': false
				});
				return false;
			}

			function changeProgress(current, total) {
				var $progress = jQuery('.progress', '.search-reindex');
				var $progressBar = jQuery('.progress-bar', $progress);
				var currentPercentValue = parseFloat(current / total * 100, 2);
				var currentPercent = currentPercentValue.toFixed(1) + '%';

				$progressBar.attr('aria-valuemax', total);
				$progressBar.attr('aria-valuenow', current);
				$progressBar.css('width', currentPercent);
				jQuery('span', $progressBar).text(currentPercent);
			}
		</script>
		<div class="panel-settings">
			<div class="title">
				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<table class="btable btable-bordered btable-striped">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.view.table" />
					</tbody>
				</table>

				<div class="">
					<div class="pull-right" style="margin-left: 20px;">
						<input class="btn color-blue" type="button" value="&label-search-reindex;"
							onclick="return rebuildSearchIndex();" />
					</div>
					
					<div class="pull-right">
						<a class="btn color-blue" href="{$lang-prefix}/admin/search/truncate/" >&label-search-empty;</a>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group" mode="settings.modify.table">
		<div class="panel-settings">
			<div class="title">
				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<table class="btable btable-striped middle-align">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.view.table" />
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option" mode="settings.view.table">
		<xsl:param name="title_column_width" select="'50%'" />
		<xsl:param name="value_column_width" select="'50%'"/>

		<tr>
			<td width="{$title_column_width}">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</td>

			<td width="{$value_column_width}">
				<xsl:apply-templates select="." mode="settings.view.option" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="option[@type = 'status']" mode="settings.view.option">
		<xsl:value-of select="value" />
	</xsl:template>

</xsl:stylesheet>