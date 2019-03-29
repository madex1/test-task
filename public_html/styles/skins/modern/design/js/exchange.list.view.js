// noinspection JSUnusedGlobalSymbols
/** Обработчик нажатия на кнопку "Выполнить импорт" во вкладке "Импорт данных" */
var exchangeDoImport = function() {
	for (var id in oTable.selectedList) {
		var h = '<div class="exchange_container">';
		h += '<div id="process-header">' + getLabel('js-exchange-import-help') + '</div>';
		h += '<div><img id="process-bar" src="/images/cms/admin/mac/process.gif" class="progress" /></div>';
		h += '<div class="status">' + getLabel('js-exchange-created') + '<span id="created_counter">0</span></div>';
		h += '<div class="status">' + getLabel('js-exchange-updated') + '<span id="updated_counter">0</span></div>';
		h += '<div class="status">' + getLabel('js-exchange-deleted') + '<span id="deleted_counter">0</span></div>';
		h += '<div id="errors_message" class="status">' + getLabel('js-exchange-import-errors') + '<span id="errors_counter">0</span>' + '</div>';
		h += '<div class="toggle-log"><a href="#" onclick="$(\'#import_log\').toggle();return false;">' + getLabel('js-exchange-show-hide-log') + '</a></div>';
		h += '<div id="import_log" style="display:none;"></div>';
		h += '<div class="eip_buttons">';
		h += '<input id="ok_btn" type="button" value="' + getLabel('js-exchange-btn_ok') + '" disabled="disabled" />';
		h += '<input id="repeat_btn" type="button" value="' + getLabel('js-exchange-btn_repeat') + '" class="repeat" disabled="disabled" />';
		h += '<input id="stop_btn" type="button" value="' + getLabel('js-exchange-btn_stop') + '" class="stop" />';
		h += '<div style="clear: both;"/>';
		h += '</div></div>';

		openDialog('', getLabel('js-exchange-import'), {
			stdButtons: false,
			html: h,
			width: 390,
			confirmCallback: function() {
			}
		});

		var createdCount = 0;
		var updatedCount = 0;
		var deletedCount = 0;
		var errorCount = 0;
		var isCancelled = false;

		var reportError = function(msg) {
			$('#errors_message').css('color', 'red');
			errorCount++;
			$('#errors_counter').html(errorCount);
			$('#import_log').append(msg + '<br />');
			$('#process-header').html(msg).css('color', 'red');
			$('#process-bar').css({'visibility': 'hidden'});
			$('#repeat_btn').one('click', function() {
				isCancelled = false;
				processImport();
			}).removeAttr('disabled');
			$('#ok_btn').one('click', function() {
				closeDialog();
			}).removeAttr('disabled');
			$('#stop_btn').attr('disabled', 'disabled');

			if (window.session) {
				window.session.stopAutoActions();
			}
		};

		/**
		 * Выполняет одну итерацию импорта через ajax-запрос.
		 * Вызывается рекурсивно, пока импорт не будет выполнен.
		 */
		var processImport = function() {
			$('#process-bar').css({'visibility': 'visible'});
			$('#process-header').html(getLabel('js-exchange-import-help')).css({'color': ''});
			$('#repeat_btn').attr('disabled', 'disabled');
			$('#ok_btn').attr('disabled', 'disabled');
			$('#stop_btn').one('click', function() {
				isCancelled = true;
				$(this).attr('disabled', 'disabled');
			}).removeAttr('disabled');

			if (window.session) {
				window.session.startAutoActions();
			}

			$.ajax({
				type: 'GET',
				url: '/admin/exchange/import_do/' + id + '.xml' + '?r=' + Math.random(),
				dataType: 'xml',

				success: function(doc) {
					$('#process-bar').css({'visibility': 'hidden'});
					var errors = doc.getElementsByTagName('error');
					if (errors.length) {
						reportError(errors[0].firstChild.nodeValue);
						return;
					}

					var log = doc.getElementsByTagName('log');
					for (var i = 0; i < log.length; i++) {
						$('#import_log').append(log[i].firstChild.nodeValue + '<br />');
					}

					var data_nl = doc.getElementsByTagName('data');
					if (!data_nl.length) {
						reportError(getLabel('js-exchange-ajaxerror'));
						return false;
					}
					var data = data_nl[0];
					createdCount += (parseInt(data.getAttribute('created')) || 0);
					updatedCount += (parseInt(data.getAttribute('updated')) || 0);
					deletedCount += (parseInt(data.getAttribute('deleted')) || 0);
					errorCount += (parseInt(data.getAttribute('errors')) || 0);

					$('#created_counter').html(createdCount);
					$('#updated_counter').html(updatedCount);
					$('#deleted_counter').html(deletedCount);
					$('#errors_counter').html(errorCount);

					var complete = data.getAttribute('complete') || false;

					if (complete === false) {
						reportError(getLabel('Parse data error. Required attribute complete not found'));
						exit();
					}

					if (complete == 1) {
						$('#process-header').html(getLabel('js-exchange-import-done')).css({'color': 'green'});
						$('#stop_btn').attr('disabled', 'disabled');
						$('#ok_btn').one('click', function() {
							closeDialog();
						}).removeAttr('disabled');

						if (window.session) {
							window.session.stopAutoActions();
						}
					} else {
						if (isCancelled) {
							$('#repeat_btn').one('click', function() {
								isCancelled = false;
								processImport();
							}).removeAttr('disabled');
							$('#ok_btn').one('click', function() {
								closeDialog();
							}).removeAttr('disabled');
						} else {
							processImport();
						}
					}
				},

				error: function() {
					if (window.session) {
						window.session.stopAutoActions();
					}

					reportError(getLabel('js-exchange-ajaxerror'));
				}

			});
		};

		processImport();
		break;
	}
};

