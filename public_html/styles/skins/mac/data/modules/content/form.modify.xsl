<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="data[@action = 'create']//field[@type = 'boolean'][@name = 'show_submenu']" mode="form-modify">
		<xsl:if test="preceding-sibling::field/@type != 'boolean'">
			<div style="clear: left;" />
		</xsl:if>
		<div class="field">
			<label for="{generate-id()}">
				<span class="label">
					<input type="hidden" name="{@input_name}" value="0" />
					<input type="checkbox" name="{@input_name}" value="1" id="{generate-id()}" checked="checked">
						<xsl:apply-templates select="." mode="required_attr">
							<xsl:with-param name="old_class" select="'checkbox'" />
						</xsl:apply-templates>
					</input>
					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</span>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="data[@type='form' and @action = 'modify']/template" mode="form-modify" priority="2">
		<form method="post" action="do/" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer" />
			<input type="hidden" name="domain" value="{$domain-floated}"/>
			<input type="hidden" name="permissions-sent" value="1" />

			<div class="panel properties-group">
				<div class="header">
					<span>
						<xsl:text>&group-template-props;</xsl:text>
					</span>
					<div class="l" /><div class="r" />
				</div>

				<div class="content">
					<div class="field">
						<label>
							<span class="label">
								<acronym>
									<xsl:text>&label-template-title;</xsl:text>
								</acronym>
							</span>
							<span>
								<input type="text" name="title" value="{@title}" />
							</span>
						</label>
					</div>

					<div class="field">
						<label>
							<span class="label">
								<acronym>
									<xsl:text>Имя шаблона</xsl:text>
								</acronym>
							</span>
							<span>
								<input type="text" name="name" value="{@name}" />
							</span>
						</label>
					</div>
					<div class="field">
						<label>
							<span class="label">
								<acronym>
									<xsl:text>&label-template-filename;</xsl:text>
								</acronym>
							</span>
							<span>
								<input type="text" name="filename" value="{@filename}" />
							</span>
						</label>
					</div>
					<div class="field">
						<label>
							<span class="label">
								<acronym>
									<xsl:text>Тип шаблона</xsl:text>
								</acronym>
							</span>
							<span>
								<select name="type">
									<option value=""></option>
									<option value="tpls">
										<xsl:if test="@type = 'tpls'">
											<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:text>tpls</xsl:text>
									</option>
									<option value="xslt">
										<xsl:if test="@type = 'xslt'">
											<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:text>xslt</xsl:text>
									</option>
									<option value="php">
										<xsl:if test="@type = 'php'">
											<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:text>php</xsl:text>
									</option>
								</select>
							</span>
						</label>
					</div>
					<h3>&label-template-used-pages;</h3>
					<xsl:call-template name="ui-smc-table" >
						<xsl:with-param name="show-toolbar">0</xsl:with-param>
						<xsl:with-param name="flat-mode">1</xsl:with-param>
						<xsl:with-param name="disable-csv-buttons">1</xsl:with-param>
						<xsl:with-param name="ignore-hierarchy" select="1" />
						<xsl:with-param name="hide-csv-import-button">1</xsl:with-param>
						<xsl:with-param name="enable-edit">false</xsl:with-param>
						<xsl:with-param name="search-show">0</xsl:with-param>
						<xsl:with-param name="template" select="@id" />
						<xsl:with-param name="menu">
							<xsl:text>var menu = []</xsl:text>
						</xsl:with-param>
					</xsl:call-template>

					<div class="save_buttons">
						<xsl:call-template name="std-form-buttons" />
					</div>

				</div>
			</div>
		</form>
	</xsl:template>

	<xsl:template match="used-pages">
		<div class="field symlink" id="UsedPages" name="used_pages[]">
			<label for="symlinkInputUsedPages">
				<span class="label">
					<acronym>
						<xsl:text>&label-template-used-pages;</xsl:text>
					</acronym>
				</span>
				<span id="symlinkInputUsedPages">
					<ul>
						<xsl:apply-templates select="page" mode="symlink" />
					</ul>
				</span>
			</label>
		</div>
	</xsl:template>
</xsl:stylesheet>