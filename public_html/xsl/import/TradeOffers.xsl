<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				extension-element-prefixes="php"
				exclude-result-prefixes="xsl php udt">


	<!-- Шаблон трансформации CommerceML => UmiDump для торговых предложений -->
	<xsl:template match="ПакетПредложений" mode="TradeOffers">
		<types>
			<xsl:call-template name="TradeStockType"/>
			<xsl:apply-templates select="Предложения/Предложение" mode="TradeOfferType"/>
		</types>
		<objects>
			<xsl:apply-templates select="Склады/Склад" mode="TradeStock"/>
			<xsl:apply-templates select="Предложения/Предложение" mode="TradeOfferDataObject"/>
		</objects>
		<pages>
			<xsl:apply-templates select="Предложения/Предложение" mode="TradeOfferProduct"/>
		</pages>
		<entities>
			<xsl:apply-templates select="ТипыЦен/ТипЦены" mode="TradeOfferPriceTypes"/>
			<xsl:apply-templates select="Предложения/Предложение" mode="TradeOffer"/>
		</entities>
		<relations>
			<xsl:apply-templates select="Предложения/Предложение" mode="TradeOfferRelation"/>
		</relations>
	</xsl:template>

	<!-- Шаблон типа склада -->
	<xsl:template name="TradeStockType">
		<type id="trade-stock-type" guid="emarket-store" name="i18n::object-type-store">
			<base module="emarket" method="store"/>
			<fieldgroups/>
		</type>
	</xsl:template>

	<!-- Шаблон типов торговых предложений -->
	<xsl:template match="Предложение[contains(Ид, '#')]" mode="TradeOfferType">
		<xsl:variable name="type.id" select="document(concat('udata://exchange/getCmlProductTypeId/', substring-before(Ид, '#')))"/>
		<type id="{$type.id}">
			<base module="catalog" method="object"/>
			<fieldgroups>
				<group title="i18n::fields-group-trade-offers" name="trade_offers">
					<field name="trade_offer_image" title="i18n::field-trade-offer-image" visible="visible">
						<type name="i18n::field-type-img_file" data-type="img_file"/>
					</field>
					<xsl:apply-templates select="ХарактеристикиТовара/ХарактеристикаТовара" mode="TradeOfferTypeField"/>
					<field name="trade_offer_list" title="i18n::field-trade-offer-list">
						<type name="i18n::field-type-offer-id-list" data-type="offer_id_list" multiple="multiple"/>
					</field>
				</group>
			</fieldgroups>
		</type>
	</xsl:template>

	<!-- Шаблон характеристики торговых предложений -->
	<xsl:template match="ХарактеристикаТовара" mode="TradeOfferTypeField">
		<field name="{Наименование}" title="{Наименование}" visible="visible">
			<type name="i18n::field-type-string	" data-type="string"/>
		</field>
	</xsl:template>

	<!-- Шаблон склада -->
	<xsl:template match="Склад" mode="TradeStock">
		<object id="{Ид}" guid="{Ид}" name="{Наименование}" type-id="trade-stock-type"/>
	</xsl:template>

	<!-- Шаблон объектов данных торговых предложений -->
	<xsl:template match="Предложение[contains(Ид, '#')]" mode="TradeOfferDataObject">
		<xsl:variable name="page.id" select="substring-before(Ид, '#')"/>
		<xsl:variable name="type.id" select="document(concat('udata://exchange/getCmlProductTypeId/', $page.id))"/>
		<xsl:variable name="offer.id" select="substring-after(Ид, '#')"/>
		<object id="{$offer.id}" guid="{$offer.id}" name="{Наименование}" type-id="{$type.id}">
			<properties>
				<group title="i18n::fields-group-trade-offers" name="trade_offers">
					<xsl:apply-templates select="ХарактеристикиТовара/ХарактеристикаТовара" mode="TradeOfferCharacteristic"/>
					<xsl:apply-templates select="Картинка" mode="TradeOfferCharacteristic"/>
				</group>
			</properties>
		</object>
	</xsl:template>

	<!-- Шаблон значений характеристик торговых предложений -->
	<xsl:template match="ХарактеристикаТовара" mode="TradeOfferCharacteristic">
		<property name="{Наименование}">
			<value>
				<xsl:value-of select="Значение" disable-output-escaping="yes" />
			</value>
		</property>
	</xsl:template>

	<!-- Шаблон значений изображений торговых предложений -->
	<xsl:template match="Картинка" mode="TradeOfferCharacteristic">
		<xsl:if test="string-length(.)">
			<property name="trade_offer_image" type="img_file">
				<title>i18n::field-trade-offer-image</title>
				<value>./images/cms/data/<xsl:value-of select="."/></value>
			</property>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон цены и остатка товара без предложения с характеристиками -->
	<xsl:template match="Предложение[not(contains(Ид, '#'))]" mode="TradeOfferProduct">
		<xsl:apply-templates select="." />
	</xsl:template>

	<!-- Шаблон типов цен торговых предложений -->
	<xsl:template match="ТипЦены" mode="TradeOfferPriceTypes">
		<entity id="{Ид}" service="TradeOfferPriceTypeExchange">
			<name>
				<xsl:value-of select="Ид" disable-output-escaping="yes"/>
			</name>
			<title>
				<xsl:value-of select="Наименование" disable-output-escaping="yes"/>
			</title>
			<xsl:if test="$settings//item[@key='exchange.translator.1c_price_type_id'] = Ид">
				<is_default>1</is_default>
				<!-- переменная используется для вызова метода -->
				<xsl:variable name="define" select="document(concat('udata://exchange/defineCmlDefaultPriceTypeRelation/', Ид))" />
			</xsl:if>
		</entity>
	</xsl:template>

	<!-- Шаблон торговых предложений -->
	<xsl:template match="Предложение[contains(Ид, '#')]" mode="TradeOffer">
		<xsl:variable name="offer.id" select="substring-after(Ид, '#')"/>
		<xsl:variable name="page.id" select="substring-before(Ид, '#')"/>
		<xsl:variable name="type.id" select="document(concat('udata://exchange/getCmlProductTypeId/', $page.id))"/>
		<entity id="{$offer.id}" service="TradeOfferExchange">
			<type_id>
				<xsl:value-of select="$type.id"/>
			</type_id>
			<data_object_id>
				<xsl:value-of select="$offer.id"/>
			</data_object_id>
			<name>
				<xsl:value-of select="Наименование" disable-output-escaping="yes"/>
			</name>
			<vendor_code>
				<xsl:choose>
					<xsl:when test="Артикул">
						<xsl:value-of select="Артикул" disable-output-escaping="yes"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$offer.id" />
					</xsl:otherwise>
				</xsl:choose>
			</vendor_code>
			<bar_code>
				<xsl:value-of select="Штрихкод" disable-output-escaping="yes"/>
			</bar_code>
			<is_active>
				<xsl:text>1</xsl:text>
			</is_active>
			<weight>
				<xsl:value-of select="Вес"/>
			</weight>
			<width>
				<xsl:value-of select="Ширина"/>
			</width>
			<length>
				<xsl:value-of select="Длина"/>
			</length>
			<height>
				<xsl:value-of select="Высота"/>
			</height>
			<total_count>
				<xsl:value-of select="Количество"/>
			</total_count>
		</entity>
		<xsl:apply-templates select="Цены/Цена" mode="TradeOfferPrices">
			<xsl:with-param name="offer.id" select="$offer.id"/>
		</xsl:apply-templates>
		<xsl:apply-templates select="Склад" mode="TradeStockBalance">
			<xsl:with-param name="offer.id" select="$offer.id"/>
		</xsl:apply-templates>
	</xsl:template>

	<!-- Шаблон цен торговых предложений -->
	<xsl:template match="Цена" mode="TradeOfferPrices">
		<xsl:param name="offer.id"/>
		<xsl:variable name="currency.code" select="key('price-definition', ИдТипаЦены)/Валюта"/>
		<entity id="{$offer.id}#{ИдТипаЦены}" service="TradeOfferPriceExchange">
			<offer_id>
				<xsl:value-of select="$offer.id"/>
			</offer_id>
			<currency_id>
				<xsl:value-of select="document(concat('udata://exchange/getCmlCurrencyIdByAlias/?alias=', php:function('urlencode', string($currency.code))))"/>
			</currency_id>
			<value>
				<xsl:value-of select="ЦенаЗаЕдиницу"/>
			</value>
			<type_id>
				<xsl:value-of select="ИдТипаЦены"/>
			</type_id>
			<xsl:if test="$settings//item[@key='exchange.translator.1c_price_type_id'] = ИдТипаЦены">
				<is_main>1</is_main>
			</xsl:if>
		</entity>
	</xsl:template>

	<!-- Шаблон складского остатка -->
	<xsl:template match="Склад" mode="TradeStockBalance">
		<xsl:param name="offer.id"/>
		<entity id="{$offer.id}#{@ИдСклада}" service="TradeStockBalanceExchange">
			<offer_id>
				<xsl:value-of select="$offer.id"/>
			</offer_id>
			<stock_id>
				<xsl:value-of select="@ИдСклада"/>
			</stock_id>
			<value>
				<xsl:value-of select="@КоличествоНаСкладе"/>
			</value>
		</entity>
	</xsl:template>

	<!-- Шаблон списка торговых предложений товара -->
	<xsl:template match="Предложение[contains(Ид, '#')]" mode="TradeOfferRelation">
		<xsl:variable name="page.id" select="substring-before(Ид, '#')"/>
		<relation page-id="{$page.id}" field-name="trade_offer_list">
			<xsl:apply-templates select="../Предложение[contains(Ид, $page.id)]" mode="TradeOfferRelationOffer"/>
		</relation>
	</xsl:template>

	<!-- Шаблон торгового предложения из списка торговых предложений товара -->
	<xsl:template match="Предложение[contains(Ид, '#')]" mode="TradeOfferRelationOffer">
		<xsl:variable name="offer.id" select="substring-after(Ид, '#')"/>
		<offer id="{$offer.id}"/>
	</xsl:template>

	<xsl:template match="Предложение" mode="TradeOfferType"/>
	<xsl:template match="Предложение" mode="TradeOfferDataObject" />
	<xsl:template match="Предложение" mode="TradeOfferProduct"/>
	<xsl:template match="Предложение" mode="TradeOffer" />
	<xsl:template match="Предложение" mode="TradeOfferRelation"/>
	<xsl:template match="Предложение" mode="TradeOfferRelationOffer"/>

	<xsl:include href="custom/TradeOffers.xsl" />

</xsl:stylesheet>
