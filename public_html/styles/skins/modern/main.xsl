<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">

<xsl:stylesheet version="1.0" exclude-result-prefixes="xlink"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink"
	xmlns:php="http://php.net/xsl">
	<xsl:output	encoding="utf-8" indent="yes" method="html" />
	
	<xsl:param name="param0">0</xsl:param>
	
	<xsl:variable name="demo" select="/result/@demo"/>

	<xsl:variable name="system-build" select="/result/@system-build" />
	
	<!-- Current language info -->
	<xsl:variable name="lang-prefix" select="/result/@pre-lang"/>
	<xsl:variable name="lang-id" select="/result/@lang-id"/>

	<!-- Префикс для запросов к методам административной панели -->
	<xsl:variable name="request-prefix" select="concat($lang-prefix, '/admin')" />

	<!-- Current interface info -->
	<xsl:variable name="iface-lang" select="/result/@interface-lang"/>

	<!-- Current user id -->
	<xsl:variable name="current-user-id" select="/result/@user-id" />
	
	<!-- Current domain info -->
	<xsl:variable name="domain"	select="php:function('urlencode', string(/result/@domain))"/>
	<xsl:variable name="domain-id" select="/result/@domain-id"/>
	<xsl:variable name="domain-floated"	select="php:function('urlencode', string(/result/@domain-floated))"/>
	<xsl:variable name="domains-list" select="document('udata://core/getDomainsList')/udata/domains" />
	<xsl:variable name="domainsCount" select="count($domains-list/domain)" />

	<!-- Header and title of current page -->
	<xsl:variable name="header"	select="document('udata://core/header')/udata"/>
	<xsl:variable name="title" select="concat('&cms-name; - ', $header)" />

	<!-- Current module::method -->
	<xsl:variable name="module"	select="/result/@module"/>
	<xsl:variable name="method"	select="/result/@method"/>
	
	<!-- Result data tag properties -->
	<xsl:variable name="data-type" select="/result/data/@type"/>
	<xsl:variable name="data-action" select="/result/data/@action"/>
	<xsl:variable name="data-total"	select="/result/data/@total"/>
	<xsl:variable name="data-offset" select="/result/data/@offset"/>
	<xsl:variable name="data-limit"	select="/result/data/@limit"/>


	<!-- Main modules menu data -->
	<xsl:variable name="modules-menu" select="document('udata://config/menu')/udata"/>
	
	<!-- Navibar -->
	<xsl:variable name="navibar" select="document(concat('udata://system/getSubNavibar/', /result/data/page/@parentId))/udata" />
	
	<!-- Errors list -->
	<xsl:variable name="errors"	select="document('udata://system/listErrorMessages')/udata"/>
	
	<!-- Interface langs list -->
	<xsl:variable name="site-langs" select="document('udata://system/getLangsList')/udata" />
	
	<!-- Favourite modules (modules dock) -->
	<xsl:variable name="favorites" select="document(concat('udata://users/getFavourites/', $current-user-id))/udata"/>
	
	<!-- "Is cache enabled" flag -->
	<xsl:variable name="cache-enabled" select="document('udata://core/cacheIsEnabled')/udata"/>
	
	<!-- Context manual url -->
	<xsl:variable name="context-manul-url" select="document('udata://core/contextManualUrl')/udata" />

	<xsl:variable name="myPerms" select="document(concat('udata://users/choose_perms/', /result/@user-id))/udata" />

	<!-- Версия визуального редактора tinyMCE -->
	<xsl:variable name="wysiwygVersion" select="document('udata://system/getAdminWysiwygVersion/')/udata" />

	<!-- Пользовательские настройки интерфейса -->
	<xsl:variable name="userSettings" select="document(concat('udata://users/loadUserSettings/', $current-user-id))/udata" />

	<!-- Идентификатор объектного типа данных -->
	<xsl:variable name="object-type-id">
		<xsl:choose>
			<xsl:when test="/result/data/page">
				<xsl:value-of select="/result/data/page/@type-id" />
			</xsl:when>
			<xsl:when test="/result/data/type">
				<xsl:value-of select="/result/data/type/@id" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="/result/data/object/@type-id" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<!-- Список групп полей текущего типа данных, которые были скрыты -->
	<xsl:variable name="hiddenGroupNameList" select="string($userSettings/items/item[@key = 'hidden-groups']/value[@tag = $object-type-id])"/>

	<xsl:include href="interface/navigation.xsl" />
	<xsl:include href="interface/layout.xsl" />
	<xsl:include href="udata://core/importSkinXsl/"/>
	

</xsl:stylesheet>
