function fileControl(name, options) {
	var _self = this;
	var container = document.getElementById('fileControlContainer_' + name);
	var select = null;
	var selectize = null;
	var inputName = options.inputName || name;
	var imagesOnly = options.imagesOnly || false;
	var videosOnly = options.videosOnly || false;
	var folderHash = options.folderHash;
	var fileHash = options.fileHash;
	var lang = options.lang || 'ru';
	var fm = options.fm || 'flash';
	var cwd = '.';
	var defaultCwd = '.';
	var loaded = false;
	var onGetFileFunction = (typeof options.onGetFileFunction == 'string') ? options.onGetFileFunction : null;

	var construct = function() {
		select = document.createElement('select');
		container.appendChild(select);

		select.onmouseover = function() {
			if (!loaded) loadListing();
		};

		window['fileControlSelect_' + name] = select;

		select.id = 'fileControlSelect_' + name;
		select.name = inputName;
		select.control = _self;
		select.className = 'fileControlSelect layout-col-control default';

		if ((navigator.userAgent.toLowerCase().indexOf("firefox") != -1) || (navigator.userAgent.toLowerCase().indexOf("chrome") != -1)) {
			select.addEventListener("drop", doDrop, false);
			select.addEventListener("dragexit", dragExit, false);
			select.addEventListener("dragover", dragExit, false);
			jQuery(select).addClass('dragNDrop');
		}

		var option = document.createElement('option');
		option.innerHTML = '';
		option.value = '';
		option.selected = true;
		select.appendChild(option);

		var btm = [
			'<div class="layout-col-icon">',
			'<a class="icon-action" href="javascript:void(0);">',
			'<i class="small-ico i-choose"></i>',
			'</a>',
			'</div>'
		].join('');

		btm = $(btm);

		$('a', btm).bind('click', function() {
			var functionName = 'show' + fm + 'FileBrowser';
			eval(functionName + '(select.id, defaultCwd, imagesOnly, videosOnly, folderHash, fileHash, lang, onGetFileFunction)');
		});

		$('#fileControlContainer_' + name).append(btm);
		selectize = $('select#fileControlSelect_' + name).selectize({
			allowEmptyOption: true,
			create: false,
			hideSelected: true
		});
		selectize = selectize[0].selectize;
	};

	function doDrop(event) {
		dragExit(event);

		var dt = event.dataTransfer;
		var files = dt.files;

		var file = files[0];
		jQuery.ajax({
			url: "/admin/data/uploadfile/?filename=" + file.name + "&folder=" + base64encode(cwd.substr(1)) + (imagesOnly ? "&showOnlyImages=1" : "" ) + (videosOnly ? "&showOnlyVideos=1" : ""),
			type: "POST",
			processData: false,
			data: file,
			complete: function(r, t) {
				_self.updateItem(r);
			}
		});
	}

	function dragExit(event) {
		event.stopPropagation();
		event.preventDefault();
	}

	var showflashFileBrowser = function(selectId, folder, imageOnly, videoOnly, folder_hash, file_hash, lang) {
		var qs = 'id=' + selectId;
		var index = 0;
		var file = cwd.replace(/^\.\//, "/") + ((index = select.value.lastIndexOf('/')) != -1 ? select.value.substr(index) : select.value );
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
		jQuery.openPopupLayer({
			name: "Filemanager",
			title: getLabel('js-file-manager'),
			width  : 1200,
			height : 600,
			url: "/styles/common/other/filebrowser/umifilebrowser.html?" + qs
		});
	};

	var showelfinderFileBrowser = function(selectId, folder, imageOnly, videoOnly, folder_hash, file_hash, lang, onGetFileFunction) {
		var qs = 'id=' + selectId;
		var index = 0;
		var file = cwd.replace(/^\.\//, "/") + ((index = select.value.lastIndexOf('/')) != -1 ? select.value.substr(index) : select.value );
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

		if (onGetFileFunction) {
			qs = qs + '&onGetFile=' + onGetFileFunction;
		}
		$.openPopupLayer({
			name: "Filemanager",
			title: getLabel('js-file-manager'),
			width  : 1200,
			height : 600,
			url: "/styles/common/other/elfinder/umifilebrowser.html?" + qs
		});

		var filemanager = jQuery('div#popupLayer_Filemanager div.popupBody');
		if (!filemanager.length) {
			filemanager = jQuery(window.parent.document.getElementById('popupLayer_Filemanager')).find('div.popupBody');
		}

		var options = '<div id="watermark_wrapper"><label for="add_watermark">';
		options += getLabel('js-water-mark');
		options += '</label><input type="checkbox" name="add_watermark" id="add_watermark"/>';
		options += '<label for="remember_last_folder">';
		options += getLabel('js-remember-last-dir');
		options += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"';

		if (getCookie('remember_last_folder', true)) options += 'checked="checked"';
		options += '/></div>';

		filemanager.append(options);
	};

	this.clearItems = function() {
		var value = cwd + '/';
		selectize.lock();
		selectize.clear();
		selectize.clearOptions();
		selectize.addOption({text:'/',value:value});
		selectize.addItem(value,true);
		selectize.unlock();
		loaded = false;
	};

	var loadListing = function() {
		loaded = true;
		jQuery.ajax({
			url: "/admin/data/getfilelist/?folder=" + base64encode(cwd.substr(1)) + (imagesOnly ? "&showOnlyImages=1" : "" ) + (videosOnly ? "&showOnlyVideos=1" : "" ),
			type: "get",
			complete: function(r, t) {
				_self.updateItems(r);
			}
		});

	};

	this.updateItems = function(response) {
		if (!response.responseXML.getElementsByTagName('empty').length) {
			var files = response.responseXML.getElementsByTagName('file');
			if (!select.options.length) {
				this.add(null, true);
			}
			for (var i = 0; i < files.length; i++) {
				this.add(files[i].getAttribute('name'));
			}
			if (jQuery.browser.msie) {
				var d = select.style.display;
				select.style.display = 'none';
				select.style.display = d;
			}
		} else {
			var option = document.createElement('option');
			option.value = '';
			option.disabled = 'disable';
			option.appendChild(document.createTextNode(getLabel('js-files-use_search')));
			select.appendChild(option);
		}
	};

	this.setFolder = function(name, isDefault) {
		if (name.indexOf('./') !== 0) {
			name = '.' + name;
		}

		// delete ending slash
		var re = new RegExp('[\/]+$', 'g');
		name = (name + '').replace(re, '');

		if (cwd != name) {
			cwd = name;
			this.clearItems();
		}
		if (isDefault != undefined && isDefault) {
			defaultCwd = name;
		}
	};

	this.add = function(name, selected) {
		if (name && !name.length) return;
		if (!name) name = '';
		if (name.lastIndexOf("/") != -1) {
			this.setFolder(name.substr(0, name.lastIndexOf("/")));
			name = name.substr(name.lastIndexOf("/") + 1);
		}

		var value = ((cwd.indexOf("./") != 0) ? '.' : '') + (cwd + '/' + name);
		selectize.addOption({text: name, value: value});
		selectize.addItem(value, true);
	};

	this.updateItem = function(response) {
		var files = response.responseXML.getElementsByTagName('file');
		if (!select.options.length) {
			this.add(null, true);
		}
		for (var i = 0; i < files.length; i++) {
			this.add(files[i].getAttribute('name'), true);
		}
		if (jQuery.browser.msie) {
			var d = select.style.display;
			select.style.display = 'none';
			select.style.display = d;
		}
	};

	construct();
}
