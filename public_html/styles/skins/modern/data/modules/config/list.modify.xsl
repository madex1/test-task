<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				xmlns:umi="http://www.w3.org/1999/xhtml">
	<xsl:variable name="lang-items" select="document('udata://system/getLangsList/')/udata/items/item" />

	<xsl:template match="/result[@method = 'langs']/data[@type = 'list' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form id="{../@module}_{../@method}_form" action="do/" method="post">
							<table class="btable btable-striped bold-head middle-align">
								<thead>
									<tr>
										<th>
											<xsl:text>&label-langs-list;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-lang-prefix;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</tr>
								</thead>
								<tbody>
									<xsl:apply-templates mode="list-modify"/>
									<tr>
										<td>
											<input type="text" class="default" name="data[new][title]" />
										</td>
										<td>
											<input type="text" class="default" name="data[new][prefix]" />
										</td>
										<td />
									</tr>
								</tbody>
							</table>
							<div class="row">
								<xsl:call-template name="std-form-buttons-settings" />
							</div>
						</form>

					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>

		<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="lang" mode="list-modify">
		<tr>
			<td>
				<input type="text" class="default" name="data[{@id}][title]" value="{@title}"/>
			</td>

			<td>
				<input type="text" class="default" name="data[{@id}][prefix]" value="{@prefix}"/>
			</td>

			<td class="center">
				<a href="{$lang-prefix}/admin/config/lang_del/{@id}/" class="delete unrestorable {/result/@module}_{/result/@method}_btn">
					<i class="small-ico i-remove"></i>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон вкладки "Домены" -->
	<xsl:template match="/result[@method = 'domains']/data">
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('.<xsl:value-of select="../@module" />_<xsl:value-of select="../@method" />_btn.refresh').click(function(){
					var id = this.rel;
					updateSitemap(id);
					return false;
				});


			});


			
			<![CDATA[
			jQuery(document).ready(function() {
			    $('form').submit(function() {
                    var inputs = $('input[name$="][host]"]'),
                        new_val = $('input[name="data[new][host]"]').val();
                    if (inputs.length > 0){
                        for (var i=0, cnt=inputs.length-1; i < cnt; i++){
                            if ($(inputs[i]).val() == new_val){
                                alert(getLabel('js-error-domain-already-exists'));
                                return false;
                            }
                        }
                    }
                    return true;
                });
			});

			var updateSitemap = function(id) {

				openDialog(getLabel('js-update-sitemap-submit'), getLabel('js-update-sitemap'), {
					width      : 390,
					cancelButton: true,
					confirmText: getLabel('js-label-yes'),
        			cancelText : getLabel('js-label-no'),
					confirmCallback : function (popupName) {
						var h  = '<div class="exchange_container">';
						h += '<div id="process-header">' + getLabel('js-updating-sitemap') + '</div>';
						h += '<div class="progress">'
						h += '<div class="progress-bar progress-bar-striped active"'
						h += 'role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">'
						h += '</div></div></div>';
						h += '<div id="export_log"></div>';
						h += '<div class="eip_buttons custom">';
						h += '<input id="stop_btn" type="button" value="' + getLabel('js-label-stop-and-close') + '" class="stop" />';
						h += '<div style="clear: both;"/>';
						h += '</div>';

						openDialog('', getLabel('js-update-sitemap'), {
							stdButtons : false,
							html       : h,
							width      : 390,
							confirmCallback : function () {}
						});
						processUpdateSitemap(id);
						closeDialog(popupName);
					}

				});

				var reportError = function(msg) {
					$('#export_log').append(msg + "<br />");
					$('.progress', '.exchange_container').detach();
					$('#process-header').detach();
					$('#exchange-container').detach();
					$('.eip_buttons').html('<input id="ok_btn" type="button" value="' + getLabel('js-sitemap-ok') + '" class="ok" style="margin:0;" /><div style="clear: both;"/>')
					$('#ok_btn').one("click", function() { closeDialog(); });
					if(window.session) {
						window.session.stopAutoActions();
					}

				}

				var completeUpdating = function() {
					$container = $('.exchange_container');
					$('#process-header', $container).text(getLabel('js-sitemap-updating-complete'));
					$('.eip_buttons #stop_btn').val(getLabel('js-sitemap-ok'));
					var progressBar = $('.progress-bar', $container);
					progressBar.attr('aria-valuenow', 0);
					progressBar.css('width', 0);
				}

				var updatingIsStopped = false;

				var processUpdateSitemap = function (id) {
					$('#stop_btn').one("click", function() {
						updatingIsStopped = true;
						closeDialog();
						return false;
					});

					if(window.session) {
						window.session.startAutoActions();
					}

					$.ajax({
						type: "GET",
						url: "/admin/config/update_sitemap/"+ id +".xml"+"?r=" + Math.random(),
						dataType: "xml",

						success: function(doc){
							var data_nl = doc.getElementsByTagName('data');
							if (!data_nl.length) {
								reportError(getLabel('js-sitemap-ajax-error'));
								return false;
							}
							var data = data_nl[0];
							var complete = data.getAttribute('complete') || false;

							if (complete === false) {
								var errors = data.getElementsByTagName('error');
								var error = errors[0] || false;

								var errorMessage = '';
								if(error !== false) {
									errorMessage = error.textContent;
								} else {
									errorMessage = getLabel('js-sitemap-ajax-error');
								}

								reportError(errorMessage);
								return false;
							}

							if (complete == 1) {
								if(window.session) {
									window.session.stopAutoActions();
								}
								completeUpdating();
							} else if (!updatingIsStopped) {
								processUpdateSitemap(id);
							}

						},

						error: function(event, XMLHttpRequest, ajaxOptions, thrownError) {
							if(window.session) {
								window.session.stopAutoActions();
							}
							reportError(getLabel('js-sitemap-ajax-error'));
						}

					});
				};
			};

			/**
			 * Обработчик выбора фавикона
			 * @param {String} filePath путь до выбранного файла
			 * @param {String} filePathContainerId идентификатор контейнера пути выбранного файла
		 	 */
			var onChooseFavicon = function(filePath, filePathContainerId){
				var $filePathContainer = $('#' + filePathContainerId);
				var $imageContainer = $filePathContainer.parent().parent().parent().siblings('img');
				var faviconExtList = ['ico', 'png', 'svg', 'jpeg', 'gif', 'jpg'];

				if (typeof filePath === 'string' && faviconExtList.indexOf(filePath.toLowerCase().split('.').pop()) >= 0) {
					$imageContainer.removeClass('hidden');
					$imageContainer.attr('src', filePath);
					return;
				}

				$imageContainer.addClass('hidden');
				$filePathContainer.val('');
				$filePathContainer.trigger('change');

				$.jGrowl(getLabel('js-error-incorrect-favicon'), {
					'header': 'UMI.CMS',
					'life': 10000
				});
			};
		]]></script>

		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:call-template name="entities.help.button" />
				</div>

				<div class="layout">
					<div class="column">
						<form id="{../@module}_{../@method}_form" action="do/" method="post">
							<table class="btable btable-striped bold-head middle-align">
								<thead>
									<tr>
										<th colspan="4">
											<xsl:text>&label-domain-address;</xsl:text>
										</th>
										<th colspan="3">
											<xsl:text>&label-domain-lang;</xsl:text>
										</th>
										<th colspan="1">
											<xsl:text>&label-use-ssl;</xsl:text>
										</th>
										<th colspan="3">
											<xsl:text>&label-favicon;</xsl:text>
										</th>
										<th colspan="1">
											<xsl:text>&label-mirrows;</xsl:text>
										</th>
										<th colspan="3">
											<xsl:text>&label-update-sitemap;</xsl:text>
										</th>
										<th colspan="1">
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</tr>
								</thead>
								<tbody>
									<xsl:apply-templates mode="list-modify"/>
									<tr>
										<td colspan="4">
											<input type="text" class="default" name="data[new][host]" />
										</td>
										<td class="center" colspan="3">
											<select class="default newselect" name="data[new][lang_id]">
												<xsl:apply-templates select="$lang-items" mode="std-form-item" />
											</select>
										</td>
										<td class="center" colspan="1">
											<input type="hidden" value="0" name="data[new][using-ssl]"/>
											<div class="checkbox">
												<input type="checkbox" class="check" id="data[new][using-ssl]" name="data[new][using-ssl]" value="1"/>
											</div>
										</td>
										<xsl:call-template name="favicon">
											<xsl:with-param name="domain.id">
												<xsl:value-of select="'new'"/>
											</xsl:with-param>
										</xsl:call-template>
										<td colspan="5"/>
									</tr>
								</tbody>
							</table>
							<div class="row">
								<xsl:call-template name="std-form-buttons-settings" />
							</div>
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>

		<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="domain" mode="list-modify">

		<tr>
			<td colspan="4">
				<input type="text" class="default" name="data[{@id}][host]" value="{@host}" />
			</td>

			<td class="center" colspan="3">
				<select class="default newselect" name="data[{@id}][lang_id]">
					<xsl:apply-templates select="$lang-items" mode="std-form-item">
						<xsl:with-param name="value" select="@lang-id" />
					</xsl:apply-templates>
				</select>
			</td>

			<td class="center" colspan="1">
				<input type="hidden" value="0" name="data[{@id}][using-ssl]"/>
				<div class="checkbox">
					<input type="checkbox" class="check" id="data[{@id}][using-ssl]" name="data[{@id}][using-ssl]" value="1" >
						<xsl:if test="@using-ssl = '1'">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
				</div>
			</td>

			<xsl:call-template name="favicon">
				<xsl:with-param name="domain.id">
					<xsl:value-of select="@id"/>
				</xsl:with-param>
				<xsl:with-param name="filepath">
					<xsl:value-of select="@icon-relative-path"/>
				</xsl:with-param>
				<xsl:with-param name="directory">
					<xsl:value-of select="@icon-folder"/>
				</xsl:with-param>
			</xsl:call-template>

			<td align="center" colspan="1">
				<a href="{$lang-prefix}/admin/config/domain_mirrows/{@id}/" class="subitems" >
					<i class="small-ico i-edit" title="&label-edit;" alt="&label-edit;"></i>
				</a>
			</td>

			<td align="center" colspan="3">
				<a href="#"  rel='{@id}' class="{/result/@module}_{/result/@method}_btn refresh">
					&label-update;
				</a>
			</td>

			<td class="center" colspan="1">
				<a href="{$lang-prefix}/admin/config/domain_del/{@id}/" class="delete unrestorable {/result/@module}_{/result/@method}_btn"
					onclick="javascript:deleteCookie('control-domain-id')">
					<i class="small-ico i-remove" title="&label-delete;" alt="&label-delete;"></i>
				</a>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="domain[@id = '1']" mode="list-modify">
		<tr>
			<td colspan="4">
				<input type="text" class="default" name="data[{@id}][host]" value="{@host}" disabled="disabled" />
			</td>

			<td class="center" colspan="3">
				<select class="default newselect" name="data[{@id}][lang_id]">
					<xsl:apply-templates select="$lang-items" mode="std-form-item">
						<xsl:with-param name="value" select="@lang-id" />
					</xsl:apply-templates>
				</select>
			</td>

			<td class="center" colspan="1">
				<input type="hidden" value="0" name="data[{@id}][using-ssl]"/>
				<div class="checkbox">
					<input type="checkbox" class="check" id="data[{@id}][using-ssl]" name="data[{@id}][using-ssl]" value="1" >
						<xsl:if test="@using-ssl = '1'">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
				</div>
			</td>

			<xsl:call-template name="favicon">
				<xsl:with-param name="domain.id">
					<xsl:value-of select="@id"/>
				</xsl:with-param>
				<xsl:with-param name="filepath">
					<xsl:value-of select="@icon-relative-path"/>
				</xsl:with-param>
				<xsl:with-param name="directory">
					<xsl:value-of select="@icon-folder"/>
				</xsl:with-param>
			</xsl:call-template>

			<td align="center" colspan="1">
				<a href="{$lang-prefix}/admin/config/domain_mirrows/{@id}/" class="subitems">
					<i class="small-ico i-edit" title="&label-edit;" alt="&label-edit;"></i>
				</a>
			</td>

			<td align="center" colspan="3">
				<a href="#"   rel='{@id}' class="{/result/@module}_{/result/@method}_btn refresh">
					&label-update;
				</a>
			</td>

			<td colspan="1"/>
		</tr>
	</xsl:template>

	<!-- Шаблон поля для ввода favicon -->
	<xsl:template name="favicon">
		<xsl:param name="domain.id"/>
		<xsl:param name="filepath"><xsl:text/></xsl:param>
		<xsl:param name="directory">./images/cms/data</xsl:param>
		<td class="favicon" colspan="3">
			<img src="{$filepath}">
				<xsl:attribute name="class">
					<xsl:choose>
						<xsl:when test="$filepath">
							<xsl:text>favicon</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>favicon hidden</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
			</img>
			<div class="file" id="{generate-id()}"
				 umi:input-name="data[{$domain.id}][favicon]"
				 umi:field-type="img_file"
				 umi:name="data[{$domain.id}][favicon]"
				 umi:lang="{/result/@interface-lang}"
				 umi:filemanager="elfinder"
				 umi:file="{$filepath}"
				 umi:folder="{$directory}"
				 umi:on_get_file_function="onChooseFavicon"
				 umi:folder-hash="{php:function('elfinder_get_hash', string($directory))}"
				 umi:file-hash="{php:function('elfinder_get_hash', string($filepath))}">
				<label for="fileControlContainer_{generate-id()}">
					<span class="layout-row-icon" id="fileControlContainer_{generate-id()}" />
				</label>
			</div>
		</td>
	</xsl:template>

	<xsl:template match="group" mode="settings-modify">
		<div class="panel-settings">
			<div class="title">
				<h3><xsl:value-of select="@label" /></h3>
			</div>
			<div class="content">
				<table class="btable btable-striped middle-align">
					<tbody>
						<xsl:apply-templates select="option" mode="settings-modify" />
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option" mode="settings-modify">
		<tr>
			<td width="40%">
				<div class="title-edit">
					<xsl:value-of select="@label" />
				</div>
			</td>

			<td width="60%">
				<input type="text" name="{@name}" id="{@name}" value="{.}" class="default" />
			</td>
		</tr>
	</xsl:template>
</xsl:stylesheet>
