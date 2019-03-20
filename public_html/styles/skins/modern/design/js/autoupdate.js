(function($, umi) {
	"use strict";

	$(function() {
		var updateFunction = (umi.data && !!umi.data['demo']) ? updateInDemo : updateSystem;
		jQuery('#update').click(updateFunction);
	});

	var updateInDemo = function(event) {
		event.preventDefault();
		$.jGrowl('<p>' + getLabel('label-stop-in-demo') + '</p>', {
			'header': 'UMI.CMS',
			'life': 10000
		});
	};

	var updateStarted = false;

	var updateSystem = function(event) {
		event.preventDefault();

		checkIntegrity(function() {
			updateStarted = true;
			step = CHECK_USER_STEP;
			install();
		});

		return false;
	};

	/**
	 * Проверяет целостность системы
	 * @param {Function} updateCallback функция для вызова обновления системы
	 */
	function checkIntegrity(updateCallback) {
		showPreloader();

		jQuery.ajax({
			url: '/admin/autoupdate/integrity/.json',
			dataType: 'json',
			success: function(response) {

				if (_.isUndefined(response.data)) {
					return updateCallback();
				}

				if (_.isUndefined(response.data.changed) || _.isUndefined(response.data.deleted)) {
					return updateCallback();
				}

				if (!_.isUndefined(response.data.changed.item) || !_.isUndefined(response.data.deleted.item)) {
					var message = _.template($('#integrity-error-message').html())({});
					showMess(message);
					bindCloseButtonHandler();
					$('.retry-button').click(updateCallback);
					return false;
				}

				return updateCallback();
			},
			error: function() {
				showMess(getLabel('js-inner-error-call-care-service'));
			}
		});
	}

	var stepHeaders = [
		'Проверка прав пользователя',
		'Проверка обновлений',
		'Загрузка пакета тестирования',
		'Распаковка архива с тестами',
		'Запись начальной конфигурации',
		'Выполняется тестирование',
		'Скачивание компонентов',
		'Распаковка компонентов',
		'Проверка компонентов',
		'Обновление подсистемы',
		'Обновление базы данных',
		'Установка компонентов',
		'Обновление конфигурации',
		'Очистка кеша',
		'Очистка системного кеша'
	];

	var stepNames = [
		'check-user',
		'check-update',
		'download-service-package',
		'extract-service-package',
		'write-initial-configuration',
		'run-tests',
		'download-components',
		'extract-components',
		'check-components',
		'update-installer',
		'update-database',
		'install-components',
		'configure',
		'cleanup',
		'clear-cache'
	];

	var CHECK_USER_STEP = 0;
	var CHECK_UPDATE_STEP = 1;
	var RUN_TESTS_STEP = 5;
	var DOWNLOAD_COMPONENTS_STEP = 6;
	var CLEAR_CACHE_STEP = 14;

	var step;
	var for_backup = '';
	var loadingSrc = '/styles/skins/modern/design/img/process.gif';
	var updateWindowName = 'update_window';

	function error() {
		if (!updateStarted) {
			return false;
		}
		var text = "Произошла ошибка во время выполнения запроса к серверу.<br/>" +
				"<a href=\"https://errors.umi-cms.ru/15000/\" target=\"_blank\" >" +
				"Подробнее об ошибке 15000</a>";
		var h = '<p style="text-align:center;">' + text + '</p>';
		h += '<p style="text-align:center;">';
		h += '<a class="btn color-blue btn-small retry-button">Повторить попытку</a></p>';
		showMess(h);

		$('.retry-button').click(function(event) {
			event.preventDefault();
			install();
			return false;
		});

		return false;
	}

	function bindCloseButtonHandler() {
		var closeButton = $('.close-dialog');

		closeButton.unbind('click');
		closeButton.click(function() {
			closeDialog(updateWindowName);
		});
	}

	function callBack(r) {
		if (!r) {
			return error();
		}

		if (jQuery('html', r).length > 0 || jQuery('result', r).length == 0) {
			return error();
		}

		var state = jQuery('install', r).attr('state');
		if (state == 'inprogress') {
			install();
			return false;
		}

		var errors = jQuery('error', r);

		// Ошибки на шаге 0, 1 обрабатываются в свиче, для остальных - обработка здесь.
		if (step > CHECK_UPDATE_STEP) {
			if (errors.length > 0) {
				var h = '<p style="text-align:center;" class="title-edit">В процессе обновления произошла ошибка.</p>';

				var mess = errors.attr('message');
				if (mess.length >= 305) {
					h += '<p style="text-align:center;"><div style="height: 80px; overflow-y: scroll;">' + mess + '</div></p>';
				} else {
					h += '<p style="text-align:center;">' + mess + '</p>';
				}

				h += '<p style="text-align:center;">';

				h += '<a class="btn color-blue btn-small close-dialog">Закрыть</a>';
				h += '<a class="btn color-blue btn-small retry-button">Повторить попытку</a></p>';

				showMess(h);
				bindCloseButtonHandler();

				$('.retry-button').click(function(event) {
					event.preventDefault();
					install();
					return false;
				});

				return false;
			}
		}

		switch (step) {
			case CHECK_USER_STEP: {
				if (errors.length > 0) {
					h = '<p style="text-align:center;" class="title-edit">Ваших прав недостаточно для обновления.</p>';
					h += '<p style="text-align:center;">Для дальнейшего обновления системы, пожалуйста, выйдите из авторизованного режима и повторно зайдите как супервайзер.</p>';
					h += '<div class="to-right"><a class="btn color-blue btn-small close-dialog">Закрыть</a></div>';

					showMess(h);
					bindCloseButtonHandler();
					return false;
				}

				break;
			}

			case CHECK_UPDATE_STEP: {
				var hasUpdates = true;

				if (errors.length > 0) {
					if (errors.attr('message') == 'Updates not avaiable.') {
						h = '<p style="text-align:center;">Доступных обновлений нет.</p>';
						h += '<div class="to-right"><a class="btn color-blue btn-small close-dialog">Закрыть</a><a class="btn color-blue btn-small force-update">Обновить принудительно</a></div>';
					} else if (errors.attr('message') == 'Updates avaiable.') {
						h = '<div style="text-align:center;" class="title-edit">Доступны обновления.</div>';
						h += '<div class="updates-available">';
						h += '<div>Посмотрите, что изменилось <a href="https://www.umi-cms.ru/product/changelog/" target="_blank">в этой версии</a>&nbsp;<span style="font-size:1.25em">→</span></div>';
						h += '<div class="confirm-update"><div class="checkbox wish-update"><input type="checkbox" class="checkbox"></div><span>Да, я хочу выполнить обновление.</span></div>';
						h += '<div class="to-right"><a class="btn color-blue btn-small close-dialog">Закрыть</a><a class="btn color-blue btn-small" id="update_button" disabled="disabled">Обновить систему</a></div>';
						h += '</div>';
					} else { // Ожидаемое сообщение - сервер отклонил запрос.
						hasUpdates = false;
						h = '<p>' + errors.attr('message') + '</p>';
						h += '<p class="title-edit">Продолжение обновления невозможно.</p>';
						h += '<div class="to-right"><a class="btn color-blue btn-small close-dialog">Закрыть</a></div>';

					}

					var options = hasUpdates ? {stdButtons: false} : {};

					showMess(h, null, options);
					bindCloseButtonHandler();

					var continueUpdating = function(event) {
						if (!$(this).attr('disabled')) {
							event.preventDefault();
							step++;
							install();
						}
					};

					$('.force-update').click(continueUpdating);

					var updateButton = $('#update_button');
					updateButton.click(continueUpdating);

					$('.wish-update').click(function() {
						if ($(this).hasClass('checked')) {
							updateButton.removeAttr('disabled');
						} else {
							updateButton.attr('disabled', 'true');
						}
					});

					return false;
				}

				break;
			}

			case RUN_TESTS_STEP: {
				h = '<p style="text-align: center; margin-bottom: 10px" class="title-edit">Сохранение перед установкой:</p>';
				h += '<p style="text-align: left; font-weight:normal;">';
				h += '<p>Перед обновлением системы необходимо сделать бекап всех файлов и дамп базы средствами хостинг-провайдера.</p>';
				h += '<div class="checkbox for-backup"><input type="checkbox" name="for_backup" value=""/></div>';
				h += '<span class="has-backup">Бекап сделан</span>';
				h += '</p>';
				h += '<div class="to-right"><a class="btn color-blue btn-small close-dialog">Закрыть</a><a class="btn color-blue btn-small" id="continueBackup" disabled="disabled">Продолжить</a></div>';

				showMess(h);
				bindCloseButtonHandler();
				for_backup = 'none';
				var continueButton = $('#continueBackup');

				continueButton.click(function(event) {
					event.preventDefault();

					if (!$(this).attr('disabled')) {
						beginRealInstallation();
					}

					return false;
				});

				$('.for-backup').click(function() {
					if ($(this).hasClass('checked')) {
						continueButton.removeAttr('disabled');
					} else {
						continueButton.attr('disabled', 'true');
					}
				});

				return false;
			}

			case CLEAR_CACHE_STEP: {
				jQuery(window).unbind('beforeunload');
				jQuery(window).bind('beforeunload', function() {
					return null;
				});

				h = '<p style="text-align:center;" class="title-edit">Обновление завершено.</p>';
				h += '<p style="text-align:center;">Узнайте, что нового <a href="https://www.umi-cms.ru/product/changelog/" target="_blank">в этой версии</a>.</p>';
				h += '<p style="text-align:center;"><a class="btn color-blue btn-small" href="/">Перейти на сайт</a></p>';

				showMess(h);
				bindCloseButtonHandler();
				return false;
			}
		}

		bindCloseButtonHandler();
		step++;
		install();
		return false;
	}

	function startPing() {
		jQuery.post('/smu/installer.php', {step: 'ping', guiUpdate: 'true'});
		setTimeout(function() {
			startPing();
		}, (3 * 60 * 1000));
	}

	function install() {
		if (step > stepNames.length - 1) {
			return false;
		}

		showPreloader(stepHeaders[step] + '.');

		jQuery.post('/smu/installer.php', {step: stepNames[step], guiUpdate: 'true'}, function(r) {
			callBack(r);
		}).fail(function() {
			error();
		});
		return false;
	}

	/**
	 * Показывает прелоудер и заданнм сообщением
	 * @param {String} message сообщение
	 */
	function showPreloader(message) {
		message = message || '';

		var h = '<p style="text-align: center;">' + message + ' Пожалуйста, подождите.</p>';
		h += '<p style="text-align: center;" class="loading-wrapper"><img src="' + loadingSrc + '" /></p>';
		showMess(h);
	}

	function showMess(message, title, options) {
		title = title || 'Обновление системы';
		var windowName = updateWindowName;
		var openedWindow = getPopupByName(windowName);

		var content = '<div class="update-info">' + message + '</div>';

		var defaultOptions = {
			name: windowName,
			width: 350,
			html: content,
			stdButtons: false,
			closeButton: false
		};

		options = $.extend(defaultOptions, options);

		if (!openedWindow) {
			openDialog('', title, options);
		} else {
			$(".eip_win_title", openedWindow.id).html(title);
			$(".popupText", openedWindow.id).html(content);
			applyOptions(options);

			$('.checkbox input:checked', openedWindow.id).parent().addClass('checked');
			$('.checkbox', openedWindow.id).click(function() {
				$(this).toggleClass('checked');
			});
		}
	}

	function applyOptions(options, popupContainer) {
		if (typeof options.stdButtons == 'boolean' && !options.stdButtons) {
			$('.eip_buttons', popupContainer).detach();
		}
	}

	function beginRealInstallation() {
		step = DOWNLOAD_COMPONENTS_STEP;

		if (window.session) {
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

}(jQuery, uAdmin));
