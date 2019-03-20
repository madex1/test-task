<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/data">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:param name="type-id" />
	<xsl:param name="hierarchy-type-id" />

	<xsl:template match="data[@type = 'form']">
		<div id="eip_page">
			<form action="do/" method="post">
				<input type="hidden" name="type-id" value="{$type-id}" />
				<input type="hidden" name="hierarchy-type-id" value="{$hierarchy-type-id}" />
				<input type="hidden" name="default-permissions-set" value="1" />
				<input type="hidden" name="csrf">
					<xsl:attribute name="value"><xsl:value-of select="//@csrf" /></xsl:attribute>
				</input>
				
				<xsl:apply-templates />
				<div class="eip_buttons">
					<input type="submit" value="&label-add;" class="primary ok" />
					<input type="button" value="&label-cancel;" class="back" onclick="popupCancel()" />
					<div style="clear:both;"></div>
				</div>
			</form>
		</div>
	</xsl:template>
	
	<xsl:template match="data[@type = 'form']/page">
		<xsl:apply-templates select="properties/group[@name = 'common']" />
		<xsl:apply-templates select="properties[count(group[not(
			@name = 'common' or
			@name = 'menu_view' or
			@name = 'more_params' or
			@name = 'svojstva_publikacii' or
			@name = 'catalog_stores_props'
		)]) > 0]" />
	</xsl:template>
	
	
	<!-- Restricted fields and groups when we use eip mode -->
	<xsl:template match="data[@type = 'form']//field[
		@name = 'h1' or
		@name = 'title' or
		@name = 'meta_keywords' or 
		@name = 'meta_descriptions' or
		@name = 'tags' or
		@name = 'begin_time' or 
		@name = 'end_time' or 
		@name = 'item_type_id' or
		@name = 'friendlist' or
		@name = 'author_id' or 
		@name = 'topics_count' or 
		@name = 'messages_count' or 
		@name = 'last_message' or 
		@name = 'last_post_time'
	]" priority="1" />
	
	<xsl:template match="data[@type = 'form']//group[
		@name = 'menu_view' or
		@name = 'more_params' or
		@name = 'svojstva_publikacii' or
		@name = 'catalog_stores_props'
	]/field" priority="1" />
	
	<xsl:template match="properties">
		<fieldset class="collapsible">
			<legend>
				<a href="#group-advanced">
					<xsl:text>&fields-group-advanced;</xsl:text>
				</a>
			</legend>
			
			<div id="group-advanced" style="display:none;">
				<ul>
					<xsl:apply-templates select="group[not(@name = 'common')]/field" />
				</ul>
			</div>
		</fieldset>
	</xsl:template>
	
	<xsl:template match="data[@type = 'form']//group">
		<xsl:variable name="basetype" select="/result/data/page/basetype" />
		<fieldset class="collapsible">
			<xsl:apply-templates select="." mode="header"/>

			<div id="group-{@name}">
				<xsl:if test="not(position() = 1)">
					<xsl:attribute name="style">
						<xsl:text>display: none</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<ul>
					<xsl:if test="@name = 'common'">
						<xsl:call-template name="page-common-fields" />
					</xsl:if>

					<xsl:apply-templates />
					
					<xsl:if test="@name = 'common' and not($basetype/@module='news' and $basetype/@method='item' or $basetype/@module='filemanager' and $basetype/@method='shared_file' or $basetype/@module='photoalbum' and $basetype/@method='photo')">
						<xsl:call-template name="page-menu-options" />
					</xsl:if>
				</ul>
			</div>
		</fieldset>
	</xsl:template>

	<xsl:template match="data[@type = 'form']//group" mode="header">
		<legend>
			<xsl:choose>
				<xsl:when test="tip">
					<acronym title="{tip}" class="eip_field_tip_title">
						<xsl:apply-templates select="." mode="title"/>
					</acronym>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="." mode="title"/>
				</xsl:otherwise>
			</xsl:choose>
		</legend>
	</xsl:template>

	<xsl:template match="group" mode="title">
		<a href="#group-{@name}" class="eip_disable_link">
			<xsl:value-of select="@title" />
		</a>
	</xsl:template>

	<xsl:template match="data[@type = 'form']//group/tip" />

	<!-- Case for unknown field -->
	<xsl:template match="data[@type = 'form']//field">
		<p class="error">
			<xsl:text>&styles-skins-eip-data-form-modify-in_current_window_management_fields_unavailable;</xsl:text>
		</p>
	</xsl:template>
	
	<!-- Case for optioned field -->
	<xsl:template match="data[@type = 'form']//field[@type = 'optioned']">
		<p class="error">
			<xsl:text>&styles-skins-eip-data-form-modify-in_current_window_management_optional_properties_unavailable;</xsl:text>
		</p>
	</xsl:template>
	
	<!-- Simple text fields -->
	<xsl:template
		match="data[@type = 'form']//field[@type = 'string' or @type = 'int' or @type = 'float' or @type = 'price' or @type='tags']">
		<li>
			<label for="{generate-id(.)}">
				<xsl:apply-templates select="." mode="title"/>
				<input type="text" name="{@input_name}" value="{.}" class="{@type}" id="{generate-id(.)}" />
			</label>
		</li>
	</xsl:template>

	<xsl:template match="field[@tip]" mode="title" >
		<p class="eip_field_title_container">
			<acronym title="{@tip}" class="eip_field_tip_title">
				<xsl:value-of select="@title" />
				<xsl:apply-templates select="@required" />
			</acronym>
		</p>
	</xsl:template>

	<xsl:template match="field" mode="title" >
		<p class="eip_field_title_container">
			<xsl:value-of select="@title" />
			<xsl:apply-templates select="@required" />
		</p>
	</xsl:template>
	
	<xsl:template match="data[@type = 'form']//field[@type = 'date']">
		<li>
			<label for="{generate-id(.)}">
				<xsl:apply-templates select="." mode="title"/>
				<input type="text" name="{@input_name}" class="{@type}" id="{generate-id(.)}">
					<xsl:attribute name="value">
						<xsl:value-of select="document('udata://system/convertDate/now/(Y-m-d%20H:i)')/udata" />
					</xsl:attribute>
				</input>
			</label>
		</li>
	</xsl:template>
	
	<!-- Password fields -->
	<xsl:template match="data[@type = 'form']//field[@type = 'password']">
		<li>
			<label for="{generate-id(.)}">
				<xsl:apply-templates select="." mode="title"/>
				<input type="password" name="{@input_name}" value="{.}" class="{@type}" id="{generate-id(.)}"/>
			</label>
		</li>
	</xsl:template>
	
	<!-- Checkbox fields -->
	<xsl:template match="data[@type = 'form']//field[@type = 'boolean']">
		<li>
			<input type="hidden" name="{@input_name}" value="0" />
			<input type="checkbox" name="{@input_name}" class="{@type}" id="{generate-id(.)}">
				<xsl:if test=". = 1">
					<xsl:attribute name="checked">
						<xsl:text>checked</xsl:text>
					</xsl:attribute>
				</xsl:if>
			</input>
			
			<label for="{generate-id(.)}" class="boolean">
				<xsl:apply-templates select="." mode="title"/>
			</label>
		</li>
	</xsl:template>
	
	<!-- Textarea fields -->
	<xsl:template match="data[@type = 'form']//field[@type = 'wysiwyg' or @type = 'text']">
		<li>
			<label>
				<xsl:apply-templates select="." mode="title"/>
				<textarea id="{@id}" name="{@input_name}" value="{.}" class="{@type} eipPopupText">
				</textarea>
			</label>
		</li>
	</xsl:template>
	
	<!-- File fields -->
	<xsl:template match="data[@type = 'form']//field[@type = 'file' or @type = 'img_file' or @type = 'video_file']">
		<xsl:variable name="type_param">
			<xsl:choose>
				<xsl:when test="@type = 'img_file'">, imagesOnly : true</xsl:when>
				<xsl:when test="@type = 'video_file'">, videosOnly : true</xsl:when>
			</xsl:choose>
		</xsl:variable>
		<li>
			<label for="{generate-id(.)}">
				<xsl:apply-templates select="." mode="title"/>
				<div id="fileControlContainer_{@name}" class="cifi" />
				<script type="text/javascript">				
					var fc_<xsl:value-of select="@name"/> = new fileControl('<xsl:value-of select="@name"/>', {fm: 'elfinder', inputName : '<xsl:value-of select="@input_name"/>' <xsl:value-of select="$type_param"/>});
						fc_<xsl:value-of select="@name"/>.setFolder('<xsl:value-of select="@destination-folder"/>', true);
					fc_<xsl:value-of select="@name"/>.add('<xsl:value-of select="."/>', true);
				</script>
			</label>
		</li>
	</xsl:template>
	
	<!-- Relation fields -->
	<xsl:template match="data[@type = 'form']//field[@type = 'relation']">
		<li>
			<label for="{generate-id(.)}">
				<xsl:apply-templates select="." mode="title"/>
				<select name="{@input_name}" id="relationSelect{@type-id}" class="{@type}">
					<xsl:choose>
						<xsl:when test="@multiple = 'multiple'">
							<xsl:attribute name="multiple">multiple</xsl:attribute>
							<xsl:attribute name="size">4</xsl:attribute>

							<xsl:apply-templates select="values/item">
								<xsl:sort select="@selected" data-type="text" order="descending" />
							</xsl:apply-templates>
						</xsl:when>

						<xsl:otherwise>
							<option/>
							<xsl:apply-templates select="values/item[@selected = 'selected']" />
						</xsl:otherwise>
					</xsl:choose>
				</select>

				<xsl:if test="@type-id">
					<input type="text" name="{@name}_new" id="relationInput{@type-id}" class="relation-search"/>
					<xsl:if test="@public-guide">
						<input type="button" id="relationButton{@type-id}" value =" " class="relation-add" />
					</xsl:if>
		
					<script type="text/javascript">
						new relationControl(<xsl:value-of select="@type-id" />);
					</script>
				</xsl:if>
			</label>
			
		</li>
	</xsl:template>
	
	<xsl:template match="field[@type = 'relation']/values/item">
		<option value="{@id}">
			<xsl:if test="@selected = 'selected'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>

			<xsl:value-of select="."/>
		</option>
	</xsl:template>

	<!-- Symlink fields -->

	<xsl:template match="data[@type = 'form']//field[@type = 'symlink']">
		<li>
			<label for="{generate-id(.)}">
				<xsl:apply-templates select="." mode="title"/>
			<div id="symlinkInput{@id}" class="symlink">
			</div>
			</label>

			<script type="text/javascript">
				var symlink = new symlinkControl(<xsl:value-of select="@id"/>, 'content', [],
									{ inputName      : '<xsl:value-of select="@input_name"/>',
									  fadeColorStart : [255, 255, 225],
									  fadeColorEnd   : [255, 255, 255]});
				<xsl:for-each select="values/item">
					symlink.addItem(<xsl:value-of select="@id"/>,
									'<xsl:value-of select="./name/text()"/>',
									['<xsl:value-of select="./basetype/@module"/>', '<xsl:value-of select="./basetype/@method"/>'],
									'<xsl:value-of select="@link"/>');
				</xsl:for-each>
			</script>
		</li>
	</xsl:template>
	
	<!-- Asteriks for required attribute -->
	<xsl:template match="@required" name="required">
		<span class="required">
			<xsl:text>*</xsl:text>
		</span>
	</xsl:template>
	
	
	<!-- Page permissions -->
	<xsl:template name="page-permissions">
		<xsl:param name="page-id" />
		
		<fieldset class="collapsible">
			<legend>
				<a href="#group-permissions">
					<xsl:text>&label-permissions;</xsl:text>
				</a>
			</legend>
			
			<div id="group-permissions">
				<ul>
					<xsl:choose>
						<xsl:when test="$page-id">
							<xsl:apply-templates select="document(concat('udata://users/permissions///', $page-id))/udata"/>
						</xsl:when>
				
						<xsl:otherwise>
							<xsl:text>TODO: Implement for new-page case</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</ul>
				
				<p class="buttons">
					<input type="button" value="&label-cancel;" />
					<input type="submit" value="&label-save;" class="primary" />
				</p>
			</div>
		</fieldset>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'users' and @method = 'permissions']">
		<li>
			<div id="permissionsContainer"></div>
			
			<script type="text/javascript">
				var perms = new permissionsControl("permissionsContainer");
				<xsl:for-each select="users/user">
					perms.add(<xsl:value-of select="@id" />, '<xsl:value-of select="@login" />', <xsl:value-of select="@access" />);
				</xsl:for-each>
				<xsl:for-each select="groups/group">
					perms.add(<xsl:value-of select="@id" />, '<xsl:value-of select="@title" />', <xsl:value-of select="@access" />);
				</xsl:for-each>
			</script>
		</li>
	</xsl:template>
	
	
	
	<xsl:template name="page-common-fields">
		<li>
			<label for="{generate-id(.)}">
				<p class="eip_field_title_container">
					<xsl:text>&label-name;</xsl:text><xsl:call-template name="required" />
				</p>
				<input type="text" name="name" value="{name}" class="string" id="{generate-id(.)}"/>
			</label>
		</li>
	</xsl:template>

	<xsl:template name="page-menu-options">
		<li>
			<input type="hidden" name="is-visible" value="0" />
			<input type="checkbox" name="is-visible" checked="" class="boolean" id="is-visible" value="1" />
			
			<label for="is-visible" class="boolean">
				<xsl:text>&label-is-visible;</xsl:text>
			</label>
		</li>
		
		<li>
			<input type="hidden" name="data[new][show_submenu]" value="0" />
			<input type="checkbox" name="data[new][show_submenu]" class="boolean" id="show-submenu" value="1" />
			
			<label for="show-submenu" class="boolean">
				<xsl:text>&field-show_submenu;</xsl:text>
			</label>
		</li>
	</xsl:template>
	
	<xsl:include href="udata://core/importSkinXsl/form.modify.xsl"/>
	<xsl:include href="udata://core/importSkinXsl/form.modify.custom.xsl"/>
</xsl:stylesheet>