// noinspection JSUnusedGlobalSymbols
/** Обработчик нажатия на кнопку "Выполнить экспорт" во вкладке "Экспорт данных" */
var exchangeDoExport = function() {
	for (var id in oTable.selectedList) {
		var h = '<form target="_blank" action="' + lang_prefix + '/admin/exchange/get_export/' + id + '/" id="export_form" method="get">';
		h += '<div><input type="radio" name="as_file" checked="checked" id="export_link" value="0" /><label for="export_link">' + getLabel('js-exchange-export-getlink') + '</label></div>';
		h += '<div><input type="radio" name="as_file" id="export_file" value="1" /><label for="export_file">' + getLabel('js-exchange-export-getfile') + '</label></div>';
		h += '</form>';

		openDialog('', getLabel('js-exchange-export'), {
			html: h,
			confirmText: 'Выбрать',
			confirmCallback: function(popupName) {

				if ($('#export_form input[name=as_file]:checked').val() == 1) {

					var h = '<div class="exchange_container">';
					h += '<div id="process-header">' + getLabel('js-exchange-export-in-progress') + '</div>';
					h += '<div><img id="process-bar" src="/images/cms/admin/mac/process.gif" class="progress" /></div>';

					h += '<div id="export_log"></div>';

					h += '<div class="eip_buttons">';
					h += '<input id="ok_btn" type="button" value="' + getLabel('js-exchange-btn_ok') + '" class="ok" disabled="disabled" />';
					h += '<input id="repeat_btn" type="button" value="' + getLabel('js-exchange-btn_repeat') + '" class="repeat" disabled="disabled" />';
					h += '<input id="stop_btn" type="button" value="' + getLabel('js-exchange-btn_stop') + '" class="stop" />';
					h += '<div style="clear: both;"/>';
					h += '</div></div>';

					openDialog('', getLabel('js-exchange-export'), {
						stdButtons: false,
						html: h,
						width: 390,
						confirmCallback: function() {
						}
					});

				}

				processExport($('#export_form input[name=as_file]:checked').val());
				closeDialog(popupName);
			}
		});

		break;
	}

	var isCancelled = false;

	var reportError = function(msg) {
		$('#export_log').html(msg);
		$('#process-header', '.exchange_container').hide();
		$('#process-header + div', '.exchange_container').hide();
		$('#repeat_btn').one('click', function() {
			isCancelled = false;
			processExport(1);
		}).removeAttr('disabled');
		$('#ok_btn').one('click', function() {
			closeDialog();
		}).removeAttr('disabled');
		$('#stop_btn').attr('disabled', 'disabled');

		if (window.session) {
			window.session.stopAutoActions();
		}

	};

	/**
	 * Выполняет одну итерацию экспорта через ajax-запрос.
	 * Вызывается рекурсивно, пока экспорт не будет выполнен.
	 * @param {String} asFile определяет, нужно ли получить результат экспорта как файл,
	 * или вывести его в браузер.
	 */
	var processExport = function(asFile) {
		if (asFile == 0) {
			window.location.href = lang_prefix + '/admin/exchange/get_export/' + id + '/?as_file=' + asFile;
			closeDialog();
			return false;
		}

		$('.exchange_container').show();
		$('#process-bar').show();
		$('#export_log').html('');
		$('#repeat_btn').attr('disabled', 'disabled');
		$('#ok_btn').attr('disabled', 'disabled');
		$('#stop_btn').one('click', function() {
			isCancelled = true;
			$(this).attr('disabled', 'disabled');
		}).removeAttr('disabled');

		if (window.session) {
			window.session.startAutoActions();
		}

		$.ajax({
			type: 'GET',
			url: lang_prefix + '/admin/exchange/get_export/' + id + '.xml' + '?r=' + Math.random(),
			dataType: 'xml',

			success: function(doc) {

				var data_nl = doc.getElementsByTagName('data');
				if (!data_nl.length) {
					reportError(getLabel('js-exchange-ajaxerror'));
					return false;
				}
				var data = data_nl[0];
				var complete = data.getAttribute('complete') || false;

				if (complete === false) {
					var errors = data.getElementsByTagName('error');
					var error = errors[0] || false;

					var errorMessage = '';
					if (error !== false) {
						errorMessage = error.textContent;
					} else {
						errorMessage = getLabel('Parse data error. Required attribute complete not found');
					}
					reportError(errorMessage);
					return false;
				}

				if (complete == 1) {
					window.location.href = lang_prefix + '/admin/exchange/get_export/' + id + '/?as_file=' + asFile;
					closeDialog();
				} else {
					if (isCancelled) {
						$('#process-bar').hide();
						$('#repeat_btn').one('click', function() {
							isCancelled = false;
							processExport(1);
						}).removeAttr('disabled');
						$('#ok_btn').one('click', function() {
							closeDialog();
						}).removeAttr('disabled');
					} else {
						processExport(asFile);
					}
				}

			},

			error: function() {
				if (window.session) {
					window.session.stopAutoActions();
				}
				reportError(getLabel('js-exchange-ajaxerror'));
			}

		});
	};
};

