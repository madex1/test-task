<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="group[@name = 'generate-config']" mode="settings.modify">
		<script type="text/javascript">
			<![CDATA[
			function ShowMsg(title, message) {
				openDialog({
					'title': title,
					'text': message
				});
				$('.eip_buttons .back').css('display','none');
			}
			$(function (){
				$('#generateView').click(function(){
					var genConfig = false;
					$(document).on('click', '#genConfigSphinx', function(){
						if ($(this + ":checked").length > 0) {
							genConfig = true;
						} else {
							genConfig = false;
						}
					});

					$.getJSON('/admin/search/isExistsConfig/', function (data) {
						var isConfig = data.result.response;
						genConfig = !isConfig;

						var genConfigText = '';
						if (isConfig) {
							genConfigText = '<label>' +
												'<input id="genConfigSphinx" type="checkbox" name="genConfigSphinx" /> ' + getLabel('js-sphinx-config-rewrite') +
											'</label>';
						}

						openDialog({
							'title': getLabel('js-search-generate-config'),
							'text': getLabel('js-sphinx-generate-warning') + '<br/>' + genConfigText,
							'OKCallback' : function() {
								var message = [getLabel('js-sphinx-build-view-error'), getLabel('js-sphinx-build-config-error')];

								if (!genConfig) {
									$.getJSON('/admin/search/generateView/', function (data) {
										if (data.result.message !== undefined) {
											message[0] = data.result.message;
										}
										ShowMsg(getLabel('js-search-generate-config'), message[0]);
									})
								} else {
									var newDef =$.when(
										$.getJSON('/admin/search/generateView/', function (data) {
											if (data.result.message !== undefined) {
												message[0] = data.result.message;
											}
										}),
										$.getJSON('/admin/search/generateSphinxConfig/', function (data) {
											if (data.result.message !== undefined) {
												message[1] = data.result.message;
											}
										})
									);
									newDef.done(function(){
										ShowMsg(getLabel('js-search-generate-config'), message[0] + '<br/>' + message[1]);
									}).fail(function(){
										ShowMsg(getLabel('js-search-generate-config'), message[0] + '<br/>' + message[1]);
									});
								}
							}
						});
					});

					return false;
				});
			});
			]]>
		</script>

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
						<p>&generate-view-and-config;</p>
					</tbody>
				</table>

				<div class="buttons">
					<div>
						<input type="submit" value="&label-build-configuration;" id="generateView" />
						<span class="l" /><span class="r" />
					</div>
				</div>
			</div>
		</div>

	</xsl:template>
</xsl:stylesheet>