<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="data[@type='form' and @action = 'modify'][template]">
		<div class="tabs-content module">
			<div id="page" class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<xsl:apply-templates mode="form-modify" />
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="data[@type='form' and @action = 'modify']/template" mode="form-modify">
		<form method="post" action="do/" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer" />
			<input type="hidden" name="domain" value="{$domain-floated}"/>
			<input type="hidden" name="permissions-sent" value="1" />

			<div class="panel-settings">
				<div class="title">
					<h3>
						<xsl:text>&group-template-props;</xsl:text>
					</h3>
				</div>

					<div class="col-md-6">
						<div class="title-edit">
							<acronym>
								<xsl:text>&label-template-title;</xsl:text>
							</acronym>
						</div>
						<span>
							<input type="text" class="default" name="title" value="{@title}" />
						</span>
					</div>

					<div class="col-md-6">
						<div class="title-edit">
							<acronym>
								<xsl:text>&label-template-directory;</xsl:text>
							</acronym>
						</div>
						<span>
							<input type="text" name="name" class="default" value="{@name}" />
						</span>
					</div>
					<div class="col-md-6">
						<div class="title-edit">
							<acronym>
								<xsl:text>&label-template-filename;</xsl:text>
							</acronym>
						</div>
						<span>
							<input type="text" name="filename" class="default" value="{@filename}" />
						</span>
					</div>
					<div class="col-md-6">
						<div class="title-edit">
							<acronym>
								<xsl:text>&label-template-type;</xsl:text>
							</acronym>
						</div>
						<span>
							<select name="type" class="default newselect">
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
					</div>

					<div class="panel-settings">
						<h3>&label-template-used-pages;</h3>
					</div>
					<div class="col-md-12">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="show-toolbar">0</xsl:with-param>
							<xsl:with-param name="flat-mode">1</xsl:with-param>
							<xsl:with-param name="disable-csv-buttons">1</xsl:with-param>
							<xsl:with-param name="ignore-hierarchy" select="1"/>
							<xsl:with-param name="hide-csv-import-button">1</xsl:with-param>
							<xsl:with-param name="enable-edit">false</xsl:with-param>
							<xsl:with-param name="search-show">1</xsl:with-param>
							<xsl:with-param name="template" select="@id"/>
							<xsl:with-param name="menu">
								<xsl:text>var menu = []</xsl:text>
							</xsl:with-param>
						</xsl:call-template>

						<div class="save_buttons">
							<xsl:call-template name="std-form-buttons"/>
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