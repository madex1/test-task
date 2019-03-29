<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>

	<!-- Вкладка "Свойства домена" модуля "Конфигурация" -->
	<xsl:template match="/result[@method = 'domain_mirrows']/data">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.modify" />

							<table class="btable btable-striped bold-head middle-align">
								<thead>
									<tr>
										<th>
											<xsl:text>&label-domain-mirror-address;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</tr>
								</thead>
								<tbody>
									<xsl:apply-templates select="domainMirrow" mode="settings-modify" />
									<tr>
										<td>
											<input type="text" name="data[new][host]" class="default" />
										</td>
										<td />
									</tr>
								</tbody>
							</table>

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
	</xsl:template>

	<xsl:template match="domainMirrow" mode="settings-modify">
		<tr>
			<td>
				<input type="text" name="data[{@id}][host]" value="{@host}" class="default" />
			</td>

			<td class="center">
				<div class="checkbox">
					<input type="checkbox" name="dels[]" value="{@id}" class="checkbox" />
				</div>
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>
