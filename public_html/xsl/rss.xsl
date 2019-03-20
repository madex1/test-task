<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" encoding="UTF-8"/>
	
	<xsl:variable name="domain" select="concat('http://', umidump/meta/domain)"/>
	
	<xsl:param name="link" />
	<xsl:param name="description" />
	<xsl:param name="lang" />

	<xsl:template match="/">
		<xsl:apply-templates select="umidump" />
	</xsl:template>

	<xsl:template match="umidump[@version='2.0']">
		<rss version="2.0" xmlns:yandex="http://news.yandex.ru">
			<channel>
				<title><xsl:value-of select="meta/site-name"/></title>
				<link><xsl:value-of select="concat($domain, $link)"/></link>
				<description>
					<xsl:choose>
						<xsl:when test="$description">
							<xsl:value-of select="concat(meta/site-name, ' - ', $description)"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="meta/site-name"/>
						</xsl:otherwise>
					</xsl:choose>
				</description>
				<language><xsl:value-of select="$language"/></language>
				<xsl:apply-templates select="pages/page"/>
			</channel>
		</rss>
	</xsl:template>
	
	<xsl:template match="page">
		<item>
			<title><xsl:value-of select="properties/group/property[@name = 'h1']/value"/></title>
			<xsl:variable name="author_id" select="properties/group/property[@name = 'author_id']/value/item/@id"/>
			<xsl:if test="$author_id">
				<xsl:variable name="author" select="document(concat('uobject://', $author_id))/udata/object"/>
				<author>
					<xsl:choose>											
						<xsl:when test="$author/properties/group/property[@name = 'is_registrated']/value = 1">
							<xsl:variable name="user" select="document(concat('uobject://', $author/properties/group/property[@name = 'user_id']/value/item/@id))/udata/object"/>
							<xsl:value-of select="$user/properties/group/property[@name = 'login']/value"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$author/properties/group/property[@name = 'nickname']/value"/>
						</xsl:otherwise>
					</xsl:choose>
				</author>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="properties/group/property[@name = 'anons']/value != ''">
					<description><xsl:value-of select="properties/group/property[@name = 'anons']/value"/></description>
				</xsl:when>
				<xsl:when test="properties/group/property[@name = 'content']/value != ''">
					<description><xsl:value-of select="properties/group/property[@name = 'content']/value"/></description>
				</xsl:when>
				<xsl:when test="properties/group/property[@name = 'message']/value != ''">
					<description><xsl:value-of select="properties/group/property[@name = 'message']/value"/></description>
				</xsl:when>
				<xsl:when test="properties/group/property[@name = 'opisanie']/value != ''">
					<description><xsl:value-of select="properties/group/property[@name = 'opisanie']/value"/></description>
				</xsl:when>
			</xsl:choose>
			<xsl:if test="properties/group/property[@name = 'publish_time']/value">
				<pubDate><xsl:value-of select="properties/group/property[@name = 'publish_time']/value"/></pubDate>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="properties/group/property[@name = 'content']/value != ''">
				     <xsl:element name="yandex:full-text" namespace="http://news.yandex.ru">
					     <xsl:value-of select="properties/group/property[@name = 'content']/value"/>
					</xsl:element>
				</xsl:when>
				<xsl:otherwise>
				     <xsl:element name="yandex:full-text" namespace="http://news.yandex.ru">
     					<xsl:value-of select="properties/group/property[@name = 'anons']/value"/>
					</xsl:element>
				</xsl:otherwise>
			</xsl:choose>
			<link><xsl:value-of select="concat($domain, @link)"/></link>
			<guid><xsl:value-of select="concat($domain, @link)"/></guid>
		</item>
	</xsl:template>

</xsl:stylesheet>