<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
	<!ENTITY sys-module 'banners'>	
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- Шаблон группы полей "Основные параметры" баннера -->
	<xsl:template match="group[@name='common']" mode="form-modify">
		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>1</xsl:text></xsl:param>
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
		<div name="g_{@name}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<a data-name="{@name}" data-label="{@title}"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:value-of select="@title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:apply-templates select="." mode="form-modify-group-fields">
								<xsl:with-param name="show-name" select="$show-name" />
								<xsl:with-param name="show-type" select="$show-type" />
							</xsl:apply-templates>

							<xsl:call-template name="calculate-ctr" />
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template name="calculate-ctr">
		<xsl:variable name="group" select="/result/data/object/properties/group[@name = 'view_params']" />
		<xsl:variable name="views-count" select="$group/field[@name = 'views_count']" />
		<xsl:variable name="clicks-count" select="$group/field[@name = 'clicks_count']" />
		<div class="col-md-6">

				<div class="title-edit">
					<acronym>
						<xsl:attribute name="title"><xsl:text>&ctr-description;</xsl:text></xsl:attribute>
						<xsl:attribute name="class"><xsl:text>acr</xsl:text></xsl:attribute>
						<xsl:text>CTR</xsl:text>
					</acronym>					
				</div>
				<span>
					<xsl:value-of select="format-number(translate(number($clicks-count) div number($views-count), 'Na', '00'), '0.####%')" />					
				</span>

		</div>		
	</xsl:template>
	
</xsl:stylesheet>