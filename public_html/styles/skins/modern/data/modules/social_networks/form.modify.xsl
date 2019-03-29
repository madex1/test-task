<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@module = 'social_networks']/data">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="loc-left">
						<a href="/admin/data/type_edit/{/result/data/object/@type-id}" class="icon-action" id="edit" title="&label-edit-type;">
							<i class="small-ico i-edit"></i>
						</a>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<form class="form_modify"  method="post" action="do/" enctype="multipart/form-data">
						<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer"/>
						<input type="hidden" name="domain" value="{$domain-floated}"/>
						<input type="hidden" name="permissions-sent" value="1"/>
						<script type="text/javascript">
							//Проверка
							var treeLink = function(key, value){
							var settings = SettingsStore.getInstance();

							return settings.set(key, value, 'expanded');
							}
						</script>
						<xsl:apply-templates select="object" mode="form-modify"></xsl:apply-templates>
						<xsl:call-template name="std-save-button"/>
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>

				</div>
			</div>

		</div>



	</xsl:template>

	<xsl:template match="object" mode="form-modify">
		<xsl:apply-templates select="properties/group" mode="form-modify">
			<xsl:with-param name="show-name"><xsl:text>0</xsl:text></xsl:with-param>
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="field[@type = 'symlink' and @name='iframe_pages']" mode="form-modify">
		<div class="col-md-6 symlink" id="{generate-id()}" name="{@input_name}">

				<div class="title-edit">
					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</div>

				<span id="symlinkInput{generate-id()}" rel="1">
					<ul>
						<xsl:apply-templates select="values/item" mode="symlink" />
					</ul>
				</span>
		</div>
	</xsl:template>

	<xsl:template match="properties/group[1]" mode="form-modify">
		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>1</xsl:text></xsl:param>

		<div class="panel-settings" name="g_{@name}">
			<div class="title">
				<h3 class="c">
					<xsl:value-of select="../../@domain" />
				</h3>
			</div>

			<div class="content">

				<div class="row">
					<xsl:apply-templates select="." mode="form-modify-group-fields">
						<xsl:with-param name="show-name" select="$show-name"/>
						<xsl:with-param name="show-type" select="$show-type"/>
					</xsl:apply-templates>

					<xsl:call-template name="std-form-data-template-id">
						<xsl:with-param name="data-id" select="../../@id"/>
						<xsl:with-param name="domain-floated" select="../../@domain"/>
						<xsl:with-param name="value" select="../../@template-id"/>
					</xsl:call-template>
				</div>

			</div>
		</div>
	</xsl:template>

	<xsl:template name="std-form-data-template-id">
		<xsl:param name="value" />
		<xsl:param name="data-id" />
		<xsl:param name="domain-floated" select="$domain-floated" />
		<xsl:variable name="templates" select="document(concat('udata://system/getTemplatesList/', $domain-floated))/udata" />

		<div class="col-md-6">

				<div class="title-edit">
					<acronym class="acr">
						<xsl:attribute name="title"><xsl:text>&tip-template-id;</xsl:text></xsl:attribute>
						<xsl:text>&label-template;</xsl:text>
					</acronym>
				</div>

				<div>
					<select class="default newselect">
						<xsl:attribute name="name">data[<xsl:value-of select="$data-id"/>][template_id]</xsl:attribute>

						<option selected="selected">
							<xsl:if test="not($value)">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							&label-default-template;
						</option>

						<xsl:apply-templates select="$templates//item" mode="std-form-item">
							<xsl:with-param name="value" select="$value" />
						</xsl:apply-templates>
					</select>
				</div>
		</div>
	</xsl:template>

</xsl:stylesheet>