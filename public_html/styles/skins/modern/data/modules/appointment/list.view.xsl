<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:variable name="add-method" select="'addPage'" />
	<xsl:variable name="basic-type" select="'page'" />

	<xsl:template match="/result[@method = 'pages']/data[@type = 'list' and @action = 'view']">
		<div class="location">
			<div class="imgButtonWrapper loc-left " xmlns:umi="http://www.umi-cms.ru/TR/umi">
				<a id="addPage" href="{$lang-prefix}/admin/{$module}/{$add-method}/{$basic-type}/"
				   class="btn color-blue" umi:type="appointment::page">
					<xsl:text>&label-add-page;</xsl:text>
				</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="content-type">pages</xsl:with-param>
					<xsl:with-param name="control-params">page</xsl:with-param>
					<xsl:with-param name="allow-drag">1</xsl:with-param>
					<xsl:with-param name="js-add-buttons">
						createAddButton(
						$('#addPage').get(0), oTable,
							'<xsl:value-of select="concat($lang-prefix, '/admin/', $module, '/',  $add-method, '/{id}/', $basic-type, '/' )" />', ['*', true]
						);
					</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'services']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left" style="bottom:0px;">
						<a id="addServiseGroup" class="btn color-blue loc-left"
						   href="{$lang-prefix}/admin/appointment/addServiceGroup/">&label-button-add-service-group;</a>
						<a id="addService" class="btn color-blue loc-left"
						   href="{$lang-prefix}/admin/appointment/addService/">&label-button-add-service;</a>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<div id="tableWrapper"></div>
						<script src="/js/underscore-min.js"></script>
						<script src="/js/backbone-min.js"></script>
						<script src="/js/twig.min.js"></script>
						<script src="/js/backbone-relational.js"></script>
						<script src="/js/backbone.marionette.min.js"></script>
						<script src="/js/app.min.js"></script>
						<script>
							<![CDATA[
							(function(){
								var addServiceUrl = $('#addService').attr('href');

								new umiDataController({
									container:'#tableWrapper',
									prefix:'/admin/appointment',
									module:'appointment',
									controlParam:'service',
									dataProtocol: 'json',
									dragAllowed:true,
									domain:1,
									lang:1,
									configUrl:'/admin/appointment/flushServiceDataConfig/.json',
									perPageLimit:20,
									dropValidator: function(element, selected, mode){
										if (element.model.attributes.__type != 'AppointmentServiceGroup') {
											return false;
										}

										for (var key in selected) {
											var selectedElement = selected[key];
											if (selectedElement.model.attributes.__type != 'AppointmentService') {
												return false;
											}
										}

										return mode;
									}
								}).start();

								dc_application.on('row_select',function (e){
									var selected = dc_application.toolbar.selectedItems,
										group = 0,
										gid = -1;
									for (var i = 0, cnt = selected.length; i < cnt; i++){
										if (selected[i].get('__type') == 'AppointmentServiceGroup' && selected[i].selected){
											group++;
											gid = dc_application.unPackId(selected[i].get('id'));
										}
									}
									if (group == 1){
										$('#addService').attr('href',addServiceUrl + gid);
									} else {
										$('#addService').attr('href',addServiceUrl);
									}
								});
							})()
							]]>
						</script>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'employees']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left" style="bottom:0px;" xmlns:umi="http://www.umi-cms.ru/TR/umi">
						<a id="addBlog" class="btn color-blue loc-left"
						   href="{$lang-prefix}/admin/appointment/addEmployee/">&label-button-add-employee;</a>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<div id="tableWrapper"></div>
						<script src="/js/underscore-min.js"></script>
						<script src="/js/backbone-min.js"></script>
						<script src="/js/twig.min.js"></script>
						<script src="/js/backbone-relational.js"></script>
						<script src="/js/backbone.marionette.min.js"></script>
						<script src="/js/app.min.js"></script>
						<script>
							(function(){
							new umiDataController({
							container:'#tableWrapper',
							prefix:'/admin/appointment',
							module:'appointment',
							controlParam:'employee',
							dataProtocol: 'json',
							domain:1,
							lang:1,
							configUrl:'/admin/appointment/flushEmployeeDataConfig/.json',
							perPageLimit:20
							}).start();
							})()
						</script>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'orders']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>
				<div class="layout">
					<div class="column">
						<div id="tableWrapper"></div>
						<script src="/js/underscore-min.js"></script>
						<script src="/js/backbone-min.js"></script>
						<script src="/js/twig.min.js"></script>
						<script src="/js/backbone-relational.js"></script>
						<script src="/js/backbone.marionette.min.js"></script>
						<script src="/js/app.min.js"></script>
						<script>
							(function(){
							new umiDataController({
							container:'#tableWrapper',
							prefix:'/admin/appointment',
							module:'appointment',
							controlParam:'order',
							dataProtocol: 'json',
							domain:1,
							lang:1,
							configUrl:'/admin/appointment/flushOrderDataConfig/.json',
							perPageLimit:20
							}).start();
							})()
						</script>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
