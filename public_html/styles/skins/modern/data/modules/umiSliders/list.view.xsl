<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон вкладки "Слайдеры" -->
	<xsl:template match="/result[@method = 'getSliders']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.create.buttons">
						<xsl:with-param name="slide.type">
							<xsl:value-of select="./slide_type"/>
						</xsl:with-param>
						<xsl:with-param name="slider.type">
							<xsl:value-of select="./slider_type"/>
						</xsl:with-param>
						<xsl:with-param name="create.method">
							<xsl:value-of select="./methods/method[type = 'create']/name"/>
						</xsl:with-param>
					</xsl:call-template>
					<xsl:call-template name="entities.help.button"/>
				</div>
				<div class="layout">
					<div class="column">
						<xsl:call-template name="entities.table.control">
							<xsl:with-param name="module">
								<xsl:value-of select="../@module"/>
							</xsl:with-param>
							<xsl:with-param name="drag.entity.type">
								<xsl:value-of select="./slide_type"/>
							</xsl:with-param>
							<xsl:with-param name="drag.target.entity.type">
								<xsl:value-of select="./slide_type"/>
							</xsl:with-param>
						</xsl:call-template>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content"/>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон табличного контрола -->
	<xsl:template name="entities.table.control">
		<xsl:param name="module"/>
		<xsl:param name="drag.entity.type"/>
		<xsl:param name="drag.target.entity.type"/>
		<div id="tableWrapper"/>
		<script src="/js/underscore-min.js?{$system-build}"/>
		<script src="/js/backbone-min.js?{$system-build}"/>
		<script src="/js/twig.min.js?{$system-build}"/>
		<script src="/js/backbone-relational.js?{$system-build}"/>
		<script src="/js/backbone.marionette.min.js?{$system-build}"/>
		<script src="/js/app.min.js?{$system-build}"/>
		<script>
			(function(){
				new umiDataController({
					container:'#tableWrapper',
					prefix:'<xsl:value-of select="concat($request-prefix, '/', $module)"/>',
					module:'<xsl:value-of select="$module"/>',
					configUrl:'/admin/umiSliders/flushDatasetConfiguration/.json',
					dragAllowed:true,
					dataProtocol: 'json',
					domain:'<xsl:value-of select="$domain-id"/>',
					lang:'<xsl:value-of select="$lang-id"/>',
					<xsl:if test="$domainsCount > 1">
						domainsList:<xsl:apply-templates select="$domains-list" mode="ndc_domain_list"/>,
					</xsl:if>
					dropValidator: function(target, dragged, mode){
						var dragTargetType = target.model.attributes.__type;
						dragTargetType = dragTargetType.replace(/(\\)/g, '');

						if (dragTargetType != '<xsl:value-of select="$drag.target.entity.type"/>') {
							return false;
						}

						for (var key in dragged) {
							var draggedElement = dragged[key];
							var draggedElementType = draggedElement.model.attributes.__type;
							draggedElementType = draggedElementType.replace(/(\\)/g, '');

							if (draggedElementType != '<xsl:value-of select="$drag.entity.type"/>'){
								return false;
							}
						}

						var validDragTypes = ['after', 'before'];

						for (var key in validDragTypes) {
							if (mode == validDragTypes[key]) {
								return mode;
							}
						}

						return false;
					}
				}).start();
			})()
		</script>
		<script src="/styles/skins/modern/design/js/{$module}.list.view.js" />
	</xsl:template>

	<!-- Шаблон кнопок создания сущностей -->
	<xsl:template name="entities.create.buttons">
		<!-- Тип сущности слайда -->
		<xsl:param name="slide.type"/>
		<!-- Тип сущности слайдера -->
		<xsl:param name="slider.type"/>
		<!-- Метод создания сущности -->
		<xsl:param name="create.method"/>
		<xsl:variable name="prefix" select="concat($request-prefix, '/', $module, '/', $create.method)"/>
		<div class="loc-left ndc-buttons" data-slide-type="{$slide.type}" data-slider-type="{$slider.type}">
			<a id="addSlider" class="btn color-blue loc-left" href="{$prefix}/?type={$slider.type}">
				<xsl:text>&label-create-slider;</xsl:text>
			</a>
			<a id="addSlide" class="btn color-blue loc-left hidden" href="{$prefix}/?type={$slide.type}">
				<xsl:text>&label-create-slide;</xsl:text>
			</a>
		</div>
	</xsl:template>

</xsl:stylesheet>
