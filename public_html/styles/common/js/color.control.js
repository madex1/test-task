/**
 * Контрол для полей типа "Цвет"
 * В качестве color picker используется Bootstrap Colorpicker
 * http://www.jqueryrain.com/?zETM2MpT
 *
 * @param {HTMLElement|jQuery} element контейнер, содержащий поле для ввода
 * @param {Object} options опции
 *
 * Описание опций:
 * debug - подключает неминифицированную версию JS файла пикера
 * pickerOptions - опции color picker (ссылка на описание сверху)
 */
function colorControl(element, options) {
    var options = options || {};
    var defaults = {
        colorBox: '<span class="color-box"><i></i></span>',
        container: element,
        assets: {
            js: '/js/jquery/colorpicker/js/bootstrap-colorpicker.js',
            jsMin: '/js/jquery/colorpicker/js/bootstrap-colorpicker.min.js',
            css: '/js/jquery/colorpicker/css/bootstrap-colorpicker.min.css'
        }
    };

    var colorPicker = {
        options: {
            defaultValues: {
                component: '.color-box',
                customClass: 'color-picker',
            },
            user: options.pickerOptions || {}
        }
    };

    jQuery.extend(colorPicker.options.user, colorPicker.options.defaultValues);
    jQuery.extend(options, defaults);

    jQuery(options.colorBox).insertAfter(jQuery('input', element));

    if (typeof jQuery.colorpicker !== 'function') {
        if (options.debug) {
            loadLibFiles(options.assets.js, options.assets.css);
        } else {
            loadLibFiles(options.assets.jsMin, options.assets.css);
        }
    }

    jQuery(options.container).colorpicker(colorPicker.options.user);

    function loadLibFiles(js, css) {
        jQuery('<script src="' + js + '" type="text/javascript" charset="utf-8"></script>').appendTo('head');
        jQuery('<link href="' + css + '" rel="stylesheet" charset="utf-8"></script>').appendTo('head');
    }
}