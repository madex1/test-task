<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				extension-element-prefixes="php"
				exclude-result-prefixes="xsl php">

	<xsl:output method="xml" encoding="utf-8"/>

	<xsl:variable name="currency" select="document('udata://emarket/currencySelector/')/udata/items" />
	<xsl:variable name="default-currency" select="$currency/item[@default = 'default']/@codename" />

	<xsl:key name="property" match="/umidump/types/type[base/@module = 'catalog'][base/@method = 'object']//field" use="@id"/>
	<xsl:key name="type" match="/umidump/types/type[base/@module = 'catalog'][base/@method = 'object']" use="@id"/>

	<xsl:template match="umidump[@version='2.0']">
		<xsl:variable name="date" select="php:function('date', 'Y-m-d')" />

		<КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="{$date}">
			<Классификатор>
				<Ид><xsl:value-of select="concat(meta/source-name , '000000000')"/></Ид>
				<Наименование>Классификатор с сайта "<xsl:value-of select="meta/domain" />"</Наименование>
				<Владелец>
					<Ид><xsl:value-of select="concat(meta/source-name , '000000000')"/></Ид>
					<Наименование>Сайт http://<xsl:value-of select="meta/domain" /></Наименование>
				</Владелец>

				<Группы>
					<xsl:apply-templates select="pages/page[basetype/@module = 'catalog' and basetype/@method = 'category']" mode="group">
						<xsl:with-param name="siteId" select="meta/source-name"/>
					</xsl:apply-templates>
				</Группы>

				<Свойства>
					<xsl:apply-templates select="types/type[base/@module = 'catalog']/fieldgroups/group[not(@name = 'common' or @name = 'cenovye_svojstva' or @name = 'product' or @name = 'menu_view' or @name = 'more_params' or @name = 'rate_voters' or @name = 'locks' or @name = 'catalog_stores_props')]/field[not(@name = 'photo' or @name='description' or @name='weight' or @name='bar_code')]" mode="property">
						<xsl:with-param name="siteId" select="meta/source-name"/>
					</xsl:apply-templates>
				</Свойства>
			</Классификатор>

			<Каталог СодержитТолькоИзменения="false">
				<Ид><xsl:value-of select="concat(meta/source-name , '000000000')"/></Ид>
				<ИдКлассификатора><xsl:value-of select="concat(meta/source-name , '000000000')"/></ИдКлассификатора>
				<Наименование>Каталог товаров сайта "<xsl:value-of select="meta/domain" />"</Наименование>
				<Владелец>
					<Ид><xsl:value-of select="concat(meta/source-name , '000000000')"/></Ид>
					<Наименование>Сайт http://<xsl:value-of select="meta/domain" /></Наименование>
				</Владелец>

				<Товары>
					<xsl:apply-templates select="pages/page[basetype/@module = 'catalog' and basetype/@method = 'object']" mode="good">
						<xsl:with-param name="siteId" select="meta/source-name"/>
					</xsl:apply-templates>
				</Товары>
			</Каталог>
			
			<ПакетПредложений СодержитТолькоИзменения="false">
				<Ид><xsl:value-of select="concat(meta/source-name , '000000000')"/></Ид>
				<Наименование>Пакет предложений сайта "<xsl:value-of select="meta/domain" />"</Наименование>
				<ИдКаталога><xsl:value-of select="concat(meta/source-name , '000000000')"/></ИдКаталога>
				<ИдКлассификатора><xsl:value-of select="concat(meta/source-name , '000000000')"/></ИдКлассификатора>
				<Владелец>
					<Ид><xsl:value-of select="concat(meta/source-name , '000000000')"/></Ид>
					<Наименование>Сайт http://<xsl:value-of select="meta/domain" /></Наименование>
				</Владелец>

				<ТипыЦен>
					<ТипЦены>
						<Ид><xsl:value-of select="concat(meta/source-name , '000000000')"/></Ид>
						<Наименование>Розничная</Наименование>
						<Валюта><xsl:value-of select="$default-currency"/></Валюта>
						<Налог>
							<Наименование>НДС</Наименование>
							<УчтеноВСумме>true</УчтеноВСумме>
						</Налог>
					</ТипЦены>
				</ТипыЦен>
				
				<Предложения>
					<xsl:apply-templates select="pages/page[basetype/@module = 'catalog' and basetype/@method = 'object']" mode="offer">
						<xsl:with-param name="siteId" select="meta/source-name"/>
					</xsl:apply-templates>
				</Предложения>

			</ПакетПредложений>

		</КоммерческаяИнформация>
	</xsl:template>
	
	<xsl:template match="page[/umidump/pages/page[basetype/@module = 'catalog' and basetype/@method = 'category']/@id = @parentId]" mode="group"/>

	<xsl:template match="page" mode="group">
		<xsl:param name="siteId"/>
		<xsl:param name="id" select="@id" />
		<xsl:param name="groupId" select="php:function('sprintf', '%09s', string($id))" />
		<xsl:param name="subgroups" select="/umidump/pages/page[@parentId = $id and basetype/@module = 'catalog' and basetype/@method = 'category']" />

		<Группа>
			<Ид><xsl:value-of select="concat($siteId, $groupId)"/></Ид>
			<Наименование><xsl:value-of select="name"/></Наименование>
			<xsl:if test="count($subgroups)">
				<Группы>
					<xsl:apply-templates select="$subgroups" mode="subgroup">
						<xsl:with-param name="siteId" select="$siteId"/>
					</xsl:apply-templates>
				</Группы>
			</xsl:if>
		</Группа>
	</xsl:template>
	
	<xsl:template match="page" mode="subgroup">
		<xsl:param name="siteId"/>
		<xsl:param name="id" select="@id" />
		<xsl:param name="groupId" select="php:function('sprintf', '%09s', string($id))" />
		<xsl:param name="subgroups" select="/umidump/pages/page[@parentId = $id and basetype/@module = 'catalog' and basetype/@method = 'category']" />

		<Группа>
			<Ид><xsl:value-of select="concat($siteId, $groupId)"/></Ид>
			<Наименование><xsl:value-of select="name"/></Наименование>
			<xsl:if test="count($subgroups)">
				<Группы>
					<xsl:apply-templates select="$subgroups" mode="subgroup">
						<xsl:with-param name="siteId" select="$siteId"/>
					</xsl:apply-templates>
				</Группы>
			</xsl:if>
		</Группа>
	</xsl:template>

	<xsl:template match="page" mode="good">
		<xsl:param name="parent-id" select="@parentId" />
		<xsl:param name="property" select="properties/group/property" />
		<xsl:param name="id" select="@id" />
		
		<xsl:param name="siteId"/>
		<xsl:param name="goodId" select="php:function('sprintf', '%09s', string($id))" />
		<xsl:param name="goodParentId" select="php:function('sprintf', '%09s', string($parent-id))" />


		<Товар>
			<Ид><xsl:value-of select="concat($siteId, $goodId)"/></Ид>
			<Артикул><xsl:value-of select="$property[@name = 'artikul']/value" /></Артикул>
			<Штрихкод><xsl:value-of select="$property[@name = 'bar_code']/value" /></Штрихкод>
			<Наименование><xsl:value-of select="name" /></Наименование>
			<ПолноеНаименование><xsl:value-of select="name" /></ПолноеНаименование>
			<БазоваяЕдиница Код="796" НаименованиеПолное="Штука" МеждународноеСокращение="PCE">шт</БазоваяЕдиница>
			<Описание><xsl:value-of select="$property[@name = 'description']/value" /></Описание>
			<Картинка><xsl:value-of select="$property[@name = 'photo']/value" /></Картинка>

			<Группы>
				<xsl:if test="@parentId">
					<Ид><xsl:value-of select="concat($siteId, $goodParentId)"/></Ид>
				</xsl:if>
			</Группы>

			<ЗначенияСвойств>
				<xsl:apply-templates select="key('type', @type-id)/fieldgroups/group[not(@name = 'common' or @name = 'cenovye_svojstva' or @name = 'product' or @name = 'menu_view' or @name = 'more_params' or @name = 'rate_voters' or @name = 'locks' or @name = 'catalog_stores_props')]/field[not(@name = 'photo' or @name='description' or @name='weight' or @name='bar_code')]"  mode="good-property">
					<xsl:with-param name="prop-values" select="properties//property" />
					<xsl:with-param name="siteId" select="$siteId"/>
				</xsl:apply-templates>
			</ЗначенияСвойств>

			<ЗначенияРеквизитов>
				<ЗначениеРеквизита>
					<Наименование>ВидНоменклатуры</Наименование>
					<Значение><xsl:value-of select="/umidump/pages/page[@id = $parent-id]/name" /></Значение>
				</ЗначениеРеквизита>
				<ЗначениеРеквизита>
					<Наименование>ТипНоменклатуры</Наименование>
					<Значение>Товар</Значение>
				</ЗначениеРеквизита>
				<ЗначениеРеквизита>
					<Наименование>Полное наименование</Наименование>
					<Значение><xsl:value-of select="name" /></Значение>
				</ЗначениеРеквизита>
				<ЗначениеРеквизита>
					<Наименование>Вес</Наименование>
					<Значение><xsl:value-of select="properties//property[@name = 'weight']/value" /></Значение>
				</ЗначениеРеквизита>
			</ЗначенияРеквизитов>
		</Товар>
	</xsl:template>


	<xsl:template match="page" mode="offer">
		<xsl:param name="properties" select="properties/group/property" />
		<xsl:param name="id" select="@id" />
		<xsl:param name="siteId"/>
		<xsl:param name="offerId" select="php:function('sprintf', '%09s', string($id))" />
		
		<Предложение>
			<Ид><xsl:value-of select="concat($siteId, $offerId)"/></Ид>
			<Штрихкод><xsl:value-of select="$properties[@name = 'bar_code']/value" /></Штрихкод>
			<Наименование><xsl:value-of select="name" /></Наименование>
			
			<Цены>
				<Цена>
					<Представление><xsl:value-of select="$properties[@name = 'price']/value" />&#160;<xsl:value-of select="$default-currency" /></Представление>
					<ИдТипаЦены><xsl:value-of select="concat($siteId, '000000000')"/></ИдТипаЦены>
					<ЦенаЗаЕдиницу><xsl:value-of select="$properties[@name = 'price']/value" /></ЦенаЗаЕдиницу>
					<Валюта><xsl:value-of select="$default-currency" /></Валюта>
					<Единица>шт</Единица>
					<Коэффициент>1</Коэффициент>
				</Цена>
			</Цены>
			<Количество>
				<xsl:choose>
					<xsl:when test="$properties[@name = 'common_quantity']/value">
						<xsl:value-of select="php:function('number_format', number($properties[@name = 'common_quantity']/value), 2, ',', '')"/>
					</xsl:when>
					<xsl:otherwise>0,00</xsl:otherwise>
				</xsl:choose>
			</Количество>
		</Предложение>
	</xsl:template>

	<xsl:template match="field" mode="good-property">
		<xsl:param name="id" select="@id" />
		<xsl:param name="prop-values" />
		<xsl:param name="prop-value" select="$prop-values[@id = $id]/value" />
		<xsl:param name="siteId"/>
		<xsl:param name="fieldId" select="@id"/>
		<xsl:param name="typeId" select="/umidump/types/type[base/@module = 'catalog' and fieldgroups/group/field/@id = $fieldId]/@id"/>
		<xsl:param name="fieldFullId" select="php:function('sprintf', '%09s', concat($typeId, '_', @id))" />
		
		<ЗначенияСвойства>
			<Ид><xsl:value-of select="concat($siteId, $fieldFullId)"/></Ид>
			<Значение>
			<xsl:choose>
				<xsl:when test="type/@data-type = 'relation'">
					<xsl:value-of select="$prop-value/item/@name" />
				</xsl:when>
				<xsl:when test="type/@data-type = 'date' and string-length($prop-value/@unix-timestamp)">
					<xsl:value-of select="php:function('date', 'Y-m-d H:i', string($prop-value/@unix-timestamp))" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$prop-value" />
				</xsl:otherwise>
			</xsl:choose>
			</Значение>
		</ЗначенияСвойства>
	</xsl:template>

	<xsl:template match="field[type/@multiple = 'multiple']" mode="good-property"/>

	<xsl:template match="field" mode="property">
		<xsl:param name="siteId"/>
		<xsl:param name="fieldId" select="@id"/>
		<xsl:param name="typeId" select="/umidump/types/type[base/@module = 'catalog' and fieldgroups/group/field/@id = $fieldId]/@id"/>
		<xsl:param name="fieldFullId" select="php:function('sprintf', '%09s', concat($typeId, '_', @id))" />
		
		<xsl:if test="generate-id(.) = generate-id(key('property', ./@id))">
			<Свойство>
				<Ид><xsl:value-of select="concat($siteId, $fieldFullId)"/></Ид>
				<Наименование><xsl:value-of select="@title" /></Наименование>
				<ТипЗначений>
					<xsl:choose>
						<xsl:when test="type/@data-type = 'int'">Число</xsl:when>
						<xsl:when test="type/@data-type = 'float'">Число</xsl:when>
						<xsl:when test="type/@data-type = 'price'">Число</xsl:when>
						<xsl:when test="type/@data-type = 'boolean'">Булево</xsl:when>
						<xsl:when test="type/@data-type = 'date'">Дата</xsl:when>
						<xsl:otherwise>Строка</xsl:otherwise>
					</xsl:choose>
				</ТипЗначений>
			</Свойство>
		</xsl:if>
	</xsl:template>

	<xsl:template match="field[type/@multiple = 'multiple']" mode="property"/>
	
	<xsl:include href="custom/commerceML.xsl" />

</xsl:stylesheet>
