<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="data[@type = 'list' and @action = 'modify']">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<xsl:call-template name="entities.help.button" />
		</div>

		<div class="layout">
			<div class="column">

				<form action="do/" method="post">
					<table class="tableContent btable btable-striped">
						<thead>
							<tr>
								<th>
									<xsl:text>&label-name;</xsl:text>
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
									<input type="text" class="default" name="data[new][name]" />
								</td>
								<td />
							</tr>
						</tbody>
					</table>
					<div class="pull-right">
						<xsl:call-template name="std-save-button" />
					</div>
				</form>

			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>

	</xsl:template>


	<xsl:template match="object" mode="list-modify">
		<tr>
			<td>
				<input type="text" class="default" name="data[{@id}][name]" value="{@name}"/>
			</td>

			<td class="center">
				<div class="checkbox">
					<input type="checkbox" name="dels[]" value="{@id}" class="check"/>
				</div>

			</td>
		</tr>
	</xsl:template>


	<xsl:template match="basetype" mode="list-modify">
		<tr>
			<td>
				<input type="text" class="default" name="data[{@id}][title]" value="{.}"/>
			</td>

			<td>
				<input type="text" class="default" name="data[{@id}][module]" value="{@module}"/>
			</td>

			<td>
				<input type="text" class="default" name="data[{@id}][method]" value="{@method}"/>
			</td>

			<td class="center">
				<div class="checkbox">
					<input type="checkbox" name="dels[]" value="{@id}" class="check"/>
				</div>

			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон кнопки вызова подсказки -->
	<xsl:template name="entities.help.button">
		<a class="btn-action loc-right infoblock-show">
			<i class="small-ico i-info"/>
			<xsl:text>&help;</xsl:text>
		</a>
	</xsl:template>

	<!-- Шаблон контента подсказки -->
	<xsl:template name="entities.help.content">
		<div class="infoblock">
			<h3>
				<xsl:text>&label-quick-help;</xsl:text>
			</h3>
			<div class="content" title="{$context-manul-url}"/>
			<div class="infoblock-hide"/>
		</div>
	</xsl:template>

	<xsl:include href="udata://core/importSkinXsl/list.modify.xsl"/>
	<xsl:include href="udata://core/importSkinXsl/list.modify.custom.xsl"/>

	<xsl:include href="udata://core/importExtSkinXsl/list.modify"/>

</xsl:stylesheet>