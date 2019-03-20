function symlinkControl(_id, _module, _types, _options, hierarchy_types, _mode) {
	hierarchy_types  = (hierarchy_types instanceof Array) ? hierarchy_types : [hierarchy_types];
	var _self      = this;
	var id         = _id;
	var types      = (_types instanceof Array) ? _types : [_types];
	var typesStr   = (types) ? '&hierarchy_types=' + types.join('-') : '';
	var htypesStr   = (hierarchy_types instanceof Array) ? '&hierarchy_types=' + hierarchy_types.join(',') : '';
	var module     = _module || null;
	var container  = null;
	var textInput  = null;
	var treeButton = null;
	var pagesList  = null;
	var suggestDiv = null;
	var suggestItems = null;
	var suggestIndex = null;
	var mouseX       = 0;
	var mouseY       = 0;
	if(!_options) _options = {};
	/*
	 * Описание опций:
	 * iconsPath	   - корневая директория с иконками
	 * fadeColorStart  - цвет начала анимации исчезновения элемента
	 * fadeColorEnd	   - цвет конца анимации исчезновения элемента
	 * inputName	   - имя поля поиска элементов
	 * noImages		   - Не использовать изображения для кнопок
	 * treeBaseURL	   - URL страницы, на которой будет отрисовано дерево
	 * rootId		   - ID корневого элемента, от которой может быть построено дерево
	 * popupTitle	   - Заголовок всплывающего (popup) окна выбора элемента
	 * showSuggestType - Отображать тип элементов при их поиске
	 * cutSuggestNames - Обрезать названия найденных элементов
	 * suggestDivWidth - Ширина блока, в котором отображаются найденные элементы
	 */
	var iconBase		= _options['iconsPath']      || '/images/cms/admin/mac/tree/';
	var fadeClrStart	= _options['fadeColorStart'] || [255,   0,   0];
	var fadeClrEnd		= _options['fadeColorEnd']   || [255, 255, 255];
	var inputName		= _options['inputName']      || ('symlinkInput' + id);
	var noImages		= _options['noImages']       || false;
	var treeBaseURL		= _options['treeURL'] || "/styles/common/js/tree.html";
	var rootId			= _options['rootId'];
	var popupTitle		= _options['popupTitle'] || getLabel("js-cms-eip-symlink-choose-element");
	var showSuggestType = typeof(_options['showSuggestType']) === 'undefined' ? true : _options['showSuggestType'];
	var cutSuggestNames	= typeof(_options['cutSuggestNames']) === 'undefined' ? true : _options['cutSuggestNames'];
	var suggestDivWidth	= parseInt(_options['suggestDivWidth']);
	var pagesCache   = {};
	var popupCallback = (_mode ? "&callback=symlinkControlsList." + id + ".onlyOne":"");
	
	var init = function() {
		if(!window.symlinkControlsList) window.symlinkControlsList = {};
		window.symlinkControlsList[id] = _self;
		container = document.getElementById('symlinkInput' + id);
		if(!container) {
			alert('Symlink container #' + id + ' not found');
			return;
		}

		var input = document.createElement('input');
		input.type  = 'hidden';
		input.name  = inputName;
		container.parentNode.insertBefore(input, container);

		pagesList  = document.createElement('ul');
		container.appendChild(pagesList);
		var bottomContainer = document.createElement('div');
		container.appendChild(bottomContainer);
		textInput = document.createElement('input');
		textInput.setAttribute('placeholder', getLabel('js-cms-eip-symlink-search'));
		bottomContainer.className = 'pick-element';
		bottomContainer.appendChild(textInput);
		var treeIconWidth  = 18,
			extraSpace	   = 28;
		textInput.style.width = bottomContainer.parentNode.offsetWidth - (treeIconWidth + extraSpace) + 'px';
		textInput.style.minWidth = bottomContainer.parentNode.offsetWidth - (treeIconWidth + extraSpace) + 'px';
		treeButton = noImages ? document.createElement('input') : document.createElement('img');
		bottomContainer.appendChild(treeButton);

		textInput.type  = 'text';
		
		if(noImages) {
			treeButton.type = 'button';
			treeButton.value = '╘';
		} else {
			treeButton.src    = "/images/cms/admin/mac/tree.png" ;
			treeButton.height = "18";
		}
		treeButton.className = 'treeButton';

		treeButton.onclick = function() {			
			jQuery.openPopupLayer({
				name   : "Sitetree",
				title  : popupTitle,
				width  : 620,
				height : 335,
				url    : treeBaseURL + "?id=" + id + (module ? "&module=" + module : "" ) + 
						 htypesStr + (window.lang_id ? "&lang_id=" + window.lang_id : "") + 
						 (rootId ? "&root_id=" + rootId : "") + popupCallback
			});
		};

		pagesList.className = 'pageslist';

		textInput.onkeypress = function(e) {
			var keyCode = e ? e.keyCode : window.event.keyCode;
			if(keyCode == 13) return false;
		};

		textInput.onkeyup = function(e) {
			var keyCode = e ? e.keyCode : window.event.keyCode;
			switch(keyCode) {
				case 38 : // Arrow up
					{
						if(suggestItems.length && (suggestIndex > 0 || suggestIndex == null )) {
							highlightSuggestItem((suggestIndex === null) ? (suggestItems.length - 1) : (suggestIndex - 1) );
						}
						break;
					}
				case 40 : // Arrow down
					{
						if(suggestItems.length && (suggestIndex < (suggestItems.length - 1) || suggestIndex == null )) {
							highlightSuggestItem((suggestIndex === null) ? 0 : (suggestIndex + 1) );
						}
						break;
					}
				case 13 : // Enter
					{
						addHighlitedItem();
						hideSuggest();
						return false;
						break;
					}
				case 27 :
					{
						hideSuggest();
						break;
					}
				default :
					{

						_self.doSearch();
					}
			}
		};
		textInput.onblur  = function() {
					if(suggestDiv) {
						if(mouseX < parseInt(suggestDiv.style.left) ||
						   mouseX > (parseInt(suggestDiv.style.left) + parseInt(suggestDiv.offsetWidth)) ||
						   mouseY < parseInt(suggestDiv.style.top) ||
						   mouseY > (parseInt(suggestDiv.style.top) + parseInt(suggestDiv.offsetHeight)) )
						 {
							hideSuggest();
						 }
					}
				}
	};

	this.loadItems = function(searchText) {
		jQuery.ajax({
			url      : "/admin/content/load_tree_node.xml?limit&domain_id[]=" + 
					   (window.domain_id ? window.domain_id : '1') + typesStr + 
					   (window.lang_id ? "&lang_id=" + window.lang_id : "") + 
					   (rootId ? "&rel=" + rootId : "") +
					   "&search-all-text[]=" + encodeURIComponent(searchText),
			type     : "get",
			complete : function(r,t) { _self.updateItems(r); } 
		});
	};

	this.onlyOne = function(pageId, name, href, basetype) {
		jQuery.closePopupLayer("Sitetree");
		if (confirm(getLabel('js-island-change-symlink-warning'))) {
			jQuery('a.button', pagesList).click();
			_self.addItem(pageId, name, basetype, href);
			jQuery('form input[name="save-mode"]:first').click();
		}
	}

	this.updateItems = function(response) {
		var eip_mode = (jQuery('html.u-eip').length > 0);
		var elements = null;
		suggestIndex = null;
		
		elements = response.responseXML.getElementsByTagName('page');
		if (!elements.length) {
			return;
		}
		
		suggestItems = elements;
		var tmp = [];
		for(var i=0; i<suggestItems.length; i++) {
			if(pagesCache[suggestItems[i].getAttribute('id')]) continue;
			tmp[tmp.length] = suggestItems[i];
		}
		suggestItems = tmp;
		var ul    = null;
		if(!suggestDiv) {
			suggestDiv = document.createElement('div');
			suggestDiv.className      = 'symlinkAutosuggest';
			var pos = jQuery(textInput).offset();
			suggestDiv.style.position = 'absolute';
			suggestDiv.style.zIndex = 1100;
			
			suggestDiv.style.width  = textInput.clientWidth + "px";
			if (!isNaN(suggestDivWidth)) {
				suggestDiv.style.width  = suggestDivWidth + "px";
			} 
			
			suggestDiv.style.top    = (pos.top + textInput.offsetHeight) + "px";
			suggestDiv.style.left   = pos.left + "px";
			if (eip_mode) {
				suggestDiv.style.backgroundColor = 'white';
				suggestDiv.style.border = '1px solid #ccc';
			}
			ul = document.createElement('ul');
			suggestDiv.appendChild(ul);
			document.body.appendChild(suggestDiv);
		}
		showSuggest();
		jQuery(document).mousemove(documentMouseMoveHandler);
		ul = suggestDiv.firstChild;
		while(ul.firstChild) {
			ul.removeChild(ul.firstChild);
		}
		for(i = 0; i < suggestItems.length; i++) {
			if(pagesCache[suggestItems[i].getAttribute('id')]) continue;
			var name = getElementText(suggestItems[i].getElementsByTagName('name')[0]);
			var type = getElementText(suggestItems[i].getElementsByTagName('basetype')[0]);
			var link =  suggestItems[i].getAttribute('link');
			var li   = document.createElement('li');
			var span = document.createElement('span');
			var a    = document.createElement('a');
			li.title = name;
			
			if (cutSuggestNames) {
				if(name.length > 20) {
					name = name.substr(0, 20) + '...';
				}
			}
			
			if(link.length > 55) link = link.substr(0, 55) + '...';
			li.appendChild(document.createTextNode(name));			
			if (showSuggestType) {
				li.appendChild(span);
			}
			span.appendChild(document.createTextNode(' (' + type + ')'));
			if (!eip_mode) {
				li.appendChild(document.createElement('br'));
				li.appendChild(a);
				a.appendChild(document.createTextNode(link));
				a.href = link;
				a.target = "_blank";
			}
			else {
				span.style.display = 'block';
				li.className = 'symlink-item-delete';
				li.style.padding = '3px';
			}
			li.onmouseover = function() {
				highlightSuggestItem(this.suggestIndex);
			};
			li.onclick     = function() {
				addHighlitedItem();
				hideSuggest();
			};
			li.suggestIndex = i;
			ul.appendChild(li);
		}
	};

	this.doSearch = function() {
		var text = textInput.value;
		_self.loadItems(text);
	};

	var highlightSuggestItem = function(itemIndex) {
		var eip_mode = (jQuery('html.u-eip').length > 0);
		if(suggestDiv.style.display != 'none') {
			var list = suggestDiv.firstChild;
			var oldHighlited = list.childNodes.item(suggestIndex);
			if(oldHighlited) {
				if (eip_mode) oldHighlited.style.backgroundColor = '';
				else oldHighlited.className = '';
			}
			if (eip_mode) list.childNodes.item(itemIndex).style.backgroundColor = '#ceeaf6';
			else list.childNodes.item(itemIndex).className    = 'active';
			suggestIndex = itemIndex;
		}
	};

	var addHighlitedItem = function() {
		if(suggestDiv && suggestDiv.style.display != 'none' && suggestIndex !== null) {
			var id    = suggestItems[suggestIndex].getAttribute('id');
			var name  = getElementText(suggestItems[suggestIndex].getElementsByTagName('name')[0]);
			var aname = suggestItems[suggestIndex].getAttribute('link');
			var type  = suggestItems[suggestIndex].getElementsByTagName('basetype')[0];
			var t     = '';
			var module = (t = type.getAttribute('module')) ? t : '';
			var method = (t = type.getAttribute('method')) ? t : '';
			_self.addItem(id, name, [module,method], aname);
		}
	};

	this.addItem = function(pageId, name, basetype, href) {
		this.delPlaceHolder();
		if(pagesCache[pageId] !== undefined) return;
		var eip_mode = (jQuery('html.u-eip').length > 0);
		var page  = document.createElement('li');
		var text  = document.createElement('span');
		var link  = document.createElement('a');
		var btn   = document.createElement('a');
		var input = document.createElement('input');
		var _self = this;
		input.type  = 'hidden';
		input.name  = inputName;
		input.value = pageId;
		btn.input  = input;
		link.href  = href;
		
		if (noImages) {
			btn.appendChild( document.createTextNode('[x]') );
		}
		else {
			var btnImage = document.createElement('img');
			btnImage.src = iconBase + 'symlink_delete.png';
			btnImage.alt = 'delete';
			if (eip_mode) btnImage.className = 'symlink-item-delete';
			btn.appendChild(btnImage);
		}
		btn.href = 'javascript:void(0);';
		if (eip_mode) {
			btn.style.marginRight = '5px';
		}
		else btn.className = 'button';
		btn.onclick = function() {
						this.input.parentNode.removeChild(this.input);
						pagesList.removeChild(this.parentNode);
						_self.addPlaceHolder();
						delete pagesCache[pageId];
					  };
		text.dataset.basetype = basetype[0] + " " + basetype[1];
		if (eip_mode) {
			text.style.marginLeft = '5px';
		}
		text.appendChild(document.createTextNode(name));
		link.appendChild(document.createTextNode(href));
		
		if(!noImages) {
			var icon  = document.createElement('img');
			icon.src   = iconBase + 'ico_' + basetype[0] + '_' + basetype[1] + '.png';
			page.appendChild(icon);
		}
		page.appendChild(text);
		var iconsWidth = 32,
			extraSpace = 38;
		text.style.maxWidth = pagesList.parentNode.offsetWidth - (iconsWidth + extraSpace) + 'px';
		
		page.appendChild(btn);
		if (eip_mode) {
			delete link;
		} else {
			page.appendChild(link);
		}
		pagesList.appendChild(page);
		container.parentNode.insertBefore(input, container);
		page.style.backgroundColor = makeHexRgb(fadeClrStart);
		page.startColor = fadeClrStart;
		page.endColor   = fadeClrEnd;
		page.pname      = name;
		page.fade		= fader;
		setTimeout(function(){page.fade()}, 2000);
		pagesCache[pageId] = true;
		if (jQuery('#eip_page').length) {
			frameElement.height = (jQuery('#eip_page').height() > 500) ? 500 : jQuery('#eip_page').height();
		}
	};
	
	this.delPlaceHolder = function () {
		if (jQuery('.eip-placeholder', pagesList).length >= 1) {
			jQuery('.eip-placeholder', pagesList).remove();
		}
	};

	this.addPlaceHolder = function (text) {
		var element = document.createElement('li'),
				_text = text || getLabel("js-cms-eip-symlink-no-elements"),
				holderClass = 'eip-placeholder';
		element.className = holderClass;
		element.appendChild(document.createTextNode(_text));

		if (jQuery('li', pagesList).length < 1) {
			pagesList.appendChild(element);
		}
	};

	var fader = function() {
		if(this.fadeColor == undefined) {
			this.fadeColor    = [];
			this.fadeColor[0] = this.startColor[0];
			this.fadeColor[1] = this.startColor[1];
			this.fadeColor[2] = this.startColor[2];
		}
		if(Math.round(this.fadeColor[0] + this.fadeColor[1] + this.fadeColor[2]) ==
		   Math.round(this.endColor[0] + this.endColor[1] + this.endColor[2])) return;
		this.fadeColor[0] += (this.endColor[0] - this.startColor[0]) / 50;
		this.fadeColor[1] += (this.endColor[1] - this.startColor[1]) / 50;
		this.fadeColor[2] += (this.endColor[2] - this.startColor[2]) / 50;
		this.style.backgroundColor = makeHexRgb(this.fadeColor);
		var _p = this;
		setTimeout(function(){_p.fade();}, 20);
	};

	var showSuggest = function() {
		if(suggestDiv) {
			var pos = jQuery(textInput).offset();
			suggestDiv.style.width  = textInput.clientWidth;
			suggestDiv.style.top    = pos.top + textInput.offsetHeight;
			suggestDiv.style.left   = pos.left;
			suggestDiv.style.display = '';
		}
	};

	var hideSuggest = function() {
		if(suggestDiv && suggestDiv.style.display != 'none') {
			suggestDiv.style.display = 'none';
			jQuery(document).unbind('mousemove', documentMouseMoveHandler);
		}
	};

	var documentMouseMoveHandler = function(e) {
		if(!e) {
			mouseX = event.clientX + document.body.scrollLeft;
			mouseY = event.clientY + document.body.scrollTop;
		} else {
			mouseX = e.pageX;
			mouseY = e.pageY;
		}
		return true;
	};

	var getElementText = function(element) {		
		return (element.firstChild && element.firstChild.nodeType == 3) ? element.firstChild.nodeValue : element.nodeValue;
	};

	// initialize
	init();
};
var hex = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
function makeHexRgb(rgb) {
	var result = '';
	for(var i = 0; i < 3; i++) {
		result = result + hex[Math.floor(rgb[i] / 16)] + hex[Math.floor(rgb[i] % 16)];
	}
	return '#' + result;
}
