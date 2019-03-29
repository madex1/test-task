<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@module = 'config' and @method = 'menu']">
		<div class="other-modules">
			<div class="modules connect " umi-key="menuTop">
				<xsl:apply-templates select="items/item[not(@type) ]" />
			</div>
			<div class="modules connect" umi-key="menuBottom">
				<xsl:apply-templates select="items/item[@type = 'system']" />
				<xsl:apply-templates select="items/item[@type = 'util']" />
			</div>
			<div class="modules-buy">
				<div class="topic">UMI.Market</div>
				<div class="description">
					<p>Если вы не нашли в системе нужный функционал, то, вероятно,
					его можно скачать бесплатно или купить в магазине готовых решений UMI.Market.</p>
					<p>В нём представлены сотни разнообразных модулей, расширений и готовых шаблонов для сайтов на UMI.CMS.
					Если нужного вам функционала не окажется и там, напишите нам на <a href="mailto:sales@umisoft.ru">sales@umisoft.ru</a>,
					и, возможно, он очень скоро появится.</p></div>
				<a class="btn color-blue" href="//market.umi-cms.ru" id="market" target="_blank"> Открыть </a>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'config' and @method = 'menu']/items/item">
		<a class="module" href="{$lang-prefix}/admin/{@name}/" umi-module="{@name}" >
			<span class="big-ico" style="background-image: url('/images/cms/admin/modern/icon/{@name}.png');"></span>
			<span class="title"><xsl:value-of select="@label" /></span>
		</a>
	</xsl:template>
</xsl:stylesheet>
