<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
		<!ENTITY sys-module        'data'>
		]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl" extension-element-prefixes="php">

	<xsl:param name="skip-lock"/>

	<!--Form modify-->

	<xsl:template match="/result[@method = 'type_edit']/data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<form action="do/" method="post" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}"/>
					<xsl:apply-templates select="type" mode="fieldgroup-common"/>
					<div class="row">
						<xsl:call-template name="std-form-buttons" />
					</div>
				</form>
				<xsl:apply-templates select="//fieldgroups" mode="fieldsgroups-other"/>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
    </xsl:template>

	<xsl:template match="type" mode="fieldgroup-common">
		<div class="panel-settings">
			<div class="title" title='&label-name;: "{@title}"'>
				<h3>
					&label-edit-type-common;
				</h3>
			</div>
			<div id="group-common"  class="content">
				<div class="row">
					<div class="col-md-6">
						<label>
							<div class="title-edit">&label-type-name;</div>
							<div>
								<input type="text" class="default" name="data[name]" value="{@title}">
									<xsl:if test="./@locked = 'locked' and not($skip-lock = 1)">
										<xsl:attribute name="disabled">disabled</xsl:attribute>
									</xsl:if>
								</input>
							</div>
						</label>
					</div>
					<div class="col-md-6">
						<label>
							<div class="title-edit">&label-type-guid;</div>
							<div>
								<input type="text" class="default" name="data[guid]" value="{@guid}">
									<xsl:if test="./@locked = 'locked' and not($skip-lock = 1)">
										<xsl:attribute name="disabled">disabled</xsl:attribute>
									</xsl:if>
								</input>
							</div>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label>
							<div class="title-edit">
								<xsl:text>&field-domain_name;</xsl:text>
							</div>
							<xsl:variable name="domain-id" select="@domain-id"/>
							<div>
								<select class="default newselect" name="data[domain_id]" value="{$domain-id}">
									<xsl:if test="./@locked = 'locked' and not($skip-lock = 1)">
										<xsl:attribute name="disabled">disabled</xsl:attribute>
									</xsl:if>
									<xsl:choose>
										<xsl:when test="$domain-id &gt; 0">
											<option value="0">&label-for-all;</option>
											<option value="{$domain-id}" selected="selected">
												<xsl:value-of select="$domains-list/domain[@id = $domain-id]/@decoded-host" />
											</option>
										</xsl:when>
										<xsl:otherwise>
											<option value="0" selected="selected">&label-for-all;</option>
										</xsl:otherwise>
									</xsl:choose>
									<xsl:apply-templates select="$domains-list" mode="domain_id">
										<xsl:with-param name="selected.id" select="$domain-id" />
									</xsl:apply-templates>
								</select>
							</div>
						</label>
					</div>
					<div class="col-md-6">
						<label>
							<div class="title-edit">
								<xsl:text>&label-hierarchy-type;</xsl:text>
							</div>
							<xsl:variable name="base-id" select="base/@id"/>
							<div>
								<!-- I will not give you normal type for creating ;( -->
								<select class="default newselect" name="data[hierarchy_type_id]" value="{base/@id}">
									<xsl:if test="./@locked = 'locked' and not($skip-lock = 1)">
										<xsl:attribute name="disabled">disabled</xsl:attribute>
									</xsl:if>
									<option/>
									<xsl:apply-templates
											select="document('udata://system/hierarchyTypesList')/udata/items/item"
											mode="std-form-item">
										<xsl:with-param name="value" select="base/@id"/>
									</xsl:apply-templates>
								</select>
							</div>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label class="checkbox-wrapper">
							<input type="hidden" name="data[is_public]" value="0"/>
							<div class="checkbox">
								<xsl:if test="@public">
									<xsl:attribute name="checked">checked</xsl:attribute>
								</xsl:if>
								<input type="checkbox" name="data[is_public]" value="1" class="checkbox">
									<xsl:if test="@public">
										<xsl:attribute name="checked">checked</xsl:attribute>
									</xsl:if>
								</input>
							</div>
							<span>
								<xsl:text>&label-is-public;</xsl:text>
							</span>
						</label>
					</div>
					<div class="col-md-6">
						<label class="checkbox-wrapper">
							<input type="hidden" name="data[is_guidable]" value="0"/>
							<div class="checkbox">
								<xsl:if test="@guide">
									<xsl:attribute name="checked">checked</xsl:attribute>
								</xsl:if>
								<input type="checkbox" name="data[is_guidable]" value="1" class="checkbox">
									<xsl:if test="@guide">
										<xsl:attribute name="checked">checked</xsl:attribute>
									</xsl:if>
								</input>
							</div>
							<span>
								<xsl:text>&label-is-guide;</xsl:text>
							</span>
						</label>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="object[not(./properties/group)]" mode="form-modify">
		<div class="panel properties-group">
			<div class="header">
				<span><xsl:text>&nbsp;</xsl:text></span>
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				<xsl:call-template name="std-form-name">
					<xsl:with-param name="value" select="@name" />
					<xsl:with-param name="show-tip"><xsl:text>0</xsl:text></xsl:with-param>
				</xsl:call-template>
				<xsl:choose>
					<xsl:when test="$data-action = 'create'">
						<xsl:call-template name="std-form-buttons-add" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="std-form-buttons" />
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
