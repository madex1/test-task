<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/autoupdate">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="group" mode="settings-view">
		<xsl:call-template name="autoupdate" />

		<div class="panel">
			<div class="header" style="cursor:default">
				<span><xsl:value-of select="@label" /></span>
				<div class="l" />
				<div class="r" />
			</div>
			<div class="content">
				<table class="tableContent">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.view" />
					</tbody>
				</table>
				<div class="buttons">
					<div>
						<xsl:choose>
							<xsl:when test="option[@name='disabled-by-host']">
								<p>
									&label-updates-disabled-by-host;
									<a href="http://{option[@name='disabled-by-host']}/admin/autoupdate/versions/">http://<xsl:value-of select="option[@name='disabled-by-host']"/></a>
								</p>
							</xsl:when>
							<xsl:otherwise>
								<input type="button" value="&label-check-updates;" />
								<span class="l" /><span class="r" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'check' and @name = 'disabled-by-host']" mode="settings.view" />

	<xsl:template match="option[@type = 'boolean' and @name = 'disabled']" mode="settings.view">
		<!-- <tr>
			<td class="eq-col">
				<label>
					<xsl:text>&label-manual-update;</xsl:text>
				</label>
			</td>
			<td>
				<div class="buttons">
					<div>
						<input type="button" value="&label-check-updates;" onclick="window.location = '/smu/index.php';" />
						<span class="l" /><span class="r" />
					</div>
				</div>
			</td>
		</tr> -->
	</xsl:template>

	<xsl:template name="autoupdate">
		<xsl:choose>
			<xsl:when test="/result/@demo">
				<script language="javascript">
					jQuery(document).ready(function() {
						jQuery('div.content div.buttons input:button').click(function() {
							jQuery.jGrowl('<p>&label-stop-in-demo;</p>', {
								'header': 'UMI.CMS',
								'life': 10000
							});
							return false;
						});
					});
				</script>
			</xsl:when>
			<xsl:otherwise>
			<xsl:text disable-output-escaping="yes">
			<![CDATA[
				<script language="javascript">
					var updateStarted = false;
					jQuery(document).ready(function() {
						jQuery.ajaxSetup({
							error: function() {
								return error();
							}
						});
						jQuery('div.content div.buttons input:button').click(function() {
							updateStarted = true;
							step = 0;
							install();
							return false;
						});
					});

					var stepHeaders = ['Проверка прав пользователя', 'Проверка обновлений', 'Загрузка пакета тестирования', 'Распаковка архива с тестами', 'Запись начальной конфигурации', 'Выполняется тестирование', 'Подготовка сохранения данных', 'Резервное копирование файлов', 'Резервное копирование базы данных', 'Скачивание компонентов', 'Распаковка компонентов', 'Проверка компонентов', 'Обновление подсистемы', 'Обновление базы данных', 'Установка компонентов', 'Обновление конфигурации', 'Очистка кеша', 'Очистка системного кеша'];
					var stepNames = ['check-user', 'check-update', 'download-service-package', 'extract-service-package', 'write-initial-configuration', 'run-tests', 'check-installed', 'backup-files', 'backup-mysql', 'download-components', 'extract-components', 'check-components', 'update-installer', 'update-database', 'install-components', 'configure', 'cleanup', 'clear-cache'];
					var step;
					var for_backup='';
					var rStep = 0;
					var rStepHeaders = ['Восстановление файлов', 'Восстановление базы данных'];
					var rStepNames = ['restore-files', 'restore-mysql'];
					var checkBackup = function( check ) {
						if(check){
							$('#continueBackup').removeAttr("disabled");
						}else{
							$('#continueBackup').attr("disabled","disabled");
						}
					}

					function error() {
						if (!updateStarted) {
							return false;
						}
						var text = "Произошла ошибка во время выполнения запроса к серверу.<br/>" +
							"<a href=\"https://errors.umi-cms.ru/15000/\" target=\"_blank\" >" +
							"Подробнее об ошибке 15000</a>";
						h='<p style="text-align:center;">' + text + '</p>';
						h+='<p style="text-align:center;">';
						h+='<button onclick="install(); return false;">Повторить попытку</button></p>';
						showMess(h);
						return false;
					}

					function changeUpdateButton(input) {
						if (input.checked) {
							jQuery("#update_button").removeAttr("disabled");
						} else {
							jQuery("#update_button").attr("disabled", "disabled");
						}
					}

					function callBack(r) {
						if (!r) {
							return error();
						}

						if (jQuery('html', r).length > 0 || jQuery('result', r).length == 0) {
							return error();
						}

						state = jQuery('install',r).attr('state');
						if (state=='inprogress') {
							install();
							return false;
						}
				
						errors = jQuery('error',r);

						// Ошибки на шаге 0, 1 обрабатываются в свитче, для остальных - обработка здесь.
						if (step>1) {
							if (errors.length>0) {
								h='<p style="text-align:center; font-weight:bold;">В процессе обновления произошла ошибка.</p>';

								var mess = errors.attr('message');
								if (mess.length >= 305) {
									h+='<p style="text-align:center;"><div style="height: 80px; overflow-y: scroll;">' + mess + '</div></p>';
								} else {
									h+='<p style="text-align:center;">' + mess + '</p>';
								}

								h+='<p style="text-align:center;">';

								if ((step>=12)&&(for_backup=='all')) {
									h+='<button onclick="rollback(); return false;">Восстановить</button>';
								}

								h+='<button onclick="install(); return false;">Повторить попытку</button></p>';

								showMess(h);
								return false;
							}
						}

						switch(step) {
							case 0: {
								if (errors.length>0) {
									h='<p style="text-align:center; font-weight:bold;">Ваших прав недостаточно для обновления.</p>';
									h+='<p style="text-align:center;">Для дальнейшего обновления системы, пожалуйста, выйдите из авторизованного режима и повторно зайдите как супервайзер.</p>';
									h+='<p style="text-align:center;"><button onclick="updateStarted=false; jQuery(\'div.popupClose\').click();">Закрыть</button></p>';

									showMess(h);
									return false;
								}
							}
							break;
							case 1: {
								if (errors.length>0) {
									if (errors.attr('message')=='Updates not avaiable.') {
										h='<p style="text-align:center; font-weight:bold;">Доступных обновлений нет.</p>';
										h+='<p style="text-align:center;"><button onclick="updateStarted=false; jQuery(\'div.popupClose\').click();">Закрыть</button>&nbsp;<button onclick="step++; install(); return false;">Обновить принудительно.</button></p>';
									} else if (errors.attr('message')=='Updates avaiable.') {
										h='<div style="text-align:center; font-weight:bold;">Доступны обновления.</div>';
										h+='<div style="padding-top:7px; padding-left:5px;">Посмотрите, что изменилось <a href="https://www.umi-cms.ru/product/changelog/" target="_blank">в этой версии</a>&nbsp;<span style="font-size:1.25em">→</span></div>';
										h+='<div style="padding-top:5px;"><label><input type="checkbox" onchange="changeUpdateButton(this);"> Да, я хочу выполнить обновление.</label></div>';
										h+='<div style="text-align:center;padding-top:10px; padding-bottom:15px;"><button onclick="updateStarted=false; jQuery(\'div.popupClose\').click();">Не обновлять.</button>&nbsp;<button onclick="step++; install(); return false;" id="update_button" disabled="disabled">Обновить систему.</button></div>';
									} else { // Ожидаемое сообщение - сервер отклонил запрос.
										h='<p style="text-align:left;">' + errors.attr('message') + '</p>';
										h+='<p style="text-align:center; font-weight:bold;">Продолжение обновления невозможно.</p>';
										h+='<p style="text-align:center;"><button onclick="updateStarted=false; jQuery(\'div.popupClose\').click();">Закрыть</button></p>';
									}

									showMess(h);
									return false;
								}
							}
							break;
							case 5: {
								
								h='<p style="text-align: center; font-weight:bold;">Сохранение перед установкой:</p>';
								h+='<p style="text-align: left; font-weight:normal;">';
								h+='<label><input type="checkbox" name="for_backup" value="none" onchange="checkBackup(this.checked)"/>Подтверждаю, что сделал бэкап всех файлов, а также дамп базы данных средствами хостинг-провайдера.</label><br/>';
								h+='</p>';
								h+='<p style="text-align: center;"><button id="continueBackup" onclick="prepareBackup(); return false;" disabled="disabled">Продолжить</button></p>';

								showMess(h);
								return false;
							}
							break;
							case 6: { // Бекапирование подготовлено
								if (for_backup=='base') { // Пропускаем бекапирование файлов
									step = 7;
								}
							}
							break;
							case 7: { // Файлы забекапированы
								if (for_backup!='all') { // Пропускаем бекапирование базы
									step = 8;
								}
							}
							break;
							case 17: {
								jQuery(window).unbind('beforeunload');
								jQuery(window).bind('beforeunload', function() { return null; } );
								h='<p style="text-align:center; font-weight:bold;">Обновление завершено.</p>';
								h+='<p style="text-align:center;">Узнайте, что нового <a href="https://www.umi-cms.ru/product/changelog/" target="_blank">в этой версии</a>.</p>';
								h+='<p style="text-align:center;"><button onclick="window.location.href=\'/\'; return false;">Перейти на сайт</button></p>';

								showMess(h);
								return false;
							}
						}

						step++;
						install();
						return false;
					}

					function startPing() {
						jQuery.post('/smu/installer.php', {step:'ping', guiUpdate:'true', mode:'update'});
						setTimeout('startPing()', (3*60*1000));
					}

					function install() {
						if (step > stepNames.length-1) {
							return false;
						}

						h='<p style="text-align: center;">' + stepHeaders[step] + '. Пожалуйста, подождите.</p>';
						h+='<p style="text-align: center;"><img src="/images/cms/loading.gif" /></p>';
						showMess(h);

						jQuery.post('/smu/installer.php', {step:stepNames[step], guiUpdate:'true', mode:'update'}, function(r) { callBack(r); } );
						return false;
					}

					function showMess(h, t) {
						if (jQuery("div.eip_win").length==0) {
							openDialog({
								'title': (typeof(t)=='string')?t:'Обновление системы',
								'text': h,
								'stdButtons': false
							});
							jQuery('div.popupClose').css('display','none');
						} else {
							jQuery("div.eip_win div.eip_win_title").html((typeof(t)=='string')?t:'Обновление системы');
							jQuery("div.eip_win div.popupText").html(h);
						}
					}

					function prepareBackup() {
						for_backup = jQuery("input[name='for_backup']:checked").val();

						if (for_backup=='none') {
							step = 9;
						} else {
							step = 6;
						}
						
						if(window.session) {
							window.session.destroy();
						}

						if (uAdmin.session.pingIntervalHandler) { // отключаем стандартный пинг
							clearInterval(uAdmin.session.pingIntervalHandler);
						}
						startPing(); // Запускаем постоянное обращение к серверу во избежание потери сессии
						jQuery(window).bind('beforeunload', areYouSure); // Пытаемся предупредить закрытие окна в процессе обновления
						install();
					}

					function areYouSure() {
						return "Вы действительно хотите прервать процесс обновления? Возможны проблемы с работоспособностью сайта!";
					}

					function rollback() {
						t = 'Отмена установки';
						h = '<p style="text-align: center;">' + rStepHeaders[rStep] + '. Пожалуйста, подождите.</p>';
						h+= '<p style="text-align: center;"><img src="/images/cms/loading.gif" /></p>';
						showMess(h, t);
						jQuery.post('/smu/installer.php', {'step':rStepNames[rStep], 'guiUpdate':'true'}, rollbackBackTrace);
					}

					function rollbackBackTrace(r) {
						errors = jQuery('error', r);
						if (errors.length>0) {
							alert('Ошибка');
						}

						state = jQuery('install', r).attr('state');
						if (state=='done') {
							rStep++;
						}

						if (rStep>rStepHeaders.length-1) {
							t = 'Отмена установки';
							h = '<p style="text-align: center;">Система была восстановлена на сохраненное состояние.</p>';
							h+= '<p style="text-align: center;"><input type="button" onclick="window.location.href=\'/admin/autoupdate/\'; return false;" value="Закрыть" /></p>';
							showMess(h, t);
							return false;
						}

						rollback();
					}

				</script>
			]]>
			</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
