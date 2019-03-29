<?xml version="1.0" encoding="UTF-8"?>

<!-- Шаблон преобразования формата CommerceML 2.04 в формат UMIDump 2.0 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns:udt="http://umi-cms.ru/2007/UData/templates"
	extension-element-prefixes="php"
	exclude-result-prefixes="xsl php udt">

	<xsl:output encoding="utf-8" method="xml" indent="yes" cdata-section-elements="value" />

	<!-- Identity map свойств товаров 1С-->
	<xsl:key name="property" match="/КоммерческаяИнформация/Классификатор/Свойства/*" use="Ид"/>

	<!-- Identity map типов цен пакета предложений 1С-->
	<xsl:key name="price-definition" match="/КоммерческаяИнформация/ПакетПредложений/ТипыЦен/ТипЦены" use="Ид"/>

	<!-- Типы цен пакета предложений 1С-->
	<xsl:variable name="price-types" select="/КоммерческаяИнформация/ПакетПредложений/ТипыЦен/ТипЦены" />

	<!-- Настройки интеграции -->
	<xsl:variable name="settings" select="document('udata://exchange/getTranslatorSettings/')/udata" />

	<!-- Активность раздела каталога по умолчанию -->
	<xsl:variable name="catalog_rubric_activity" select="number($settings//item[@key='exchange.translator.catalog_rubric_activity'])" />

	<!-- Отображаемость в меню раздела каталога по умолчанию -->
	<xsl:variable name="catalog_rubric_visible" select="number($settings//item[@key='exchange.translator.catalog_rubric_visible'])" />

	<!-- Активность объекта каталога по умолчанию -->
	<xsl:variable name="catalog_item_activity" select="number($settings//item[@key='exchange.translator.catalog_item_activity'])" />

	<!-- Отображаемость в меню объекта каталога по умолчанию -->
	<xsl:variable name="catalog_item_visible" select="number($settings//item[@key='exchange.translator.catalog_item_visible'])" />

	<!-- Шаблон раздела каталога по умолчанию -->
	<xsl:variable name="catalog_rubric_template" select="string($settings//item[@key='exchange.translator.catalog_rubric_template'])" />

	<!-- Шаблон объекта каталога по умолчанию -->
	<xsl:variable name="catalog_item_template" select="string($settings//item[@key='exchange.translator.catalog_item_template'])" />

	<!-- Использовать ли функционал торговых предложений для импорта пакета предложений 1С -->
	<xsl:variable name="isTradeOfferUsed" select="number($settings//item[@key='use_cml_trade_offers'])" />

	<!-- Восстанавливать ли товары и разделы каталога из модуля "Корзины" при импорте каталога товаров 1С -->
	<xsl:variable name="isCatalogItemsRestoreOnImport" select="number($settings//item[@key='restore_deleted_catalog_items_from_cml'])" />

	<!-- Идентификатор каталога из 1С -->
	<xsl:variable name="catalog-id" select="/КоммерческаяИнформация/Каталог/Ид" />

	<xsl:template match="/">
		<umidump xmlns:xlink="http://www.w3.org/TR/xlink" version="2.0">
			<meta>
				<source-name>commerceML2</source-name>
			</meta>
			<xsl:apply-templates select="КоммерческаяИнформация/Классификатор" />
			<xsl:apply-templates select="КоммерческаяИнформация/Каталог" />
			<xsl:apply-templates select="КоммерческаяИнформация/ПакетПредложений" />

			<xsl:if test="count(КоммерческаяИнформация/Документ)">
				<xsl:apply-templates select="КоммерческаяИнформация" mode="document" />
			</xsl:if>
		</umidump>
	</xsl:template>

	<!-- Заказы -->
	<xsl:template match="КоммерческаяИнформация" mode="document">
		<objects>
			<xsl:apply-templates select="Документ" />
		</objects>
	</xsl:template>

	<xsl:template match="Документ">
		<xsl:param name="properties" select="ЗначенияРеквизитов/ЗначениеРеквизита" />
		<xsl:param name="is_paid" select="string-length($properties[Наименование = 'Дата оплаты по 1С']/Значение) > 0" />
		<xsl:param name="is_dispatched" select="string-length($properties[Наименование = 'Дата отгрузки по 1С']/Значение) > 0" />
		<xsl:param name="is_canceled" select="$properties[Наименование = 'ПометкаУдаления']/Значение = 'true'" />
		<xsl:param name="is_ready" select="$properties[Наименование = 'Проведен']/Значение = 'true'" />

		<object id="{Номер}" update-only="1">
			<properties>
				<group name="order_props">
					<title>Свойства заказа</title>

					<xsl:if test="$is_paid">
						<property name="payment_status_id">
							<title>Статус оплаты</title>
							<value>accepted</value>
						</property>
					</xsl:if>

					<xsl:if test="$is_dispatched">
						<property name="status_id">
							<title>Статус заказа</title>
							<value>delivery</value>
						</property>
					</xsl:if>

					<xsl:choose>
						<xsl:when test="$is_canceled">
							<property name="status_id">
								<title>Статус заказа</title>
								<value>canceled</value>
							</property>
						</xsl:when>
						<xsl:when test="$is_ready">
							<property name="status_id">
								<title>Статус заказа</title>
								<value>ready</value>
							</property>
						</xsl:when>
					</xsl:choose>
				</group>

				<group name="integration_date">
					<title>Свойства для интеграции с 1С</title>

					<property name="1c_order_number" title="Номер заказа в 1С" type="string" allow-runtime-add="1">
						<type data-type="string" />
						<title>Номер заказа в 1С</title>
						<value><xsl:value-of select="$properties[Наименование = 'Номер по 1С']/Значение"/></value>
					</property>

					<xsl:if test="string-length($properties[Наименование = 'Дата оплаты по 1С']/Значение)">
						<property name="payment_date" title="Дата оплаты" type="date" allow-runtime-add="1">
							<type data-type="date" />
							<title>Дата оплаты</title>
							<value><xsl:value-of select="$properties[Наименование = 'Дата оплаты по 1С']/Значение" /></value>
						</property>
					</xsl:if>

					<xsl:if test="string-length($properties[Наименование = 'Номер оплаты по 1С']/Значение)">
						<property name="payment_document_num" title="Номер платежного документа" type="string" allow-runtime-add="1">
							<type data-type="string" />
							<title>Номер платежного документа</title>
							<value><xsl:value-of select="$properties[Наименование = 'Номер оплаты по 1С']/Значение"/></value>
						</property>
					</xsl:if>

					<property name="need_export" title="Выгружать заказ в 1С при следующем сеансе связи" type="boolean" allow-runtime-add="1">
						<type data-type="boolean" />
						<title>Выгружать заказ в 1С при следующем сеансе связи</title>
						<value>0</value>
					</property>
				</group>

			</properties>
		</object>
	</xsl:template>

	<!-- Предложения -->
	<xsl:template match="ПакетПредложений">
		<xsl:choose>
			<xsl:when test="$isTradeOfferUsed = 1">
				<xsl:apply-templates select="." mode="TradeOffers"/>
			</xsl:when>
			<xsl:otherwise>
				<pages>
					<xsl:apply-templates select="Предложения/Предложение" />
				</pages>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="Предложения/Предложение">
		<page id="{Ид}" update-only="1">
			<properties>
				<xsl:apply-templates select="Цены" />

				<group name="catalog_stores_props" title="Склады">
					<property name="common_quantity" title="Общее количество на складах" type="float" is-public="1" allow-runtime-add="1">
						<type data-type="float" />
						<title>Общее количество на складах</title>
						<value><xsl:value-of select="Количество"/></value>
					</property>
				</group>
			</properties>
		</page>
	</xsl:template>

	<xsl:template match="Цены">
		<xsl:param name="default-price" select="Цена[ИдТипаЦены = string($settings//item[@key='exchange.translator.1c_price_type_id'])]" />
		<group name="cenovye_svojstva" title="Ценовые свойства">
			<xsl:choose>
				<xsl:when test="count(Цена) > 1 and $default-price">
					<xsl:apply-templates select="$default-price" mode="default-price"/>
				</xsl:when>
				<xsl:when test="count(Цена) > 1 and not($default-price)">
					<xsl:apply-templates select="Цена[position() = 1]" mode="default-price"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="Цена" mode="default-price"/>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:apply-templates select="Цена" mode="other-price"/>
		</group>
	</xsl:template>

	<xsl:template match="Цена" mode="default-price">
		<xsl:variable name="currency_ref" select="concat('udata://exchange/getCurrencyCodeByAlias/?alias=', php:function('urlencode', string(Валюта)))" />
		<xsl:variable name="currency" select="document($currency_ref)/udata" />

		<property name="price" title="i18n::field-cena" type="price" is-public="1" allow-runtime-add="1">
			<type data-type="price" />
			<title>i18n::field-cena</title>
			<value currency_code="{$currency}"><xsl:value-of select="ЦенаЗаЕдиницу"/></value>
		</property>
	</xsl:template>

	<xsl:template match="Цена" mode="other-price">
		<xsl:variable name="price_id" select="concat('exchange.translator.1c_price.', ИдТипаЦены)" />

		<xsl:if test="$settings//item[@key=$price_id]">
			<xsl:variable name="currency_ref" select="concat('udata://exchange/getCurrencyCodeByAlias/?alias=', php:function('urlencode', string(Валюта)))" />
			<xsl:variable name="currency" select="document($currency_ref)/udata" />

			<property name="{$settings//item[@key=$price_id]}" is-public="1" visible="visible" allow-runtime-add="1">
				<xsl:attribute name="title">
					<xsl:value-of select="key('price-definition', ИдТипаЦены)/Наименование"/>
				</xsl:attribute>

				<type data-type="price" />
				<title>
					<xsl:value-of select="key('price-definition', ИдТипаЦены)/Наименование"/>
				</title>
				<value currency_code="{$currency}">
					<xsl:value-of select="ЦенаЗаЕдиницу"/>
				</value>
			</property>
		</xsl:if>
	</xsl:template>

	<!-- Товары -->
	<xsl:template match="Классификатор">
		<types>
			<!-- Корневой тип "Объект каталога" -->
			<type id="root-catalog-object-type" title="Объект каталога" parent-id="root-pages-type" locked="locked">
				<base module="catalog" method="object">Объекты каталога</base>
				<fieldgroups>
					<group name="common" title="Основные параметры">
						<field name="title" title="i18n::field-title">
							<type name="Строка" data-type="string"/>
						</field>

						<field name="h1" title="i18n::field-h1">
							<type name="Строка" data-type="string"/>
						</field>
					</group>
				</fieldgroups>
			</type>

			<!-- Корневой тип "Раздел каталога" -->
			<type id="root-catalog-category-type" title="Раздел каталога" parent-id="root-pages-type">
				<base module="catalog" method="category">Разделы каталога</base>
				<fieldgroups>
					<group name="common" title="Основные параметры" locked="locked">
						<field name="title" title="i18n::field-title">
							<type name="Строка" data-type="string"/>
						</field>

						<field name="h1" title="i18n::field-h1" visible="visible">
							<type name="Строка" data-type="string"/>
						</field>
					</group>
				</fieldgroups>
			</type>

			<xsl:apply-templates select="Группы" mode="types" />
		</types>
	</xsl:template>

	<xsl:template match="Группы/Группа" mode="types">
		<type id="{Ид}" title='1C: {Наименование}' parent-id="root-catalog-object-type">
			<xsl:if test="name(../../.) = 'Группа'">
				<xsl:attribute name="parent-id"><xsl:value-of select="../../Ид" /></xsl:attribute>
			</xsl:if>

			<base module="catalog" method="object">Объект каталога</base>

			<fieldgroups>
				<group name="product" title="1C: Общие свойства" visible="visible">
					<field name="photo" title="Картинка" visible="visible">
						<type name="Изображение" data-type="img_file"/>
					</field>

					<field name="1c_catalog_id" title="Идентификатор каталога 1С" visible="visible">
						<type name="Строка" data-type="string"/>
					</field>

					<field name="1c_product_id" title="Идентификатор в 1С" visible="visible">
						<type name="Строка" data-type="string"/>
					</field>

					<field name="artikul" title="Артикул" visible="visible">
						<type name="Строка" data-type="string"/>
					</field>

					<field name="bar_code" title="Штрих-код" visible="visible">
						<type name="Строка" data-type="string"/>
					</field>

					<field name="weight" title="Вес" visible="visible">
						<type name="Число с точкой" data-type="float"/>
					</field>
				</group>
			</fieldgroups>
		</type>

		<xsl:apply-templates select="Группы" mode="types" />
	</xsl:template>

	<xsl:template match="Каталог">
		<pages>
			<!-- Корневой каталог -->
			<page id="{$catalog-id}" type-id="root-catalog-category-type">
				<xsl:if test="$isCatalogItemsRestoreOnImport = 1">
					<xsl:attribute name="is-deleted">0</xsl:attribute>
				</xsl:if>

				<default-active>
					<xsl:value-of select="$catalog_rubric_activity" />
				</default-active>

				<default-visible>
					<xsl:value-of select="$catalog_rubric_visible" />
				</default-visible>

				<basetype module="catalog" method="category">Разделы каталога</basetype>

				<name><xsl:value-of select="Наименование" /></name>

				<xsl:if test="string-length($catalog_rubric_template)">
					<default-template>
						<xsl:value-of select="$catalog_rubric_template" />
					</default-template>
				</xsl:if>

				<properties>
					<group name="common">
						<title>Основные параметры</title>

						<property name="title" type="string">
							<title>i18n::field-title</title>
							<value><xsl:value-of select="Наименование" /></value>
						</property>

						<property name="h1" type="string">
							<title>i18n::field-h1</title>
							<value><xsl:value-of select="Наименование" /></value>
						</property>
					</group>
				</properties>
			</page>

			<!-- Разделы каталога -->
			<xsl:apply-templates select="/КоммерческаяИнформация/Классификатор/Группы" mode="groups" />

			<!-- Объекты каталога -->
			<xsl:apply-templates select="Товары/Товар" />
		</pages>
	</xsl:template>

	<xsl:template match="Группы/Группа" mode="groups">
		<page id="{Ид}" parentId="{$catalog-id}" type-id="root-catalog-category-type">
			<xsl:if test="$isCatalogItemsRestoreOnImport = 1">
				<xsl:attribute name="is-deleted">0</xsl:attribute>
			</xsl:if>

			<xsl:if test="name(../../.) = 'Группа'">
				<xsl:attribute name="parentId"><xsl:value-of select="../../Ид" /></xsl:attribute>
			</xsl:if>

			<default-active>
				<xsl:value-of select="$catalog_rubric_activity" />
			</default-active>

			<default-visible>
				<xsl:value-of select="$catalog_rubric_visible" />
			</default-visible>

			<basetype module="catalog" method="category">Разделы каталога</basetype>

			<name><xsl:value-of select="Наименование" /></name>

			<xsl:if test="string-length($catalog_rubric_template)">
				<default-template>
					<xsl:value-of select="$catalog_rubric_template" />
				</default-template>
			</xsl:if>

			<properties>
				<group name="common">
					<title>Основные параметры</title>

					<property name="title" type="string">
						<title>i18n::field-title</title>
						<default-value><xsl:value-of select="Наименование" /></default-value>
					</property>

					<property name="h1" type="string">
						<title>i18n::field-h1</title>
						<default-value><xsl:value-of select="Наименование" /></default-value>
					</property>
				</group>
			</properties>
		</page>

		<xsl:apply-templates select="Группы" mode="groups" />
	</xsl:template>

	<xsl:template match="Товары/Товар">
		<xsl:param name="group_id" select="string(Группы/Ид)" />

		<xsl:param name="name">
			<xsl:choose>
				<xsl:when test="string-length(ПолноеНаименование)">
					<xsl:value-of select="ПолноеНаименование" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="Наименование" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>

		<page id="{Ид}" parentId="{$group_id}" type-id="{$group_id}">
			<xsl:choose>
				<xsl:when test="Статус = 'Удален'">
					<xsl:attribute name="is-deleted">1</xsl:attribute>
				</xsl:when>
				<xsl:when test="$isCatalogItemsRestoreOnImport = 1">
					<xsl:attribute name="is-deleted">0</xsl:attribute>
				</xsl:when>
			</xsl:choose>

			<xsl:if test="not(Группы/Ид)">
				<xsl:attribute name="parentId"><xsl:value-of select="$catalog-id" /></xsl:attribute>
				<xsl:attribute name="type-id">root-catalog-object-type</xsl:attribute>
			</xsl:if>

			<default-active>
				<xsl:value-of select="$catalog_item_activity" />
			</default-active>

			<default-visible>
				<xsl:value-of select="$catalog_item_visible" />
			</default-visible>

			<basetype module="catalog" method="object">Объекты каталога</basetype>

			<name><xsl:value-of select="$name" /></name>

			<xsl:if test="string-length($catalog_item_template)">
				<default-template>
					<xsl:value-of select="$catalog_item_template" />
				</default-template>
			</xsl:if>

			<properties>
				<group name="common">
					<title>Основные параметры</title>

					<property name="title" type="string">
						<title>i18n::field-title</title>
						<default-value><xsl:value-of select="$name" /></default-value>
					</property>

					<property name="h1" type="string">
						<title>i18n::field-h1</title>
						<default-value><xsl:value-of select="$name" /></default-value>
					</property>
				</group>

				<group name="product">
					<title>1C: Общие свойства</title>

					<xsl:if test="string-length(Описание)">
						<property name="description" title="Описание" type="wysiwyg" allow-runtime-add="1">
							<type data-type="wysiwyg" />
							<title>Описание</title>
							<value>
								<xsl:choose>
									<xsl:when test="Описание/@ФорматHTML = 'true'">
										<xsl:value-of select="Описание"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="php:function('nl2br', string(Описание))" disable-output-escaping="yes" />
									</xsl:otherwise>
								</xsl:choose>
							</value>
						</property>
					</xsl:if>

					<property name="1c_catalog_id" type="string">
						<title>Идентификатор каталога 1С</title>
						<value><xsl:value-of select="$catalog-id" /></value>
					</property>

					<property name="1c_product_id" type="string">
						<title>Идентификатор в 1С</title>
						<value><xsl:value-of select="Ид" /></value>
					</property>

					<property name="artikul" type="string">
						<title>Артикул</title>
						<value><xsl:value-of select="Артикул" /></value>
					</property>

					<property name="bar_code" type="string">
						<title>Штрих-код</title>
						<value><xsl:value-of select="Штрихкод" /></value>
					</property>

					<property name="weight" type="float">
						<title>Вес</title>
						<value><xsl:value-of select="ЗначенияРеквизитов/ЗначениеРеквизита[Наименование = 'Вес']/Значение"/></value>
					</property>

					<xsl:apply-templates select="Картинка" />
				</group>

				<xsl:apply-templates select="ЗначенияСвойств" />
			</properties>
		</page>
	</xsl:template>

	<xsl:template match="Товар/ЗначенияСвойств">
		<group name="special" title="1C: Специфические свойства" visible="visible">
			<xsl:apply-templates select="ЗначенияСвойства"/>
		</group>
	</xsl:template>

	<xsl:template match="ЗначенияСвойств/ЗначенияСвойства">
		<xsl:param name="property" select="key('property', Ид)" />

		<xsl:param name="value-id">
			<xsl:choose>
				<xsl:when test="ИдЗначения"><xsl:value-of  select="string(ИдЗначения)"/></xsl:when>
				<xsl:when test="Значение"><xsl:value-of  select="string(Значение)"/></xsl:when>
				<xsl:otherwise>string</xsl:otherwise>
			</xsl:choose>
		</xsl:param>

		<xsl:param name="property_name">
			<xsl:choose>
				<xsl:when test="$property/Имя"><xsl:value-of select="$property/Имя"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="$property/Наименование"/></xsl:otherwise>
			</xsl:choose>
		</xsl:param>

		<xsl:param name="data-type">
			<xsl:choose>
				<xsl:when test="$property/ТипыЗначений/ТипЗначений/Тип = 'Число'">float</xsl:when>
				<xsl:when test="$property/ТипыЗначений/ТипЗначений/Тип = 'Булево'">boolean</xsl:when>
				<xsl:when test="$property/ТипыЗначений/ТипЗначений/Тип = 'Дата'">date</xsl:when>
				<xsl:when test="$property/ТипыЗначений/ТипЗначений/Тип = 'Справочник'">relation</xsl:when>
				<xsl:when test="$property/ТипЗначений = 'Справочник'">relation</xsl:when>
				<xsl:otherwise>string</xsl:otherwise>
			</xsl:choose>
		</xsl:param>

		<xsl:if test="$property">
			<property name="{$property_name}" title="{$property/Наименование}" type="{$data-type}" is-public="1" visible="visible" allow-runtime-add="1">
				<type data-type="{$data-type}" />
				<title><xsl:value-of select="$property/Наименование"/></title>

				<value>
					<xsl:choose>
						<xsl:when test="$data-type = 'relation'">
							<xsl:choose>
								<xsl:when test="$property/ТипыЗначений/ТипЗначений/ВариантыЗначений/ВариантЗначения[Ид = $value-id]">
									<xsl:apply-templates select="$property/ТипыЗначений/ТипЗначений/ВариантыЗначений/ВариантЗначения[Ид = $value-id]" mode="relation-value" />
								</xsl:when>
								<xsl:otherwise>
									 <xsl:apply-templates select="$property/ВариантыЗначений/Справочник[ИдЗначения = $value-id]" mode="relation-value" />
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise><xsl:value-of select="Значение" /></xsl:otherwise>
					</xsl:choose>
				</value>
			</property>
		</xsl:if>
	</xsl:template>

	<xsl:template match="ВариантыЗначений/ВариантЗначения" mode="relation-value">
		<item name="{Значение}" />
	</xsl:template>

	<xsl:template match="ВариантыЗначений/Справочник" mode="relation-value">
		<item name="{Значение}" />
	</xsl:template>

	<xsl:template match="ЗначенияСвойства/Значение">
		<value><xsl:value-of select="."/></value>
	</xsl:template>

	<xsl:template match="Товар/Картинка">
		<xsl:if test="string-length(.)">
			<property name="photo" type="img_file">
				<title>Картинка</title>
				<value>./images/cms/data/<xsl:value-of select="."/></value>
			</property>
		</xsl:if>
	</xsl:template>

	<xsl:include href="custom/commerceML2.xsl" />
	<xsl:include href="TradeOffers.xsl" />

</xsl:stylesheet>
