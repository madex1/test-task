<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'config']/data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
		<div class="section selected">
			<div class="location">
				<xsl:call-template name="entities.help.button" />
			</div>
			<div class="layout">
				<div class="column">
					<form method="post" action="do/" enctype="multipart/form-data">
						<xsl:apply-templates select="." mode="settings.modify" />
						<div class="row">
							<xsl:call-template name="std-form-buttons-settings" />
						</div>
					</form>
				</div>
				<div class="column">
					<xsl:call-template name="entities.help.content" />
				</div>
			</div>
		</div>
		</div>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="group[@name = 'snapshots']" mode="settings.modify">

		<xsl:variable name="snapshots" select="document(concat('ufs://', .))/udata"/>

		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		<script type="text/javascript"><![CDATA[
			function reportJsonStatus(msg) {
				$('#snapshots-errors').find('li img').remove();
				$('#snapshots-errors').html($('#snapshots-errors').html() + "<li>" + msg + "</li>");
			}

			function reportJsonError(msg) {
				$('#snapshots-errors').find('li img').remove();
				$('#snapshots-errors').html($('#snapshots-errors').html() + "<li class='error'>" + msg + "</li>");
			}

			function makeJsonRequest(url) {
				$('snapshots-errors').html('');

				var script = document.createElement("script");
				script.charset = "utf-8";
				script.defer = "defer";
				script.src = url;
				document.body.appendChild(script);
			}

			function createSnapshot() {
				openDialog('', getLabel('js-backup-make-header'), {
					'html': getLabel('js-backup-make-content')+'<ul id="snapshots-errors" style="margin-top:10px; font-size:14px"><li style="text-align:center;"><img src="/styles/skins/modern/design/img/process.gif"/></li></ul>',
					'stdButtons': false,
					width: 500
				});
				window.stopJsonRequests = false;

				if(window.session) {
					 window.session.startAutoActions();
				}

				makeJsonRequest(']]><xsl:value-of select="$lang-prefix" /><![CDATA[/admin/backup/createSnapshot/');
			}

			function restoreSnapshot(filename) {
				openDialog(getLabel('js-backup-restore-confirm'), getLabel('js-backup-restore-title'), {
					confirmText: getLabel('js-label-yes'),
					cancelButton: true,
					cancelText: getLabel('js-label-no'),
					confirmCallback: function(popupName) {
						closeDialog(popupName);
						openDialog('', getLabel('js-backup-restore-header'), {
							'html': getLabel('js-backup-restore-content')+'<ul id="snapshots-errors" style="margin-top:10px; font-size:14px"></ul>',
							'stdButtons': false
						});
						window.stopJsonRequests = false;

						if(window.session) {
							 window.session.startAutoActions();
						}

						makeJsonRequest(']]><xsl:value-of select="$lang-prefix" /><![CDATA[/admin/backup/restoreSnapshot/?filename=' + filename);
					}
				});
				return;
			}

			jQuery(document).ready(function() {
				jQuery('#do_shapshot').click(function(){
					createSnapshot();
					return false;
				});
			});

		]]>
		</script>

		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="@label" />
				</h3>
			</div>
			<div class="content">
				<div class="">
					<a href="#"  class="{/result/@module}_{/result/@method}_btn btn color-blue" id="do_shapshot">
						<xsl:text>&label-make-snapshot;</xsl:text>
					</a>
				</div>

				<!--<div id="snapshots-errors" />-->

				<div style="clear: left;" />

				<table class="btable btable-striped">
					<thead>
						<tr>
							<th>
								<xsl:text>&label-snapshots-files;</xsl:text>
							</th>

							<th>
								<xsl:text>&label-snapshots-unpack;</xsl:text>
							</th>

							<th>
								<xsl:text>&label-delete;</xsl:text>
							</th>
						</tr>
					</thead>

					<tbody>
						<xsl:apply-templates select="$snapshots" mode="snapshots" />
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[count(file) = 0]" mode="snapshots">
		<tr>
			<td colspan="3">
				<p align="center">
					<xsl:text>&label-snapshots-empty;</xsl:text>
				</p>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="file" mode="snapshots">
		<xsl:variable name="date" select="document(concat('udata://system/convertDate/', @create-time, '/Y-m-d%20H:m:s'))" />

		<tr>
			<td>
				<a href="{$lang-prefix}/admin/backup/downloadSnapshot/?filename={@name}" title="&label-download;" style="margin-right:20px;">
					<strong><xsl:value-of select="@name"/></strong>
				</a>
				<xsl:text>&label-create-time;: </xsl:text>
				<xsl:value-of select="$date/udata"/>
			</td>

			<td style="text-align:center;">
				<a href="" class="restore" onclick="restoreSnapshot('{@name}'); return false;" title="&label-snapshots-unpack-do;">
					<i class="small-ico i-restore"></i>
				</a>
			</td>

			<td style="text-align:center;">
				<a href="{$lang-prefix}/admin/backup/deleteSnapshot/?filename={@name}" class="delete unrestorable" title="&label-delete;">
					<i class="small-ico i-remove"></i>
				</a>
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>