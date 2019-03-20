/** Селект для фильтров таблицы со списком с абсолютным позиционированием. */

var ControlComboBox = function (option){
    var el = $(option.el) || {},
        ui = {},
        parent,
        opt = [],
        selected = {},
        dd = {},
        label = {},
        locked = false,
        sourceUri = option.sourceUri || '/admin/data/guide_items_all/',
        typeId = option.type || null;

    function init(){
        var optList = [];
        if (el.length > 0 && el.prop('tagName').toLowerCase() === 'select'){
            optList = el.find('option');
            name = el.attr('name');
            dd = $('<ul class="select-filter-list" style="display: none; position: absolute;"></ul>');
            for (var i=0, cnt=optList.length; i<cnt; i++){
                var item = $(optList[i]),
                    data = {
                        label:item.text(),
                        selected: item.is(':selected'),
                        value:item.attr('value')
                    };
                opt.push(data);
                if (data.selected){
                    selected = data;
                }
                dd.append($('<li data="'+i+'">'+data.label+'</li>'));
            }
            parent = el.parent();
            ui = $([
                '<div class="select filter">',
                    '<div class="selected">'+selected.label+'</div>',
                '</div>'
            ].join(''));
            el.after(ui);
            el.hide();
            selectSetValue();

            label = $('div.selected',ui);

            dd.css('left',label.offset().left+'px');
            dd.css('top',label.offset().top+label.height()+2+'px');
            dd.css('width',ui.width()+'px');

            label.on('click',function (e) {
                ddToggleHandler(e);
            });

            $('li',dd).on('click',function () {
                selected = opt[$(this).attr('data')];
                selectSetValue ();
                labelSetValue();
            });

            $('body').append(dd);

            $(document).on('click',function (e) {
                if ($(e.target).parents(".select").is(ui)) return;
                dd.slideUp(100, function(){
                    label.removeClass('focus');
                });
            })
        }
    }


    function ddToggleHandler(e){
        if (locked) return false;
        var el =  $(e.currentTarget);
        el.toggleClass('focus');
        if (dd.is(':visible')){
            dd.slideUp(100);
        } else {
            dd.css('left',label.offset().left+'px');
            dd.css('top',label.offset().top+label.height()+2+'px');
            dd.css('width',ui.width()+'px');
            dd.slideDown(100);
        }
    }


    function labelSetValue(){
        label.text(selected.label);
        el.trigger('change');
    }

    function selectSetValue (){
        el.find('option').remove();
        el.append($('<option selected="selected" value="'+selected.value+'">'+selected.label+'</option>'));
    }

    function updateItemsAll(r, callback) {
        callback = typeof callback == 'function' ? callback : function() {
        };
        updateElements(r, callback);
    }

    function updateElements(response, callback) {
        callback = (typeof callback === 'function') ? callback : function(r) {};

        var items = response.responseXML.getElementsByTagName('object');
        lock();
        var oldValue = selected;

        clearOptions();

        for (var i = 0, count = items.length; i < count; i++) {
            addOption({
                label: items[i].getAttribute('name'),
                value: items[i].getAttribute('id')
            });
        }

        //addItem();

        unlock();
        callback(response);
    }

    function addItem(item) {
        console.log(item);
    }

    function loadItemsAll(callback, override) {
        jQuery.ajax({
            url: sourceUri + typeId + ".xml?allow-empty",
            type: "get",
            complete: function(r) {
                if (override) {
                    callback(r);
                    return;
                }

                updateItemsAll(r, callback);
            }
        });
    }

    function getValue() {
        return el.val();
    }

    function lock() {
        locked = true;
    }

    function unlock() {
        locked = false;
    }

    function clearOptions(){
        el.find('option').remove();
        dd.find('li').remove();
        selected = {};
        opt = [];
    }

    function addOption(item){
        if (item.selected === undefined){
            item['selected'] = false;
        }
        opt.push(item);
        dd.append($('<li data="'+item.value+'">'+item.label+'</li>'));
    }

    function change(callback) {
        if (typeof callback == 'function'){
            el.on('change',callback)
        }
    }


    init();

    return {
        lock: lock,
        unlock: unlock,
        addItem: addItem,
        change: change,
        loadItemsAll: loadItemsAll
    }
};

