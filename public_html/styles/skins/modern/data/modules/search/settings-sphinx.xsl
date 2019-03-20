<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="group[@name = 'generate-config']" mode="settings.view">
		<script type="text/javascript">
			<![CDATA[
			function ShowMsg(title, message) {
				openDialog('', title, {
					width: 350,
					html: message,

				});
			}
			$(function (){
				$('#generateView').click(function(){
					var genConfig = false;
					$(document).on('click', '#genConfigSphinx', function(){
						if ($(this).filter(":checked").length > 0) {
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
							genConfigText = '<div class="checkbox sphinx"><input id="genConfigSphinx" type="checkbox" name="genConfigSphinx" class="checkbox"/></div> <span>' +
							getLabel('js-sphinx-config-rewrite') + '</span>';
						}

						openDialog('', getLabel('js-search-generate-config'), {
							width: 350,
							html: getLabel('js-sphinx-generate-warning') + '<br/>' + genConfigText,
							cancelButton: true,
							openCallback: function() {
								jQuery('.checkbox.sphinx').click(function() {
									jQuery(this).toggleClass('checked');
								})

								if (!isConfig) {
									jQuery('#confirm-button').click();
								}
							},
							'confirmCallback' : function(popupName) {
								var message = [getLabel('js-sphinx-build-view-error'), getLabel('js-sphinx-build-config-error')];

								if (!genConfig) {
									$.getJSON('/admin/search/generateView/', function (data) {
										if (data.result.message !== undefined) {
											message[0] = data.result.message;
										}
										ShowMsg(getLabel('js-search-generate-config'), message[0]);
									})
								} else {
									var newDef = $.when(
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


									newDef.done(function() {
										var content = message[0] + '<br/>' + message[1];

										if (message[0] && message[1]) {
											content = getLabel('js-search-reindex-table-and-config-created');
										}

										ShowMsg(getLabel('js-search-generate-config'), content);
									}).fail(function() {
										ShowMsg(getLabel('js-search-generate-config'), message[0] + '<br/>' + message[1]);
									});
								}

								closeDialog(popupName);
							}
						});
					});

					return false;
				});
			});
			]]>
		</script>

		<div class="panel-settings">
			<div class="title field-group-toggle">
				<div class="round-toggle"></div>
				<h3>
					<xsl:value-of select="@label" />
				</h3>
			</div>
			<div class="content">
				<table class="tableContent">
					<tbody>
						<p>&generate-view-and-config;</p>
					</tbody>
				</table>

				<div class="buttons">
					<div class="pull-right">
						<input type="submit" value="&label-build-configuration;" id="generateView" class="btn color-blue"/>
					</div>
				</div>
			</div>
		</div>

		<xsl:call-template name="std-form-buttons-settings" />

	</xsl:template>

	<xsl:template match="option[../@name = 'fields-weight-options' and @type = 'int']" mode="settings.modify-option">
		<input type="number" class="default" name="{@name}" value="{value}" id="{@name}" min="0" />
	</xsl:template>


</xsl:stylesheet>