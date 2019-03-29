(function($, uAdmin) {
    $(function() {
        var data = uAdmin.data.data;
        var encodingFieldName = 'encoding_' + data['object-type'];
        var $encodingSelect = $('select[name*=' + encodingFieldName + ']');
        var selectedEncoding = $encodingSelect.children('option:selected').text();
        var formatFieldName = 'format';

        if (!data.action || data.action != 'create') {
            loadItemsForField(encodingFieldName);
            loadItemsForField(formatFieldName);
        }


        if (selectedEncoding.length === 0) {
            // Получаем кодировку по умолчанию
            var defaultEncoding = data['default-encoding'];
            if (defaultEncoding.toLowerCase() === 'cp1251' || !defaultEncoding) {
                defaultEncoding = 'Windows-1251';
            }
            // Находим опцию, соответствующую кодировке по умолчанию
            var $encodingOption = $('option', $encodingSelect).filter(function() {
                return $(this).text().toLowerCase() == defaultEncoding.toLowerCase();
            });
            $encodingOption.attr('selected', true);
        }

        var csvOptionId = data['csv-format-id'];

        var $formatSelect = $('select[name*=' + formatFieldName + ']');
        // Выбранный формат данных
        var $selectedOption = $formatSelect.children('option:selected');
        // Блок поля "Кодировка"
        var $encodingFieldBlock = $encodingSelect.closest('div.field');

        if ($selectedOption.val() == csvOptionId) {
            $encodingFieldBlock.show();
        } else {
            $encodingFieldBlock.hide();
        }

        function loadItemsForField(fieldName) {
            var fieldSelect = $('select[name*=' + fieldName + ']');
            var id = fieldSelect.attr('id');
            var suffix = id.split('relationSelect')[1];

            if (!suffix) {
                return;
            }

            var field = searchInObject(data, 'name', fieldName);
            var typeId = field['type-id'];

            if (!typeId) {
                 return;
            }

            var fieldControl = new relationControl(typeId, suffix);
            fieldControl.loadItemsAll();

            function searchInObject(object, property, value) {
                if (typeof object != 'object') {
                    return;
                }

                var keys = Object.keys(object);

                if (keys.length === 0) {
                    return;
                }


                for (var i = 0; i < keys.length; i++) {
                    var key = keys[i];
                    if (object.hasOwnProperty(key)) {
                        var sub = object[key];

                        if (sub[property] === value) {
                            return sub;
                        } else {
                            var result = searchInObject(sub, property, value);
                            if (result) {
                                return result;
                            }
                        }
                    }
                }
            }
        }

        // Обработчик события выбора формата данных
        $formatSelect.change(function() {

            var $selectedOption = $(this).children('option:selected');

            if ($selectedOption.val() == csvOptionId) {
                $encodingFieldBlock.show();
            } else {
                $encodingFieldBlock.hide();
            }
        });
    });

}(jQuery, uAdmin));