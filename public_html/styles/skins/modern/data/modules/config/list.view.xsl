<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон вкладки "Решения" -->
	<xsl:template match="/result[@method = 'solutions']/data">
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		<div class="tabs-content module" data-is-last-version="{@is-last-version}">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>
				<xsl:apply-templates select="document('udata://system/listErrorMessages')/udata/items" mode="config.error"/>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&label-domains-without-solutions;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-install;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:choose>
											<xsl:when test="/result/data/domain[not(solution)]">
												<xsl:apply-templates select="/result/data/domain[not(solution)]" mode="solution.list"/>
											</xsl:when>
											<xsl:otherwise>
												<tr>
													<td class="solution_table_header">&label-domains-have-solutions;</td>
													<td/>
												</tr>
											</xsl:otherwise>
										</xsl:choose>
									</tbody>
								</table>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&label-domains-with-solutions;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:choose>
											<xsl:when test="/result/data/domain[solution]">
												<xsl:apply-templates select="/result/data/domain[solution]" mode="solution.list"/>
											</xsl:when>
											<xsl:otherwise>
												<tr>
													<td class="solution_table_header">&label-domains-have-not-solutions;</td>
													<td/>
												</tr>
											</xsl:otherwise>
										</xsl:choose>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
		<link rel="stylesheet" type="text/css" href="/js/jquery/fancybox/jquery.fancybox.css?{$system-build}" />
		<script type="text/javascript" charset="utf-8" src="/js/jquery/fancybox/jquery.fancybox.pack.js?{$system-build}" />
		<script type="text/javascript" charset="utf-8"
				src="/styles/skins/modern/data/modules/config/ComponentInstaller.js?{$system-build}" />
	</xsl:template>

	<!-- Шаблон ошибки получения списка доменов с установленными решениями -->
	<xsl:template match="domain[error]" mode="solution.list">
		<tr>
			<td>
				<p>&error-label-available-module-list;</p>
				<p>
					<xsl:value-of select="error" disable-output-escaping="yes"/>
				</p>
			</td>
			<td class="center"/>
		</tr>
	</xsl:template>

	<!-- Шаблон домена с установленным решением -->
	<xsl:template match="domain[solution]" mode="solution.list">
		<tr>
			<td class="solution_table_header">
				<xsl:apply-templates select="." mode="solution.list.row"/>
			</td>
			<td class="center">
				<xsl:apply-templates select="." mode="delete.button"/>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон кнопки удаления -->
	<xsl:template match="domain[solution]" mode="delete.button">
		<a href="{$lang-prefix}/admin/config/deleteSolution/{solution/@name}/{@id}" title="&label-delete;" class="delete" data-type="solution">
			<i class="small-ico i-remove"/>
		</a>
	</xsl:template>

	<!-- Шаблон кнопки удаления для пользовательского шаблона -->
	<xsl:template match="domain[solution[@isCustom = '1']]" mode="delete.button">
		<a class="custom_solution_delete">
			<i class="small-ico i-alert" title="&label-custom-site-alert;"/>
		</a>
	</xsl:template>

	<!-- Шаблон установленного решения -->
	<xsl:template match="domain" mode="solution.list.row">
			<a>
				<xsl:value-of select="@host" />
			</a>
			&nbsp;
			<span class="solution_title">
				<xsl:value-of select="solution/@title"/>
			</span>
			&nbsp;
			<xsl:apply-templates select="solution" mode="solution.image" />
	</xsl:template>

	<!-- Шаблон кнопки предпросмотра шаблона -->
	<xs:template match="solution[@isCustom = '0']" mode="solution.image">
		<i class="solution_info small-ico i-zoom" title="&label-more-info;" />
		<a class="solution_image" href="{./@image}" title="{./@title}">
			<img src="{./@thumb}" alt="{./@title}" />
		</a>
	</xs:template>

	<!-- Шаблон неустановленного решения -->
	<xsl:template match="domain" mode="solution.list">
		<tr>
			<td class="solution_table_header">
				<a>
					<xsl:value-of select="@host" />
				</a>
			</td>
			<td class="center">
				<a data-component="all" data-type="solution" data-domain-id="{@id}" title="&label-install;">
					<i class="small-ico i-upload"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон вкладки "Модули" -->
	<xsl:template match="/result[@method = 'modules']/data">

		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />

		<div class="tabs-content module" data-is-last-version="{@is-last-version}">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>
				<xsl:apply-templates select="document('udata://system/listErrorMessages')/udata/items" mode="config.error"/>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&module-list-available-for-installing;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-install;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:choose>
											<xsl:when test="available-module">
												<xsl:apply-templates select="available-module" mode="list-view"/>
											</xsl:when>
											<xsl:otherwise>
												<tr>
													<td>&all-available-modules-installed;</td>
													<td/>
												</tr>
											</xsl:otherwise>
										</xsl:choose>
									</tbody>
								</table>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&label-modules-list;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:apply-templates select="module" mode="list-view"/>
									</tbody>
								</table>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<form action="{$lang-prefix}/admin/config/add_module_do/" enctype="multipart/form-data" method="post">
									<div class="field modules">
										<div>
											<div class="title-edit">
												<xsl:text>&label-install-path;</xsl:text>
											</div>
											<input value="classes/components/" class="default module-path" name="module_path" />
											<input type="submit" class="btn color-blue install-btn" value="&label-install;" />
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
		<script src="/styles/skins/modern/data/modules/config/ComponentInstaller.js?{$system-build}" />
	</xsl:template>

	<!-- Шаблон вкладки "Расширения" -->
	<xsl:template match="/result[@method = 'extensions']/data">

		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />

		<div class="tabs-content module" data-is-last-version="{@is-last-version}">
			<div class="section selected">
				<div class="location">
					<xsl:call-template name="entities.help.button" />
				</div>
				<xsl:apply-templates select="document('udata://system/listErrorMessages')/udata/items" mode="config.error"/>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&extension-list-available-for-installing;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-install;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:choose>
											<xsl:when test="available-extension">
												<xsl:apply-templates select="available-extension" mode="list-view"/>
											</xsl:when>
											<xsl:otherwise>
												<tr>
													<td>&all-available-extensions-installed;</td>
													<td/>
												</tr>
											</xsl:otherwise>
										</xsl:choose>
									</tbody>
								</table>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&label-extensions-list;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:apply-templates select="installed-extension" mode="list-view"/>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
		<script src="/styles/skins/modern/data/modules/config/ComponentInstaller.js?{$system-build}" />
	</xsl:template>

	<!-- Шаблон для отображение списка ошибок -->
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages']/items" mode="config.error">
		<div class="column">
			<div id="errorList">
				<p class="error"><strong>&js-label-errors-found;</strong></p>
				<ol class="error">
					<xsl:apply-templates select="item" mode="config.error"/>
				</ol>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон для отображения одной ошибки в списке -->
	<xsl:template match="items/item" mode="config.error">
		<li>
			<xsl:value-of select="." disable-output-escaping="yes"/>
		</li>
	</xsl:template>

	<!-- Шаблон строки в списке модулей, доступных для установки -->
	<xsl:template match="available-module" mode="list-view">
		<tr>
			<td>
				<a>
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a data-component="{.}" title="&label-install;">
					<i class="small-ico i-upload"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон строки в списке расширений, доступных для установки -->
	<xsl:template match="available-extension" mode="list-view">
		<tr>
			<td>
				<a>
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a data-component="{.}" data-type="extension" title="&label-install;">
					<i class="small-ico i-upload"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон вывода ошибки формирования списка доступных модулей или расширений -->
	<xsl:template match="available-module[@error]|available-extension[@error]" mode="list-view">
		<tr>
			<td>
				<p>&error-label-available-module-list;</p>
				<p>
					<xsl:value-of select="@error" disable-output-escaping="yes"/>
				</p>
			</td>
			<td class="center"/>
		</tr>
	</xsl:template>

	<!-- Шаблон строки в списке установленных модулей -->
	<xsl:template match="module" mode="list-view">
		<tr>
			<td>
				<a href="{$lang-prefix}/admin/{.}/">
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a href="{$lang-prefix}/admin/config/del_module/{.}/" title="&label-delete;" class="delete" data-type="module">
					<i class="small-ico i-remove"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон строки в списке установленных расширений -->
	<xsl:template match="installed-extension" mode="list-view">
		<tr>
			<td>
				<a>
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a href="{$lang-prefix}/admin/config/deleteExtension/{.}/" title="&label-delete;" class="delete" data-type="extension">
					<i class="small-ico i-remove"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон вкладки "phpInfo" -->
	<xsl:template match="/result[@method = 'phpInfo']/data">
		<xsl:apply-templates select="data/alert" />
		<div class="phpinfo-container">
			<xsl:value-of select="data/info" disable-output-escaping="yes"/>
		</div>
	</xsl:template>

	<!-- Шаблон предупреждения -->
	<xsl:template match="data/alert">
		<div class="column">
			<div id="errorList">
				<p class="error"><strong>&label-alert-header;</strong></p>
				<ol class="error">
					<xsl:value-of select="." disable-output-escaping="yes"/>
				</ol>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
