/** Контрол поля типа "Изображение" */

var ControlImageFile = function (options) {
    var wrapper = options.container || null,
        id = 'imageField_',
        file = '',
        alt = '',
        title = '',
        file_hash = '',
        folder_hash = '',
        name = '',
        fm = '',
        fieldId = null,
        cwd = '.',
        lang = options.lang || 'ru',
        selectId = 'imageControlSelect_',
        selectObj = null,
        container = null;

    function init() {
        id = id + wrapper.attr('id');
        selectId = selectId + wrapper.attr('id');
        container = $('#' + id,wrapper);
        file = wrapper.attr('umi:file');
        alt = wrapper.attr('umi:alt');
        title = wrapper.attr('umi:title');
        file_hash = wrapper.attr('umi:file-hash');
        folder_hash = wrapper.attr('umi:folder-hash');
        name = wrapper.attr('umi:input-name');
        fm = wrapper.attr('umi:filemanager');
        fieldId = wrapper.attr('umi:field-id');
        container.html('');
        var value = (file.indexOf('./')>=0 ? '':'.')+file;
        value = (value == '.') ? '' : value;
        var closeButtonHint = getLabel('js-image-field-remove-image');
        var altButtonHint = alt || getLabel('js-image-field-empty-attribute');
        var altButtonExtraClass = alt ? '' : 'empty';
        var titleButtonHint = title || getLabel('js-image-field-empty-attribute');
        var titleButtonExtraClass = title ? '' : 'empty';

        container.append($([
            '<div class="thumbnail">'+( file !== '' ? '<img src="'+file+'"/>' : getLabel('js-image-field-empty') ),
            (file == '' ? '':'<div class="close" title = "' + closeButtonHint + '">&times;</div>' +
                (fieldId ? '<div class="alt ' + altButtonExtraClass + '" title="' + altButtonHint + '">ALT</div>' : '') +
                (fieldId ? '<div class="title ' + titleButtonExtraClass + '" title="' + titleButtonHint + '">TITLE</div>' : '')),
            '</div>',
            '<input type="hidden" name="' + name + '" id="' + selectId + '" value="' + value + '" />',
            ((file != '' && fieldId) ? '<input type="hidden" name="data[images][' + fieldId + '][alt]" value="' + alt + '" />' : ''),
            ((file != '' && fieldId) ? '<input type="hidden" name="data[images][' + fieldId + '][title]" value="' + title + '" />' : '')

        ].join('')));

        selectObj = $('#'+selectId,wrapper);

        container.find('.thumbnail').on('click',function(){
            if (fm == 'elfinder'){
                showElfinderFileBrowser(selectObj,cwd,true,false,folder_hash,file_hash,lang)
            }
        });

        if (file !== ''){
            container.find('.close').on('click',function(e){
                closeClickHandler();
                e.stopPropagation();
            });

            bindImageAttributeEditors(container);
        }

        selectObj.on('change',function(){
            fileChangeHandler(this);
        });
    }

    /**
     * Прикрепляет действия к кнопкам редактирования атрибутов изображения
     * @param {jQuery} $container элемент контрола изображения
     */
    function bindImageAttributeEditors($container) {
        var $altInput = $('input[name$="[alt]"]', $container);

        $container.find('.alt').on('click',function(e) {
            var $altButton = $('div.alt', $container);
            imageAttributeClickHandler($altInput, getLabel('js-image-field-alt-dialog-title'), $altButton);
            e.stopPropagation();
        });

        var $titleInput = $('input[name$="[title]"]', $container);

        $container.find('.title').on('click',function(e) {
            var $titleButton = $('div.title', $container);
            imageAttributeClickHandler($titleInput, getLabel('js-image-field-title-dialog-title'), $titleButton);
            e.stopPropagation();
        });
    }

    /**
     * Обработчик нажатия на кнопку редактирование атрибута изображения
     * @param {jQuery} $input контейнер для значения атрибута
     * @param {String} header заголовок окна для ввода значения атрибута
     * @param {jQuery} $button кнопка вызова редактирования
     */
    function imageAttributeClickHandler($input, header, $button){
        openDialog('', header, {
            html: '<input type="text" class="default" value="' + $input.val() + '" id="imageAttribute"/>',
            confirmText: 'OK',
            confirmOnEnterElement: '#imageAttribute',
            cancelButton: true,
            cancelText: getLabel('js-cancel'),
            confirmCallback: function (dialogName) {
                var val = $('#imageAttribute').val();

                if (val.length > 0) {
                    $button.attr('title', val);
                    $button.removeClass('empty');
                } else {
                    $button.attr('title', getLabel('js-image-field-empty-attribute'));
                    $button.addClass('empty');
                }

                $input.val(val);
                closeDialog(dialogName);
            }
        });
    }

    function fileChangeHandler(obj){
        var el = $('.thumbnail',container),
            val = obj.value;
        el.html('');
        file = val;
        obj.value = '.'+val;
        var closeButtonHint = getLabel('js-image-field-remove-image');
        var altButtonHint = getLabel('js-image-field-empty-attribute');
        var titleButtonHint = getLabel('js-image-field-empty-attribute');

        el.append($('<img src="'+val+'"/>' +
            (fieldId ?  '<div class="alt empty" title="' + altButtonHint + '">ALT</div>' : '') +
            (fieldId ?  '<div class="title empty" title="' + titleButtonHint + '">TITLE</div>' : '') +
            (fieldId ?  '<input type="hidden" name="data[images][' + fieldId + '][alt]" value="" />' : '') +
            (fieldId ?  '<input type="hidden" name="data[images][' + fieldId + '][title]" value="" />' : '')
        ));
        var close = $('<div class="close" title="' +  closeButtonHint + '">&times;</div>');
        close.on('click',function(e){
            closeClickHandler();
            e.stopPropagation();
        });
        bindImageAttributeEditors(el);
        el.append(close);
        cwd = '.'+val.substr(0, val.lastIndexOf("/"));
    }

    function closeClickHandler(){
        selectObj.val('');
        container.find('.thumbnail').html('файл не выбран');
        file = '';
        cwd = '.';
    }

    function showElfinderFileBrowser(select, folder, imageOnly, videoOnly, folder_hash, file_hash, lang) {
        var qs = 'id='+select.attr('id');
        var index = 0;
        var file  = cwd.replace(/^\.\//, "/") + ((index = select.val().lastIndexOf('/')) != -1 ? select.val().substr(index) : select.val() );
        qs = qs + '&file=' + file;
        if (folder) {
            qs = qs + '&folder=' + folder;
        }
        if (imageOnly) {
            qs = qs + '&image=1';
        }
        if (videoOnly) {
            qs = qs + '&video=1';
        }
        if (typeof(folder_hash) != 'undefined') {
            qs = qs + '&folder_hash=' + folder_hash;
        }
        if (typeof(file_hash) != 'undefined') {
            qs = qs + '&file_hash=' + file_hash;
        }
        if (lang) {
            qs = qs + '&lang=' + lang;
        }
        $.openPopupLayer({
            name   : "Filemanager",
            title  : getLabel('js-file-manager'),
            width  : 1200,
            height : 600,
            url    : "/styles/common/other/elfinder/umifilebrowser.html?"+qs
        });

        var filemanager = jQuery('div#popupLayer_Filemanager div.popupBody');
        if (!filemanager.length) {
            filemanager = jQuery(window.parent.document.getElementById('popupLayer_Filemanager')).find('div.popupBody');
        }

        var footer = '<div id="watermark_wrapper"><label for="add_watermark">';
        footer += getLabel('js-water-mark');
        footer += '</label><input type="checkbox" name="add_watermark" id="add_watermark"/>';
        footer += '<label for="remember_last_folder">';
        footer += getLabel('js-remember-last-dir');
        footer += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"'
        if (getCookie('remember_last_folder', true) > 0) {
            footer += 'checked="checked"';
        }
        footer +='/></div>';

        filemanager.append(footer);
    }

    init();
};
