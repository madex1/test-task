function changeParentControl(_id, _module, _options, hierarchy_types, _mode) {
	var _self			 = this;
	var id				 = _id;
	var hierarchy_types	 = (hierarchy_types instanceof Array) ? hierarchy_types : [hierarchy_types];
	var htypesStr		 = (hierarchy_types instanceof Array) ? '&hierarchy_types=' + hierarchy_types.join(',') : '';
	var module			 = _module || null;
	var container		 = null;
	var textInput		 = null;
	var treeButton		 = null;
	var pagesList		 = null;
	var suggestDiv		 = null;
	var suggestItems	 = null;
	var suggestIndex	 = null;
	var mouseX			 = 0;
	var mouseY			 = 0;
	if (!_options) {
		var _options = {};
	}
	var iconBase	 = _options['iconsPath'] || '/images/cms/admin/mac/tree/';
	var fadeClrStart = _options['fadeColorStart'] || [255, 0, 0];
	var fadeClrEnd	 = _options['fadeColorEnd'] || [255, 255, 255];
	var inputName	 = _options['inputName'] || ('changeParentInput' + id);
	var noImages	 = _options['noImages'] || false;
	var pagesCache	 = {};
	var virtualsMode = _mode;
	var popUpCallback = _mode ? '' : '&callback=changeParentControlsList["' + id + '"].moveItem';
	
	var init = function() {
		if (!window.changeParentControlsList) {
			window.changeParentControlsList = {};
		}
		window.changeParentControlsList[id] = _self;
		container = document.getElementById('changeParentInput' + id);
		if (!container) {
			alert('Change parent container #' + id + ' not found');
			return;
		}

		var input = document.createElement('input');
		input.type  = 'hidden';
		input.name  = inputName;
		container.parentNode.insertBefore(input, container);

		pagesList = document.createElement('ul');
		container.appendChild(pagesList);
		textInput = document.createElement('input');
		container.appendChild(textInput);
		treeButton = noImages ? document.createElement('input') : document.createElement('img');
		container.appendChild(treeButton);

		textInput.type  = 'text';

		if (noImages) {
			treeButton.type = 'button';
			treeButton.value = '?';
		} else {
			treeButton.src = "/images/cms/admin/mac/tree.png" ;
			treeButton.height = "18";
		}
		treeButton.className = 'treeButton';

		treeButton.onclick = function() {
			jQuery.openPopupLayer({
				name	 : "Sitetree",
				title	 : "Выбор страницы",
				width	 : 620,
				height	 : 335,
				url		 : "/styles/common/js/parents.html?id=" + id + (module ? "&module=" + module : "" ) + htypesStr + (window.lang_id ? "&lang_id=" + window.lang_id : "") + popUpCallback
			});
		};

		pagesList.className = 'pageslist';

		textInput.onkeypress = function(e) {
			var keyCode = e ? e.keyCode : window.event.keyCode;
			if (keyCode == 13) return false;
		};

		textInput.onkeyup = function(e) {
			var keyCode = e ? e.keyCode : window.event.keyCode;
			switch(keyCode) {
				case 38 : // Arrow up
					{
						if (suggestItems.length && (suggestIndex > 0 || suggestIndex == null )) {
							highlightSuggestItem((suggestIndex === null) ? (suggestItems.length - 1) : (suggestIndex - 1) );
						}
						break;
					}
				case 40 : // Arrow down
					{
						if (suggestItems.length && (suggestIndex < (suggestItems.length - 1) || suggestIndex == null )) {
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
			if (suggestDiv) {
				if (mouseX < parseInt(suggestDiv.style.left) ||
					mouseX > (parseInt(suggestDiv.style.left) + parseInt(suggestDiv.offsetWidth)) ||
					mouseY < parseInt(suggestDiv.style.top) ||
					mouseY > (parseInt(suggestDiv.style.top) + parseInt(suggestDiv.offsetHeight)) ) {
						hideSuggest();
				}
			}
		};
	};

	this.loadSuggestItems = function(searchText) {
		jQuery.ajax({
			url : "/admin/content/load_tree_node.xml?limit&domain_id[]=" + (window.domain_id ? window.domain_id : '1') + htypesStr + (window.lang_id ? "&lang_id=" + window.lang_id : "") + "&search-all-text[]=" + encodeURIComponent(searchText),
			type : "get",
			complete : function(r,t) {
				_self.updateSuggestItems(r);
			}
		});
	};

	this.updateSuggestItems = function(response) {
		suggestIndex = null;
		suggestItems = response.responseXML.getElementsByTagName('page');
		if (!suggestItems.length) {
			return;
		}
		var tmp = [];
		for (var i = 0; i < suggestItems.length; i++) {
			if (pagesCache[suggestItems[i].getAttribute('id')]) {
				continue;
			}
			tmp[tmp.length] = suggestItems[i];
		}
		suggestItems = tmp;
		var ul = null;
		if (!suggestDiv) {
			suggestDiv = document.createElement('div');
			suggestDiv.className = 'symlinkAutosuggest';
			var pos = jQuery(textInput).offset();
			suggestDiv.style.position = 'absolute';
			suggestDiv.style.zIndex = 1050;
			suggestDiv.style.width = textInput.clientWidth + "px";
			suggestDiv.style.top = (pos.top + textInput.offsetHeight) + "px";
			suggestDiv.style.left = pos.left + "px";
			ul = document.createElement('ul');
			suggestDiv.appendChild(ul);
			document.body.appendChild(suggestDiv);
		}
		showSuggest();
		jQuery(document).mousemove(documentMouseMoveHandler);
		ul = suggestDiv.firstChild;
		while (ul.firstChild) {
			ul.removeChild(ul.firstChild);
		}
		for (i = 0; i < suggestItems.length; i++) {
			if (pagesCache[suggestItems[i].getAttribute('id')]) {
				continue;
			}
			var name = getElementText(suggestItems[i].getElementsByTagName('name')[0]);
			var type = getElementText(suggestItems[i].getElementsByTagName('basetype')[0]);
			var link = suggestItems[i].getAttribute('link');
			var li	 = document.createElement('li');
			var span = document.createElement('span');
			var a	 = document.createElement('a');
			li.title = name;
			if (name.length > 20) {
				name = name.substr(0, 20) + '...';
			}
			li.appendChild(document.createTextNode(name));
			li.appendChild(span);
			li.appendChild(document.createElement('br'));
			li.appendChild(a);
			span.appendChild(document.createTextNode(' (' + type + ')'));
			a.href = link;
			a.target = "_blank";
			if (link.length > 170) {
				link = link.substr(0, 170) + '...';
			}
			a.appendChild(document.createTextNode(link));
			li.onmouseover = function() { highlightSuggestItem(this.suggestIndex);};
			li.onclick = function() {
				addHighlitedItem();
				hideSuggest();
				};
			li.suggestIndex = i;
			ul.appendChild(li);
		}
	};

	this.doSearch = function() {
		var text = textInput.value;
		_self.loadSuggestItems(text);
	};

	var highlightSuggestItem = function(itemIndex) {
		if(suggestDiv.style.display != 'none') {
			var list = suggestDiv.firstChild;
			var oldHighlited = list.childNodes.item(suggestIndex);
			if (oldHighlited) {
				oldHighlited.className = '';
			}
			list.childNodes.item(itemIndex).className = 'active';
			suggestIndex = itemIndex;
		}
	};

	var addHighlitedItem = function() {
		if(suggestDiv && suggestDiv.style.display != 'none' && suggestIndex !== null) {
			var id = suggestItems[suggestIndex].getAttribute('id');
			if (virtualsMode) {
				_self.addItem(id);
			} else {
				_self.moveItem(id);
			}
		}
	};

	this.addItem = function(pageId) {
		jQuery.ajax({
			url : "/admin/content/tree_copy_element.json",
			type : "get",
			dataType : "json",
			data : {
				element : window.page_id,
				rel : pageId,
				copyAll : 1,
				return_copies : 1,
				clone_mode : 0
			},
			success : function(response){
				pageCopy = response.data.page.copies.copy[0];
				linkText = document.createElement('span');
				for (i in pageCopy.parents.item) {
					parentItem = pageCopy.parents.item[i];
					var link = document.createElement('a');
					link.href = "/admin/" + parentItem.module + "/" + parentItem.method + "/";
					link.target = "_blank";
					link.className = "tree_link";
					link.onclick = function() {
						return treeLink(parentItem.settingsKey, parentItem.treeLink);
					};
					link.title = parentItem.url;
					link.appendChild(document.createTextNode(parentItem.name));
					linkText.appendChild(link);
					linkText.appendChild(document.createTextNode(" / "));
				}
				var link = document.createElement('a');
				link.href = pageCopy['edit-link'];
				link.title = pageCopy.url;
				link.appendChild(document.createTextNode(pageCopy.name));
				linkText.appendChild(link);

				_self.loadItem(pageCopy.id, linkText, pageCopy.basetype);
			}
		});
	};

	this.moveItem = function(pageId) {
		if (window.page_id == pageId) {
			return false;
		}
		jQuery.ajax({
			url : "/admin/content/tree_move_element.json",
			type : "get",
			dataType : "json",
			data : {
				element : window.page_id,
				rel : pageId,
				return_copies : 1
			},
			success : function(response){
				pageCopy = response.data.page.copies.copy[0];
				linkText = document.createElement('span');
				for (i in pageCopy.parents.item) {
					parentItem = pageCopy.parents.item[i];
					var link = document.createElement('a');
					link.href = "/admin/" + parentItem.module + "/" + parentItem.method + "/";
					link.target = "_blank";
					link.className = "tree_link";
					link.onclick = function() {
						return treeLink(parentItem.settingsKey, parentItem.treeLink);
					};
					link.title = parentItem.url;
					link.appendChild(document.createTextNode(parentItem.name));
					linkText.appendChild(link);
					linkText.appendChild(document.createTextNode(" / "));
				}
				var link = document.createElement('span');
				link.title = pageCopy.url;
				link.appendChild(document.createTextNode(pageCopy.name));
				linkText.appendChild(link);

				_self.loadItem(pageCopy.id, linkText, pageCopy.basetype);
			}
		});
	};

	this.loadItem = function(pageId, pageParents, basetype) {
		if ((pagesCache[pageId] !== undefined) && virtualsMode) {
			return;
		}
		var page  = document.createElement('li');

		if (virtualsMode) {
			var btn   = document.createElement('a');
			if (noImages) {
				btn.appendChild(document.createTextNode('[x]'));
			} else {
				var btnImage = document.createElement('img');
				btnImage.src = iconBase + 'symlink_delete.png';
				btnImage.alt = 'delete';
				btn.appendChild(btnImage);
			}
			btn.href = 'javascript:void(0);';
			btn.className = 'button';
			btn.onclick = function() {
				jQuery.ajax({
					url : "/admin/content/tree_delete_element.xml",
					type : "get",
					dataType : "xml",
					data : {
						element : pageId,
						childs : 1,
						allow : true
					},
					context : this,
					success : function(){
						pagesList.removeChild(this.parentNode);
						delete pagesCache[pageId];
					}
				});
			};
			page.appendChild(btn);
		}
		if (!noImages) {
			var icon = document.createElement('img');
			icon.src = iconBase + 'ico_' + basetype.module + '_' + basetype.method + '.png';
			page.appendChild(icon);
		}

		var parentsText = pageParents.innerText || pageParents.textContent;
		if (parentsText.length > 170) { // Длина слишком большая, не влезаем в отведенную область
			newTitle = pageParents.innerText; // Сразу запомним полный исходный текст до сокращений
			lastChildText = pageParents.lastChild.innerText || pageParents.lastChild.textContent;
			if (lastChildText.length > 100) { // Начнем с проверки длины названия страницы
				lastChildText = lastChildText.substr(0, 96) + '...';
				if (pageParents.lastChild.innerText) {
					pageParents.lastChild.innerText = lastChildText;
				} else {
					pageParents.lastChild.textContent = lastChildText;
				}
				parentsText = pageParents.innerText || pageParents.textContent;
			}
			if (parentsText.length > 170) { // Все равно не влезаем
				// Пока не влезем в отведенную область, будем удалять родителей от центра, кроме последнего
				while (parentsText.length > 170 && pageParents.childNodes.length > 3) { // (a, #text, span должны остаться)
					dropLink = parseInt(pageParents.childNodes.length / 2);
					dropLink = dropLink - (dropLink%2); // Начинаем постоянно с четного элемента.
					pageParents.removeChild(pageParents.childNodes[dropLink]);
					pageParents.removeChild(pageParents.childNodes[(dropLink - 1)]);
					parentsText = pageParents.innerText || pageParents.textContent;
				}

				var link = document.createElement('span');
				link.title = newTitle;
				if (parentsText.length <= 170) { // В результате удаления поместились, заменяем на признак сокращенного пути. Всех мы не удалили, т.к. стоит ограничение в цикле
					dropLink = parseInt(pageParents.childNodes.length / 2);
					dropLink = dropLink - (1 - dropLink%2); // А вот здесь нам как раз нужен нечетный элемент
					link.appendChild(document.createTextNode(' / ... /'));
				} else { // Мы все равно не помещаемся, удаляем последнего родителя
					pageParents.removeChild(pageParents.childNodes[0]); // После удаления список элементов сдвинется
					dropLink = 0;
					link.appendChild(document.createTextNode('... /'));
				}
				pageParents.replaceChild(link, pageParents.childNodes[dropLink]);
			}
		}

		page.appendChild(pageParents);
		if (!virtualsMode && pagesList.lastChild) {
			pagesList.removeChild(pagesList.lastChild);
		}
		pagesList.appendChild(page);
		page.style.backgroundColor = makeHexRgb(fadeClrStart);
		page.startColor = fadeClrStart;
		page.endColor = fadeClrEnd;
		page.pname = pageParents;
		page.fade = fader;
		setTimeout(
			function(){
				page.fade();
			},
			2000
		);
		pagesCache[pageId] = true;
		if (jQuery('#eip_page').length) {
			frameElement.height = (jQuery('#eip_page').height() > 500) ? 500 : jQuery('#eip_page').height();
		}
	};

	var fader = function() {
		if (this.fadeColor == undefined) {
			this.fadeColor = [
				this.startColor[0],
				this.startColor[1],
				this.startColor[2]
			];
		}
		if(Math.round(this.fadeColor[0] + this.fadeColor[1] + this.fadeColor[2]) == Math.round(this.endColor[0] + this.endColor[1] + this.endColor[2])) {
			return;
		}
		this.fadeColor[0] += (this.endColor[0] - this.startColor[0]) / 50;
		this.fadeColor[1] += (this.endColor[1] - this.startColor[1]) / 50;
		this.fadeColor[2] += (this.endColor[2] - this.startColor[2]) / 50;
		this.style.backgroundColor = makeHexRgb(this.fadeColor);
		var _p = this;
		setTimeout(
			function(){
				_p.fade();
			},
			20
		);
	};

	var showSuggest = function() {
		if (suggestDiv) {
			var pos = jQuery(textInput).offset();
			suggestDiv.style.width = textInput.clientWidth;
			suggestDiv.style.top = pos.top + textInput.offsetHeight;
			suggestDiv.style.left = pos.left;
			suggestDiv.style.display = '';
		}
	};

	var hideSuggest = function() {
		if (suggestDiv && suggestDiv.style.display != 'none') {
			suggestDiv.style.display = 'none';
			jQuery(document).unbind('mousemove', documentMouseMoveHandler);
		}
	};

	var documentMouseMoveHandler = function(e) {
		if (!e) {
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
