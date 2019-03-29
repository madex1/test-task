<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi"
	xmlns:php="http://php.net/xsl">

	<xsl:template match="@unix-timestamp">
		<xsl:value-of select="document(concat('udata://system/convertDate/', .))/udata" />
	</xsl:template>

	<xsl:template name="ui-new-table">
        <xsl:param name="controlParam">unknown</xsl:param>
        <xsl:param name="dragAllowed">'false'</xsl:param>
        <xsl:param name="configUrl">unknown</xsl:param>
        <xsl:param name="perPageLimit">10</xsl:param>
        <xsl:param name="dropValidator">0</xsl:param>
        <xsl:param name="toolbarFunction">0</xsl:param>
        <xsl:param name="toolbarMenu">0</xsl:param>
        <xsl:param name="showDomain">0</xsl:param>
        <xsl:param name="showSelectButtons">1</xsl:param>
        <xsl:param name="showResetButtons">1</xsl:param>
        <xsl:param name="pageLimits">[10, 20, 50, 100]</xsl:param>

        <xsl:variable name="module" select="/result/@module" />
        <xsl:variable name="prefix" select="concat('/admin/', $module)" />
        <xsl:variable name="lang" select="/result/@lang-id" />
        <xsl:variable name="domain" select="/result/@domain-id" />

		<div id="tableWrapper"/>
		<script src="/js/underscore-min.js"/>
		<script src="/js/backbone-min.js"/>
		<script src="/js/backbone-relational.js"/>
		<script src="/js/backbone.marionette.min.js"/>
		<script src="/js/app.min.js"/>
		<script>
            new umiDataController({
                container:'#tableWrapper',
                prefix: '<xsl:value-of select="$prefix"/>',
                module:'<xsl:value-of select="$module"/>',
                controlParam:'<xsl:value-of select="$controlParam"/>',
                dataProtocol: 'json',
                dragAllowed:<xsl:value-of select="$dragAllowed"/>,
                domain:<xsl:value-of select="$domain"/>,
                lang:<xsl:value-of select="$lang"/>,
                configUrl:'<xsl:value-of select="$configUrl" />',
                perPageLimit:<xsl:value-of select="$perPageLimit"/>,
                <xsl:if test="($showDomain) = 1 and ($domainsCount>1)">
                domainsList:<xsl:apply-templates select="$domains-list" mode="ndc_domain_list"/>,
                </xsl:if>
                <xsl:if test="not($dropValidator = 0)">
                dropValidator: <xsl:value-of select="$dropValidator"/>,
                </xsl:if>
                <xsl:if test="not($toolbarFunction = 0)">
                toolbarFunction: <xsl:value-of select="$toolbarFunction"/>,
                </xsl:if>
                <xsl:if test="not($toolbarMenu = 0)">
                toolbarMenu: <xsl:value-of select="$toolbarMenu"/>,
                </xsl:if>
				showSelectButtons: <xsl:value-of select="$showSelectButtons"/>,
				showResetButtons: <xsl:value-of select="$showResetButtons"/>,
				pageLimits: <xsl:value-of select="$pageLimits"/>
            }).start();
		</script>
	</xsl:template>

    <xsl:template match="domains" mode="ndc_domain_list">
        [
            <xsl:apply-templates select="domain" mode="ndc_domain_list"/>
        ]
    </xsl:template>

    <xsl:template match="domain" mode="ndc_domain_list">
        { 
            id: <xsl:value-of select="@id"/>,
            host: '<xsl:value-of select="@host"/>',
            langId: <xsl:value-of select="@lang-id"/>,
            <xsl:if test="@is-default = 1">
            isDefault: 1
            </xsl:if>
        },
    </xsl:template>

	<xsl:template name="ui-smc-table">
		<xsl:param name="control-id" select="concat($module, '-', $method)" />
		<!-- ID языковой версии -->
		<xsl:param name="control-lang-id" select="$lang-id" />
		<xsl:param name="control-domain-id" select="$domain-id" />

		<!-- ID типа данных элементов, которые будут запрошены -->
		<xsl:param name="control-type-id" />
		<!-- JS-код, который будут выполнен по окончанию полной загрузки данных -->
		<xsl:param name="data-set-init-end-requests" />
		<!-- JS-код, который будут выполнен по окончанию отрисовки контрола -->
		<xsl:param name="on-render-complete" />
		<!-- Развернуть все деревья иерархии -->
		<xsl:param name="expand-all" />
		<!-- Игнорировать иерархическую структуру страниц -->
		<xsl:param name="ignore-hierarchy" />
		<xsl:param name="control-module" select="$module" />
		<xsl:param name="control-params" />
		<xsl:param name="control-host" select="/result/@domain" />
		<!-- Отображает форму поиска -->
		<xsl:param name="search-show">1</xsl:param>
		<xsl:param name="search-advanced-allow">0</xsl:param>
		<!-- Отображает панель инструментов -->
		<xsl:param name="show-toolbar">1</xsl:param>
		<!-- Отображает список доменов -->
		<xsl:param name="domains-show">0</xsl:param>
		<!-- Тип элементов -->
		<xsl:param name="content-type">pages</xsl:param>
		<!-- Включает плоский режим, когда отсутствует иерархия -->
		<xsl:param name="flat-mode">0</xsl:param>
		<!-- Включает управление активностью объектов -->
		<xsl:param name="enable-objects-activity">0</xsl:param>
		<!-- Допутимо ли перемещение элементов -->
		<xsl:param name="allow-drag">0</xsl:param>
		<!-- Отключить кнопки импорта/экспорта CSV -->
		<xsl:param name="disable-csv-buttons">0</xsl:param>
		<!-- Скрыть кнопки импорта/экспорта CSV -->
		<xsl:param name="hide-csv-import-button">0</xsl:param>
		<xsl:param name="js-add-buttons" />
		<!-- JS-функция, которая вызывается при получении значений полей -->
		<xsl:param name="js-value-callback" />
		<xsl:param name="js-dataset-events" />
		<!-- Игнорировать редактирование определенных полей -->
		<xsl:param name="js-ignore-props-edit">[]</xsl:param>
		<!-- Включить режим быстрого редактирования -->
		<xsl:param name="enable-edit">true</xsl:param>
		<xsl:param name="js-visible-props-menu">''</xsl:param>
		<xsl:param name="js-required-props-menu">[]</xsl:param>
		<xsl:param name="js-sequence-props-menu">[]</xsl:param>
		<!-- На запрашивать данные о виртуальных копиях-->
		<xsl:param name="js-disable-virtual-copy">0</xsl:param>
		<!-- JS-код для пунктов контекстного меню -->
		<xsl:param name="menu" select="''" />
		<xsl:param name="toolbarmenu" select="''" />
		<xsl:param name="label_first_column" select="''" />
		<xsl:param name="js-has-checkboxes" select="'true'" />
		<xsl:param name="disable-name-filter">false</xsl:param>

		<script type="text/javascript">
			var oTable = null;
			var oDataSet = null;
			var oFilterController = null;

			$(document).ready(function() {

			$('.toolbar').each(function() {
			var that = this;
			$(window).bind('scroll',function (){
			var el = $(that),
			parent = el.parent();
			if (jQuery(window).scrollTop()>parent.position().top){
			el.addClass("fixed");
			}
			else
			{
			el.removeClass("fixed");
			}
			});
			});

			TemplatesDataSet.getInstance();
			editableCell.enableEdit = <xsl:value-of select="$enable-edit" />;
			editableCell.ignorePropNames = <xsl:value-of select="$js-ignore-props-edit" />;

			oDataSet = new dataSet(
			'<xsl:value-of select="$control-module" />',
			true,
			'<xsl:value-of select="$control-params" />',
			{
			<xsl:if test="$data-set-init-end-requests">
				onInitEndRequests: <xsl:value-of select="$data-set-init-end-requests" />,
			</xsl:if>
			}
			);

			oDataSet.addEventHandler('onBeforeExecute', createConfirm(oDataSet));

			<xsl:value-of select="$js-dataset-events" />

			var oInitFltr = new filter();
			<xsl:if test="$js-disable-virtual-copy = 1">
				oInitFltr.setVirtualCopyChecking(false);
			</xsl:if>

			var oDefaultFilter = new filter();
			oDefaultFilter.setVirtualCopyChecking(true);

			<xsl:if test="$js-disable-virtual-copy = 1">
				oDefaultFilter.setVirtualCopyChecking(false);
			</xsl:if>

			oDefaultFilter.setViewMode(true);
			oDefaultFilter.setLang('<xsl:value-of select="$control-lang-id" />');

			var cookieDomainId = getCookie('control-domain-id');
			var domainId;

			if (cookieDomainId === null) {
				domainId = '<xsl:value-of select="$control-domain-id" />';
				setCookie('control-domain-id', domainId);
			} else {
				domainId = cookieDomainId;
			}

			oDefaultFilter.setDomain(domainId);

			<xsl:if test="$control-type-id &gt; 0">
				oDefaultFilter.setOtypes('<xsl:value-of select="$control-type-id" />');
			</xsl:if>

			<xsl:if test="$param0">
				oDefaultFilter.setEntityIDs('<xsl:value-of select="$param0" />');
			</xsl:if>

			oDataSet.setDefaultFilter(oDefaultFilter);

			var newFunc = null;
			var toolbarMenu = null;

			<xsl:choose>
				<xsl:when test="string-length($toolbarmenu)">
					<xsl:value-of select="$toolbarmenu" />
				</xsl:when>
			</xsl:choose>


			<xsl:variable name="table-control-id" select="concat('tree-', $control-id)"/>

			oTable = new Control(oDataSet, TableItem, {
			id : '<xsl:value-of select="$table-control-id" />',
			<xsl:if test="$show-toolbar = 1">
				toolbar: TableToolbar,
				toolbarFunctions: newFunc,
				toolbarMenu: toolbarMenu,
			</xsl:if>
			disableNameFilter :<xsl:value-of select="$disable-name-filter" />,
			enableEdit : <xsl:value-of select="$enable-edit" />,
			visiblePropsMenu : <xsl:value-of select="$js-visible-props-menu" />,
			requiredPropsMenu : <xsl:value-of select="$js-required-props-menu" />,
			sequencePropsMenu : <xsl:value-of select="$js-sequence-props-menu" />,
			hasCheckboxes: <xsl:value-of select="$js-has-checkboxes" />,

			iconsPath : '/images/cms/admin/mac/tree/',
			container : document.getElementById('table_container_<xsl:value-of select="$control-id" />'),

			<xsl:if test="$disable-csv-buttons = 1">
				disableCSVButtons: true,
			</xsl:if>

			<xsl:if test="$allow-drag != 1">
				allowDrag : false,
			</xsl:if>

			<xsl:if test="$label_first_column != ''">
				label_first_column : '<xsl:value-of select="$label_first_column" />',
			</xsl:if>

			<xsl:if test="$on-render-complete">
				onRenderComplete: <xsl:value-of select="$on-render-complete" />,
			</xsl:if>

			<xsl:if test="$hide-csv-import-button = 1">
				hideCsvImportButton: true,
			</xsl:if>

			<xsl:if test="string-length($js-value-callback)">
				onGetValueCallback: <xsl:value-of select="$js-value-callback" />,
			</xsl:if>

			<xsl:if test="$expand-all">
				expandAll: true,
			</xsl:if>

			<xsl:if test="$ignore-hierarchy">
				ignoreHierarchy: true,
			</xsl:if>

			<xsl:choose>
				<xsl:when test="$content-type = 'objects'">
					flatMode : true,
					<xsl:if test="$enable-objects-activity = 1">
						enableObjectsActivity : true,
					</xsl:if>
					contentType : 'objects',
				</xsl:when>

				<xsl:when test="$content-type = 'types'">
					contentType : 'objectTypes',
					objectTypesMode : true,
					<xsl:if test="$flat-mode = 1">
						flatMode : true,
					</xsl:if>
				</xsl:when>

				<xsl:otherwise>
					<xsl:if test="$flat-mode = 1">
						flatMode : true,
					</xsl:if>
				</xsl:otherwise>
			</xsl:choose>
			nullparam: 0,
			perPageLimits: [2, 4, 8, 10, 20]
			});

			var menu = [];
			<xsl:choose>
				<xsl:when test="string-length($menu)">
					<xsl:value-of select="$menu" />
				</xsl:when>
				<xsl:when test="$module = 'catalog' and $method = 'tree'"><![CDATA[
						var menu = [
							['view-page', 'view', ContextMenu.itemHandlers.viewElement],
							['edit-page', 'ico_edit', ContextMenu.itemHandlers.editItem],
							['change-activity', 'ico_unblock', ContextMenu.itemHandlers.activeItem],
							'-',
							['csv-export', 'i-csv-export', ContextMenu.itemHandlers.csvExport],
							['csv-import', 'i-csv-import', ContextMenu.itemHandlers.csvImport],
							'-',
							['copy-url', false, ContextMenu.itemHandlers.copyUrl],
							['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
						]
				]]></xsl:when>

				<xsl:when test="$content-type = 'pages'"><![CDATA[
						var menu = [
							['view-page', 'view', ContextMenu.itemHandlers.viewElement],
							['edit-page', 'ico_edit', ContextMenu.itemHandlers.editItem],
							['change-activity', 'ico_unblock', ContextMenu.itemHandlers.activeItem],
							'-',
							['csv-export', 'i-csv-export', ContextMenu.itemHandlers.csvExport],
							['csv-import', 'i-csv-import', ContextMenu.itemHandlers.csvImport],
							'-',
							['copy-url', false, ContextMenu.itemHandlers.copyUrl],
							['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
						]
				]]></xsl:when>

				<xsl:when test="$content-type = 'objects' and $enable-objects-activity = 1"><![CDATA[
						var menu = [
							['edit-item', 'ico_edit', ContextMenu.itemHandlers.editItem],
							['change-activity', 'ico_unblock', ContextMenu.itemHandlers.activeObjectItem],
							['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
						]
				]]></xsl:when>

				<xsl:when test="$content-type = 'objects' and not($enable-objects-activity = 1)"><![CDATA[
						var menu = [
							['edit-item', 'ico_edit', ContextMenu.itemHandlers.editItem],
							['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
						]
				]]></xsl:when>

				<xsl:otherwise>
					<xsl:value-of select="$menu" />
				</xsl:otherwise>
			</xsl:choose>


			var menuBuilder = function(o_menu) {
			Control.enabled = false;
			o_menu.a = [];
			for (var i = 0; i &lt; menu.length; i++) {
			var itm = menu[i];
			if (itm == '-') {
			if(o_menu.a.length &amp;&amp; o_menu.a[o_menu.a.length-1] == "-") continue;
			o_menu.a.push(itm);
			} else {
			var action = menu[i][2](menu[i]);
			if (typeof(action) == 'object') o_menu.a.push(action);
			}
			}
			return true;
			}

			var oMenu = $.cmenu.getMenu(menuBuilder);
			var cont = '#table_container_<xsl:value-of select="$control-id" />';

			$(cont).bind('contextmenu', function (event) {
			var el = event.target;
			if (!el &amp;&amp; !el.parentNode) return;

			if (Control.HandleItem ) {
			var keys = Object.keys(Control.HandleItem.control.selectedList);
			Control.handleMouseUp(event);
			if (keys.length == 1){
			var targetRowId = $(event.target).closest('tr').attr('rel');
			if (keys[0] == targetRowId){
			$.cmenu.show(oMenu, Control.HandleItem.control.initContainer.offsetParent, event);
			}
			}
			}
			});

			$(cont).bind('mousedown', function(event) {
			if(Control.HandleItem &amp;&amp; event.altKey) {
			$.cmenu.lockHiding = true;
			$.cmenu.show(oMenu, Control.HandleItem.control.initContainer.offsetParent, event);
			return;
			}
			});

			$(cont).bind('mouseout', function () {
			$.cmenu.lockHiding = false;
			});


			var oRoot = oTable.setRootNode({
			'id' : 0,
			'allow-drag' : oTable.dragAllowed,
			'force-draw' : false,
			'iconbase' : '/images/cms/admin/mac/tree/ico_domain.png',
			'name' : '<xsl:value-of select="$control-host" />',
			'is-active' : '1',
			'allow-copy' : false,
			'allow-activity' : false,
			'create-link' : '/admin/content/add/0/page/?domain=<xsl:value-of select="$control-host" />'
			});

			oRoot.filter = oInitFltr;

			<xsl:if test="$search-show = 1">
				var searchStorage = new SearchAllTextStorage('<xsl:value-of select="$table-control-id" />');
				oFilterController = new filterController(oTable, 'catalog-object', true, $('#search')[0], {
				<xsl:if test="$search-advanced-allow = 1">
					nativeAdvancedMode:false
				</xsl:if>
				}, searchStorage);
			</xsl:if>

			<xsl:value-of select="$js-add-buttons" />

			SettingsStore.getInstance().addEventHandler("onloadcomplete", function() {
			for (var i = 0; i &lt; Control.instances.length; i++) {
			Control.instances[i].init();
			}
			});

			$('.overflow-block').resize(function (){
			var over = $(this),
			table = $('.overflow-block table'),
			plus = $('.overflow-block table .plus');
			if (plus.length>0){
			if (over.width() !== table.width()){
			plus.css('width','80px');
			} else {
			plus.css('width','100%');
			}
			}
			})

			});
			<xsl:if test="($content-type = 'pages' or $domains-show = 1) and $domainsCount > 1">
				function changeDomain(select) {
					if(!oTable || !oDataSet) return;

					var oInitFltr = new filter();

					var oDefaultFilter = new filter();
					oDefaultFilter.setVirtualCopyChecking(true);
					oDefaultFilter.setViewMode(true);
					oDefaultFilter.setLang('<xsl:value-of select="$control-lang-id" />');

					var selectedDomainId = select.value;
					setCookie('control-domain-id', selectedDomainId);

					oDefaultFilter.setDomain(selectedDomainId);
					oDataSet.setDefaultFilter(oDefaultFilter);
					oDataSet.clearFiltersCache();

					oTable.selectedList = [];
					oTable.removeItem(oTable.getRootNodeId(), true);
					var root = oTable.items[oTable.getRootNodeId()];
					root.loaded = false;
					if(root.isExpanded) root.expand();
					oTable.getSelectionCallback()(null, true);
					initCreateTypeSelector(selectedDomainId);
				}
			</xsl:if>
		</script>

		<div class="location">
			<xsl:if test="$method = 'messages' and $module='webforms' ">
				<xsl:apply-templates select="document(concat('udata://webforms/getForms/', $param0))/udata" />
			</xsl:if>
			<div id="search" class="filter-container pull-right" />
		</div>
		<div class="location" style="position: static !important;">
			<div class="loc-left" style="display:table-cell; height:18px; width:1px;"></div>
			<div class="row">
				<div id="tollbar_wrapper" class="toolbar pull-left" style="display: hide; min-height:18px;"></div>
				<div class="save_size"></div>
			</div>

			<div class="loc-right">
				<xsl:if test="($content-type = 'pages' or $domains-show = 1) and $domainsCount > 1">
					<div class="domains_selector">
						<select onchange="javascript:changeDomain(this)" class="default newselect">
							<xsl:apply-templates select="$domains-list" mode="domain-select" />
						</select>
					</div>
				</xsl:if>
			</div>
		</div>

		<div style="width: 100%;" class="tableItemContainer location">
			<div class="gray_border_except_top overflow-block">
				<table class="table allowContextMenu" id="table_container_{$control-id}" style="table-layout:fixed;"
							oncontextmenu="return false">
					<colgroup />
				</table>
				<div id="nodata" style="display: none;">
					<xsl:text>&table-control-nodata;</xsl:text>
				</div>
			</div>
		</div>
		<div class="location">
			<div id="csv-buttons-zone" class=" loc-left"></div>
			<div id="per_page_limit" class="paging panel-sorting loc-right"></div>
		</div>
		<script id="fast-edit-file-control" type="text/template">
			<![CDATA[
				<div class="layout-row-icon">
					<div class="layout-col-control">
						<% if (value) { %>
							<input id="<%= id %>" type="text" class="default" value="<%= value %>"/>
						<% } else { %>
							<input id="<%= id %>" type="text" class="default"/>
						<% } %>
					</div>
					<div class="layout-col-icon">
						<a class="icon-action">
							<i class="small-ico i-choose" />
						</a>
					</div>
				</div>
			]]>
		</script>
		<script id="file-browser-options" type="text/template">
			<![CDATA[
				<div id="watermark_wrapper">
					<label for="add_watermark">
						<%= watermarkMessage %>
					</label>
					<input type="checkbox" name="add_watermark" id="add_watermark"/>
					<label for="remember_last_folder">
						<%= rememberMessage %>
					</label>
					<% if (remember) { %>
						<input type="checkbox" name="remember_last_folder" id="remember_last_folder" checked="checked"/>
					<% } else { %>
						<input type="checkbox" name="remember_last_folder" id="remember_last_folder"/>
					<% } %>
				</div>
			]]>
		</script>
		<script id="fast-edit-image-preview" type="text/template">
			<![CDATA[
				<% if (isBroken == '1') { %>
					<span title="404" style="color:red;font-weight: bold;cursor: pointer;">?</span>
					<span style="text-decoration: line-through;" title="<%= filePath %>"><%= fileName %></span>
				<% } else { %>
					<img alt="" style="width:13px;height:13px;cursor: pointer;" src="/images/cms/image.png"
						onmouseover="TableItem.showPreviewImage(
							event, '/autothumbs.php?img=<%= filePathWithoutExt %>_sl_180_120.<%= ext %>'
						)"
					/>
					<span title="<%= filePath %>"><%= fileName %></span>
				<% } %>
			]]>
		</script>
		<script id="fast-edit-file-preview" type="text/template">
			<![CDATA[
				<% if (isBroken == '1') { %>
					<span style="text-decoration: line-through;" title="<%= filePath %>"><%= filePath %></span>
				<% } else { %>
					<span title="<%= filePath %>"><%= filePath %></span>
				<% } %>
			]]>
		</script>
	</xsl:template>

	<xsl:template match="domain" mode="domain-select">
		<xsl:variable name="control-domain-id" select="php:function('getCookie', 'control-domain-id')" />

		<xsl:variable name="selected-domain-id">
			<xsl:choose>
				<xsl:when test="string-length($control-domain-id) > 0">
					<xsl:value-of select="$control-domain-id" />
				</xsl:when>

				<xsl:otherwise>
					<xsl:value-of select="$domain-id" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<option value="{@id}">
			<xsl:if test="@id = $selected-domain-id">
				<xsl:attribute name="selected">
					<xsl:text>selected</xsl:text>
				</xsl:attribute>
			</xsl:if>

			<xsl:value-of select="@decoded-host" />
		</option>
	</xsl:template>

	<xsl:template name="ui-smc-tree">
		<xsl:param name="init">1</xsl:param>
		<xsl:param name="host" />
		<xsl:param name="control-id" />
		<xsl:param name="lang-id" select="$lang-id" />
		<xsl:param name="domain-id" />
		<xsl:param name="menu" value="''" />
		<xsl:param name="disableTooManyChildsNotification" value="0" />
		<xsl:param name="uid" select="generate-id()" />
		<script type="text/javascript">
			$(document).ready(function() {
			TemplatesDataSet.getInstance();

			var oDataSet = new dataSet('<xsl:value-of select="$module" />', true);
			
			oDataSet.addEventHandler('onBeforeExecute', createConfirm(oDataSet));

			var oInitFltr = new filter();
			oInitFltr.setParentElements(0);
			oInitFltr.setViewMode(false);
			oInitFltr.setLang('<xsl:value-of select="$lang-id" />');

			var oDefaultFilter = new filter();
			oDefaultFilter.setVirtualCopyChecking(true);
			oDefaultFilter.setViewMode(false);
			oDefaultFilter.setDomain('<xsl:value-of select="$domain-id" />');
			oDefaultFilter.setLang('<xsl:value-of select="$lang-id" />');

			oDataSet.setDefaultFilter(oDefaultFilter);

			var oTree = new Control(oDataSet, TreeItem, {
			id : 'tree-<xsl:value-of select="$module" />-<xsl:value-of select="$method" />-<xsl:value-of select="$control-id" />',
			uid : '<xsl:value-of select="$uid" />',
			toolbar : TreeToolbar,

			<xsl:if test='$disableTooManyChildsNotification'>
				disableTooManyChildsNotification: <xsl:value-of select="$disableTooManyChildsNotification" />,
			</xsl:if>

			iconsPath : '/images/cms/admin/mac/tree/',
			container : document.getElementById('tree_container_<xsl:value-of select="$control-id" />')
			});

			var oRoot = oTree.setRootNode({
			'id' : 0,
			'allow-drag' : false,
			'iconbase' : '/images/cms/admin/mac/tree/ico_domain.png',
			'name' : '<xsl:value-of select="$host" />',
			'is-active' : '1',
			'allow-copy' : false,
			'allow-activity' : false,
			'create-link' : '<xsl:value-of select="$lang-prefix" />/admin/content/add/0/page/?domain=<xsl:value-of select="$host" />'
			});

			oRoot.filter = oInitFltr;

			var oFilterController = new filterController(oTree, 'root-pages-type', true, null, {nativeAdvancedMode:true});

			var menu = [];
			<xsl:value-of select="$menu" />

			var menuBuilder = function(o_menu) {
			
			o_menu.a = [];
			for (var i = 0; i &lt; menu.length; i++) {
			var itm = menu[i];
			if (itm == '-') {
			if(o_menu.a.length &amp;&amp; o_menu.a[o_menu.a.length-1] == "-") continue;
			o_menu.a.push(itm);
			} else {
			var action = menu[i][2](menu[i]);
			if (typeof(action) == 'object') o_menu.a.push(action);
			}
			}
			return true;
			}

			var oMenu = $.cmenu.getMenu(menuBuilder);
			var cont = '#tree_container_<xsl:value-of select="$control-id" />';

			$(cont).bind('contextmenu', function (event) {
			
			if (Control.HandleItem) {
			var keys = Object.keys(Control.HandleItem.control.selectedList);
			Control.handleMouseUp(event);
			if (keys.length == 1){
			var targetRowId = $(event.target).closest('li').attr('rel');
			if (keys[0] == targetRowId){
			$.cmenu.show(oMenu, Control.HandleItem.control.initContainer, event);
			}
			}


			}
			});

			$(cont).bind('mousedown', function(event) {
			if(Control.HandleItem &amp;&amp; event.altKey) {
			
			$.cmenu.lockHiding = true;
			$.cmenu.show(oMenu, document.body, event);
			console.log(222);
			return;
			}
			});

			$(cont).bind('mouseout', function () {
			$.cmenu.lockHiding = false;
			});

			<xsl:if test="$init = 1">
				SettingsStore.getInstance().addEventHandler("onloadcomplete", function() {
				for (var i = 0; i &lt; Control.instances.length; i++) {
				Control.instances[i].init();
				}
				});
			</xsl:if>
			
			$('.toolbar').each(function() {
			var that = this;
			$(window).bind('scroll',function (){
			var el = $(that),
			parent = el.parent();
			if (jQuery(window).scrollTop()>parent.position().top){
			el.addClass("fixed");
			}
			else
			{
			el.removeClass("fixed");
			}
			});
			});
			});
		</script>

		<xsl:if test="position() > 1">
			<div style="width:100%;border-bottom:1px dashed #aaa;margin:30px 0 30px 0;">
				<xsl:text> </xsl:text>
			</div>
		</xsl:if>

		<div class="row">
			<div id="filterWrapper_{$uid}"></div>
		</div>
		<div class="row">
			<div class="saveSize"></div>
			<div id="tree_toolbar_{$uid}" class="toolbar pull-left "></div>
		</div>
		<div class="row">
			<div id="search_result"></div>
		</div>
		<div class="row">
			<ul id="tree_container_{$control-id}" class="tree-container allowContextMenu md-col-12" oncontextmenu="return false">
			</ul>
		</div>
	</xsl:template>

	<!-- Плавающие кнопки для настроек модуля -->
	<xsl:template name="std-form-buttons-settings">
		<div id="buttons_wr" class="col-md-12">
			<div class="btn-select color-blue pull-right" style="width:200px; margin-top:10px;">
				<div class="selected">
					<input type="submit" value="&label-save;" name="save-mode" />
				</div>

				<ul class="list">
					<li>
						<input type="button" value="&label-cancel;"
									 onclick="javascript: window.location = document.referrer;" />
					</li>
				</ul>
			</div>
		</div>
	</xsl:template>

	<xsl:template name="std-form-buttons-add">
		<xsl:param name="disable.view.button">0</xsl:param>
		<div id="buttons_wr" class="col-md-12">
			<div class="btn-select color-blue pull-right" style="width:200px; margin-top:10px;">
				<div class="selected">
					<input type="submit" value="&label-save-add;" name="save-mode" />
				</div>

				<ul class="list">
					<li>
						<input type="submit" value="&label-save-add-exit;" name="save-mode" />
					</li>

					<xsl:choose>
						<xsl:when test="$module = 'social_networks' or
									($module = 'content' and $method = 'tpl_edit') or
									$module = 'dispatches' or
									$module = 'exchange' or
									$module = 'webforms' or
									$module = 'emarket' or
									$module = 'banners' or
									$module = 'users' or
									$module = 'menu' or
									$module = 'data' or
									$module = 'seo' or
									$module = 'umiRedirects' or
									$module = 'appointment' or
									$module = 'umiSettings' or
									$disable.view.button = 1
									" />
						<xsl:otherwise>
							<li>
								<input type="submit" value="&label-save-add-view;" name="save-mode" />
							</li>

						</xsl:otherwise>
					</xsl:choose>
					<li>
						<input type="button" value="&label-cancel;"
									 onclick="javascript: window.location = '{/result/@referer-uri}';" />
					</li>
				</ul>
			</div>
		</div>

		<xsl:if test="/result/data/page">
			<script>
				jQuery('div.buttons input:submit').attr('disabled', 'disabled');
			</script>
		</xsl:if>
	</xsl:template>

	<xsl:template name="std-form-buttons">
		<xsl:param name="disable.view.button">0</xsl:param>
		<div id="buttons_wr" class="col-md-12">
			<div class="btn-select color-blue pull-right" style="width:220px;  margin-top:10px;">

				<div class="selected">
					<input type="submit" value="&label-save-exit;" name="save-mode" />
				</div>

				<ul class="list">
					<li>
						<input type="submit" value="&label-save;" name="save-mode" />
					</li>

					<xsl:choose>
						<xsl:when test="$module = 'social_networks' or
								($module = 'content' and $method = 'tpl_edit') or
								$module = 'dispatches' or
								$module = 'exchange' or
								$module = 'webforms' or
								$module = 'emarket' or
								$module = 'banners' or
								$module = 'users' or
								$module = 'menu' or
								$module = 'data' or
								$module = 'seo'or
								$module = 'umiRedirects' or
								$module = 'appointment' or
								$disable.view.button = 1
								" />
						<xsl:otherwise>
							<li>
								<input type="submit" value="&label-save-view;" name="save-mode" />
							</li>
						</xsl:otherwise>
					</xsl:choose>

					<li>
						<input type="button" value="&label-cancel;"
									 onclick="javascript: window.location = '{/result/@referer-uri}';" />
					</li>
				</ul>
			</div>
		</div>

		<xsl:if test="/result/data/page">
			<script>
				jQuery('div.buttons input:submit').attr('disabled', 'disabled');
			</script>
		</xsl:if>
	</xsl:template>

	<xsl:template name="std-save-button">
		<div class="row">
			<div class="pull-right">
				<input type="submit" value="&label-save;" class="btn color-blue" name="save-mode" />
			</div>
		</div>
	</xsl:template>

	<xsl:template name="std-form-name">
		<xsl:param name="value" />
		<xsl:param name="label" select="'&label-name;'" />
		<xsl:param name="show-tip">
			<xsl:text>1</xsl:text>
		</xsl:param>

		<div class="col-md-6">
			<div class="title-edit">
				<acronym>
					<xsl:if test="$show-tip = '1'">
						<xsl:attribute name="class">
							<xsl:text>acr</xsl:text>
						</xsl:attribute>
						<xsl:attribute name="title">
							<xsl:text>&tip-page-name;</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="$label" />
				</acronym>
			</div>
			<input class="default" type="text" name="name" value="{$value}" />
		</div>
	</xsl:template>

	<xsl:template name="std-form-alt-name">
		<xsl:param name="value" />

		<div class="col-md-6">
			<div class="title-edit">
				<acronym class="acr">
					<xsl:attribute name="title">
						<xsl:text>&tip-alt-name;</xsl:text>
					</xsl:attribute>
					<xsl:text>&label-alt-name;</xsl:text>
				</acronym>
			</div>
			<span>
				<input class="default" type="text" name="alt-name" value="{$value}" />
			</span>
		</div>
	</xsl:template>

	<xsl:template name="std-form-active-control">
		<xsl:param name="value" />

		<span class="pull-left">
			<a id="is-active-control" class="icon-action" href="javascript:void(0);">
				<xsl:attribute name="title">
					<xsl:choose>
						<xsl:when test="$value and $value = 1">
							<xsl:text>&tip-is-active;</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>&tip-is-noactive;</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>

				<i class="small-ico i-vision">
					<xsl:if test="$value and $value = 1">
						<xsl:attribute name="class">small-ico i-hidden</xsl:attribute>
					</xsl:if>
				</i>
			</a>
		</span>
	</xsl:template>

	<xsl:template name="std-form-is-active">
		<xsl:param name="value" />
		<xsl:param name="form_name" />

		<input type="hidden" name="active" value="0" id="is-active">
			<xsl:if test="$form_name">
				<xsl:attribute name="name">
					<xsl:value-of select="$form_name" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="$value and $value != 0">
				<xsl:attribute name="value">1</xsl:attribute>
			</xsl:if>
		</input>
	</xsl:template>

	<xsl:template name="std-form-is-visible">
		<xsl:param name="value" />

		<div class="col-md-6" style="min-height:30px;">
			<input type="hidden" name="is-visible" value="0" />
			<label>
				<div class="checkbox">
					<input type="checkbox" name="is-visible" value="1" class="checkbox" id="is-visible">
						<xsl:if test="$value">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
				</div>

				<span>
					<acronym class="acr">
						<xsl:attribute name="title">
							<xsl:text>&tip-is-visible;</xsl:text>
						</xsl:attribute>
						<xsl:text>&label-is-visible;</xsl:text>
					</acronym>
				</span>
			</label>
		</div>
	</xsl:template>

	<xsl:template name="std-form-is-default">
		<xsl:param name="value" />

		<div class="col-md-6" style="min-height:30px;">
			<input type="hidden" name="is-default" value="0" />
			<label>
				<div class="checkbox">
					<input type="checkbox" name="is-default" value="1" class="checkbox" id="is-default">
						<xsl:if test="$value">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
				</div>
				
				<span>
					<acronym class="acr">
						<xsl:attribute name="title">
							<xsl:text>&tip-is-default;</xsl:text>
						</xsl:attribute>
						<xsl:text>&label-is-default;</xsl:text>
					</acronym>
				</span>
			</label>
		</div>
	</xsl:template>

	<xsl:template name="std-form-data-type">
		<xsl:param name="typeId" />
		<xsl:param name="domainId">
			<xsl:value-of select="$domain-id" />
		</xsl:param>

		<xsl:variable name="object-types" select="document(concat('udata://system/getObjectTypesList/', $typeId, '//', $domainId))/udata" />

		<div class="col-md-6 extended_fields data-type">
			<xsl:choose>
				<xsl:when test="count($object-types//item) &gt; 1">
					<div class="title-edit">
						<acronym class="acr" style="float:left">
							<xsl:attribute name="title">
								<xsl:text>&tip-object-type;</xsl:text>
							</xsl:attribute>
							<xsl:text>&label-type;</xsl:text>
						</acronym>
					</div>
					<span>
						<select name="type-id" class="edit default newselect" onchange="changeEditLink();">
							<xsl:apply-templates select="$object-types//item" mode="std-form-item">
								<xsl:with-param name="value" select="$typeId" />
							</xsl:apply-templates>
						</select>
					</span>
				</xsl:when>
				<xsl:otherwise>
					<div class="title-edit">
						<xsl:text>&label-type-field;</xsl:text>
					</div>
					<input type="text" class="default" disabled="disabled" value="{$object-types//item}" />
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

	<xsl:template name="std-form-template-id">
		<xsl:param name="value" />
		<xsl:variable name="templates" select="document(concat('udata://system/getTemplatesList/', $domain-floated))/udata" />

		<div class="col-md-6">
			<div class="title-edit">
				<acronym class="acr">
					<xsl:attribute name="title">
						<xsl:text>&tip-template-id;</xsl:text>
					</xsl:attribute>
					<xsl:text>&label-template;</xsl:text>
				</acronym>
			</div>
			<span>
				<select class="default newselect" name="template-id">
					<xsl:apply-templates select="$templates//item" mode="std-form-item">
						<xsl:with-param name="value" select="$value" />
					</xsl:apply-templates>
				</select>
			</span>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="std-form-item">
		<xsl:param name="value" />

		<option value="{@id}">
			<xsl:if test="@id = $value">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template name="std-page-permissions">
		<xsl:param name="page-id" select="0" />

		<div class="col-md-12" id="permissionsContainer">
			<ul>
				<xsl:choose>
					<xsl:when test="@id">
						<xsl:apply-templates select="document(concat('udata://users/permissions///', @id))/udata" mode="page-permissions" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates
								select="document(concat('udata://users/permissions/', $module, '/', /result/data/page/basetype/@method, '//', /result/data/page/@parentId))/udata"
								mode="page-permissions" />
					</xsl:otherwise>
				</xsl:choose>
			</ul>
		</div>
	</xsl:template>

	<xsl:template match="users/user" mode="page-permissions">
		<li umi:id="{@id}" umi:access="{@access}">
			<xsl:value-of select="@login" />
		</li>
	</xsl:template>

	<xsl:template match="groups/group" mode="page-permissions">
		<li umi:id="{@id}" umi:access="{@access}">
			<xsl:value-of select="@title" />
		</li>
	</xsl:template>

	<!-- Шаблон для контрола поля с типом "Составное" -->
	<xsl:template name="std-optioned-control">
		<xsl:param name="guide-id" select="@guide-id" />
		<xsl:param name="input-name" select="@input_name" />
		<xsl:param name="title" select="@title" />
		<xsl:param name="tip" select="@tip" />
		<xsl:param name="type">
			<xsl:text>float</xsl:text>
		</xsl:param>

		<xsl:variable name="isPublic" select="@public-guide = 1" />

		<div>
			<div class="title-edit">
				<acronym>
					<xsl:if test="$tip">
						<xsl:attribute name="title">
							<xsl:value-of select="$tip" />
						</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="$title" />
				</acronym>
				<xsl:apply-templates select="." mode="required_text" />
			</div>

			<div class="values">
				<xsl:apply-templates mode="field-optioned">
					<xsl:with-param name="input-name" select="$input-name" />
					<xsl:with-param name="type" select="$type" />
				</xsl:apply-templates>
			</div>

			<div class="layout-row-icon row">
				<div class="col-md-6" style="min-height:10px;">
					<div class="optioned-select-container">
						<div class="selectize-container">
							<select umi:guide="{$guide-id}" umi:name="{$input-name}"></select>
						</div>

						<xsl:if test="$isPublic">
							<xsl:call-template name="edit-guide-items-link">
								<xsl:with-param name="typeId" select="$guide-id" />
							</xsl:call-template>
						</xsl:if>
					</div>

					<xsl:if test="$isPublic">
						<div class="layout-col-icon optioned-add-relation-container">
							<a class="icon-action relation-add">
								<i class="small-ico i-add" title="&js-add-relation-item;"></i>
							</a>
						</div>
					</xsl:if>
				</div>

				<div class="col-md-5">
					<div class="layout-col-control">
						<input type="number" class="default edit number" umi:type="{$type}" step="any" autocomplete="off" />
					</div>

					<div class="layout-col-icon">
						<a class="icon-action add-option">
							<i class="small-ico i-add" title="&js-add-option;"></i>
						</a>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field/values/value" mode="field-optioned">
		<xsl:param name="input-name" />
		<xsl:param name="type">
			<xsl:text>float</xsl:text>
		</xsl:param>

		<xsl:variable name="position" select="position()" />

		<div class="layout-row-icon row">
			<div class="col-md-6" style="min-height:10px;">
				<xsl:value-of select="object/@name" />
				<input type="hidden" name="{$input-name}[{$position}][rel]" value="{object/@id}" />
			</div>
			<div class="col-md-5">
				<div class="layout-col-control">
					<xsl:choose>
						<xsl:when test="$type = 'int'">
							<input type="number" class="default" umi:type="{$type}" name="{$input-name}[{$position}][{$type}]">
								<xsl:attribute name="value">
									<xsl:choose>
										<xsl:when test="@int">
											<xsl:value-of select="@int" />
										</xsl:when>
										<xsl:otherwise>
											<xsl:text>0</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:attribute>
							</input>
							<input type="hidden" umi:type="float" name="{$input-name}[{$position}][float]" value="1" />
						</xsl:when>
						<xsl:when test="$type = 'varchar'">
							<input type="text" class="default" umi:type="{$type}" name="{$input-name}[{$position}][{$type}]" value="{@varchar}" />
							<input type="hidden" umi:type="int" name="{$input-name}[{$position}][int]" value="1" />
						</xsl:when>
						<xsl:otherwise>
							<input type="number" class="default" umi:type="float" step="any" name="{$input-name}[{$position}][float]">
								<xsl:attribute name="value">
									<xsl:choose>
										<xsl:when test="@float">
											<xsl:value-of select="@float" />
										</xsl:when>
										<xsl:otherwise>
											<xsl:text>0</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:attribute>
							</input>
							<input type="hidden" umi:type="int" name="{$input-name}[{$position}][int]" value="1" />
						</xsl:otherwise>
					</xsl:choose>
				</div>
				<div class="layout-col-icon">
					<a class="icon-action remove-option">
						<i class="small-ico i-remove" title="&js-remove-option;"></i>
					</a>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="@demo" mode="stopdoItInDemo">
		<script type="text/javascript">
			function disableInDemo() {
			jQuery.jGrowl('<p>В демонстрационном режиме эта функция недоступна</p>', {
			'header': 'UMI.CMS',
			'life': 10000
			});
			}

			jQuery(document).ready(function() {
			jQuery('#<xsl:value-of select="../@module" />_<xsl:value-of select="../@method" />_form').unbind().submit(function(e){
			e.stopImmediatePropagation();
			disableInDemo();
			return false;
			});
			jQuery('.<xsl:value-of select="../@module" />_<xsl:value-of select="../@method" />_btn').unbind().click(function(e){
			e.stopImmediatePropagation();
			disableInDemo();
			return false;
			});
			});
		</script>
	</xsl:template>

</xsl:stylesheet>
