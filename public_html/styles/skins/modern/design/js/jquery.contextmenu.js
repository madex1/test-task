/**
Author: A.Chakkaev [1602] http://1602.habrahabr.ru/
Created: summer 2008
Modified: 23 oct 2008

*/
/*global cm_img, globals, MenuItem, jQuery*/

(function (jQuery) {


	if (typeof cm_img !== 'function') {
		cm_img = function (img, alt, style) {/* {{{ */
			if (alt) {
				alt = alt.replace(/"/, '\"');
			}

			if (img == 'checked'){
				img = 'i-apply';
			}

			if (img.indexOf('.')>-1) {
				return '<img src="/images/cms/admin/mac/tree/' + img +
				(img.search(/\.(gif|jpg|jpeg)$/i) === -1?'.png':'') +
				'" width="16" height="16" alt="' +
				(alt?alt:'img') + '" ' +
				(alt?'title="' + alt + '"':'') +
				(style?' style="' + style + '"':'') + ' />';
			} else  {
				return '<i class="' + (img !== 'undefined' ? 'small-ico ' + img : 'small-ico-empty' ) + '"  alt="' + alt + '" />';
			}
		};
	}

	if (typeof globals === 'undefined') {
		globals = {
			activeModule: window
		};
	}
	
	/**
	 * create object MenuItem
	 *
	 * @param string caption	displayed label, required parameter, if first symbol is "!"
	 *							then this menu item is disabled by default
	 * @param string icon		name of 16x16 icon, displayed on the left side of label, optional
	 * @param function execute	this will called when menu item was triggered
	 * @param object submenu	subitems of current item
	**/
	MenuItem = function (caption, icon, execute, submenu) {
		if (caption.search(/^!/) !== -1) {
			this.disabled = true;
			caption = caption.substr(1);
		}
		this.caption = caption;
		this.icon = icon;
		this.execute = execute;
		this.submenu = submenu;
	};
	
	jQuery.cmenu = {
		c: [],
		init: function (id, act) {		/* Create cmenu object	{{{ */
			var x = {
				cn: 'cmenu',
				id: id,
				jq: jQuery('<div iuid="' + id + '" class="cmenu"></div>'),
				r: false
			};
			x[typeof act === 'function'?'f':'a'] = act;
			
			jQuery('body').append(x.jq);
			return x;
			/* }}} */
		},
		render: function (x) {			/* Render menu items	{{{ */
			if (typeof x.f === 'function') {
				if (typeof x.caller !== 'object') {
					return false;
				}
				x.r = x.f(x);
				if (typeof x.r === 'object') {
					x.a = x.r;
					x.r = false;
				} else {
					x.r = !x.r;
				}
			}
			if (x.async) {
				if (!x.a) {
					x.done = function () {
						x.v = false;
						jQuery.cmenu.show(x, x.caller);
					}
					return false;
				}
				x.r = false;
			}
			if (x.r) {
				return false;
			}
			x.r = true;
			
			var h = '';
			if (x.type === 'radio') {
				var radio = x.get();
			} else {
				radio = false;
			}
			var strAsd = ' onmouseover="jQuery(this).addClass(\'cmenuItemHover\'); jQuery.cmenu.to=setTimeout(function(){var m = jQuery.cmenu.getMenu(' + x.id + ');m && m.sub && jQuery.cmenu.hideMenu(m.sub);},300);" onmouseout="jQuery(this).removeClass(\'cmenuItemHover\'); clearTimeout(jQuery.cmenu.to);" ';
			for (var i in x.a) {
				var a = x.a[i];
				if (a === '-') {                        
					h += '<hr' + (jQuery.browser.msie?' style="width:50px;align:center;"':'') + '/>';
					continue;
				}
				
				if (a.constructor === Array) {
					a = (function (x) {
						return new MenuItem(x[0], x[1], x[2], x[3]);
					})(a);
					x.a[i] = a;
				}
				x.a[i].parent = x.parent_item;
				// Условие невидимости действия
				if (typeof a.visible !== 'undefined' && !a.visible ||
					(typeof a.acid !== 'undefined' && jQuery.inArray(a.acid, globals.accessedActions || []))) {
					continue;
				}
				
				if (a.submenu && (!a.submenu.cn || a.submenu.cn !== 'cmenu')) {
					a.submenu = this.getMenu(a.submenu);
				}
				// Calc caption
				var caption = a.caption;
				if (radio && caption === radio) { // radio
					caption = '<strong><u>' + a.caption + '</u></strong>';
				} else { // other
					
				}
				h += '<div class="cmenuItem" item_id="' + i + '" ' +
					(a.disabled? 
						// Недоступный элемент
						'style="color:#808080;" ':
						// Доступный элемент
						'onclick="jQuery.cmenu.exec(this);" ' +
						
						//'onclick="jQuery.cmenu.exec(' + x.id + ',\'' + i + '\');" ' +
						(a.submenu?
						// Есть подменю
						this.getCaller(a.submenu, 'hovertimeout'):
						// Нет подменю
						strAsd)
					) +
				'>' +
				cm_img(a.icon?a.icon:'undefined', ' ') + ' ' + caption +
				(a.submenu?cm_img('page-next.gif', ' ', 'position:absolute;right:0px;vertical-align:middle;'):'') + '</div>';
			}
			x.jq.html(h);
			
			
			/* }}} */
		},
		exec: function (item_element) {		/* Execute action	{{{ */
			item_element = jQuery(item_element);
			var act = item_element.attr('item_id');
			var id = item_element.parent().attr('iuid');
			
			var m = jQuery.cmenu.c[id];
			if (!m) {
				alert('Menu not found');
				return false;
			}
			if (!m.a || !m.a[act]) {
				alert('Action not found');
				return false;
			}
			if (m.type === 'radio') {
				m.set(m.a[act].caption);
				this.render(m);
				return false;
			}
			if (typeof m.a[act].execute === 'function' && !m.a[act].disabled) {
				m.a[act].execute.apply(globals.activeModule, [m.a[act], m, m.p, item_element[0]]);
			}
			/* }}} */
		},
		getMenu: function (acts) {		/* Get menu from global collection	{{{ */
			var t = typeof acts;
			if (t.search(/function|object|undefined/) !== -1) { // Init menu with (un)defined actions
				var id = this.c.length;
				this.c.push({id: id});
				this.c[id] = this.init(id, acts);
				return this.c[id];
			} else { // Select from collection (acts - number or string)
				return this.c[acts];
			}
			/* }}} */
		},
		show: function (menu, parent, oEvent) {			/* Show menu m near parent object p	{{{ */
			Control.enabled = false;
			if (typeof menu !== 'object') {
				menu = this.getMenu(menu);
			}
			if (menu.v && menu.caller === parent) {
				return false;
			}
			if (!this.hideBinded) {
				this.hideBinded = true;
				jQuery(document).bind('click', this.hideAll);
			}
			var prev_caller = menu.caller;
			menu.caller = parent;
			if (menu.sub) {
				this.hideMenu(menu.sub);
			}
			var $parent = jQuery(parent);
			// Если вызвавший меню элемент - элемент меню (то есть показываем подменю)
			// то надо оставить p подсвеченным (класс cmenuItemWithSub);
			// также надо установить родительскому меню ссылку на дочернее, а дочернему - на родителя
			// и еще - если у нашего меню уже есть подменю - скрыть его
			if ($parent.hasClass('cmenuItem') && !$parent.hasClass('cmenuItemWithSub')) {
				$parent.addClass('cmenuItemWithSub');
				var parentMenu = jQuery.cmenu.getMenu(parseInt(jQuery(parent.parentNode).attr('iuid'), 10));
				if (parentMenu) {
					if (parentMenu.sub) {
						if (parentMenu.sub === menu) {
							jQuery(prev_caller).removeClass('cmenuItemWithSub');
						} else {
							jQuery.cmenu.hideMenu(parentMenu.sub);
							if (jQuery.cmenu.to && clearTimeout(jQuery.cmenu.to)) {
								delete jQuery.cmenu.to;
							}
						}
					}
					parentMenu.sub = menu;
					menu.parentMenu = parentMenu;
				}
			}
			
			menu.p = this.getPath(parent);
			menu.parent_item = menu.p[menu.p.length-1].cmenu_item;
			this.render(menu);
			
			if (menu.jq[0].offsetParent !== menu.p[0].offsetParent && menu.p[0].offsetParent) {
				menu.jq.appendTo(menu.p[0].offsetParent);
			}

			// Display menu
			if (menu.jq.css('display') === 'none') {
				menu.jq.show();
			}

			// Calculate menu parameters
			var calculatedOffsetParent = menu.jq[0].offsetParent;
			var calculatedOffsetWidth = menu.jq[0].offsetWidth;
			var calculatedOffsetHeight = menu.jq[0].offsetHeight;
			
			// Calc visible screen bounds (this code is common)
			var width = 0, height = 0;
			if (typeof(window.innerWidth) === 'number') {// не msie
				width = window.innerWidth;
				height = window.innerHeight;
			} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
				width = document.documentElement.clientWidth;
				height = document.documentElement.clientHeight;
			}
			var sx = 0, sy = 0;
			if (typeof window.pageYOffset === 'number') {
				sx = window.pageXOffset;
				sy = window.pageYOffset;
			} else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
				sx = document.body.scrollLeft;
				sy = document.body.scrollTop;
			} else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
				sx = document.documentElement.scrollLeft;
				sy = document.documentElement.scrollTop;
			}

			var winHeight = height + sy;
			var winWidth = width + sx;
			
			// Получаем абсолютное смещение элемента, вызвавшего меню (parent)
			// относительно calculatedOffsetParent
			// относительно курсора мыши
			var off = this.getOffset(parent, calculatedOffsetParent);
			var pW = parent.offsetWidth;
			var pH = parent.offsetHeight;

			if (oEvent) {
				off = { 'x' : oEvent.pageX, 'y' : oEvent.pageY };
				pW = 0; pH = 0;
			}

			// Очень важный момент - в какую сторону показывать меню (по горизонтали)
			// Задача - если есть место чтобы показать справа от объекта
			//	- показываем справа: left = off.x+parent.offsetWidth
			// если места справа нет
			// - показываем слева: left = off.x-calculatedOffsetWidth
			// Наличие места вычисляем исходя из
			// - размеров блока меню (calculatedOffsetWidth)
			// - смещению (off.x) родительского элемента относительно общего offsetParent-а (calculatedOffsetParent)
			// - ширине экрана (winWidth)
			menu.jq.css('left', calculatedOffsetParent.offsetLeft + off.x + pW + calculatedOffsetWidth > winWidth?off.x - calculatedOffsetWidth:off.x + pW);
			// Еще один очень важный момент - в какую сторону показывать меню (по вертикали)
			// Задача - если есть место чтобы показать снизу от объекта
			//	- показываем снизу: top = off.y-2
			// если места снизу нет 
			// - показываем сверху: top = off.y-calculatedOffsetHeight+parent.offsetHeight+4
			// Наличие места вычисляем исходя из
			// - размеров блока меню (calculatedOffsetHeight)
			// - смещению (off.y) родительского элемента относительно общего offsetParent-а (calculatedOffsetParent)
			// - высоте экрана (winHeight)

			var top_pos = calculatedOffsetParent.offsetTop + off.y + calculatedOffsetHeight > winHeight?off.y - calculatedOffsetHeight + pH + 4:off.y - 2;

			if (calculatedOffsetParent.tagName.toLowerCase() == 'body' || top_pos < 0) {
				top_pos = 0;
			}

			menu.jq.css('top', top_pos);
			// Устанавливаем флаг видимости меню
			menu.v = true;
			
			var id, act, elem;
			menu.jq.find('div.cmenuItem').each(function() {
				elem = jQuery(this);
				act = elem.attr('item_id');
				id = elem.parent().attr('iuid');
				menu = jQuery.cmenu.c[id];
				if (menu.a[act].onRender && typeof menu.a[act].onRender === 'function') {
					menu.a[act].onRender(this);
				}
			});

			if (menu.jq.find('div.cmenuItem').length == 0) {
				menu.jq.hide();
			}
		},
		getPath: function (el) {		/* Menu calling stack	{{{ */
			var p = [], jel;
			while (el) {
				jel = jQuery(el);
				if (!jel.hasClass('cmenuItem')) {
					p.push(el);
					break;
				}
				el.cmenu = jQuery.cmenu.getMenu(parseInt(jel.parent().attr('iuid'), 10));
				el.cmenu_item = el.cmenu.a[jel.attr('item_id')];
				p.push(el);
				
				// Go to parent
				el = el.cmenu.caller;
			}
			return p.reverse();
			/* }}} */
		},
		hideAll: function () {			/* Hide all displayed menus	{{{ */
			if(ContextMenu.allowControlEnable) Control.enabled = true;
			// Если блокировано сокрытие меню - выйти
			if (jQuery.cmenu.lockHiding) {
				return false;
			}
			// Отбиндить сокрытие всех меню по клику
			jQuery(document).unbind('click', jQuery.cmenu.hideAll);
			jQuery.cmenu.hideBinded = false;
			// Скрыть менюшки
			var len = jQuery.cmenu.c.length;
			for (var i = 0; i < len; i++) {
				jQuery.cmenu.hideMenu(jQuery.cmenu.c[i]);
			}
			/* }}} */
		},
		hideMenu: function (m) {		/* {{{ */
			if (!m || !m.v) {
				return;
			}
			m.v = false;
			this.hideMenu(m.sub);
			if (m.caller) {
				jQuery(m.caller).removeClass('cmenuItemWithSub');
			}
			m.jq.remove();
			/* }}} */
		},
		getCaller: function (id, event) {/* Compile menu-caller-string (inline script attributes)	{{{ */
			var m = false;
			if (typeof id === 'object') {
				m = true;
				id = id.id;
			}
			if (typeof id !== 'number') {
				//console.error('jQuery.cmenu.getCaller - unexpected type of first parameter ('+(typeof id)+'), expecting number');
				return '';
			}
			switch (event) {
			case 'click':
			default:
				return 'onclick="jQuery.cmenu.show(' + id + ',this);jQuery.cmenu.lockHiding=true;" onmouseout="jQuery.cmenu.lockHiding=false;"';
			case 'hovertimeout':
				return 'onmouseover="jQuery(this).addClass(\'cmenuItemHover\');var t=this;jQuery.cmenu.to=setTimeout(function(){jQuery.cmenu.show(' + id + ',t);jQuery.cmenu.lockHiding=true;},200);" onmouseout="jQuery(this).removeClass(\'cmenuItemHover\');clearTimeout(jQuery.cmenu.to);jQuery.cmenu.lockHiding=false;"';
					// (m?'m=jQuery.cmenu.getMenu('+id+');m&&m.sub&&$.cmenu.hideMenu(m.sub);':'')
			}
			
			/* }}} */
		},
		getOffset: function (el, stop) {/* Offset el against stop	{{{ */
			if (el.offsetParent && el.offsetParent !== stop) {
				var of = this.getOffset(el.offsetParent, stop);
				of.x += el.offsetLeft;
				of.y += el.offsetTop;
				return of;
			} else {
				return {
					x: el.offsetLeft,
					y: el.offsetTop
				};
			}
			/* }}} */
		}
	};
	
	jQuery.fn.bindMenu = function (event, menu) {/* jQuery-plugin for menu binding	{{{ */
		if (arguments.length === 1) {
			menu = event;
			event = 'click';
		}
		if (!menu.jq) {
			menu = jQuery.cmenu.getMenu(menu);
		}
		return this.each(function () {
			jQuery(this).bind(event, function (oEvent) {
				jQuery.cmenu.lockHiding = true;
				jQuery.cmenu.show(menu, this, oEvent);
			})
			.bind('mouseout', function () {
				jQuery.cmenu.lockHiding = false;
			});
		});
		/* }}} */
	};
	
	

})(jQuery);
/* :folding=explicit:*/
