<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<div class="tabs-content module {$module}-module">
			<div class="section selected">
				<xsl:apply-templates select="$errors" />

				<form class="form_modify" method="post" action="do/" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer" />
					<input type="hidden" name="domain" value="{$domain-floated}" />
					<input type="hidden" name="permissions-sent" value="1" />

					<script type="text/javascript">
						var treeLink = function(key, value) {
						var settings = SettingsStore.getInstance();
						return settings.set(key, value, 'expanded');
						}
					</script>

					<div class="panel-settings">
						<div class="title">
							<h3>
								<xsl:value-of select="notification-label" />
							</h3>
						</div>
						<div class="content">
							<div class="layout">
								<div class="column">
									<xsl:apply-templates select="//mail-template" mode="notifications.mail-template" />
								</div>
								<div class="column">
									<xsl:call-template name="entities.tip.content" />
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<xsl:choose>
							<xsl:when test="$data-action = 'create'">
								<xsl:call-template name="std-form-buttons-add" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:call-template name="std-form-buttons" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</form>
			</div>
		</div>

		<script src="/styles/skins/modern/data/modules/umiNotifications/js/mail-template.js" type="text/javascript" />
		<xsl:call-template name="wysiwyg-init" />
	</xsl:template>

	<xsl:template match="mail-template" mode="notifications.mail-template">
		<div class="row">
			<div class="col-md-12">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</div>

			<div class="col-md-12">
				<div class="mail-template">
					<div class="col-md-9">
						<xsl:choose>
							<xsl:when test="type = 'subject'">
								<input type="text" class="default mail-template-subject" id="{generate-id()}" name="{@name}" value="{content}" />
							</xsl:when>
							<xsl:otherwise>
								<textarea class="mail-template-content wysiwyg" id="{generate-id()}" name="{@name}" >
									<xsl:value-of select="content" disable-output-escaping="yes" />
								</textarea>
							</xsl:otherwise>
						</xsl:choose>
					</div>

					<div class="col-md-3">
						<div class="inserting-fields" id="{@name}">
							<ul>
								<xsl:apply-templates select="fields/child::node()" mode="notifications.mail-field" />
							</ul>
						</div>
						<div>
							<input class="control-button" name="{@name}" type="button" value="&label-variables-control;" />
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="node()" mode="notifications.mail-field" >
		<li>
			<a class="insert-link" data-value="{name()}" href="">
				<xsl:value-of select="node()" />
			</a>
		</li>
	</xsl:template>

</xsl:stylesheet>
