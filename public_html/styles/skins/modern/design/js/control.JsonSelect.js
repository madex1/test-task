/**
 * Контрол в виде Select для редактирования
 * json данных хранящихся в поле объекта или страницы
 */


var JsonSelectControl = function (options) {
    var optionsList = options.optionsList | [], // Список значений для select
        onChange = options.onChange || null, // Обработчик события change select
        encoder = options.encoder || null, // Кастомная функция преобразования данных в json
        isMultiple = options.isMultiple || false, // Доступен ли мультиселект у select
        selectize = {}, // Эксземпляр объекта selectize который обрабатывает select
        selectedItems = options.selectedItems || [], // Выбранные значение если есть
        $el = options.el || null; // тег input с которым работае контрол

    /**
     * Добавляет вариант выбора в select
     * @param opt - массив значений или одно значение
     * @param clear - атрибут определяющий надо ли удалить старые вырианты перед добавлением
     */
    function addOption(opt, clear) {
        var clearOptions = clear || false;
        if (selectize) {
            selectize.lock();
            if (clearOptions) {
                selectize.clearOptions();
            }
            selectize.addOption(opt);
            selectize.unlock();
        }

    }

    /**
     * Добавляет выбранные элементы
     * @param selected  - массив значений или одно значение
     * @param clear - надо ли очистить выбранные до этого
     */
    function addItem(selected, clear) {
        var clearOption = clear || false;
        if (selectize) {
            selectize.lock();
            if (clearOption) {
                selectize.clear();
            }
            if (_.isArray(selected)) {
                _.each(selected,function (item) {
                    selectize.addItem(item, false);
                });
            } else {
                selectize.addItem(selected);
            }
            selectize.unlock();
        }
    }

    /** Обработчик события change select. */
    function onChangeHandler() {
        var controlValue = getValue();
        if (_.isFunction(onChange)) {
            onChange(controlValue);
        } else {
            $el.val(controlValue);
        }
    }

    /** Инициализирует контрол */
    function init() {
        $el = $($el);
        if ($el.length == 0) return false;

        var selectElement = $('<select></select>');
        if (isMultiple) {
            selectElement.attr('multiple', 'multiple');
        }

        $el.parent().append(selectElement);
        selectElement = $('select', $el.parent()).selectize({
            plugins: ['remove_button', 'clear_selection'],
            onChange: function () {
                onChangeHandler();
            }

        });
        selectize = selectElement[0].selectize;
        if (optionsList) {
            addOption(optionsList);
            if (selectedItems) {
                addItem(selectedItems);
            }
        }
    }

    /**
     * Возвращает значение выбранное в Selectize в виде json
     * @returns {*}
     */
    function getValue() {
        var controlValue = selectize.getValue();
        return (_.isFunction(encoder)) ? encoder(controlValue) : JSON.stringify(controlValue);
    }

    init();
    return {
        getValue: getValue,

        query: function (url, data) {
            return $.ajax(url, data);
        },

        addOption: addOption,

        addItem: addItem
    }
};
