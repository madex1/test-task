<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				extension-element-prefixes="php"
				xmlns:umi="http://www.w3.org/1999/xhtml"
		>


	<xsl:template match="/result[@method = 'editOrder']/data[@type = 'form' and @action = 'modify']">
		<div class="editing-functions-wrapper">
			<div class="tabs editing"></div>
			<div class="toolbar clearfix">
				<xsl:call-template name="delete.button">
					<xsl:with-param name="id" select="./id" />
					<xsl:with-param name="method">
						<xsl:text>deleteOrder</xsl:text>
					</xsl:with-param>
					<xsl:with-param name="location">
						<xsl:text>/admin/appointment/orders</xsl:text>
					</xsl:with-param>
					<xsl:with-param name="title">&label-deleting;</xsl:with-param>
					<xsl:with-param name="content">&label-are-you-really-want-to-delete;</xsl:with-param>
					<xsl:with-param name="cancel">&label-deleting-cancel;</xsl:with-param>
					<xsl:with-param name="confirm">&label-deleting-confirm;</xsl:with-param>
				</xsl:call-template>
			</div>
		</div>
		<div class="tabs-content module appointment-module">
			<div class="section selected">
				<xsl:apply-templates select="$errors" />
				<form class="form_modify"  method="post" action="do/" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer"/>
					<input type="hidden" name="domain" value="{$domain-floated}"/>
					<input type="hidden" name="permissions-sent" value="1"/>
					<script type="text/javascript">
						var treeLink = function(key, value){
						var settings = SettingsStore.getInstance();
						return settings.set(key, value, 'expanded');
						}
					</script>
					<div class="panel-settings">
						<div class="title">
							<h3>
								<xsl:text>&label-order-params;</xsl:text>
							</h3>
						</div>
						<xsl:variable name="services" select="document(concat('udata://appointment/servicesList///', ./service_id))/udata"/>
						<div class="content">
							<div class="layout">
								<div class="column">
									<div class="row">
										<div class="col-md-6">
											<div class="title-edit">
												<xsl:text>&label-field-customer-name;</xsl:text>
											</div>
											<input class="default" type="text" value="{ ./name }" disabled="disabled" />
										</div>
										<div class="col-md-6">
											<div class="title-edit">
												<xsl:text>&label-field-customer-phone;</xsl:text>
											</div>
											<input class="default" type="text" value="{ ./phone }" disabled="disabled" />
										</div>
										<div class="col-md-6">
											<div class="title-edit">
												<xsl:text>&label-field-customer-email;</xsl:text>
											</div>
											<input class="default" type="text" value="{ ./email }" disabled="disabled" />
										</div>
										<div class="col-md-6">
											<div class="title-edit">
												<xsl:text>&label-field-order-service;</xsl:text>
											</div>
											<input class="default" type="text" value="{ $services/items/item[@selected]/@name }" disabled="disabled" />
										</div>
										<div class="col-md-6">
											<div class="title-edit">
												<xsl:text>&label-field-order-comment;</xsl:text>
											</div>
											<span>
												<textarea disabled="disabled" readonly="readonly" style="resize: none;">
													<xsl:value-of select="./comment"/>
												</textarea>
											</span>
										</div>
									</div>
									<xsl:variable name="statuses" select="document(concat('udata://appointment/statusesList//', ./status_id))/udata"/>
									<xsl:variable name="employees" select="document(concat('udata://appointment/employeesListByServiceId//', $services/items/item[@selected]/@id, '//', ./employee_id))/udata"/>
									<div class="row">
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-order-status;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<select class="required default newselect" name="{concat('data[', ./id, '][status_id]')}">
													<option value="{$statuses/items/item[@selected]/@code}" selected="selected">
														<xsl:value-of select="$statuses/items/item[@selected]/@name"/>
													</option>
													<xsl:for-each select="$statuses/items/item[not(@selected)]">
														<option value="{./@code}">
															<xsl:value-of select="./@name"/>
														</option>
													</xsl:for-each>
												</select>
											</span>
										</div>
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-order-employee;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<select class="required default newselect" name="{concat('data[', ./id, '][employee_id]')}">
													<option value="{$employees/items/item[@selected]/@id}" selected="selected">
														<xsl:value-of select="$employees/items/item[@selected]/@name"/>
													</option>
													<xsl:for-each select="$employees/items/item[not(@selected)]">
														<option value="{./@id}">
															<xsl:value-of select="./@name"/>
														</option>
													</xsl:for-each>
												</select>
											</span>
										</div>
										<div class="col-md-6 datePicker default-empty-validation" umi:date-only = "true" umi:date-format = "dd.mm.yy">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-order-date;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<input
														class="default date-field"
														type="text"
														name="{concat('data[', ./id, '][date]')}"
														value="{php:function('date', 'd.m.Y', number(./date/@unix-timestamp))}"
														id="{generate-id()}"
														placeholder="&js-date-placeholder;"
														/>
											</span>
										</div>
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-order-time;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<input
														class="default time-field"
														type="text"
														name="{concat('data[', ./id, '][time]')}"
														value="{./time}"
														id="{generate-id()}"
														placeholder="&js-time-placeholder;
														"/>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<xsl:call-template name="std-form-buttons"/>
					</div>
				</form>
			</div>
		</div>
		<xsl:call-template name="error-checker" >
			<xsl:with-param name="launch" select="1" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="/result[@method = 'editService' or @method = 'addService']/data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<xsl:if test="./id">
			<div class="editing-functions-wrapper">
				<div class="tabs editing"></div>
				<div class="toolbar clearfix">
					<xsl:call-template name="delete.button">
						<xsl:with-param name="id" select="./id" />
						<xsl:with-param name="method">
							<xsl:text>deleteServices</xsl:text>
						</xsl:with-param>
						<xsl:with-param name="location">
							<xsl:text>/admin/appointment/services</xsl:text>
						</xsl:with-param>
						<xsl:with-param name="title">&label-deleting;</xsl:with-param>
						<xsl:with-param name="content">&label-are-you-really-want-to-delete;</xsl:with-param>
						<xsl:with-param name="cancel">&label-deleting-cancel;</xsl:with-param>
						<xsl:with-param name="confirm">&label-deleting-confirm;</xsl:with-param>
					</xsl:call-template>
				</div>
			</div>
		</xsl:if>
		<xsl:variable name="id">
			<xsl:choose>
				<xsl:when test="./id">
					<xsl:value-of select="./id"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>new</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="parentId">
			<xsl:choose>
				<xsl:when test="./rel">
					<xsl:value-of select="./rel"/>
				</xsl:when>
				<xsl:when test="./group_id">
					<xsl:value-of select="./group_id"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text></xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="tabs-content module appointment-module">
			<div class="section selected">
				<xsl:apply-templates select="$errors" />
				<form class="form_modify"  method="post" action="do/" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer"/>
					<input type="hidden" name="domain" value="{$domain-floated}"/>
					<input type="hidden" name="permissions-sent" value="1"/>
					<script type="text/javascript">
						var treeLink = function(key, value){
						var settings = SettingsStore.getInstance();
						return settings.set(key, value, 'expanded');
						}
					</script>
					<div class="panel-settings">
						<div class="title">
							<h3>
								<xsl:text>&label-service-params;</xsl:text>
							</h3>
						</div>
						<xsl:variable name="service.groups" select="document(concat('udata://appointment/serviceGroupsList///',$parentId ,'/',./group_id))/udata"/>
						<div class="content">
							<div class="layout">
								<div class="column">
									<div class="row">
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-service-name;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<input class="default" type="text" name="{concat('data[', $id, '][name]')}" value="{./name}" id="{generate-id()}"/>
											</span>
										</div>
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-service-time;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<input class="default time-field" type="text" name="{concat('data[', $id, '][time]')}" value="{./time}" id="{generate-id()}" placeholder="&js-time-placeholder;"/>
											</span>
										</div>
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-service-price;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<input class="default number-field" type="text" name="{concat('data[', $id, '][price]')}" value="{./price}" id="{generate-id()}"/>
											</span>
										</div>
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-service-group;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<select class="required default newselect" autocomplete="off"
														name="{concat('data[', $id, '][group_id]')}">
													<option value="{$service.groups/items/item[@selected]/@id}"
															selected="selected">
														<xsl:value-of
																select="$service.groups/items/item[@selected]/@name"/>
													</option>
													<xsl:for-each
															select="$service.groups/items/item[not(@selected)]">
														<option value="{./@id}">
															<xsl:value-of select="./@name"/>
														</option>
													</xsl:for-each>
												</select>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<xsl:choose>
							<xsl:when test="$id = 'new'">
								<xsl:call-template name="std-form-buttons-add"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:call-template name="std-form-buttons"/>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</form>
			</div>
		</div>
		<xsl:call-template name="error-checker" >
			<xsl:with-param name="launch" select="1" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="/result[@method = 'editServiceGroup' or @method = 'addServiceGroup']/data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<xsl:if test="./id">
			<div class="editing-functions-wrapper">
				<div class="tabs editing"></div>
				<div class="toolbar clearfix">
					<xsl:call-template name="delete.button">
						<xsl:with-param name="id" select="./id" />
						<xsl:with-param name="method">
							<xsl:text>deleteServiceGroups</xsl:text>
						</xsl:with-param>
						<xsl:with-param name="location">
							<xsl:text>/admin/appointment/services</xsl:text>
						</xsl:with-param>
						<xsl:with-param name="title">&label-deleting;</xsl:with-param>
						<xsl:with-param name="content">&label-are-you-really-want-to-delete;</xsl:with-param>
						<xsl:with-param name="cancel">&label-deleting-cancel;</xsl:with-param>
						<xsl:with-param name="confirm">&label-deleting-confirm;</xsl:with-param>
					</xsl:call-template>
				</div>
			</div>
		</xsl:if>
		<xsl:variable name="id">
			<xsl:choose>
				<xsl:when test="./id">
					<xsl:value-of select="./id"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>new</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<div class="tabs-content module appointment-module">
			<div class="section selected">
				<xsl:apply-templates select="$errors" />
				<form class="form_modify"  method="post" action="do/" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer"/>
					<input type="hidden" name="domain" value="{$domain-floated}"/>
					<input type="hidden" name="permissions-sent" value="1"/>
					<script type="text/javascript">
						var treeLink = function(key, value){
						var settings = SettingsStore.getInstance();
						return settings.set(key, value, 'expanded');
						}
					</script>
					<div class="panel-settings">
						<div class="title">
							<h3>
								<xsl:text>&label-service-group-params;</xsl:text>
							</h3>
						</div>
						<div class="content">
							<div class="layout">
								<div class="column">
									<div class="row">
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-service-group-name;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<input class="default" type="text" name="{concat('data[', $id, '][name]')}" value="{./name}" id="{generate-id()}"/>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<xsl:choose>
							<xsl:when test="$id = 'new'">
								<xsl:call-template name="std-form-buttons-add"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:call-template name="std-form-buttons"/>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</form>
			</div>
		</div>
		<xsl:call-template name="error-checker" >
			<xsl:with-param name="launch" select="1" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="/result[@method = 'editEmployee' or @method = 'addEmployee']/data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<xsl:if test="./id">
			<div class="editing-functions-wrapper">
				<div class="tabs editing"></div>
					<div class="toolbar clearfix">
					<xsl:call-template name="delete.button">
						<xsl:with-param name="id" select="./id" />
						<xsl:with-param name="method">
							<xsl:text>deleteEmployees</xsl:text>
						</xsl:with-param>
						<xsl:with-param name="location">
							<xsl:text>/admin/appointment/employees</xsl:text>
						</xsl:with-param>
						<xsl:with-param name="title">&label-deleting;</xsl:with-param>
						<xsl:with-param name="content">&label-are-you-really-want-to-delete;</xsl:with-param>
						<xsl:with-param name="cancel">&label-deleting-cancel;</xsl:with-param>
						<xsl:with-param name="confirm">&label-deleting-confirm;</xsl:with-param>
					</xsl:call-template>
				</div>
			</div>
		</xsl:if>
		<xsl:variable name="id">
			<xsl:choose>
				<xsl:when test="./id">
					<xsl:value-of select="./id"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>new</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<div class="tabs-content module appointment-module">
			<div class="section selected">
				<xsl:apply-templates select="$errors" />
				<form class="form_modify"  method="post" action="do/" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}" id="form-referer"/>
					<input type="hidden" name="domain" value="{$domain-floated}"/>
					<input type="hidden" name="permissions-sent" value="1"/>
					<script type="text/javascript">
						var treeLink = function(key, value){
						var settings = SettingsStore.getInstance();
						return settings.set(key, value, 'expanded');
						}
					</script>
					<div class="panel-settings">
						<div class="title field-group-toggle">
							<xsl:if test="$id != 'new'">
								<div class="round-toggle"></div>
							</xsl:if>
							<h3>
								<xsl:text>&label-employee-params;</xsl:text>
							</h3>
						</div>
						<div class="content">
							<div class="layout">
								<div class="column">
									<div class="row">
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-employee-name;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<input class="default" type="text" name="{concat('data[', $id, '][name]')}" value="{./name}" id="{generate-id()}"/>
											</span>
										</div>
										<xsl:variable name="filemanager-id" select="document(concat('uobject://',/result/@user-id))/udata//property[@name = 'filemanager']/value/item/@id" />
										<xsl:variable name="filemanager">
											<xsl:choose>
												<xsl:when test="not($filemanager-id)">
													<xsl:text>elfinder</xsl:text>
												</xsl:when>
												<xsl:otherwise>
													<xsl:value-of select="document(concat('uobject://',$filemanager-id))/udata//property[@name = 'fm_prefix']/value" />
												</xsl:otherwise>
											</xsl:choose>
										</xsl:variable>
										<div class="col-md-6 img_file default-empty-validation" id="{generate-id()}" umi:input-name="{concat('data[', $id, '][photo]')}"
											 umi:field-type="umiImageType"
											 umi:name="photo"
											 umi:file="{./photo/@path}"
											 umi:file-hash="{php:function('elfinder_get_hash', string(./photo/@path))}"
											 umi:lang="{/result/@interface-lang}"
											 umi:filemanager="{$filemanager}"

												>
											<label for="imageField_{generate-id()}">
												<div class="title-edit">
													<acronym title="{@tip}">
														<xsl:text>&label-field-employee-photo;</xsl:text>
													</acronym>
													<sup><xsl:text>*</xsl:text></sup>
												</div>
												<div class="layout-row-icon" id="imageField_{generate-id()}">

												</div>
											</label>
										</div>
										<div class="col-md-6 default-empty-validation">
											<div class="title-edit">
												<acronym title="{@tip}">
													<xsl:text>&label-field-employee-description;</xsl:text>
												</acronym>
												<sup><xsl:text>*</xsl:text></sup>
											</div>
											<span>
												<textarea name="{concat('data[', $id, '][description]')}" id="{generate-id()}" style="resize: none;">
													<xsl:value-of select="./description"/>
												</textarea>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<xsl:if test="./id">
						<div class="panel-settings">
							<div class="title field-group-toggle">
								<div class="round-toggle"></div>
								<h3>
									<xsl:text>&label-employee-services;</xsl:text>
								</h3>
							</div>
							<xsl:variable name="services" select="document('udata://appointment/servicesList//150')/udata" />
							<xsl:variable name="employees.services" select="document(concat('udata://appointment/employeeServicesIdsList//', ./id))/udata" />
							<div class="content">
								<div class="layout">
									<div class="column">
										<div class="col-md-6">
											<div class="title-edit">
												<xsl:text>&label-services;</xsl:text>
											</div>
											<span>
												<select class="required default newselect" name="data[services][]" multiple="multiple">
													<xsl:for-each select="$services/items/item">
														<xsl:variable name="service.id" select="./@id"/>
														<option value="{./@id}">
															<xsl:if test="$employees.services/items/item[@service_id = $service.id]/@id">
																<xsl:attribute name="selected">
																	<xsl:text>selected</xsl:text>
																</xsl:attribute>
															</xsl:if>
															<xsl:value-of select="./@name"/>
														</option>
													</xsl:for-each>
												</select>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<script type="text/javascript" src="/styles/skins/modern/data/modules/appointment/time.ranges.selections.js"/>
						<xsl:variable name="employee.schedules" select="document(concat('udata://appointment/employeeSchedulesList//', ./id))/udata" />
						<xsl:variable name="work.times" select="document('udata://appointment/getScheduleWorkTimes/')/udata" />
						<div class="panel-settings">
							<div class="title field-group-toggle">
								<div class="round-toggle"></div>
								<h3>
									<xsl:text>&label-employee-schedule;</xsl:text>
								</h3>
							</div>
							<div class="content">
								<div class="layout">
									<div class="column">
										<xsl:call-template name="day.row">
											<xsl:with-param name="day.name">
												<xsl:text>&label-day-0;</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="day.number">
												<xsl:text>0</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="employee.schedules" select="$employee.schedules"/>
											<xsl:with-param name="work.times" select="$work.times"/>
 										</xsl:call-template>
										<xsl:call-template name="day.row">
											<xsl:with-param name="day.name">
												<xsl:text>&label-day-1;</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="day.number">
												<xsl:text>1</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="employee.schedules" select="$employee.schedules"/>
											<xsl:with-param name="work.times" select="$work.times"/>
										</xsl:call-template>
										<xsl:call-template name="day.row">
											<xsl:with-param name="day.name">
												<xsl:text>&label-day-2;</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="day.number">
												<xsl:text>2</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="employee.schedules" select="$employee.schedules"/>
											<xsl:with-param name="work.times" select="$work.times"/>
										</xsl:call-template>
										<xsl:call-template name="day.row">
											<xsl:with-param name="day.name">
												<xsl:text>&label-day-3;</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="day.number">
												<xsl:text>3</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="employee.schedules" select="$employee.schedules"/>
											<xsl:with-param name="work.times" select="$work.times"/>
										</xsl:call-template>
										<xsl:call-template name="day.row">
											<xsl:with-param name="day.name">
												<xsl:text>&label-day-4;</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="day.number">
												<xsl:text>4</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="employee.schedules" select="$employee.schedules"/>
											<xsl:with-param name="work.times" select="$work.times"/>
										</xsl:call-template>
										<xsl:call-template name="day.row">
											<xsl:with-param name="day.name">
												<xsl:text>&label-day-5;</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="day.number">
												<xsl:text>5</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="employee.schedules" select="$employee.schedules"/>
											<xsl:with-param name="work.times" select="$work.times"/>
										</xsl:call-template>
										<xsl:call-template name="day.row">
											<xsl:with-param name="day.name">
												<xsl:text>&label-day-6;</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="day.number">
												<xsl:text>6</xsl:text>
											</xsl:with-param>
											<xsl:with-param name="employee.schedules" select="$employee.schedules"/>
											<xsl:with-param name="work.times" select="$work.times"/>
										</xsl:call-template>
									</div>
								</div>
							</div>
						</div>
					</xsl:if>
					<div class="row">
						<xsl:choose>
							<xsl:when test="$id = 'new'">
								<xsl:call-template name="std-form-buttons-add"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:call-template name="std-form-buttons"/>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</form>
			</div>
		</div>
		<xsl:call-template name="error-checker" >
			<xsl:with-param name="launch" select="1" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="day.row" >
		<xsl:param name="day.number"/>
		<xsl:param name="day.name"/>
		<xsl:param name="work.times"/>
		<xsl:param name="employee.schedules"/>
		<div class="row work_time_range" data-from="&label-from;" data-to="&label-to;">
			<div class="col-md-1 work_time_label">
				<div class="title-edit">
					<acronym>
						<xsl:value-of select="$day.name" />
 					</acronym>
				</div>
			</div>
			<div class="col-md-1">
				<select class="work_time_from" name="{concat('data[schedules][', $day.number, '][from]')}">
					<xsl:apply-templates select="$work.times" >
						<xsl:with-param name="selected">
							<xsl:value-of select="$employee.schedules/items/item[@number = $day.number]/@time_start" />
						</xsl:with-param>
						<xsl:with-param name="default">
							<xsl:text>&label-from;</xsl:text>
						</xsl:with-param>
					</xsl:apply-templates>
				</select>
			</div>
			<div class="time_range_separator">
				<xsl:text>&nbsp; – &nbsp;</xsl:text>
			</div>
			<div class="col-md-1">
				<select class="work_time_to" name="{concat('data[schedules][', $day.number, '][to]')}">
					<xsl:apply-templates select="$work.times" >
						<xsl:with-param name="selected">
							<xsl:value-of select="$employee.schedules/items/item[@number = $day.number]/@time_end" />
						</xsl:with-param>
						<xsl:with-param name="default">
							<xsl:text>&label-to;</xsl:text>
						</xsl:with-param>
					</xsl:apply-templates>
				</select>
			</div>
			<div class="time_range_separator">
				<div class="work_time_clear">×</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template name="delete.button">
		<xsl:param name="id"/>
		<xsl:param name="method"/>
		<xsl:param name="location"/>
		<xsl:param name="title"/>
		<xsl:param name="content"/>
		<xsl:param name="confirm"/>
		<xsl:param name="cancel"/>
		<a id="remove-object" title="&label-deleting-confirm;" class="icon-action">
			<i class="small-ico i-remove"></i>
		</a>
		<script type="text/javascript">
			var obj_id = '<xsl:value-of select="$id" />';
			var del_func_name = '<xsl:value-of select="$method" />';
			var afterDeleteLocation = '<xsl:value-of select="$location" />';
			var windowTitle =  '<xsl:value-of select="$title" />';
			var windowContent =  '<xsl:value-of select="$content" />';
			var windowConfirm = '<xsl:value-of select="$confirm" />';
			var windowCancel = '<xsl:value-of select="$cancel" />';

			$(document).ready(function (){
				$('#remove-object').on('click',function (){
					var csrf = window.parent.csrfProtection.token;
					openDialog('', windowTitle, {
						cancelButton: true,
						html: windowContent,
						confirmText: windowConfirm,
						cancelText: windowCancel,
						confirmCallback: function(popupName) {
							$.ajax({
								url:'/admin/'+curent_module+'/'+del_func_name+'.xml?childs=1&amp;element='+obj_id+'&amp;allow=true&amp;csrf=' + csrf,
								dataType:'xml',
								success: function(data){
									closeDialog(popupName);
									window.location = afterDeleteLocation;
								}
							});
						}
					});
				});
			});
		</script>
	</xsl:template>

	<xsl:template match="udata[@module = 'appointment' and @method = 'getScheduleWorkTimes']">
		<xsl:param name="selected"/>
		<xsl:param name="default"/>
		<xsl:if test="$selected = ''">
			<option value="-1">
				<xsl:value-of select="$default"/>
			</option>
		</xsl:if>
		<xsl:for-each select="items/item">
			<option value="{./@value}" data-number="{./@number}">
				<xsl:if test="$selected = ./@value">
					<xsl:attribute name="selected">
						<xsl:text>selected</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="./@value"/>
			</option>
		</xsl:for-each>
	</xsl:template>

</xsl:stylesheet>
