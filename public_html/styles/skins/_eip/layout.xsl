<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:variable name="system-build" select="/result/@system-build" />

	<xsl:template match="/">
		<html>
			<head>
				<title>
					<xsl:text>&cms-name; - </xsl:text>
					<xsl:value-of select="$header" />
				</title>

				<!-- Global variables -->
				<script type="text/javascript">
					var pre_lang = '<xsl:value-of select="$lang-prefix" />';
				</script>

				<script type="text/javascript" src="/js/jquery/jquery.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/js/jquery/jquery-migrate.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/js/jquery/jquery-ui.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/js/jquery/jquery.umipopups.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/js/jquery/jquery.contextmenu.js?{$system-build}" charset="utf-8" />

				<!-- Include labels -->
				<script type="text/javascript" src="/ulang/{$iface-lang}/common/content/date/{$module}?js" charset="utf-8" />


				<!-- Umi ui controls -->
				<xsl:if test="/result/data[@type = 'list']">
					<script	type="text/javascript" src="/js/smc/compressed.js?{$system-build}"></script>
				</xsl:if>

				<!-- umi ui css -->
				<link type="text/css" rel="stylesheet" href="/styles/common/css/compiled.css?{$system-build}"/>
				
				<script type="text/javascript" src="/styles/common/js/file.control.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/relation.control.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/permissions.control.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/symlink.control.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/permissions.control.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/utilities.js?{$system-build}" charset="utf-8" />

				<script	type="text/javascript" src="/js/cms/admin.js?{$system-build}"></script>
				<script	type="text/javascript" src="/js/cms/wysiwyg/wysiwyg.js?{$system-build}"></script>
				<script type="text/javascript">
					uAdmin({
						'csrf': '<xsl:value-of select="//@csrf" />'
					});
				</script>

				<script	type="text/javascript" src="/styles/skins/_eip/js/popup.js?{$system-build}"></script>

				<link href="/styles/skins/_eip/css/permissions.control.css?{$system-build}" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/relation.control.css?{$system-build}" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/symlink.control.css?{$system-build}" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/popup.css?{$system-build}" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/popup_page.css?{$system-build}" rel="stylesheet" type="text/css" />
			</head>
			
			<body>
				<xsl:apply-templates select="$errors" />
				<xsl:apply-templates select="result" />
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
