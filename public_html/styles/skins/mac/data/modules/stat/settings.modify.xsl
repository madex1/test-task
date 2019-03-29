<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'settings' and @action = 'modify']">
		<form method="post" action="do/" enctype="multipart/form-data">
			<xsl:apply-templates select="." mode="settings.modify" />
		</form>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>
	
	<xsl:template match="/result[@method = 'config']/data[@type = 'settings' and @action = 'modify']">
		<form method="post" action="do/" enctype="multipart/form-data">
			<xsl:apply-templates select="." mode="settings.modify" />
		</form>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		<script type="text/javascript" language="javascript">
		  <![CDATA[
			function ClearButtonClick () {
				var callback = function () {
					window.location.href = "]]><xsl:value-of select="$lang-prefix"/>/admin/stat/clear/do<![CDATA[";
				};

				openDialog({
					title       : "]]>&label-stat-clear;<![CDATA[",
					text        : "]]>&label-stat-clear-confirm;<![CDATA[",
					OKText      : "]]>&label-clear;<![CDATA[",
					cancelText  : "]]>&label-cancel;<![CDATA[",
					OKCallback	: callback
				});                

				return false;
			}
		  ]]>
		  </script>
		<div class="panel properties-group">
			<div class="header">
				<span>&label-stat-clear;</span>
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				&label-stat-clear-help;
				<div class="buttons">
					<div>
						<input type="button" value="&label-stat-clear;" onclick="javascript:ClearButtonClick();" />
						<span class="l" />
						<span class="r" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template match="/result/data/group[@name='statDomainConfig']" mode="settings.modify">
		<xsl:if test="count(option) > 1">
			<div class="panel properties-group">
				<div class="header">
					<span>
						<xsl:value-of select="@label" />
					</span>
					<div class="l" /><div class="r" />
				</div>
				<div class="content">
					<table class="tableContent">
						<tbody>
							<xsl:apply-templates select="option" mode="settings.modify-nolabel" />
						</tbody>
					</table>
					<xsl:call-template name="std-save-button" />
				</div>
			</div>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="option" mode="settings.modify-nolabel">
		<tr>
			<td class="eq-col">
				<label for="{@name}">
					<xsl:value-of select="substring-after(@label,'collect-')" />
				</label>
			</td>
			
			<td>
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</td>
		</tr>
	</xsl:template>
	
	
</xsl:stylesheet>