/**
 * AdminIndexing  - модуль для управления индексацией в административной части
 * Содержит следующие объекты:
 * Settings - хранит многочисленные настройки интерфейса и не только
 * Controller - в нем определены основные функции взаимодействия интерфейсов
 *
 * Глобальные объекты (функции): jQuery, openDialog, oTable, uAdmin
 */

var AdminIndexing = (function($){

    var Settings = {};
    var Controller = {};

    /** Инициализвция настроек (объекта Settings) */
    function init() {

        /**
         * Основные настройки модуля
         * {} storage настройки для localStorage
         * {} server настройки, связанные с сервером
         * {} messagesOptions настройки всплывающих сообщений
         * {} loadingOverlay настройки отображения загрузки
         */
        Settings.module = {
            dirPath:  '/styles/skins/mac/data/modules/catalog/filters/',
            storage: {
                success: 'indexingSuccessful',
                category: 'categoryId'
            },
            server: {
                path: '/admin/udata://catalog/getSettings/.json',
                onLoad: Controller.onSettingsLoad,
                settings: null
            },
            messagesOptions: {
                header: 'UMI.CMS',
                life: 5000
            },
            loadingOverlay: {
                image: '/styles/skins/mac/data/modules/catalog/filters/loadingoverlay/img/ajax_loader_gray_128.gif',
                color: 'rgba(0, 0, 0, 0.0)'
            }
        };

        /**
         * Настройки кнопок
         * indexIt кнопка запуска индексирования
         * addToIndex кнопка добавления элементов для индексации
         * delete кнопка удаления индексов
         */
        Settings.buttons = {
            indexIt: {
                resource: {
                    fileName: 'index.button.html',
                    fullPath: ''
                },
                title: 'js-indexing-index-it',
                template: {
                    onLoad: Controller.saveIndexButtonTemplate,
                    source: null
                },
                source: null,
                onClick: Controller.showIndexingForm
            },
            addToIndex: {
                title: 'Добавить категорию для индексации',
                selector: '#add_to_index',
                onClick: Controller.addToIndexClick
            },
            delete: {
                title: 'Удалить',
                onClick: function() {
                    var elementId = getItemId(this);
                    var element = [elementId];

                    Controller._deleteIndexes(element);
                },
                resource: {
                    fileName: 'delete.button.html'
                }
            }
        };

        /**
         * Настройки полей индексации
         * indexState Состояние индексации
         * level Уровень вложенности, выбранный пользователем
         * chosen Выбран ли для индексации
         * indexDate Дата индексации
         */
        Settings.fields = {
            indexState: {
                name: 'index_state',
                prefix: {
                    done: 'Проиндексирован',
                    someValue: 'Проиндексирован на '
                },
                doneValue: 100
            },
            level: {
                name: 'index_level',
                maxValue: 9999
            },
            chosen: {
                name: 'index_choose'
            },
            indexDate: {
                name: 'index_date'
            }
        };
        /**
         * Настройки всплывающих окон
         * catalog Выбор/смена раздела
         * level Установка уровня индексации
         * indexForm Форма непосредственной индексации
         * deleteConfirm Подтверждение удаления индексов
         * alreadyIndexed Подтверждение добавления категории, чей родитель
         * проиндексирован до уровня, в котором находится эта категория
         */
        Settings.popups = {
            catalog: {
                name: "Catalog",
                title: getLabel('js-indexing-choose-category'),
                changingTitle: 'Смена раздела',
                width: 620,
                height: 335,
                resource: 'tree.html?hierarchy_types=catalog-category',
                onClose: Controller.onCategoryChooseClose
            },
            level: {
                name: "levelInputDialog",
                title: getLabel('js-indexing-category-params'),
                width: 450,
                height: 50,
                resource: 'level.dialog.html',
                onClose: Controller.onLevelDialogClose
            },
            indexForm: {
                name: 'indexForm',
                title: getLabel('js-indexing'),
                resource: 'indexing.html',
                width: 400,
                height: 300,
                onClose: Controller.onIndexFormClose
            },
            deleteConfirm: {
                title: getLabel('js-indexing-deleting-confirmation'),
                text: getLabel('js-indexing-deleting-willing'),
                confirmButton: getLabel('js-indexing-delete'),
                width: 300,
                name: 'delete_index'
            },
            alreadyIndexed: {
                title: getLabel('js-indexing-adding-confirmation'),
                text:  getLabel('js-indexing-duplication-index'),
                confirmButton: getLabel('js-indexing-yes'),
                cancelButton: getLabel('js-indexing-no'),
                width: 400,
                name: 'already_indexed'
            }
        };

        (function () {
            for (var buttonName in Settings.buttons) {
                if (Settings.buttons.hasOwnProperty(buttonName)) {
                    var button = Settings.buttons[buttonName];
                    if (button.resource && button.resource.fileName) {
                        button.resource.fullPath = Settings.module.dirPath + button.resource.fileName;
                    }
                }
            }
        })();

        /** Настройки, связанные с табличным контролом */
        Settings.table =  {
            object: null,
            rows: {
                menu: [
                    ['delete', 'ico_del', Controller.cancelChoosing]
                ]
            }
        };

        /**
         * Адреса, по которым можно выполнить определенные действия на сервере
         * cancelChoosing Удалить индексы
         * setValue Установить значения
         */
        Settings.actions = {
            cancelChoosing: {
                url: '/admin/udata://catalog/deleteIndex/.json'
            },
            setValue: {
                url: '/admin/udata://catalog/setValueForIndexField/.json'
            }
        }


    }

    /**
     * Записывает в поле fieldName значение value элемента tableItem на сервере
     * @param {TableItem} tableItem элемент табличного контрола (предствляет страницу в системе)
     * @param {String} fieldName имя поля
     * @param {String} value новое значение
     * @param {Function} success обработчик события успешной перезаписи значения поля на сервере
     */
    Controller.setValue = function(tableItem, fieldName, value, success) {
        var id = typeof tableItem.id !== 'undefined' ? tableItem.id : tableItem;
        var args = arguments;
        var url = Settings.actions.setValue.url,
            data = {
                param0: id,
                param1: fieldName,
                param2: value
            },
            successFunc = (typeof success === 'function' ? success : function() {}),
            callback = function (response) {
                if (typeof(response.data) !== 'undefined') {
                    if (response.data.success === true ) {
                        successFunc();
                    } else {
                        throw args.callee.name + '(): Data is empty';
                    }
                } else {
                    throw args.callee.name + '(): Data is empty';
                }
            };
        makePostRequest(url, data, callback);
    };

    /**
     * Удаляет данные индекса для элементов
     * @param {Array} elements массив с ID элементов
     */
    Controller._deleteIndexes = function(elements) {
        var elements = Array.isArray(elements) ? elements : [];
        var url = Settings.actions.cancelChoosing.url;
        var data;


        var callback = function (response) {
            if (typeof response.data !== 'undefined' && response.data.success === true) {
                $.LoadingOverlay('hide');
                window.location.reload();
            } else {
                throw arguments.callee.name + "(): Data was no saved properly";
            }

        };

        data = {elements: elements};
        var confirmSettings = Settings.popups.deleteConfirm;

        openDialog({
            name: confirmSettings.name,
            title: confirmSettings.title,
            text: confirmSettings.text,
            width: confirmSettings.width,
            closeButton: true,
            stdButtons : true,
            OKText: confirmSettings.confirmButton,
            cancelText: getLabel("js-cancel"),
            OKCallback: function() {
                $.closePopupLayer(confirmSettings.name);
                $.LoadingOverlay('show');
                makePostRequest(url, data, callback);
            },
            cancelCallback : function() {
                $.closePopupLayer(confirmSettings.name);
            }
        });

    };

    /**
     * Возвращает данные для формирования контестного меню для индексов
     * @param action
     * @returns {{caption: *, icon: *, visible: boolean, execute: Function}}
     * caption - Название пункта меню
     * icon - путь до иконки пункта
     * visible - видимость
     * execute - обработчик события клина по пункту меню
     */
    Controller.cancelChoosing = function(action) {
        return {
            caption: getLabel('js-' + action[0]),
            icon: action[1],
            visible: true,
            execute: function () {
                var table = Settings.table.object;
                var elements = [];

                for (var id in table.selectedList) {
                    if (!isNaN(parseInt(id))) {
                        elements.push(parseInt(id));
                    }
                }

                Controller._deleteIndexes(elements);

            }
        };
    };
    /**
     * Обработчик события успешной загрузки настроек индексации с сервера
     * @param {String} response ответ от сервера
     */
    Controller.onSettingsLoad = function (response) {
        var data = $.parseJSON(response.responseText);
        if (typeof data['data'] !== 'undefined' && typeof data['data'].settings === 'object') {
            Settings.module.server.settings = data['data'].settings;
        }
    };

    /**
     * Обработчик события закрытия диалогового окна установки уровня вложеннсти индексации
     * @param data данные статуса закрытия окна
     * {{Number} oldCategory,
     * {Number} newCategory,
     * {Number} level,
     * {Boolean} success}
     */
    Controller.onLevelDialogClose = function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            $.closePopupLayer(Settings.popups.catalog.name, data);
        }
    };

    /**
     * Обработчик события закрытия диалогового окна выбора/смены категории
     * @param data данные статуса закрытия окна
     * {{Number} oldCategory,
     * {Number} newCategory,
     * {Number} level,
     * {Boolean} success}
     */
    Controller.onCategoryChooseClose = function(data) {
        if (typeof data !== 'undefined' && data.success === true) {
            $.LoadingOverlay('show');
            Controller.setValue(data['newCategory'], Settings.fields.level.name, data.level,
                function () {
                    Controller.setValue(data['newCategory'], Settings.fields.chosen.name, true,
                        function () {
                            if (typeof data['oldCategory'] !== 'undefined') {
                                Controller.setValue(data['oldCategory'], Settings.fields.level.name, '',
                                    function () {
                                        Controller.setValue(data['oldCategory'], Settings.fields.chosen.name, false,
                                            function () {
                                                $.LoadingOverlay('hide');
                                                window.location.reload();
                                            }
                                        );
                                    }
                                );
                            } else {
                                $.LoadingOverlay('hide');
                                window.location.reload();
                            }

                        }
                    );
                }
            );
        }
    };

    /**
     * Отобразить всплывающее сообщение
     * @param {String} message текст сообщения
     */
    Controller.showMessage = function(message) {
        if (typeof $.jGrowl === 'function') {
            $.jGrowl(message, Settings.module.messagesOptions);
        }
    };

    /** Обработчик события закрытия диалогового окна индексации */
    Controller.onIndexFormClose = function() {
        var storage = window.localStorage;
        var success = (storage.getItem(Settings.module.storage.success) === '1');

        if (success) {
            storage.removeItem(Settings.module.storage.success);
            window.location.reload();
        }
    };

    /** Обработчик события, которое возникает после полной отрисовки табличного контрола */
    Controller.onRenderComplete = function() {
        var indexButton = {
            source: AdminIndexing.Settings.buttons.indexIt.source,
            $elements: null,
            tag: '',
            class: '',
            id: '',
            text: ''
        };

        Settings.table.object = window.oTable;

        indexButton.class = $(indexButton.source).attr('class');
        indexButton.tag = $(indexButton.source).prop('tagName');
        indexButton.id = $(indexButton.source).attr('id');
        indexButton.text = $(indexButton.source).text();

        // Назначить обработчики события нажатия на кнопку индексации
        var classSelector = '.' + indexButton.class;
        var textSelector = indexButton.tag + ':contains("' + indexButton.text + '")';

        if ($(classSelector).length) {
            indexButton.$elements = $(classSelector);
        } else if ($(textSelector).length) {
            indexButton.$elements = $(textSelector);
        }

        if (indexButton.$elements) {
            if (indexButton.$elements.length) {
                $.each(indexButton.$elements, function(index, el) {
                    $(el).click(Settings.buttons.indexIt.onClick);
                })

            }
        }

        //Назначить обработчики события нажатии на заголовках добавленных категорий
        var $namesColumnCells = $('tr[rel] td.column:first-child', Settings.table.object.container);
        var $namesLinks = $namesColumnCells.find('div > a');

        $namesLinks.click(function(e) {
            e.preventDefault();
            var popupOptions = Object.create(Settings.popups.catalog);
            var params = {
                categoryId: getItemId(this)
            };
            popupOptions.title = popupOptions.changingTitle + ' - ' +
                                 Settings.table.object.items[getItemId(this)].name;
            popupOptions.resource = Controller.replaceParams(popupOptions.resource, params);

            Controller.showPopup(popupOptions);

            popupOptions = null;
        });

        // Добавить элементы крестика, посредством которого можно удалять индексы

        makePostRequest(Settings.buttons.delete.resource.fullPath, null, onLoadDeleteButton, true);

        function onLoadDeleteButton(response) {
            var $deleteButtons = $(response).appendTo($namesColumnCells);
            $deleteButtons.click(Settings.buttons.delete.onClick);
            $namesColumnCells.mouseover(function() {
                var $delButton = $(this).find('.' + $deleteButtons.attr('class'));
                $delButton.show();
            });
            $namesColumnCells.mouseout(function() {
                var $delButton = $(this).find('.' + $deleteButtons.attr('class'));
                $delButton.hide();
            });
        }

    };

    /** Отображает форму индексации (всплываюшее окно с формой) */
    Controller.showIndexingForm = function() {
        var itemId = getItemId(this);
        var tableItem = Settings.table.object.items[itemId];

        var params = {
            categoryId: itemId,
            level: tableItem.getValue(Settings.fields.level.name)
        };
        var settings = Object.create(Settings.popups.indexForm);

        settings.resource = Controller.replaceParams(settings.resource, params);
        settings.title = Settings.popups.indexForm.title + ' раздела "' + tableItem.name + '"';

        Controller.showPopup(settings);
    };

    /**
     * Обработчик события нажатия на кнопку добавления раздела для индексации
     * @param {Event} e
     */
    Controller.addToIndexClick = function(e) {
        e.preventDefault();
        Controller.showPopup(Settings.popups.catalog);
    };

    /**
     * Сохраняет шаблон кнопки индексации
     * @param {String} response ответ от сервера
     */
    Controller.saveIndexButtonTemplate = function(response) {
        Settings.buttons.indexIt.template.source = response.responseText;
    };

    /**
     * Заменяет значения GET-параметров в строке запроса url и добавляет несуществующие
     * @param {String} url строка запроса
     * @param {Object} params заменяемые параметры запроса
     * @returns {String} новоя строка запроса
     */
    Controller.replaceParams = function(url, params) {
        if (typeof params !== 'object') {
            throw arguments.callee.name + "(): Second param must be object";
        }

        var query = url.split('?');
        var resource = query[0];
        query = (typeof query[1] === 'undefined') ? '' : query[1];
        var queryParams = (query.length > 0) ? Controller.getArgs(query) : {};
        var result = '';

        $.extend(queryParams, params);

        result += resource + '?' + $.param(queryParams);

        return result;
    };

    /**
     * Возвращает объект, содержащий данные о GET-параметрах строки path
     * @param {String} path строка запроса
     * @returns {Object}
     */
    Controller.getArgs = function (path) {
        var args = {};
        var query = path.replace(/(^\?)/,'');
        var pairs = query.split("&");

        for (var i = 0; i < pairs.length; i++) {
            var pos = pairs[i].indexOf('=');
            if (pos == -1) continue;
            var argName = pairs[i].substring(0, pos);
            var value = pairs[i].substring(pos + 1);
            args[argName] = unescape(value);
        }

        return args;
    };

    /**
     * Возвращает исходный код кнопки индексации
     * @returns {String}
     * @throws
     */
    Controller.getIndexButtonSource = function(title) {
        var title = title || window.getLabel(Settings.buttons.indexIt.title);

        var button = {
            data: {
                Text: title
            },
            element: null
        };

        button.element = $.tmpl(Settings.buttons.indexIt.template.source, button.data)[0];

        Settings.buttons.indexIt.source = button.element;

        if (button.element instanceof HTMLElement) {
            return button.element.outerHTML;
        }

        throw arguments.callee.name + "(): Indexing button is not HTML Element";
    };

    /**
     * Обработчик события отрисовки значений в ячейках табличного контрона
     * @param {Object|String} prop объект свойства или его значение
     * @param {String} name имя свойства
     * @returns {Object|String}
     */
    Controller.onLoadPropValue = function(prop, name) {

        if (name === Settings.fields.indexState.name) {
            var indexStateValue = prop.value;
            var indexingProgress = Number(indexStateValue);

            switch (true) {
                case (typeof indexStateValue === 'undefined'): {
                    prop = Controller.getIndexButtonSource();
                    break;
                }
                case (indexingProgress === 0): {
                    prop.value = Controller.getIndexButtonSource();
                    break;
                }
                case (indexingProgress > 0 && indexingProgress < Settings.fields.indexState.doneValue): {
                    prop.value = Settings.fields.indexState.prefix.some + indexingProgress.toFixed(1) + '%';
                    break;
                }
                case (indexingProgress === Settings.fields.indexState.doneValue): {
                    prop.value = Settings.fields.indexState.prefix.done + ', ' + Controller.getIndexButtonSource('Переиндексировать');
                    break;
                }

                // No default
            }

            return prop;
        }

        return prop;
    };

    /**
     * Функция отображения popup окна
     * @param {Object} options опции отображения
     */
    Controller.showPopup = function (options) {
        var settings = {
            name: '',
            title: '',
            width: 0,
            height: 0,
            resource: '',
            onClose: function() {}
        };

        $.extend(settings, options);

        if (typeof settings.name !== 'string' || settings.name.length <= 0) {
            throw arguments.callee.name + '(): Name of popup must be set';
        }

        if (typeof settings.resource !== 'string' || settings.resource <= 0) {
            throw arguments.callee.name + '(): Resource must be set';
        }

        $.openPopupLayer({
            name: settings.name,
            title: settings.title,
            width: settings.width,
            height: settings.height,
            url: Settings.module.dirPath + settings.resource,
            afterClose: settings.onClose
        });
    };

    /**
     * Выполняет  AJAX-запрос методом POST
     * @param {String} url адрес запроса
     * @param {Object} data данные, которые будут переданы на сервер
     * @param {Function} callback обработчик события успешного выполнения запроса
     * @param {Boolean} isHTML ожидаются ли данные от сервера в формате HTML или JSON в ином случае
     */
    function makePostRequest(url, data, callback, isHTML) {
        var args = arguments;
        var dataType = isHTML ? 'html' : 'json';

        $.ajax({
            url: url,
            dataType: dataType,
            type: 'post',
            data: data,
            success: callback,
            error: function() {
                $.LoadingOverlay('hide');
                throw args.callee.name + '(): Some AJAX error occurred';
            }
        });
    }

    /**
     * Возвращает ID страницы, по элементу который находится ближе всего к соответствующей строке
     * в табличном контроле
     * @param element
     * @returns {*}
     */
    function getItemId(element) {
        return $(element).closest('tr[rel]', Settings.table.object.container).attr('rel');
    }

    init();

    $(function() {

        var langId = null;
        var langParam = '';
        if (uAdmin.data) {
            langId = uAdmin.data['lang-id'];
            langParam = (langId ? '&lang_id=' + langId : '');
        }

        Settings.popups.catalog.resource += langParam;

        var loadingOptions = Settings.module.loadingOverlay;
        $.LoadingOverlaySetup({
            image: loadingOptions.image,
            color: loadingOptions.color
        });

        $(Settings.buttons.addToIndex.selector).click(function(e) {
            var callback = function() {};

            if (typeof Settings.buttons.addToIndex.onClick === 'function') {
                callback = Settings.buttons.addToIndex.onClick;
            }

            callback(e);
        });
    });

    return {
        Controller: Controller,
        Settings: Settings
    };

})(jQuery);