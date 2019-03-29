<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
	<xsl:template match="udata[@module = 'users' and @method = 'getFavourites']">
		<div class="fixed_wrapper fixed">
			<div class="basic-modules-btn hidden" id="basic-modules-up">
				<img src="/styles/skins/modern/design/img/dashboard_up.png"/>
			</div>
			<div class="scroll-wrapper" id="basic-modules-scroll-container">
				<div class="basic-modules connect" umi-key="dockItems">
					<xsl:apply-templates select="items/item"/>
				</div>
			</div>
			<div class="basic-modules-btn hidden" id="basic-modules-down">
				<img src="/styles/skins/modern/design/img/dashboard_down.png"/>
			</div>
		</div>

	</xsl:template>

	<xsl:template match="udata[@module = 'users' and @method = 'getFavourites']/items/item">
		<a class="module" href="{$lang-prefix}/admin/{@id}/" umi-module="{@id}">
			<xsl:if test="$module = @id">
				<xsl:attribute name="class"><xsl:text>module selected</xsl:text></xsl:attribute>
			</xsl:if>
			<span class="big-ico" style="background-image: url('/images/cms/admin/modern/icon/{@id}.png');"></span>
			<span class="title"><xsl:value-of select="@label" /></span>
		</a>
	</xsl:template>
</xsl:stylesheet>