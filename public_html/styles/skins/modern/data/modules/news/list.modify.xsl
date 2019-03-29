<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/news/" [
        <!ENTITY sys-module        'news'>
        ]>


<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:variable name="feed-types"   select="document('udata://content/getObjectsByTypeList/472b07b9fcf2c2451e8781e944bf5f77cd8457c8/')/udata/items/item"/>
	<xsl:variable name="news-rubrics" select="document('udata://news/getObjectNamesForRubrics/')/udata/items/item"/>
	<xsl:variable name="feed-charsets" select="document('udata://content/getObjectsByTypeList/news-rss-source-charset/')/udata/items/item"/>

	<xsl:template match="/result[@method = 'rss_list']/data[@type = 'list' and @action = 'modify']">

		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form action="do/" method="post">
							<table class="btable btable-striped">
								<thead>
									<tr>
										<th>
											<xsl:text>&label-feed-name;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-feed-url;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-feed-charset;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-feed-type;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-feed-news-rubric;</xsl:text>
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
											<input class="default" type="text" name="data[new][name]" />
										</td>
										<td>
											<input class="default" type="text" name="data[new][url]" />
										</td>
										<td class="center">
											<select name="data[new][charset_id]" style="width: 97%;" class="newselect default">
												<option value="0">&label-feed-charset-auto;</option>
												<xsl:apply-templates select="$feed-charsets" mode="field-select-option" />
											</select>
										</td>
										<td class="center">
											<select name="data[new][rss_type]" style="width: 97%;" class="newselect default">
												<xsl:apply-templates select="$feed-types" mode="field-select-option" />
											</select>
										</td>
										<td class="center">
											<select name="data[new][news_rubric]" class="newselect default">
												<xsl:apply-templates select="$news-rubrics" mode="field-select-option"/>
											</select>
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
			</div>
		</div>


	</xsl:template>

	<xsl:template match="/result[@method = 'rss_list']/data/object" mode="list-modify">
		<xsl:variable name="object-info" select="document(concat('uobject://', @id))/udata/object/properties" />

		<tr>
			<td>
				<input type="text" class="default" name="data[{@id}][name]" value="{@name}" />
			</td>

			<td>
				<input type="text" class="default" name="data[{@id}][url]" value="{$object-info//property[@name = 'url']/value/text()}"  />
			</td>

			<td class="center">
				<select name="data[{@id}][charset_id]" style="width: 97%;" class="newselect default">
					<option value="0">&label-feed-charset-auto;</option>
					<xsl:apply-templates select="$feed-charsets" mode="field-select-option">
						<xsl:with-param name="value" select="$object-info//property[@name = 'charset_id']/value/item/@id"/>
					</xsl:apply-templates>
				</select>
			</td>

			<td class="center">
				<select name="data[{@id}][rss_type]" style="width: 97%;" class="newselect default">
					<xsl:apply-templates select="$feed-types" mode="field-select-option">
						<xsl:with-param name="value" select="$object-info//property[@name = 'rss_type']/value/item/@id"/>
					</xsl:apply-templates>
				</select>
			</td>

			<td class="center">
				<select name="data[{@id}][news_rubric]" class="newselect default">
					<xsl:apply-templates select="$news-rubrics" mode="field-select-option">
						<xsl:with-param name="value" select="$object-info//property[@name = 'news_rubric']/value/item/@id"/>
					</xsl:apply-templates>
				</select>
			</td>

			<td class="center">
				<div class="checkbox">
					<input type="checkbox" name="dels[]" value="{@id}" class="check"/>
				</div>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="item" mode="field-select-option">
		<xsl:param name="value" />
		<option value="{@id}">
			<xsl:if test="$value = @id">
				<xsl:attribute name="selected"><xsl:text>selected</xsl:text></xsl:attribute>
			</xsl:if>
			<xsl:value-of select="." />
		</option>
	</xsl:template>

</xsl:stylesheet>
