<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'lists']/data[@type = 'list' and @action = 'view']">
		<script src="/styles/skins/modern/data/modules/umiRedirects/removeAllRedirects.js?{$system-build}" />

		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left" style="bottom:0px;">
						<a id="addRedirectButton" class="btn color-blue loc-left"
						   href="{$lang-prefix}/admin/umiRedirects/add/">&label-button-add-redirect;</a>

						<a id="removeAllRedirectsButton" class="btn color-blue loc-left">
							&label-button-remove-all-redirects;
						</a>
					</div>

					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<div id="tableWrapper"></div>
						<script src="/js/underscore-min.js"></script>
						<script src="/js/backbone-min.js"></script>
						<script src="/js/twig.min.js"></script>
						<script src="/js/backbone-relational.js"></script>
						<script src="/js/backbone.marionette.min.js"></script>
						<script src="/js/app.min.js"></script>
						<script>
							(function(){
								new umiDataController({
								container:'#tableWrapper',
								prefix:'/admin/umiRedirects',
								module:'umiRedirects',
								controlParam:'',
								dataProtocol: 'json',
								domain:1,
								lang:1,
								configUrl:'/admin/umiRedirects/flushDataConfig/.json',
								debug:true
								}).start();
							})()
						</script>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>

		</div>

	</xsl:template>

</xsl:stylesheet>
