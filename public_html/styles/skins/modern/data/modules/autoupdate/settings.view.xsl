<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/autoupdate">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон вкладки "Состояние обновлений" -->
	<xsl:template match="result[@method = 'versions']/data">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="group" mode="settings.view"/>
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
		<div id="integrity-error-message" class="hidden">
			<p>&label-integrity-violation-found;</p>
			<div class="to-right">
				<a class="btn color-blue btn-small close-dialog">&label-integrity-close-dialog;</a>
				<a class="btn color-blue btn-small retry-button">&label-integrity-ignore-risk;</a>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group" mode="settings.view">
		<xsl:call-template name="autoupdate" />

		<div class="panel-settings">
			<div class="title" style="cursor:default">
				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<table class="tableContent">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.view" />
					</tbody>
				</table>
				<xsl:if test="option[@name='alert']">
					<div class="column">
						<div id="errorList">
							<p class="error"><strong>&label-alert-header;</strong></p>
							<ol class="error">
								<xsl:value-of select="option[@name='alert']/value" disable-output-escaping="yes"/>
							</ol>
						</div>
					</div>
				</xsl:if>
				<div class="buttons">
					<div class="pull-right">
						<xsl:choose>
							<xsl:when test="option[@name='disabled-by-host']">
								<p>
									&label-updates-disabled-by-host;
									<a href="http://{option[@name='disabled-by-host']}/admin/autoupdate/versions/">http://<xsl:value-of select="option[@name='disabled-by-host']"/></a>
								</p>
							</xsl:when>
							<xsl:otherwise>
								<input type="button" class="btn color-blue" value="&label-check-updates;" id="update" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'check' and @name = 'disabled-by-host']" mode="settings.view" />
	<xsl:template match="option[@type = 'boolean' and @name = 'disabled']" mode="settings.view" />
	<xsl:template match="option[@type = 'alert' and @name = 'alert']" mode="settings.view" />

	<xsl:template name="autoupdate">
		<script src="/styles/skins/modern/design/js/autoupdate.js?{$system-build}" />
	</xsl:template>

	<!-- Шаблон вкладки "Целостность" -->
	<xsl:template match="result[@method = 'integrity']/data[not(error)]">
		<div class="section selected">
			<div class="location">
				<xsl:call-template name="entities.help.button" />
			</div>
			<div class="layout">
				<div class="column">
					<div class="panel-settings" name="deleted">
						<div class="title">
							<h3>&label-deleted-file-list;</h3>
						</div>
						<div class="layout">
							<xsl:apply-templates select="./deleted" mode="file.list"/>
						</div>
					</div>
					<div class="panel-settings" name="changed">
						<div class="title">
							<h3>&label-changed-file-list;</h3>
						</div>
						<div class="layout">
							<xsl:apply-templates select="./changed" mode="file.list"/>
						</div>
					</div>
				</div>
				<div class="column">
					<xsl:call-template name="entities.help.content" />
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон списка файлов -->
	<xsl:template match="deleted|changed" mode="file.list">
		<xsl:choose>
			<xsl:when test="item">
				<xsl:apply-templates select="item" mode="file.list"/>
			</xsl:when>
			<xsl:otherwise>
				<div>&label-empty-file-list;</div>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Шаблон файла в списке -->
	<xsl:template match="item" mode="file.list">
		<div>
			<xsl:value-of select="./@path" />
		</div>
	</xsl:template>

	<!-- Шаблон ошибки на вкладке "Целостность" -->
	<xsl:template match="result[@method = 'integrity']/data/error">
		<div class="layout">
			<div class="column">
				<div id="errorList">
					<p class="error"><strong><xsl:text>&label-errors-found;:</xsl:text></strong></p>
					<ol class="error">
						<li>
							<xsl:value-of select="." disable-output-escaping="yes" />
						</li>
					</ol>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
