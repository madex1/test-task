<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<!-- Шаблоны панели выбора языковой версии сайтов -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Пустая панель выбора языковой версии сайтов -->
	<xsl:template match="udata[@module = 'system' and @method = 'getLangsList']" />

	<!-- Панель выбора языковой версии сайтов -->
	<xsl:template match="udata[@module = 'system' and @method = 'getLangsList' and count(items/item) &gt; 1]">
		<div id="lang" class="lang menu">
			<div class="selected">
				<xsl:value-of select="/udata/items/item[@is-current = '1']" />
			</div>

			<ul>
				<xsl:apply-templates select="items/item[not(@is-current)]" mode="lang_list_item" />
			</ul>
		</div>
	</xsl:template>

	<!-- Язык системы, кроме текущего -->
	<xsl:template match="item" mode="lang_list_item">
		<li>
			<a href="/{@prefix}/admin/{$module}/{$method}">
				<xsl:value-of select="." />
			</a>
		</li>
	</xsl:template>
</xsl:stylesheet>
