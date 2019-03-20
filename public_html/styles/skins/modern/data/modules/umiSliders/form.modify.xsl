<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				extension-element-prefixes="php"
				xmlns:umi="http://www.w3.org/1999/xhtml">

	<!-- Идентификаторы родительской сущности -->
	<xsl:param name="rel"/>
	<!-- Тип редактируемой сущности -->
	<xsl:param name="type"/>

	<!-- Шаблон формы редактирования или добавления сущности -->
	<xsl:template match="data[@type = 'form' and (@action = 'modify' or @action = 'create') and @ui_type = 'ndc']">
		<xsl:variable name="module" select="../@module"/>
		<xsl:variable name="entity.id" select="./@entity_id"/>

		<xsl:if test="@action = 'modify'">
			<xsl:call-template name="entity.edit.form.toolbar">
				<xsl:with-param name="delete.enabled">
					<xsl:value-of select="./@deleting_enabled"/>
				</xsl:with-param>
				<xsl:with-param name="delete.module">
					<xsl:value-of select="$module"/>
				</xsl:with-param>
				<xsl:with-param name="delete.method">
					<xsl:value-of select="./methods/method[type = 'delete']/__name"/>
				</xsl:with-param>
				<xsl:with-param name="delete.entity.id">
					<xsl:value-of select="$entity.id"/>
				</xsl:with-param>
				<xsl:with-param name="delete.entity.type">
					<xsl:value-of select="$type"/>
				</xsl:with-param>
			</xsl:call-template>
		</xsl:if>

		<script src="/styles/skins/modern/design/js/form.modify.js" />

		<div class="tabs-content module {$module}-module">
			<div class="section selected">
				<xsl:call-template name="ndc.form">
					<xsl:with-param name="entity.id">
						<xsl:value-of select="$entity.id"/>
					</xsl:with-param>
					<xsl:with-param name="action">
						<xsl:value-of select="./@form_action"/>
					</xsl:with-param>
					<xsl:with-param name="referrer">
						<xsl:value-of select="../referer-uri"/>
					</xsl:with-param>
				</xsl:call-template>
			</div>
		</div>

		<xsl:call-template name="error-checker" >
			<xsl:with-param name="launch" select="1" />
		</xsl:call-template>
	</xsl:template>

	<!-- Шаблон формы создания и добавления сущностей -->
	<xsl:template name="ndc.form" >
		<xsl:param name="entity.id" />
		<xsl:param name="action" />
		<xsl:param name="referrer" />
		<xsl:apply-templates select="$errors" />

		<xsl:variable name="entity.type">
			<xsl:value-of select="concat('type=', $type)" />
		</xsl:variable>

		<xsl:variable name="relation">
			<xsl:value-of select="concat('rel[]=', $rel)" />
		</xsl:variable>

		<xsl:variable name="domain">
			<xsl:value-of select="concat('domain_id[]=', $domain-id)" />
		</xsl:variable>

		<xsl:variable name="language">
			<xsl:value-of select="concat('lang_id[]=', $lang-id)" />
		</xsl:variable>

		<xsl:variable name="request-params">
			<xsl:value-of select="
					concat('?', $entity.type, '&amp;', $relation, '&amp;', $domain, '&amp;', $language)" />
		</xsl:variable>

		<form class="form_modify" method="post"
			  action="{$request-prefix}/{$module}/{$action}/{$entity.id}/{$request-params}"
			  enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{$referrer}" id="form-referer"/>
			<div class="panel-settings">
				<div class="title">
					<h3>
						<xsl:text>&label-entities-params;</xsl:text>
					</h3>
				</div>
				<div class="content">
					<div class="layout">
						<xsl:apply-templates select="fields" mode="ndc.fields">
							<xsl:with-param name="entity.id">
								<xsl:value-of select="$entity.id"/>
							</xsl:with-param>
						</xsl:apply-templates>
					</div>
				</div>
			</div>
			<div class="row">
				<xsl:call-template name="entity.edit.form.buttons">
					<xsl:with-param name="create.mode">
						<xsl:choose>
							<xsl:when test="@action = 'create'">1</xsl:when>
							<xsl:otherwise>0</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
				</xsl:call-template>
			</div>
		</form>
		<xsl:call-template name="wysiwyg-init" />
	</xsl:template>

	<!-- Шаблон списка полей формы редактирования сущности -->
	<xsl:template match="fields" mode="ndc.fields">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<!--

		Обрабатываемые данные:

		<fields>
			<field required="1" title="Название"/>
			<field title="Скорость переключения слайдов"/>
			<field title="Включено переключение слайдов по кругу"/>
			<field title="Время задержки перед переключением слайдов"/>
			<field title="Включено автоматическое переключение слайдов"/>
			<field title="Количество отображаемых слайдов в слайдере"/>
			<field title="Включен случайный порядок слайдов"/>
			<field required="1" title="Идентификатор"/>
			<field required="1" title="Домен"/>
			<field required="1" title="Язык"/>
		</fields>

		-->
		<div class="column">
			<div class="row">
				<!-- Первой группой выводим все поля, обязательные для заполнения -->
				<xsl:apply-templates select="field[@required = 1]" mode="ndc.field">
					<xsl:with-param name="entity.id">
						<xsl:value-of select="$entity.id"/>
					</xsl:with-param>
				</xsl:apply-templates>
			</div>
			<div class="row">
				<!-- Во второй группе требуется вывести 4 поля, первое уже выведено, так как обязательное,
				поэтому выводим до 5 первых необязательных полей ( < 6) -->
				<xsl:apply-templates select="field[position() &lt; 6 and not(@required)]" mode="ndc.field">
					<xsl:with-param name="entity.id">
						<xsl:value-of select="$entity.id"/>
					</xsl:with-param>
				</xsl:apply-templates>
			</div>
			<div class="row">
				<!-- Выводим все оставшиеся необязательные поля, то есть поля, начинающиеся с 6 позиции ( > 5) -->
				<xsl:apply-templates select="field[position() &gt; 5 and not(@required)]" mode="ndc.field">
					<xsl:with-param name="entity.id">
						<xsl:value-of select="$entity.id"/>
					</xsl:with-param>
				</xsl:apply-templates>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон поля "Идентификатор" слайдера -->
	<xsl:template match="field[@name = 'custom_id' and @type = 'string']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div class="col-md-6 default-empty-validation">
			<xsl:apply-templates select="." mode="ndc.field.title"/>
			<span>
				<input class="default" type="text" name="{concat('data[', $entity.id, '][', ./@name, ']')}"
					   value="{.}" id="{generate-id()}">
					<xsl:if test="$entity.id != 'new' and string-length(string(.)) > 0">
						<xsl:attribute name="disabled">
							<xsl:text>disabled</xsl:text>
						</xsl:attribute>
					</xsl:if>
				</input>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон поля типа "Ссылка" формы редактирования сущности -->
	<xsl:template match="field[@name = 'link' and @type = 'string']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="./@required">
						<xsl:text>col-md-6 default-empty-validation</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>col-md-6</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates select="." mode="ndc.field.title"/>
			<div class="pick-element layout-row-icon">
				<div class="layout-col-control">
					<input class="default" type="text" name="{concat('data[', $entity.id, '][', ./@name, ']')}"
						   value="{.}" id="{generate-id()}"/>
				</div>
				<div class="layout-col-icon" onclick="showTree({generate-id()})">
					<img class="treeButton icon-action" src="/images/cms/admin/mac/tree.png" height="18"/>
				</div>
			</div>
			<script>
				function showTree(inputData) {
					var $input = $(inputData);

					window.parent.saveSlideLink = function(id) {
						$input.val('%content get_page_url(' + id + ')%');
						window.parent.jQuery.closePopupLayer($input.attr('id'));
					};

					$.openPopupLayer({
						name : $input.attr('id'),
						title: getLabel('js-label-select-page'),
						width : 620,
						height : 340,
						url : "/styles/common/js/tree.html?callback=saveSlideLink"
					});
				};
			</script>
		</div>
	</xsl:template>

	<!-- Шаблон поля типа "Идентификатор домена" формы редактирования сущности -->
	<xsl:template match="field[@name = 'domain_id' and @type = 'integer']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<xsl:param name="selected.id">
			<xsl:value-of select="."/>
		</xsl:param>
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="./@required">
						<xsl:text>col-md-6 default-empty-validation</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>col-md-6</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates select="." mode="ndc.field.title"/>
			<div class="layout-row-icon">
				<div class="layout-col-control selectize-container">
					<select class="default newselect required"
							autocomplete="off" name="{concat('data[', $entity.id, '][', ./@name, ']')}">
						<xsl:if test="$selected.id">
							<option value="{$selected.id}" selected="selected">
								<xsl:value-of select="$domains-list/domain[@id = $selected.id]/@host" />
							</option>
						</xsl:if>
						<xsl:apply-templates select="$domains-list" mode="domain_id">
							<xsl:with-param name="selected.id" select="$selected.id" />
						</xsl:apply-templates>
					</select>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="domains/domain" mode="domain_id">
		<xsl:param name="selected.id"/>
		<xsl:if test="$selected.id != @id">
			<option value="{@id}">
				<xsl:value-of select="@host" />
			</option>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон поля типа "Идентификатор языка" формы редактирования сущности -->
	<xsl:template match="field[@name = 'language_id' and @type = 'integer']" mode="ndc.field">
		<!-- Идентификатор сущности -->
		<xsl:param name="entity.id"/>
		<xsl:param name="selected.id">
			<xsl:value-of select="."/>
		</xsl:param>
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="./@required">
						<xsl:text>col-md-6 default-empty-validation</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>col-md-6</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates select="." mode="ndc.field.title"/>
			<div class="layout-row-icon">
				<div class="layout-col-control selectize-container">
					<select class="default newselect required"
							autocomplete="off"
							name="{concat('data[', $entity.id, '][', ./@name, ']')}">
						<xsl:if test="$selected.id">
							<option value="{$selected.id}" selected="selected">
								<xsl:value-of select="$site-langs/items/item[@id = $selected.id]" />
							</option>
						</xsl:if>
						<xsl:apply-templates select="$site-langs" mode="lang_id">
							<xsl:with-param name="selected.id" select="$selected.id" />
						</xsl:apply-templates>
					</select>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'getLangsList']" mode="lang_id">
		<xsl:param name="selected.id"/>
		<xsl:apply-templates select="items/item" mode="lang_id">
			<xsl:with-param name="selected.id" select="$selected.id" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="item" mode="lang_id">
		<xsl:param name="selected.id"/>
		<xsl:if test="$selected.id != @id">
			<option value="{@id}">
				<xsl:value-of select="." />
			</option>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>