<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Подключает скрипт для валидации полей -->
	<!-- @param launch нужно ли запустить валидацию с параметрами по умолчанию -->
	<xsl:template name="error-checker">
		<xsl:param name="launch" select="''"/>

		<script src="/styles/skins/modern/design/js/error-checker.js" />

		<script id="error-checker-template" type="text/template">
			<![CDATA[
			<div id="fields-errors">
				<ul class="disc">
					<% _.each(errors, function(error){ %>
						<li>
							<%= error.title %>
							<% if (error.text) { %>
								«<span class="field-name"><%= error.text %></span>»
							<% } %>
							.
						</li>
					<% }); %>
				</ul>
			</div>
			]]>
		</script>
		<xsl:if test="$launch">
			<script>
				var form = jQuery('form.form_modify').eq(0);
				jQuery('form.form_modify').submit(function() {
					return checkErrors({
						form: form,
						check: {
							empty: null,
							number: null,
							time: null,
							date: null,
							passwordRepeat: null
						}
					});
				});
			</script>
		</xsl:if>

	</xsl:template>
	
	<xsl:template match="data[error]">
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="data/error">
		<div class="location">
			<a class="btn-action loc-right infoblock-show">
				<i class="small-ico i-info"></i>
				<xsl:text>&help;</xsl:text>
			</a>
		</div>
		<div class="layout">
			<div class="column">
				<div id="errorList">
					<p class="error" style="margin-top:0px;"><strong><xsl:text>&label-errors-found;:</xsl:text></strong></p>

					<ol class="error">
						<li>
							<xsl:value-of select="." disable-output-escaping="yes" />
						</li>
					</ol>
				</div>
			</div>
			<div class="column">
				<div id="info_block" class="infoblock">
					<h3>
						<xsl:text>&label-quick-help;</xsl:text>
					</h3>
					<div class="content" title="{$context-manul-url}">
					</div>
					<div class="infoblock-hide"></div>
				</div>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages']">
		<div id="errorList">
			<p class="error"><strong><xsl:text>&label-errors-found;:</xsl:text></strong></p>
		
			<ol class="error">
				<xsl:apply-templates select="items/item" />
			</ol>
		</div>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages'][count(items/item) = 0]" />
	
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages']/items/item">
		<li>
			<xsl:value-of select="." disable-output-escaping="yes" />
		</li>
	</xsl:template>
	
	<!-- Temporary template to make dev a little bit easy -->
	<xsl:template match="data">
		<div id="errorList">
			<p class="error"><strong><xsl:text>&label-errors-found;:</xsl:text></strong></p>

			<ol class="error">
				<li>
					<xsl:text>&error-method-doesnt-exists;</xsl:text>
				</li>
			</ol>
		</div>
	</xsl:template>
</xsl:stylesheet>