// noinspection JSUnusedGlobalSymbols
/** Обработчик нажатия на кнопку "Подготовить к экспорту" во вкладке "Экспорт данных" */
var prepareExport = function() {
	for (var id in oTable.selectedList) {
		openDialog('', getLabel('js-exchange-prepare-export'), {
			html: getLabel('js-exchange-prepare-export-submit'),
			cancelButton: true,
			confirmText: getLabel('js-label-yes'),
			cancelText: getLabel('js-label-no'),
			width: 390,
			confirmCallback: function(popupName) {

				var h = '<div class="exchange_container">';
				h += '<div id="process-header">' + getLabel('js-exchange-export') + '</div>';
				h += '<div><img id="process-bar" src="/images/cms/admin/mac/process.gif" class="progress" /></div>';

				h += '<div id="prepare_log"></div>';
				h += '<div id="export_log"></div>';
				h += '<div class="eip_buttons">';
				h += '<input id="stop_btn" type="button" value="' + getLabel('js-exchange-btn_stop') + '" class="stop" />';
				h += '<div style="clear: both;"/>';
				h += '</div></div>';

				openDialog('', getLabel('js-exchange-export'), {
					stdButtons: false,
					html: h,
					width: 390,
					confirmCallback: function() {
					}

				});
				processPrepareExport(id, popupName);
				closeDialog(popupName);
			}

		});

		var reportError = function(msg) {
			$('#export_log').append(msg + '<br />');
			$('#process-bar').detach();
			$('#process-header').detach();
			$('#exchange-container').detach();
			$('.eip_buttons').html('<input id="ok_btn" type="button" value="' + getLabel('js-exchange-btn_ok') + '" class="ok" style="margin:0;" /><div style="clear: both;"/>');
			$('#ok_btn').one('click', function() {
				closeDialog();
			});

			if (window.session) {
				window.session.stopAutoActions();
			}
		};

		var processPrepareExport = function(id, popupName) {
			$('#stop_btn').one('click', function() {
				closeDialog(popupName);
				return false;
			});

			if (window.session) {
				window.session.startAutoActions();
			}

			$.ajax({
				type: 'GET',
				url: lang_prefix + '/admin/exchange/prepareElementsToExport/' + id + '.xml' + '?r=' + Math.random(),
				dataType: 'xml',

				success: function(doc) {
					var data_nl = doc.getElementsByTagName('data');
					if (!data_nl.length) {
						reportError(getLabel('js-exchange-ajaxerror'));
						return false;
					}
					var data = data_nl[0];
					var complete = data.getAttribute('complete') || false;

					var log = doc.getElementsByTagName('log');
					for (var i = 0; i < log.length; i++) {
						$('#prepare_log').append(log[i].firstChild.nodeValue + '<br />');
					}

					if (complete === false) {
						var errors = data.getElementsByTagName('error');
						var error = errors[0] || false;

						var errorMessage = '';
						if (error !== false) {
							errorMessage = error.textContent;
						} else {
							errorMessage = getLabel('Parse data error. Required attribute complete not found');
						}

						reportError(errorMessage);
						return false;
					}

					if (complete == 1) {
						$('#export_log').html(getLabel('js-exchange-prepare-done')).css({'color': 'green'});
						$('#process-bar').detach();
						$('#process-header').detach();
						$('#exchange-container').detach();
						$('.eip_buttons').html('<input id="ok_btn" type="button" value="' + getLabel('js-exchange-btn_ok') + '" class="ok" style="margin:0;" /><div style="clear: both;"/>');
						$('#ok_btn').one('click', function() {
							closeDialog();
						});

						if (window.session) {
							window.session.stopAutoActions();
						}
					} else {
						var preparation = data.getAttribute('preparation') || false;
						if (preparation == 1) {
							reportError(getLabel('js-exchange-type-error'));
						} else {
							processPrepareExport(id);
						}
					}
				},

				error: function() {
					if (window.session) {
						window.session.stopAutoActions();
					}
					reportError(getLabel('js-exchange-ajaxerror'));
				}

			});
		};
		break;
	}
};
