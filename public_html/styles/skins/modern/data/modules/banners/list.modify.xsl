<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" exclude-result-prefixes="xlink"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:template match="data" priority="1">
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">

				<form action="do/" method="post" class="banners-places-form">
					<div class="row">
					<table class="btable btable-striped">
						<thead>
							<tr>
								<th>
									<xsl:text>&label-place-id;</xsl:text>
								</th>
								<th>
									<xsl:text>&label-place-desc;</xsl:text>
								</th>
								<th>
									<xsl:text>&lable-place-random;</xsl:text>
								</th>
								<th>
									<xsl:text>&label-delete;</xsl:text>
								</th>
							</tr>
						</thead>
						<tbody>
							<xsl:apply-templates mode="list-modify"/>
							<tr>
								<td>
									<input class="default" type="text" name="data[new][name]"/>
								</td>

								<td>
									<input class="default" type="text" name="data[new][descr]"/>
								</td>

								<td class="center">
									<input type="hidden" name="data[new][is_show_rand_banner]" value="0" />
									<div class="checkbox">
										<input type="checkbox" class="check" name="data[new][is_show_rand_banner]" value="1" />
									</div>
								</td>

								<td/>
							</tr>
						</tbody>
					</table>
					</div>
					<xsl:call-template name="std-save-button"/>
				</form>
				
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>

		<script>
			jQuery(function() {

				jQuery('.banners-places-form').bind('submit', function(event) {
					jQuery('input[type=submit]', this).attr('disabled', true);
				});
			})
		</script>
	</xsl:template>


	<xsl:template match="object" mode="list-modify">
		<xsl:variable name="object" select="document(concat('uobject://', @id))/udata" />
		
		<tr>
			<td>
				<input class="default" type="text" name="data[{@id}][name]" value="{@name}"/>
			</td>
			
			<td>
				<input class="default" type="text" name="data[{@id}][descr]" value="{$object//property[@name='descr']/value}" />
			</td>
			
			<td class="center">
                <input type="hidden" name="data[{@id}][is_show_rand_banner]" value="0"/>
				<div class="checkbox">
					<input type="checkbox" name="data[{@id}][is_show_rand_banner]" value="1" class="check">
						<xsl:if test="$object//property[@name='is_show_rand_banner']/value = 1">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
				</div>

			</td>

			<td class="center">
				<div class="checkbox">
					<input type="checkbox" name="dels[]" value="{@id}" class="check"/>
				</div>
			</td>
		</tr>
	</xsl:template>
</xsl:stylesheet>