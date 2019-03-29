<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
>

	<!-- Шаблон вкладки "Производительность" модуля "Конфигурация" -->
	<xsl:template match="/result[@method = 'cache']/data">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">
				<form id="{../@module}_{../@method}_form" action="do/" method="post">
					<xsl:apply-templates select="group" mode="settings.modify.table" />

					<div class="row">
						<xsl:call-template name="std-form-buttons-settings" />
					</div>
				</form>

				<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
			</div>

			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'cache']//group" mode="settings.modify.table">
		<xsl:if test="position() = 1">
			<script type="text/javascript" language="javascript" src="/js/jquery/jquery.cookie.js" />
			<script type="text/javascript" src="/styles/skins/modern/data/modules/config/cache.js" />
		</xsl:if>

		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="@label" />
				</h3>
			</div>
			<div class="content">
				<table class="btable btable-striped middle-align">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify.table" />
						<xsl:if test="@name = 'test'">
							<tr>
								<td>
									<div class="speedmark-link">
										<a href="#"
										   onclick="return speedmark.start()">&js-check-speedmark;
										</a>
									</div>
									<div class="speedmark" style="display:none;">
										&js-system-speedmark;:
										<span id="speedmark_avg" />
										<p>&js-index-speedmark;</p>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="server_load">
										<xsl:value-of select="php:function('get_server_load','')" />
									</div>
								</td>
							</tr>
						</xsl:if>
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'status' and @name = 'reset']" mode="settings.modify.table">
		<tr>
			<td />
			<td>
				<input type="button" class="btn color-blue" value="{@label}" id="cache_reset" />
			</td>
		</tr>

		<xsl:if test="not($demo)">
			<script>
				jQuery('#cache_reset').click(function(){
				location.pathname = '<xsl:value-of select="$lang-prefix" />/admin/config/cache/reset/';
				return false;
				});
			</script>
		</xsl:if>
		<xsl:if test="$demo">
			<script>
				jQuery('#cache_reset').click(function(){
				jQuery.jGrowl('<p>В демонстрационном режиме эта функция недоступна</p>', {
				'header': 'UMI.CMS',
				'life': 10000
				});
				return false;
				});
			</script>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
