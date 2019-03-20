<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM  "ulang://common/users">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<!-- Current language info -->
	<xsl:variable name="lang-prefix" select="/result/@pre-lang" />
	<xsl:variable name="errors"	select="document('udata://system/listErrorMessages')/udata"/>

	<!-- Header and title of current page -->
	<xsl:variable name="header" select="document('udata://core/header')/udata" />
	<xsl:variable name="title" select="concat('&cms-name; - ', $header)" />

	<!-- Skins and langs list from system settings -->
	<xsl:variable name="skins" select="document('udata://system/getSkinsList/')/udata" />
	<xsl:variable name="interfaceLangs" select="document('udata://system/getInterfaceLangsList/')/udata" />
	<xsl:variable name="fromPage" select="document('udata://system/getCurrentURI/1/')/udata" />

	<xsl:template match="/">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>
					<xsl:value-of select="$title" />
				</title>
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/grid.css" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/main.css" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/selectize.css" />
				<link type="text/css" rel="stylesheet" href="/styles/skins/modern/design/css/mainLogin.css" />
			</head>

			<body>
				<div class="bubbles"></div>
				<div class="bubbles-front"></div>
				<div id="auth">
					<img src="/styles/skins/modern/design/img/auth-logo.png" />
					<div class="cont">
						<xsl:call-template name="commonError">
							<xsl:with-param name="message" select="$errors" />
						</xsl:call-template>

						<xsl:apply-templates select="result/data/error" />

						<xsl:call-template name="mainLoginForm" />
						<xsl:call-template name="mainRestorePasswordForm" />
						<div style="clear:both" />
					</div>
					<div class="foot" />
				</div>

				<script type="text/javascript" src="/js/jquery/jquery.js"></script>
				<script type="text/javascript" src="/js/jquery/jquery-migrate.js"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/selectize.min.js"></script>
				<script type="text/javascript" src="/styles/skins/modern/design/js/mainLogin.js"></script>
			</body>
		</html>
	</xsl:template>

	<xsl:template name="mainLoginForm">
		<form action="{$lang-prefix}/admin/users/login_do/" method="post">
			<input type="hidden" name="from_page" value="{$fromPage}" />

			<div class="row">
				<div class="col-md-12">
					<div class="title-edit">
						<xsl:text>&label-login;</xsl:text>
					</div>

					<xsl:choose>
						<xsl:when test="/result[@demo='1']">
							<input class="default" type="text" id="login_field" name="login"
										 value="demo" />
						</xsl:when>
						<xsl:otherwise>
							<input class="default" type="text" id="login_field" name="login" />
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					<div class="title-edit">
						<xsl:text>&label-password;</xsl:text>
					</div>

					<xsl:choose>
						<xsl:when test="/result[@demo='1']">
							<input class="default" type="password" id="password_field" name="password"
										 value="demo" />
						</xsl:when>
						<xsl:otherwise>
							<input class="default" type="password" id="password_field" name="password" />
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</div>

			<xsl:if test="count($skins//item) &gt; 1">
				<div class="row">
					<div class="col-md-12">
						<div class="title-edit">
							<xsl:text>&label-skin;</xsl:text>
						</div>
						<select id="skin_field" name="skin_sel">
							<xsl:apply-templates select="$skins" />
						</select>
					</div>
				</div>
			</xsl:if>

			<xsl:if test="count($interfaceLangs//item) &gt; 1">
				<div class="row">
					<div class="col-md-12">
						<div class="title-edit">
							<xsl:text>&label-interface-lang;</xsl:text>
						</div>
						<select id="ilang" name="ilang">
							<xsl:apply-templates select="$interfaceLangs" />
						</select>
					</div>
				</div>
			</xsl:if>

			<div class="row">
				<div class="col-md-6 last">
					<input type="submit" id="submit_field" value="&label-login-do;" class="btn color-blue btn-small" />
				</div>
			</div>
		</form>
	</xsl:template>
	
	<xsl:template name="mainRestorePasswordForm">
		<form id="forget" action="{$lang-prefix}/users/forget_do/" method="post">
			<div class="row">
				<div class="col-md-12">
					<div id="forgetLabel" class="title-edit">
						<xsl:text>&forget-password;</xsl:text>
					</div>
				</div>
			</div>
			<div class="container">
				<div class="row">
					<div class="col-md-12">
						<div>&enter-credentials;</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<label>
							<input type="radio" id="forget_login" class="checkbox" name="choose_forget" checked="checked" />
							<span>
								<xsl:text>&label-login;</xsl:text>
							</span>
						</label>

						<label>
							<input type="radio" id="forget_email" class="checkbox" name="choose_forget" />
							<span>
								<xsl:text>&label-email;</xsl:text>
							</span>
						</label>

						<input class="default" type="text" name="forget_login" />
					</div>
				</div>
				<div class="row">
					<div class="col-md-6 last">
						<input type="submit" id="submit_field" value="&forget-button;" class="btn color-blue btn-small" />
					</div>
				</div>

			</div>
		</form>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'getSkinsList']//item">
		<option value="{@id}">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'getSkinsList']//item[@id = ../@current]">
		<option value="{@id}" selected="selected">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'getInterfaceLangsList']//item">
		<option value="{@prefix}">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'getInterfaceLangsList']//item[@prefix = ../@current]">
		<option value="{@prefix}" selected="selected">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="error">
		<xsl:call-template name="commonError">
		      <xsl:with-param name="message" select="."/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="commonError">
		<xsl:param name="message" />

		<xsl:if test="string-length($message) > 0">
			<div class="row error-test">
				<div class="col-md-12">
					<p class="error">
						<strong>
							<xsl:text>&label-error;: </xsl:text>
						</strong>
						<xsl:value-of select="$message" />
					</p>
				</div>
			</div>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>
