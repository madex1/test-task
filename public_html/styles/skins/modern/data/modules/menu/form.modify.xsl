<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="field[@type = 'text' and @name='menuhierarchy']" mode="form-modify">

		<div class="col-md-12" style="margin-top:30px;">
			<textarea style="display:none;" name="{@input_name}" id="{@name}">
				<xsl:apply-templates select="." mode="required_attr">
					<xsl:with-param name="old_class" select="@type" />
				</xsl:apply-templates>
				<xsl:value-of select="." />
			</textarea>

			<div class="col-md-6 choose-pages">
				<!-- block for pages tree output-->
				<div class="contentPages">
					<div class="contentPagesWrap">
						<h2>&label-menu-add-page-from-structure;</h2>

						<div style="width:100%;">
							&label-menu-current-domain;&#160;&#160;<select style="width:200px;" id="domainSelect" onchange="javascript:changeDomain();">
						</select>
						</div>

						<div class="tree-wrapper">
							<ul id="tree_container1" class="tree-container"></ul>
						</div>
					</div>
				</div>
				<!--/block for pages tree output -->
				<xsl:variable name="url.suffix" select="document('udata://menu/getUrlSuffix/')/udata"/>
				<!-- block for custom pages add-->
				<div class="contentPages">
					<div class="contentPagesWrap">
						<h2>&label-menu-add-system-page;</h2>
						<ul class="menu_pages_list">
							<li class="ti">
								<div class="ti">
									<img style="border: 0px none;" alt="&label-menu-page-content;" title="&label-menu-page-content;" src="/images/cms/admin/mac/tree/ico_content_.png" class="ti-icon" />
									<a class="systemLink" href="{$lang-prefix}/emarket/cart{$url.suffix}">
										<xsl:apply-templates select="/result" mode="menu_cart" />
									</a>
								</div>
							</li>
							<li class="ti">
								<div class="ti">
									<img style="border: 0px none;" alt="&label-menu-page-content;" title="&label-menu-page-content;" src="/images/cms/admin/mac/tree/ico_content_.png" class="ti-icon" />
									<a class="systemLink" href="{$lang-prefix}/users/registrate{$url.suffix}">
										<xsl:apply-templates select="/result" mode="menu_registrate" />
									</a>
								</div>
							</li>
							<li class="ti">
								<div class="ti">
									<img style="border: 0px none;" alt="&label-menu-page-auth;" title="&label-menu-page-auth;" src="/images/cms/admin/mac/tree/ico_content_.png" class="ti-icon" />
									<a class="systemLink" href="{$lang-prefix}/users/login{$url.suffix}">
										<xsl:apply-templates select="/result" mode="menu_login" />
									</a>
								</div>
							</li>
							<li class="ti">
								<div class="ti">
									<img style="border: 0px none;" alt="&label-menu-page-personal;" title="&label-menu-page-personal;" src="/images/cms/admin/mac/tree/ico_content_.png" class="ti-icon" />
									<a class="systemLink" href="{$lang-prefix}/users/settings{$url.suffix}">
										<xsl:apply-templates select="/result" mode="menu_settings" />
									</a>
								</div>
							</li>
							<li class="ti">
								<div class="ti">
									<img style="border: 0px none;" alt="&label-menu-page-restore;" title="&label-menu-page-restore;" src="/images/cms/admin/mac/tree/ico_content_.png" class="ti-icon" />
									<a class="systemLink" href="{$lang-prefix}/users/forget{$url.suffix}">
										<xsl:apply-templates select="/result" mode="menu_forget" />
									</a>
								</div>
							</li>
						</ul>
					</div>
				</div>
				<!--/block for custom pages add -->

				<!-- block for custom pages add-->
				<div class="customPages">
					<div class="customPagesWrap formClass clearfix">
						<h2>&label-menu-add-link;</h2>
						<p id="custom-name-block">
							<label for="custom-name">
								<span class='title-edit'>&label-menu-add-link-title;</span>
								<input type="text" value="&label-menu-add-link-title-value;" class="default text" name="custom-name" id="custom-name" onblur="javascript: if(this.value == '') this.value = '&label-menu-add-link-title-value;';" onfocus="javascript: if(this.value == '&label-menu-add-link-title-value;') this.value = '';"/>
							</label>
						</p>
						<p id="custom-url-block">
							<label for="custom-url">
								<span class='title-edit'>&label-menu-add-link-url;</span>
								<input type="text" value="&label-menu-add-link-url-value;" class="default text" name="custom-url" id="custom-url" onblur="javascript: if(this.value == '') this.value = '&label-menu-add-link-url-value;';" onfocus="javascript: if(this.value == '&label-menu-add-link-url-value;') this.value = '';"/>
							</label>
						</p>
						<!--<p class='add-custom-menu-block butonBlock'>
							<img style="display:none;" src="/images/cms/admin/mac/tree/loading.gif" class="waiting" />
							<input type='button' id="submit_customlink" class='button save submitEditBlock' value='&label-menu-add-at-menu;' />
						</p>-->
						<a id="submit_customlink" class="btn color-blue pull-right btn-small">&label-menu-add-at-menu;</a>
					</div>
				</div>
				<!--/block for custom pages add -->
			</div>


			<!-- block for pages tree output-->
			<!--<div class="menuPages">-->
			<div class="col-md-6 menuPages">
				<div class="menuPagesWrap">
					<h2>&label-menu-pages;</h2>

					<div class="dd menu_pages_list">
						<ol class="dd-list">

						</ol>
					</div>

				</div>
			</div>
			<!--/block for pages tree output -->

			<!--<div class="item_add_blocks">-->


			<link href="/styles/skins/modern/data/modules/menu/style.css" rel="stylesheet" type="text/css" />
			<link href="/styles/skins/modern/data/modules/menu/nestable.css" rel="stylesheet" type="text/css" />
			<script type="text/javascript" src="/styles/skins/modern/data/modules/menu/jquery.nestable.js" />
			<script type="text/javascript" src="/styles/skins/modern/data/modules/menu/menu.js" />
			<script type="text/javascript">
				var tmp_lang_id = <xsl:value-of select="$lang-id"/>;
			</script>
			<script type="text/javascript" src="/styles/skins/modern/data/modules/menu/tree.js" />
		</div>
	</xsl:template>

	<xsl:template match="/result" mode="menu_cart">Cart</xsl:template>
	<xsl:template match="/result[@lang = 'ru']" mode="menu_cart">Корзина</xsl:template>

	<xsl:template match="/result" mode="menu_registrate">Registration</xsl:template>
	<xsl:template match="/result[@lang = 'ru']" mode="menu_registrate">Регистрация</xsl:template>

	<xsl:template match="/result" mode="menu_login">Authorization</xsl:template>
	<xsl:template match="/result[@lang = 'ru']" mode="menu_login">Авторизация</xsl:template>

	<xsl:template match="/result" mode="menu_settings">Profile</xsl:template>
	<xsl:template match="/result[@lang = 'ru']" mode="menu_settings">Личный кабинет</xsl:template>

	<xsl:template match="/result" mode="menu_forget">Password recovery</xsl:template>
	<xsl:template match="/result[@lang = 'ru']" mode="menu_forget">Восстановление пароля</xsl:template>

</xsl:stylesheet>