<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


	<xsl:template match="/result[@method = 'config']/data[@type = 'list' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form action="do/" method="post">
							<xsl:apply-templates select="domain" mode="list-modify" />
							<xsl:call-template name="std-save-button" />
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>

	</xsl:template>
	
	<xsl:template match="domain" mode="list-modify">
		<table class="btable ">
			<thead>
				<tr>
					<th colspan="5">
						<xsl:value-of select="@host" />
					</th>
				</tr>

				<tr>
					<th>
						<xsl:text>&label-template-title;</xsl:text>
					</th>
					
					<th>
						<xsl:text>&label-template-filename;</xsl:text>
					</th>

					<th>
						<xsl:text>&label-template-directory;</xsl:text>
					</th>

					<th>
						<xsl:text>&label-template-is-default;</xsl:text>
					</th>
					
					<th>
						<xsl:text>&label-template-edit;</xsl:text>
					</th>
					
					<th>
						<xsl:text>&label-template-delete;</xsl:text>
					</th>
				</tr>
			</thead>
			
			<tbody>
				<xsl:apply-templates mode="list-modify"/>
				
				<tr>
					<td>
						<input type="text" class="default" name="data[{@host}][new][title]" placeholder="&label-enter-new-template-title;" />
					</td>
					
					<td>
						<input type="text" class="default" name="data[{@host}][new][filename]" placeholder="my_new_template.phtml" />
					</td>

					<td>
						<input type="text" class="default" name="data[{@host}][new][directory]" placeholder="&label-enter-new-template-directory;" />
					</td>
					
					<td align="center">
						<input type="radio" name="data[default][{@id}]" value="new" class="check" />
					</td>
					
					<td colspan="2" />
				</tr>
			</tbody>
		</table>
	</xsl:template>


	<xsl:template match="template" mode="list-modify">
		<tr>
			<td>
				<input type="text" class="default" name="data[{../@host}][{@id}][title]" value="{@title}"/>
			</td>
			
			<td>
				<input type="text" class="default" name="data[{../@host}][{@id}][filename]" value="{@filename}"/>
			</td>

			<td>
				<input type="text" class="default" name="data[{../@host}][{@id}][directory]" value="{@name}" />
			</td>
			
			<td align="center">
				<input type="radio" name="data[default][{@domain-id}]" value="{@id}" class="check">
					<xsl:if test="@is-default = '1'">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
				</input>
			</td>

			<td align="center">
				<a href="{$lang-prefix}/admin/content/tpl_edit/{@id}/?domain={../@host}">
					<i class="small-ico i-edit" title="&label-edit;" alt="&label-edit;"></i>
				</a>
			</td>

			<td align="center">
				<div class="checkbox">
					<input type="checkbox" name="dels[]" value="{@id}"/>
				</div>
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>
