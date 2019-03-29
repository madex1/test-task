<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet
		version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:xlink="http://www.w3.org/TR/xlink"
		xmlns:php="http://php.net/xsl"
		exclude-result-prefixes="xlink">

	<xsl:template match="/">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta http-equiv="X-UA-Compatible" content="IE=edge" />

				<title>
					<xsl:value-of select="$title" />
				</title>

				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/calendar/calendar.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/popup.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/grid.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/main.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/table.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/sweetalert2.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/selectize.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/jquery.jgrowl.css?{$system-build}" />

				<xsl:choose>
					<xsl:when test="$module='webforms'">
						<script type="text/javascript" src="/ulang/{$iface-lang}/common/content/date/data/{$module}?js;{$system-build}" charset="utf-8"></script>
					</xsl:when>
					<xsl:otherwise>
						<script type="text/javascript" src="/ulang/{$iface-lang}/common/content/date/{$module}?js;{$system-build}" charset="utf-8"></script>
					</xsl:otherwise>
				</xsl:choose>

				<xsl:if test="$module='stat'">
					<script type="text/javascript" src="https://www.google.com/jsapi"></script>
				</xsl:if>

				<script type="text/javascript" src="/js/jquery/jquery.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/jquery/jquery-migrate.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/jquery/jquery-ui.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/jquery/jquery-ui-i18n.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/jquery/jquery.umipopups.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" src="/styles/skins/modern/design/js/jquery.contextmenu.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/jquery/jquery.jgrowl_minimized.js?{$system-build}"></script>

				<script type="text/javascript" src="/styles/skins/modern/design/js/sweetalert2.min.js?{$system-build}"></script>
				<script src="/js/underscore-min.js?{$system-build}"></script>

				<script type="text/javascript" src="/styles/skins/modern/design/js/selectize.min.js?{$system-build}"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/selectize.clear_selection.js?{$system-build}"></script>

				<script type="text/javascript" src="/styles/skins/modern/design/js/control.Relation.js?{$system-build}"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/control.SetingsMenu.js?{$system-build}"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/control.ImageFile.js?{$system-build}"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/control.MultiImage.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/jquery.resize.js?{$system-build}"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/control.Combobox.js?{$system-build}"></script>

				<script src="/styles/skins/modern/design/js/common/compressed.js?{$system-build}"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/smc/compressed.js?{$system-build}"></script>

				<script type="text/javascript" src="/js/cms/protect.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/cms/admin.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/cms/session.js?{$system-build}"></script>
				<script src="/styles/skins/modern/design/js/common/jquery.tmpl.js?{$system-build}"></script>
				<script src="/styles/skins/modern/design/js/sly.min.js?{$system-build}"></script>
				<script src="/styles/skins/modern/design/js/main.js?{$system-build}"></script>
				<script src="/styles/skins/modern/design/js/mobile-detect.min.js?{$system-build}"></script>

				<script type="text/javascript">
					<![CDATA[
						if ( self.parent && !(self.parent===self) && (self.parent.frames.length!=0) )
							self.parent.location = document.location;
					]]>
				</script>

				<!--[if IE 8]>
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/main.css?{$system-build}"/>
				<![endif]-->

				<script>
					var interfaceLang = '<xsl:value-of select="$iface-lang" />';
					csrfProtection = null;
					uAdmin({
						'lifetime' : '<xsl:value-of select="/result/@session-lifetime" />',
						'access' : '<xsl:value-of select="$myPerms///module[@name='config']/@access" />'
					},
					'session');

					$(function() {
						jQuery(function() {
							csrfProtection = new CSRF('<xsl:value-of select=".//@csrf" />');
							csrfProtection.protectForms();
							csrfProtection.setAjaxSettings();
						});

						window.curent_module = '<xsl:value-of select="$module" />';
						window.pre_lang = '<xsl:value-of select="$lang-prefix" />';
						window.domain = '<xsl:value-of select="$domain" />';
						window.domain_id = '<xsl:value-of select="$domain-id" />';
						window.lang_id  = '<xsl:value-of select="$lang-id" />';
						window.is_page  = <xsl:value-of select="boolean(/result/data/page)" />;
						window.is_object  = <xsl:value-of select="boolean(/result/data/object)" />;
						window.edition = '<xsl:value-of select="/result/@edition"/>';
						window.is_new   = <xsl:value-of select="boolean(not(/result/data/*/@id))" />;
						window.page_id  = <xsl:choose><xsl:when test="/result/data/page/@id"><xsl:value-of select="/result/data/page/@id" /></xsl:when><xsl:otherwise>0</xsl:otherwise></xsl:choose>;
						window.object_id  = <xsl:choose>
							<xsl:when test="/result/data/object/@id">
								<xsl:value-of select="/result/data/object/@id" />
							</xsl:when>
							<xsl:when test="/result/data/page/@object-id">
								<xsl:value-of select="/result/data/page/@object-id" />
							</xsl:when>
							<xsl:otherwise>0</xsl:otherwise>
						</xsl:choose>;

						if ($('form.form_modify').length > 0) {
							$('form').bind('keyup',function(e) {
								if (e.keyCode == 13){
									$(e.target).submit()
								}
							});
						}
					});
				</script>

				<script type="text/javascript" src="/js/cms/panel/tickets.js?{$system-build}"></script>

				<xsl:if test="count(//field[@type = 'wysiwyg'])">
					<xsl:call-template name="wysiwyg-init" />
				</xsl:if>
			</head>

			<body>
				<div id="main" class="main">
					<div class="nav">
						<xsl:apply-templates select="$modules-menu" />

						<div class="select-modules">
							<span class="big-ico" style="background-image: url('/images/cms/admin/modern/icon/modules.png');"></span>
							<span class="title">&modules;</span>
						</div>

						<xsl:apply-templates select="$favorites" />
					</div>
					<div class="container">
						<div class="head">
							<xsl:apply-templates select="document('udata://autoupdate/getDaysLeft/')/udata/trial" mode="trial-days-left" />
							<xsl:call-template name="panel-buttons" />
							<xsl:call-template name="user-menu" />
							<xsl:apply-templates select="$site-langs" />
						</div>

						<xsl:variable name="modules_config" select="document('udata://config/menu')/udata" />

						<div class="content">
							<div class="module-description location">
								<xsl:variable name="fileName">
									<xsl:value-of select="$module" />
								</xsl:variable>	
								<span class="big-ico loc-left" style="background-image: url('/images/cms/admin/modern/icon/{$fileName}.png');"></span>
								
								<xsl:if
										test="not($method = 'config') and $modules_config//item[@name = $module]/@config = 'config' and not($method = 'backup_copies') and not($method = 'trash') and not(/result/data/@type = 'settings' and /result/data/@action = 'modify')">
									<a class="btn-action loc-right" href="{$lang-prefix}/admin/{$module}/config/">
										<i class="small-ico i-settings"></i>
										<xsl:text>&config;</xsl:text>
									</a>
								</xsl:if>

								<xsl:if test="$method = 'backup_copies'">
									<a class="btn-action loc-right" href="{$lang-prefix}/admin/{$module}/config/">
										<i class="small-ico i-settings"></i>
										<xsl:text>&config;</xsl:text>
									</a>
								</xsl:if>

								<xsl:apply-templates select="$navibar" />
							</div>
							<xsl:apply-templates select="result" mode="tabs" />
						</div>
					</div>
				</div>
				<!-- _____________________ футер __________________________________ -->
				<div class="footer">
					<div class="container location">
						<span>&copyright; &cms-name;</span>
						<span style="color: #787B81; font-size: 11px; padding-left:15px;">Установите <a href="#" style="color: #0088E8; font-size: 11px;"
																																														onclick="modalMobileInfo(); return false;">мобильное
							приложение UMI.Manager
						</a> для обработки заказов.
						</span>
						<a target="_blank" class="loc-right" href="//help.docs.umi-cms.ru" id="support">Руководство пользователя
							<span>UMI.CMS</span>
						</a>
					</div>
					<div class="panel-controls location">
						<div class="btn-select color-blue loc-right">
							<div class="selected">Сохранить</div>
							<ul class="list">
								<li>Посмотреть на сайте</li>
								<li>Выйти</li>
							</ul>
						</div>
						<a class="btn color-blue loc-right" href="#">Открыть Market</a>
					</div>
				</div>

				<script type="text/javascript">
					/*$(function() {
					$(".basic-modules, .modules").sortable({
					placeholder: "ui-state-highlight",
					connectWith: ".connect",
					helper:'clone',
					revert: false,
					update: function( event, ui ) {
					saveMenuSettings();
					}
					});
					});*/

					<![CDATA[
							function modalMobileInfo() {
								var contDiv = document.createElement("div");
								var html = getLabel('js-mobile-info-content');

								openDialog('', getLabel('js-mobile-app'), {name : 'InstallMobile',
                                    width : 420,
                                    html : html,
                                    confirmButton: false,
                                    cancelButton: true,
                                    cancelText: getLabel('js-close')
                                });
								return false;
							}
							]]>
				</script>
				<script src="/styles/skins/modern/design/js/tickets.js?{$system-build}"></script>
			</body>
		</html>
	</xsl:template>

	<xsl:template name="wysiwyg-init">
		<script type="text/javascript" src="/js/cms/wysiwyg/wysiwyg.js?{$system-build}"></script>
		<script type="text/javascript">
			uAdmin('type', '<xsl:value-of select="$wysiwygVersion" />', 'wysiwyg');
		</script>
	</xsl:template>

</xsl:stylesheet>
