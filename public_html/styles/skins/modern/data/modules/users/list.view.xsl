<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/users" [
	<!ENTITY sys-module        'users'>
	<!ENTITY sys-method-add        'add'>
	<!ENTITY sys-method-edit    'edit'>
	<!ENTITY sys-method-del        'del'>
	<!ENTITY sys-method-list    'users_list'>
	<!ENTITY sys-method-acivity    'activity'>

	<!ENTITY sys-type-user        'user'>
	<!ENTITY sys-type-group        'users'>

	<!ENTITY sys-method-list-users-all    'users_list_all'>
	<!ENTITY sys-method-list-users        'users_list'>
	<!ENTITY sys-method-list-groups        'groups_list'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="result[@method = 'users_list' or @method = 'users_list_all']/data">
		<div class="location">
			<div class="imgButtonWrapper loc-left" xmlns:umi="http://www.umi-cms.ru/TR/umi">
				<a href="{$lang-prefix}/admin/&sys-module;/add/user/" class="type_select btn color-blue" umi:type="users::user">
					<xsl:text>&label-add-user;</xsl:text>
				</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">

				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="control-params" select="$method" />
					<xsl:with-param name="content-type">objects</xsl:with-param>
					<xsl:with-param name="enable-objects-activity">1</xsl:with-param>
					<xsl:with-param name="js-visible-props-menu">'register_date[250px]'</xsl:with-param>
					<xsl:with-param name="js-sequence-props-menu">['register_date']</xsl:with-param>
					<xsl:with-param name="js-ignore-props-edit">['groups']</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>
	
	<xsl:template match="result[@method = 'groups_list']/data">
		<div class="location">
			<div class="imgButtonWrapper loc-left">
				<a href="{$lang-prefix}/admin/&sys-module;/add/users/" class="btn color-blue">
					<xsl:text>&label-add-group;</xsl:text>
				</a>
			</div>
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">

				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="control-params">groups</xsl:with-param>
					<xsl:with-param name="content-type">objects</xsl:with-param>
				</xsl:call-template>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>
</xsl:stylesheet>