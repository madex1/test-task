<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns="http://www.w3.org/2005/Atom"
				exclude-result-prefixes="">

	<xsl:output method="xml" encoding="UTF-8"/>

	<xsl:variable name="domain" select="concat('http://', umidump/meta/domain)"/>
	
	<xsl:param name="link" />
	<xsl:param name="description" />
	<xsl:param name="lang" />

	<xsl:template match="/">
		<xsl:apply-templates select="umidump" />
	</xsl:template>


	<xsl:template match="umidump[@version='2.0']">
		<feed>
			<title><xsl:value-of select="meta/site-name"/></title>
			<id><xsl:value-of select="meta/domain"/></id>
			<link href="{concat($domain, $link)}" />
			<subtitle>
				<xsl:choose>
					<xsl:when test="$description">
						<xsl:value-of select="concat(meta/site-name, ' - ', $description)"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="meta/site-name"/>
					</xsl:otherwise>
				</xsl:choose>
			</subtitle>
			<!--updated><xsl:value-of select="element/updateTime/UTC" /></updated-->
			<xsl:apply-templates select="pages/page"/>
		</feed>
	</xsl:template>


	<xsl:template match="page">
		<xsl:element name="entry">
			<title>
				<xsl:value-of select="name"/>
			</title>
			<xsl:variable name="author_id" select="properties/group/property[@name = 'author_id']/value/item/@id"/>
			<xsl:if test="$author_id">
				<xsl:variable name="author" select="document(concat('uobject://', $author_id))/udata/object"/>
				<author>
					<name>
						<xsl:choose>											
							<xsl:when test="$author/properties/group/property[@name = 'is_registrated']/value = 1">
								<xsl:variable name="user" select="document(concat('uobject://', $author/properties/group/property[@name = 'user_id']/value/item/@id))/udata/object"/>
								<xsl:value-of select="$user/properties/group/property[@name = 'login']/value"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$author/properties/group/property[@name = 'nickname']/value"/>
							</xsl:otherwise>
						</xsl:choose>
					</name>
				</author>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="properties/group/property[@name = 'anons']/value != ''">
					<summary type="html"><xsl:value-of select="properties/group/property[@name = 'anons']/value"/></summary>
				</xsl:when>
				<xsl:when test="properties/group/property[@name = 'content']/value != ''">
					<content type="html"><xsl:value-of select="properties/group/property[@name = 'content']/value"/></content>
				</xsl:when>
				<xsl:when test="properties/group/property[@name = 'message']/value != ''">
					<content><xsl:value-of select="properties/group/property[@name = 'message']/value"/></content>
				</xsl:when>
				<xsl:when test="properties/group/property[@name = 'opisanie']/value != ''">
					<content><xsl:value-of select="properties/group/property[@name = 'opisanie']/value"/></content>
				</xsl:when>
			</xsl:choose>
			<xsl:if test="properties/group/property[@name = 'publish_time']/value">
				<published><xsl:value-of select="properties/group/property[@name = 'publish_time']/value"/></published>
			</xsl:if>
			<link href="{concat($domain, @link)}" rel="alternate"/>
			<id><xsl:value-of select="concat($domain, @link)"/></id>
			<!--updated><xsl:value-of select="updateTime/UTC"/></updated-->
		</xsl:element>
	</xsl:template>
</xsl:stylesheet>