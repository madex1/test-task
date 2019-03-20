<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="object" mode="form-modify" priority="1">
		<xsl:apply-templates select="properties/group" mode="form-modify">
			<xsl:with-param name="show-name" select="count(.//field[@name = 'nazvanie'])" />
		</xsl:apply-templates>
		<xsl:variable name="user-id" select="/result/data/object/@id" />
		<xsl:choose>
			<xsl:when test="$user-id">
				<xsl:apply-templates select="document(concat('udata://users/choose_perms/', $user-id))/udata" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="document('udata://users/choose_perms/')/udata" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон группы полей "Идентификационные данные" пользователя -->
	<xsl:template match="properties/group[@name = 'idetntify_data']" mode="form-modify">
		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>1</xsl:text></xsl:param>
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
		<div name="g_{@name}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<a data-name="{@name}" data-label="{@title}"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:value-of select="@title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="param" select="@name" />
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-7">
								<div class="row">
									<xsl:apply-templates select="field[@name = 'login']" mode="form-modify">
										<xsl:with-param name="class">col-md-10</xsl:with-param>
									</xsl:apply-templates>
									<xsl:apply-templates select="field[@name = 'password']" mode="form-modify"/>
									<xsl:apply-templates select="field[@name != 'groups' and @name != 'login' and @name != 'password']" mode="form-modify">
										<xsl:with-param name="class">col-md-10</xsl:with-param>
									</xsl:apply-templates>
								</div>
							</div>
							<xsl:apply-templates select="field[@name = 'groups']" mode="form-modify">
								<xsl:with-param name="class">col-md-4</xsl:with-param>
							</xsl:apply-templates>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<!--
	 	Вывод поля "пароль" в форме редактирования пользователя с полем для повторного ввода пароля
	 -->
	<xsl:template match="/result[@module = 'users' and @method = 'edit']//field[@type = 'password']" mode="form-modify">
		<div class="col-md-10">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:value-of select="@title" />
				</acronym>
			</div>
			<span>
				<input type="password" name="{@input_name}" value="{.}" id="{generate-id()}" class="password default" />
			</span>
		</div>
		<div class="col-md-10 password-repeat">
			<div class="title-edit">
				<acronym>
					<xsl:text>&label-repeat-password;</xsl:text>
				</acronym>
			</div>
			<span>
				<input type="password" name="data[{../../../@id}][password_repeat][]" class="password default"
					   data-password-input-name="{@input_name}"/>
			</span>
		</div>
	</xsl:template>

	<!--
	 	Вывод поля "пароль" в форме добавления пользователя с полем для повторного ввода пароля,
	 	оба поля обязательны для заполнения
	 -->
	<xsl:template match="/result[@module = 'users' and @method = 'add']//field[@type = 'password']" mode="form-modify">
		<div class="col-md-10 default-empty-validation">
			<div class="title-edit">
				<acronym title="{@tip}">
					<xsl:value-of select="@title" />
				</acronym>
				<sup><xsl:text>*</xsl:text></sup>
			</div>
			<span>
				<input type="password" name="{@input_name}" value="{.}" id="{generate-id()}" class="password default" />
			</span>
		</div>
		<div class="col-md-10 default-empty-validation password-repeat">
			<div class="title-edit">
				<acronym>
					<xsl:text>&label-repeat-password;</xsl:text>
				</acronym>
				<sup><xsl:text>*</xsl:text></sup>
			</div>
			<span>
				<input type="password" name="data[new][password_repeat][]" class="password default"
					   data-password-input-name="{@input_name}"/>
			</span>
		</div>
	</xsl:template>

	<!-- Шаблон группы полей "Дополнительная информация" пользователя -->
	<xsl:template match="properties/group[@name = 'more_info']" mode="form-modify">
		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>1</xsl:text></xsl:param>
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
		<div name="g_{@name}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<a data-name="{@name}" data-label="{@title}"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:value-of select="@title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="param" select="@name" />
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:apply-templates select="/result[@module = 'users' and @method = 'edit']"
												 mode="form-modify-actAsUser"/>
							<xsl:apply-templates select="." mode="form-modify-group-fields">
								<xsl:with-param name="show-name" select="$show-name"/>
								<xsl:with-param name="show-type" select="$show-type"/>
							</xsl:apply-templates>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@module = 'users' and @method = 'edit']" mode="form-modify-actAsUser">
		<div class="col-md-12" style="margin-bottom:13px;">
			<xsl:variable name="user-id" select="/result/data/object/@id" />
			<a href="{$lang-prefix}/admin/emarket/actAsUser/{$user-id}/">
				<xsl:attribute name="title">&label-act-as-user-tip;</xsl:attribute>
				<xsl:text>&label-act-as-user;</xsl:text>
			</a>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'string' and @name = 'nazvanie']" mode="form-modify" />

	<!-- Шаблон прав доступа пользователя или группы -->
	<xsl:template match="udata[@module = 'users' and @method = 'choose_perms']">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'perms')"/>
		<div name="g_perms">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<a data-name="'perms'" data-label="&permissions-panel;"/>
			<summary class="group-tip">
				<xsl:text>Управление правами доступа пользователя к модулям в административной панели.</xsl:text>
			</summary>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:text>&permissions-panel;</xsl:text>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="param" select="'perms'" />
					<xsl:with-param name="force-show" select="1" />
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<table class="btable btable-striped permissions">
							<thead>
								<tr>
									<th style="width:40px;"></th>
									<th>
										<xsl:text>&permissions-module;</xsl:text>
									</th>

									<th>
										<xsl:text>&permissions-use-access;</xsl:text>
									</th>

									<th>
										<xsl:text>&permissions-other-access;</xsl:text>
									</th>
								</tr>
							</thead>

							<tbody>
								<xsl:apply-templates select="module" />
							</tbody>
						</table>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="module">
		<tr>
			<th>
				<i class="big-ico" style="background-image: url('/images/cms/admin/modern/icon/{@name}.png');"></i>
			</th>
			<td>
				<div class="module_icon" style="padding-top:10px;">
					<span>
						<xsl:value-of select="@label"/>
					</span>

				</div>
			</td>

			<td align="center">
				<input value="{@name}" name="ps_m_perms[{@name}]" type="hidden" />
				<div class="checkbox">
					<xsl:if test="@access > 0">
						<xsl:attribute name="class">checkbox checked</xsl:attribute>
					</xsl:if>

					<input value="{@name}" name="m_perms[]" type="checkbox" class="check">
						<xsl:if test="@access > 0">
							<xsl:attribute name="checked"/>
						</xsl:if>
					</input>
				</div>
			</td>

			<td style="width: 41%;">
				<xsl:if test="@name = 'content'">
					<ul style="margin-bottom:15px;">
						<xsl:apply-templates select="../domains/domain" />
					</ul>
				</xsl:if>

				<ul>
					<xsl:apply-templates select="option" />
				</ul>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="module/option">
		<li>
			<label>
				<div class="checkbox">
					<xsl:if test="@access > 0">
						<xsl:attribute name="class">checkbox checked</xsl:attribute>
					</xsl:if>
					<input type="checkbox" name="{../@name}[{@name}]" class="check" value="1">
						<xsl:if test="@access > 0">
							<xsl:attribute name="checked"/>
						</xsl:if>
					</input>
				</div>
				<span>
					<xsl:value-of select="@label"/>
				</span>
			</label>
		</li>
	</xsl:template>

	<xsl:template match="domains/domain">
		<li>
			<input type="hidden" name="domain[{@id}]" value="0"/>
			<label>
				<div class="checkbox">
					<xsl:if test="@access > 0">
						<xsl:attribute name="class">checkbox checked</xsl:attribute>
					</xsl:if>
					<input type="checkbox" name="domain[{@id}]" value="1" class="check">
						<xsl:if test="@access > 0">
							<xsl:attribute name="checked"/>
						</xsl:if>
					</input>
				</div>
				<span><xsl:value-of select="@host" /></span>
			</label>
		</li>
	</xsl:template>

	<xsl:template match="field[@name = 'groups' and document('udata://system/getGuideItemsCount/users-users/')/udata/items/@total &lt; 16]" mode="form-modify" priority="1">
		<dl class="col-md-4">
			<dt class="title-edit">
				<xsl:value-of select="@title" />
			</dt>

			<xsl:apply-templates select="values/item" mode="form-modify" />
		</dl>
	</xsl:template>

	<xsl:template match="field[@name = 'groups']/values/item" mode="form-modify" priority="1">
		<xsl:choose>
			<xsl:when test="document(concat('uobject://', @id))/udata/object/@guid = 'users-users-15'">
				<xsl:variable name="userInfo" select="document(concat('uobject://', $current-user-id))/udata/object" />
				<xsl:if test="$userInfo/@guid = 'system-supervisor' or count($userInfo//property[@name='groups']/value/item[@guid='users-users-15'])">
					<dd>
						<input type="hidden" name="{../../@input_name}" value="0" />
						<div class="checkbox">
							<xsl:if test="@selected = 'selected'">
								<xsl:attribute name="class">
									<xsl:text>checkbox checked</xsl:text>
								</xsl:attribute>
							</xsl:if>
							<input type="checkbox" name="{../../@input_name}" value="{@id}" class="checkbox"
								   id="group-{@id}">
								<xsl:if test="@selected = 'selected'">
									<xsl:attribute name="checked">
										<xsl:text>checked</xsl:text>
									</xsl:attribute>
								</xsl:if>
							</input>
						</div>
						<span for="group-{@id}">
							<xsl:value-of select="."/>
						</span>
					</dd>
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<dd>
					<input type="hidden" name="{../../@input_name}" value="0" />
					<div class="checkbox">
						<xsl:if test="@selected = 'selected'">
							<xsl:attribute name="class">
								<xsl:text>checkbox checked</xsl:text>
							</xsl:attribute>
						</xsl:if>
						<input type="checkbox" name="{../../@input_name}" value="{@id}" class="checkbox"
							   id="group-{@id}">
							<xsl:if test="@selected = 'selected'">
								<xsl:attribute name="checked">
									<xsl:text>checked</xsl:text>
								</xsl:attribute>
							</xsl:if>
						</input>
					</div>
					<label for="group-{@id}">
						<xsl:value-of select="." />
					</label>
				</dd>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="/result[@method = 'add']//field[@name = 'is_activated' and @type = 'boolean']" mode="form-modify">
		<xsl:if test="preceding-sibling::field/@type != 'boolean'">
			<div style="clear: left;" />
		</xsl:if>
		<div class="col-md-6 field">
			<label class="inline" for="{generate-id()}">
				<span class="label">
					<input type="hidden" name="{@input_name}" value="0" />
					<div class="checkbox checked">
						<input type="checkbox" name="{@input_name}" value="1" id="{generate-id()}" checked="checked">
							<xsl:apply-templates select="." mode="required_attr">
								<xsl:with-param name="old_class" select="'checkbox'"/>
							</xsl:apply-templates>
						</input>
					</div>

					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</span>
			</label>
		</div>
	</xsl:template>
	
	<xsl:template match="field[@name = 'filemanager_directory']" mode="form-modify" priority="1">		
		<xsl:variable name="userInfo" select="document(concat('uobject://', $current-user-id))/udata/object" />
		<xsl:if test="$userInfo/@guid = 'system-supervisor' or count($userInfo//property[@name='groups']/value/item[@guid='users-users-15'])">
			<div class="col-md-6">
					<div class="title-edit">
						<acronym title="{@tip}">
							<xsl:apply-templates select="." mode="sys-tips" />
							<xsl:value-of select="@title" />
						</acronym>
					</div>
					<span>
						<input id="{generate-id()}" class="string default" type="text" value="{.}" name="{@input_name}"/>
					</span>
			</div>
		</xsl:if>
	</xsl:template>

	<!-- Шаблон группы полей "Статистическая информация" пользователя -->
	<xsl:template match="properties/group[@name = 'statistic_info']" mode="form-modify">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, @name)"/>
		<div name="g_{@name}">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="$groupIsHidden">
						<xsl:text>panel-settings has-border</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>panel-settings</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<a data-name="{@name}" data-label="{@title}"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:value-of select="@title" />
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="param" select="@name" />
					<xsl:with-param name="isHidden" select="$groupIsHidden"/>
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:if test="position() = 1 and not(/result/@method='template_add') and not(/result/@method='template_edit')">
								<div class="col-md-12">
									<div class="title-edit">
										<xsl:text>&label-name;</xsl:text>
									</div>
									<span>
										<input type="text" name="name" value="{../../@name}"/>
									</span>
								</div>
							</xsl:if>
							<xsl:apply-templates select="field" mode="form-modify"/>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template match="properties/group[@name = 'statistic_info']/field" mode="form-modify">
		<div class="col-md-12">
				<div class="title-edit">
					<acronym title="{@tip}"><xsl:value-of select="./@title" /></acronym>
				</div>
				<a href="{.}" id="{generate-id()}" class="text" name="{@input_name}">
					<xsl:apply-templates select="." mode="value" />
				</a>
		</div>
	</xsl:template>

	<xsl:template match="properties/group[@name = 'statistic_info']/field" mode="value">
		<xsl:text>/</xsl:text>
	</xsl:template>

	<xsl:template match="properties/group[@name = 'statistic_info']/field[. != '']" mode="value">
		<xsl:value-of select="." disable-output-escaping="yes" />
	</xsl:template>

</xsl:stylesheet>
