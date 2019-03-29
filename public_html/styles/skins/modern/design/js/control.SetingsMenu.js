/** Класс реализующий меню на страницах форм создания и редактирования объектов */
var controlSettingsMenu = function (options) {
    var options = options || {},
        $ = jQuery || {},
        container = options.container || '.tabs.editing',
        anchorSelector = options.anchor || '.panel-settings a:first-child[data-label]',
        wrapper = options.wrapper || '.editing-functions-wrapper',
        fixObj = $(options.fix) || null,
        panelList = [],
        anchors = [],
        lastAnchor = -1,
        isPanelFixed = false,
        checkSelection = true;

    function clickHandler(link) {
        checkSelection = false;
        var section = $(link).parent();
        setSelected(section);
        section.addClass('selected');

        var hash = $(link).attr('href');

        if (!hash) {
            return;
        }

        var linkName = hash.replace(/^#(.+)/, '$1');
        var anchor = $(anchorSelector + '[data-name=' + linkName + ']');
        history.pushState(null, null, hash);

        scrollToElement(anchor, function() {
            checkSelection = true;
        });
    }

    function setSelected(el) {
        $('.selected', container).removeClass('selected');
        el.addClass('selected');
    }

    function scrollHandler() {
        var el = wrapper.parent(),
            top = $(window).scrollTop();

        if (top > el.position().top+65) {
            isPanelFixed = true;
            wrapper.addClass('fixed');
            if (fixObj !== null){
                fixObj.addClass('fix');
            }
        } else {
            isPanelFixed = false;
            wrapper.removeClass('fixed');
            if (fixObj !== null){
                fixObj.removeClass('fix');
            }
        }

        var group = null;
        var groupTop = null;
        var groupHeight = null;
        var windowScrollTop = null;

        for (var i = 0, cnt = anchors.length; i < cnt; i++) {
            group = anchors[i].top;
            groupTop = group.position().top;
            groupHeight = group.outerHeight();
            windowScrollTop = isPanelFixed ? top + $(container).outerHeight() : top;

            if (groupTop <= windowScrollTop && windowScrollTop < groupTop + groupHeight) {
                if (lastAnchor != i && checkSelection) {
                    setSelected(anchors[i].el);
                    lastAnchor = i;
                }
                break;
            }
        }
    }

    function init() {
        var counter = 0;
        container = $(container);
        panelList = $(anchorSelector);
        wrapper = $(wrapper);


        if (panelList.length > 1) {
            for (var i = 0, cnt = panelList.length; i < cnt; i++) {
                if ($(panelList[i]).parent().css('display') == 'none'){
                    continue;
                }
                anchors.push({
                    name: $(panelList[i]).data('name'),
                    label: $(panelList[i]).attr('data-label'),
                    top: $(panelList[i]).parent()
                });
                var el = $([
                    '<div class="section ' + (i == 0 ? 'selected' : '') + '">',
                    '<a href="#' + anchors[counter].name + '">',
                    anchors[counter].label,
                    '</a>',
                    '</div>'
                ].join(''));
                anchors[counter].el = el;
                el.on('click', function (e) {
                    e.preventDefault();
                    clickHandler(e.target);
                });
                container.append(el);
                counter++;
            }

            $(window).on('scroll', scrollHandler);
        } else {
            if (!container.hasClass('notextselect')) {
                container.hide();
            }
        }
    }

    /**
     * Прокручивает окно до определенного элемента
     * @param {HTMLElement|jQuery} element
     * @param {Function} onScrollFinished
     */
    function scrollToElement(element, onScrollFinished) {
        onScrollFinished = typeof onScrollFinished == 'function' ? onScrollFinished : function() {};
        element = $(element);

        if (element.length === 0) {
            return;
        }

        var panel = $(container);
        var toolbar = panel.siblings('.toolbar');

        var scrollPosition = element.offset().top - panel.outerHeight() - toolbar.outerHeight();

        if (!isPanelFixed) {
            scrollPosition -= panel.outerHeight() / 2;
        }

        $('html, body').animate({
            scrollTop: scrollPosition
        }, {
            complete: onScrollFinished
        });
    }

    /**
     * Возвращает элемент якоря по его имени
     * @param {String} name
     * @returns {*}
     */
    function getAnchor(name) {
        if (!name) {
            return null;
        }

        name = name.replace(/^#(.+)/, '$1');
        var anchor = $(anchorSelector + '[data-name=' + name + ']');
        return anchor.get(0);
    }

    /** Прокручивает окно до текущего якоря, хеш-тег которого содержится в адресе */
    function scrollToCurrentAnchor() {
        scrollToElement(getAnchor(location.hash));
    }

    init();
    sliderForNavigation();
    return {
        scrollToCurrentAnchor: scrollToCurrentAnchor
    }
};