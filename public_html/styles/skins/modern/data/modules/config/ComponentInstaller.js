"use strict";
/**
 * Функционал отдельной установки компонентов
 * @constructor
 */
function ComponentInstaller() {
	/** @type {String} путь установщика */
	this.installerUri = '/smu/installer.php';
	/** @type {String} идентификатор диалогового окна */
	this.dialogName = 'install-component';
	/** @type {String} селектор контейнера текста диалогового окна */
	this.dialogTextSelector = '.popupText';
	/** @type {String} селектор контейнера флага "используется последняя версия системы" */
	this.isLastVersionSelector = 'div[data-is-last-version]';
	/** @type {String} селектор кнопок, которые запускают установку компонентов */
	this.installButtonsSelector = 'a[data-component]';
	/** @type {String} селектор кнопок, которые запускают удаление компонентов */
	this.deteleButtonsSelector = 'a.delete';
	/** @type {String} селектор кнопок, которые информируют о необходимости ручного удаления пользовательского сайта */
	this.customSolutiondeteleButtonsSelector = 'a.custom_solution_delete';
	/** @type {String} селектор кнопок, которые отображают краткую информацию об установленном решении */
	this.infoButtonsSelector = 'i.i-zoom';
	/** @type {String} название заключительного шага из списка шагов установщика */
	this.finalStep = 'cleanup';
	/** @type {Number} DIALOG_WINDOW_WIDTH ширина диалоговых окон */
	this.DIALOG_WINDOW_WIDTH = 650;
	/** @type {Number} MILLISECONDS_TO_WAIT_REQUEST время ожидания завершения предыдущего шага установки */
	this.MILLISECONDS_TO_WAIT_REQUEST = 100;
	/** @type {Number} MILLISECONDS_TO_CLOSE_DIALOG время ожидания перед закрытием окна с прогрессом установки */
	this.MILLISECONDS_TO_CLOSE_DIALOG = 2500;

	/** @type {Array} список шагов установщика для установки компонента */
	this.componentInstallSteps = [
		'check-user',
		'download-service-package',
		'extract-service-package',
		'get-update-instructions',
		'download-component',
		'extract-component',
		'install-component',
		'execute-component-manifest',
		this.finalStep
	];
	/** @type {Array} список шагов установщика для установки решения */
	this.solutionInstallSteps = [
		'check-user',
		'download-service-package',
		'extract-service-package',
		'get-update-instructions',
		'download-demosite',
		'extract-demosite',
		'save-overwritable-state',
		'install-demosite',
		'restore-supervisor',
		this.finalStep
	];
	/** @type {Array} список идентификаторов отложенных запусков выполнения шагов установщика */
	this.stepTimeoutIdList = [];
	/** @type {Boolean} флаг блокировки выполнения шага установщика */
	this.lock = false;

	/** Конструктор */
	this.construct = function() {
		this.bindInstallButtons();
		this.bindDeleteButtons();
		this.bindInfoButtons();
	};

	/**
	 * Определяет работает ли система в демонстрационном режиме
	 * @returns {Boolean}
	 */
	this.isDemoMode = function() {
		return uAdmin.data && !!uAdmin.data['demo'];
	};

	/** Прикрепляет обработчик нажатия к кнопкам показа краткой информации о решениях */
	this.bindInfoButtons = function() {
		var that = this;

		this.getInfoButtons().on('click', function() {
			that.showSolutionInfo($(this));
		});
	};

	/**
	 * Отображает краткую информацию о решении
	 * @param {jQuery|HTMLElement} $button кнопка показа краткой информации о решении
	 */
	this.showSolutionInfo = function($button) {
		var $image = $('a.solution_image', $button.parent());
		$image.fancybox();
		$image.trigger('click');
	};

	/**
	 * Возвращает кнопки показа краткой информации о решениях
	 * @returns {jQuery|HTMLElement}
	 */
	this.getInfoButtons = function() {
		return $(this.infoButtonsSelector);
	};

	/** Прикрепляет обработчик нажатия к кнопкам запуска установки компонентов */
	this.bindInstallButtons = function() {
		var that = this;

		this.getInstallButtons().on('click', function() {
			if (that.isDemoMode()) {
				return that.showDemoModeNotify();
			}

			var params = {
				'component' : $(this).data('component'),
				'is_extension' : Number($(this).data('type') === 'extension'),
				'type' : ($(this).data('type') === 'solution') ? 'demosite' : 'system',
				'domain_id' : Number($(this).data('domain-id'))
			};

			that.handleInstallButton(params);
		});
	};

	/** Прикрепляет обработчик нажатия к кнопкам запуска удаления компонентов */
	this.bindDeleteButtons = function() {
		var that = this;

		this.getDeleteButtons().on('click', function(event) {
			if (that.isDemoMode()) {
				return that.showDemoModeNotify();
			}

			var $target = $(event.currentTarget);

			if ($target.data('isConfirmed')) {
				return true;
			}

			event.preventDefault();
			that.handleDeleteButton(event);
		});

		this.getCustomSolutionDeleteButtons().on('click', function(event) {
			if (that.isDemoMode()) {
				return that.showDemoModeNotify();
			}

			event.preventDefault();
			that.showInfo(getLabel('js-label-delete-solution-manually'));
		});
	};

	/** Показывает уведомление, что действие запрещено в демонстрационном режиме */
	this.showDemoModeNotify = function() {
		$.jGrowl(getLabel('js-label-stop-in-demo'), {
			'header': 'UMI.CMS',
			'life': 10000
		});
	};

	/**
	 * Возвращает кнопки запуска установки компонентов
	 * @returns {jQuery|HTMLElement}
	 */
	this.getInstallButtons = function() {
		return $(this.installButtonsSelector);
	};

	/**
	 * Возвращает кнопки запуска удаления компонентов
	 * @returns {jQuery|HTMLElement}
	 */
	this.getDeleteButtons = function() {
		return $(this.deteleButtonsSelector);
	};

	/**
	 * Возвращает кнопки информирования о необходимости удалять пользовательское решения вручную
	 * @returns {jQuery|HTMLElement}
	 */
	this.getCustomSolutionDeleteButtons = function() {
		return $(this.customSolutiondeteleButtonsSelector);
	};

	/**
	 * Обработчик нажатия на кнопку установки компонента
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.handleInstallButton = function(params) {
		this.unbindInstallButtons();

		if (params.type === 'demosite') {
			this.showSolutionList(params);
		} else {
			this.startInstall(params);
		}

		this.bindInstallButtons();
	};

	/**
	 * Обработчик нажатия на кнопку удаления компонента
	 * @param {Object} event событие клика на кнопку удаления
	 */
	this.handleDeleteButton = function(event) {
		var $target = $(event.currentTarget);
		var messageKey = 'js-label-delete-' + $target.data('type') + '-warning-text';
		var message = getLabel(messageKey);

		this.showDisclaimer(function() {
			$target.data('isConfirmed', true);
			event.currentTarget.click();
		}, message);
	};

	/**
	 * Показывает виджет со списком доступных решений
	 * @param {Object} params параметры устанавливаемого решения
	 */
	this.showSolutionList = function(params) {
		var content = '<iframe id="solution-list" scrolling="no" src="/admin/config/getFullSolutionList/" width="900px" height="665px"/>';
		var that = this;
		openDialog('', getLabel('js-label-choose-solution'), {
			name: 'solution-list',
			width: 900,
			html: content,
			stdButtons: true,
			closeButton: true,
			cancelButton: true,
			confirmText: getLabel('js-install'),
			cancelText: getLabel('js-close'),
			confirmCallback: function() {
				var siteName = $('#solution-list').contents().find('div.site_holder div.selected').data('name');

				if (typeof siteName !== 'string' || siteName.length === 0) {
					return alert(getLabel('js-select-solution-for-installation'));
				}

				params.component = siteName;
				closeDialog('solution-list');
				that.startInstall(params);
			}
		});
	};

	/**
	 * Запускает установку компонента
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.startInstall = function(params) {
		var that = this;
		var messageKey = that.isLastVersion() ? 'js-label-create-backup' : 'js-label-not-last-version-warning-text';
		return this.showDisclaimer(function() {
			that.install(params);
		}, getLabel(messageKey));
	};

	/**
	 * Показывает предупреждение
	 * @param {Function} callback обработчик нажатия кнопки "Продолжить"
	 * @param {String} message сообщение
	 */
	this.showDisclaimer = function(callback, message) {
		openDialog('', getLabel('js-label-warning'), {
			name: 'disclaimer',
			width: this.DIALOG_WINDOW_WIDTH,
			html: message,
			confirmText: getLabel('js-label-continue'),
			cancelButton: true,
			confirmCallback: function() {
				closeDialog('disclaimer');
				callback();
			}
		});
	};

	/**
	 * Показывает информацию
	 * @param {String} message сообщение
	 */
	this.showInfo = function(message) {
		openDialog('', getLabel('js-label-info'), {
			name: 'information',
			width: this.DIALOG_WINDOW_WIDTH,
			html: message,
			confirmText: getLabel('js-label-ok'),
			cancelButton: false,
			confirmCallback: function() {
				closeDialog('information');
			}
		});
	};

	/**
	 * Устанавливает компонент
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.install = function(params) {
		this.openDialog(getLabel('js-label-component-install') + params['component']);
		var installSteps = (params.type === 'demosite') ? this.solutionInstallSteps : this.componentInstallSteps;
		var that = this;
		$.each(installSteps, function(index) {
			that.waitRequest(installSteps[index], params);
		});
	};

	/**
	 * Ожидает завершения предыдущего шага установки и запускает заданный шаг
	 * @param {String} step название шага, который требуется запустить
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.waitRequest = function(step, params) {
		var that = this;

		var timeoutId = setTimeout(function() {
			if (that.lock === true) {
				return that.waitRequest(step, params);
			}

			that.lock = true;
			that.sendRequest(step, params);
		}, this.MILLISECONDS_TO_WAIT_REQUEST);

		this.stepTimeoutIdList.push(timeoutId);
	};

	/**
	 * Отправляет запрос к установщику
	 * @param {String} step название шага, который требуется запустить
	 * @param {Object} params параметры устанавливаемого компонента
	 * @param {Function} callback обработчик ответа запроса
	 */
	this.sendRequest = function(step, params, callback) {
		var that = this;
		var request = {
			step: step,
			guiUpdate: 'true',
			manifest_config_name: 'install',
			mode: 'update'
		};

		request = $.extend(request, params);
		callback = callback || function(response) {
			that.handleResponse(step, params, response);
		};

		$.post(this.installerUri, request, function(response) {
			callback(response);
		}).fail(function() {
			that.endInstallWithError(getLabel('js-label-installation-unavailable'));
		});
	};

	/**
	 * Обрабатывает ответ установщика
	 * @param {String} step название шага, который был выполнен
	 * @param {Object} params параметры устанавливаемого компонента
	 * @param {Object} response ответ установщика
	 */
	this.handleResponse = function(step, params, response) {
		var state = $('install', response).attr('state');
		var isError = typeof state === 'undefined';

		if (isError) {
			return this.endInstallWithError($('error', response).attr('message'));
		}

		var $messages = $('message', response);
		var that = this;

		$.each($messages, function(index) {
			that.pushToDialog($($messages[index]).text());
		});

		if (state !== 'done') {
			return this.sendRequest(step, params);
		}

		this.lock = false;

		if (this.isFinalStep(step)) {
			this.endInstall(getLabel('js-label-component-installed'));
		}
	};

	/**
	 * Завершает установку
	 * @param {String} message сообщение
	 */
	this.endInstall = function(message) {
		this.clearTimeout();
		this.lock = false;
		this.pushToDialog(message);
		this.closeDialogAndReload();
	};

	/**
	 * Завершает установку с ошибкой
	 * @param {String} error сообщение об ошибке
	 */
	this.endInstallWithError = function(error) {
		this.clearTimeout();
		this.lock = false;
		this.pushToDialog(error);
		this.sendRequest('restore-supervisor', {}, function() {});
	};

	/** Удаляет все отложенные выполнения шагов установщика */
	this.clearTimeout = function() {
		var that = this;

		$.each(this.stepTimeoutIdList, function(index) {
			clearTimeout(that.stepTimeoutIdList[index]);
		});
	};

	/**
	 * Определяет является ли заданный шаг финальным
	 * @param {String} step название проверяемого шага
	 * @returns {Boolean}
	 */
	this.isFinalStep = function(step) {
		return step === this.finalStep;
	};

	/**
	 * Определяет используется ли последняя версия системы
	 * @returns {Boolean}
	 */
	this.isLastVersion = function() {
		return $(this.isLastVersionSelector).data('is-last-version') === 1;
	};

	/**
	 * Помещает сообщение в диалоговое окно
	 * @param {String} message сообщение
	 */
	this.pushToDialog = function(message) {
		var dialog = this.getDialog();

		if (!dialog) {
			return;
		}

		$(this.dialogTextSelector, dialog.id).html(message);
	};

	/**
	 * Открывает диалоговое окно
	 * @param {String} title заголовок и первоначальный контент
	 */
	this.openDialog = function(title){
		openDialog('', title, {
			name: this.dialogName,
			width: this.DIALOG_WINDOW_WIDTH,
			html: title,
			stdButtons: false,
			closeButton: false
		});
	};

	/** Закрывает диалоговое окно и перезагружает окно */
	this.closeDialogAndReload = function() {
		var that = this;

		if (this.getDialog()) {
			setTimeout(function() {
				closeDialog(that.dialogName);
				window.location.reload();
			}, this.MILLISECONDS_TO_CLOSE_DIALOG);
		}
	};

	/**
	 * Возвращает диалоговое окно
	 * @returns {*}
	 */
	this.getDialog = function() {
		return getPopupByName(this.dialogName);
	};

	/** Открепляет обработчик нажатия от кнопок запуска установки компонентов */
	this.unbindInstallButtons = function() {
		this.getInstallButtons().off('click');
	};
}

$(function() {
	var installer = new ComponentInstaller();
	installer.construct();
});