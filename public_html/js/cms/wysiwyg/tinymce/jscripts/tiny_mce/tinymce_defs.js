var mce_lang = typeof(interfaceLang) !== 'undefined' ? interfaceLang : 'ru';

window.mceCommonSettings = {
	// General options
	mode : "none",
	theme : "umi",
	language : mce_lang,
	width : "100%",
	plugins : "safari,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

	inlinepopups_skin : 'butterfly',

	toolbar_standart : "fontsettings,tablesettings,|,"
	+"cut,copy,paste,|,pastetext,pasteword,|,selectall,cleanup,|,"
	+ "undo,redo,|,"
	+ "link,unlink,anchor,image,media,|,"
	+ "charmap,code",

	toolbar_tables : "table,delete_table,|,col_after,col_before,row_after,row_before,|,delete_col,delete_row,|,split_cells,merge_cells,|,row_props,cell_props",

	toolbar_fonts: "formatselect,fontselect,fontsizeselect,|,"
	+ "bold,italic,underline,|,"
	+ "justifyleft,justifycenter,justifyright,justifyfull,|,"
	+ "bullist,numlist,outdent,indent,|,"
	+ "forecolor,backcolor,|,"
	+ "sub,sup",


	theme_umi_toolbar_location : "top",
	theme_umi_toolbar_align : "left",
	theme_umi_statusbar_location : "bottom",
	theme_umi_resize_horizontal : false,
	theme_umi_resizing : true,

	convert_urls : false,
	relative_urls : false,

	file_browser_callback : "umiFileBrowserCallback",// Callbacks

	extended_valid_elements : "script[type=text/javascript|src|languge|lang],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align]", // extend tags and atributes

	content_css : "/css/cms/style.css" // enable custom CSS
}

var umiTreeLink = function(field_name, url, type, win) {
	var domain_floated    = window.domain_floated;
	var domain_floated_id = window.domain_floated_id;
	var lang_id           = window.lang_id || false;
	//var sTreeLinkUrl = "/js/tinymce/jscripts/tiny_mce/themes/umi/treelink.html?domain="+domain_floated+"&domain_id=" + domain_floated_id + /*"&lang="+lang+*/"&lang_id="+lang_id;
	var sTreeLinkUrl = "/js/tinymce/jscripts/tiny_mce/themes/umi/treelink.html" + (lang_id ? "?lang_id=" + lang_id : '');
	tinyMCE.activeEditor.windowManager.open({
		url    : sTreeLinkUrl,
		width  : 525,
		height : 308,
		inline         : true,
		scrollbars	   : false,
		resizable      : false,
		maximizable    : false,
		close_previous : false
	}, {
		window    : win,
		input     : field_name,
		editor_id : tinyMCE.selectedInstance.editorId
	});
	return false;
}

var umiflashFileManager = function(field_name, url, type, win, lang, folder_hash, file_hash) {
	var input = win.document.getElementById(field_name);
	if(!input) return false;
	var qs    = [];
	qs.push("id=" + field_name);
	switch(type) {
		case "image" : qs.push("image=1"); break;
		case "media" : qs.push("media=1"); break;
	}
	if(input.value.length) {
		var folder = input.value.substr(0, input.value.lastIndexOf('/'));
		qs.push("folder=." + folder);
		qs.push("file=" + input.value);
	}
	jQuery.openPopupLayer({
		name   : "Filemanager",
		title  : getLabel('js-file-manager'),
		width  : 1200,
		height : 600,
		url    : "/styles/common/other/filebrowser/umifilebrowser.html?" + qs.join("&")
	});
	return false;
}

var umielfinderFileManager = function(field_name, url, type, win, lang, folder_hash, file_hash) {

	var qs    = [];
	qs.push("id=" + field_name);
	switch(type) {
		case "image" : qs.push("image=1"); break;
		case "media" : qs.push("media=1"); break;
	}

	qs.push("folder_hash=" + folder_hash);
	qs.push("file_hash=" + file_hash);
	qs.push("lang=" + lang);

	$.openPopupLayer({
		name   : "Filemanager",
		title  : getLabel('js-file-manager'),
		width  : 1200,
		height : 600,
		url    : "/styles/common/other/elfinder/umifilebrowser.html?"+ qs.join("&")
	});

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

	window.parent.jQuery('#popupLayer_Filemanager .popupBody').append(footer);

	return false;
};

window.umiFileBrowserCallback = function(field_name, url, type, win) {		
	switch (type) {
		case "file"  : umiTreeLink(field_name, url, type, win); break;
		case "image" :
		case "media" :

			var input = win.document.getElementById(field_name);
			if(!input) return false;
			var folder = '';
			var file = '';
			if(input.value.length) {
				folder = input.value.substr(0, input.value.lastIndexOf('/'));
				file = input.value;
	}

			jQuery.ajax({
				url: "/admin/data/get_filemanager_info/",
				data: "folder=" + folder + '&file=' + file,
				dataType: 'json',
				complete: function(data){
					data = eval('(' + data.responseText + ')');
					var folder_hash = data.folder_hash;
					var file_hash = data.file_hash;
					var lang = data.lang;
					var fm = data.filemanager;

					var functionName = 'umi' + fm + 'FileManager';
					eval(functionName + '(field_name, url, type, win, lang, folder_hash, file_hash)');
				}
			});
			break;
	}
	return false;
}

/**
 * Encode string to base64
 * @param str string to encode
 * @return String base64 encoded string
 */
function base64encode(str) {
		var sWinChrs     = 'АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыьэюя';
		var sBase64Chrs  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
		var arrBase64    = sBase64Chrs.split('');

		var a = new Array();
		var i = 0;
		for(i = 0; i < str.length; i++) {
			var cch = str.charCodeAt(i);
			if (cch > 127) {
				cch = sWinChrs.indexOf(str.charAt(i)) + 163;
				if(cch < 163) continue;
			}
			a.push(cch);
		}
		var s    = new Array();
		var lPos = a.length - a.length % 3;
		var t = 0;
		for (i=0; i<lPos; i+=3) {
			t = (a[i]<<16) + (a[i+1]<<8) + a[i+2];
			s.push(arrBase64[(t>>18)&0x3f] + arrBase64[(t>>12)&0x3f] + arrBase64[(t>>6)&0x3f] + arrBase64[t&0x3f] );
		}
		switch (a.length-lPos) {
			case 1 :
					t = a[lPos]<<4;
					s.push(arrBase64[(t>>6)&0x3f] + arrBase64[t&0x3f] + '==');
					break;
			case 2 :
					t = (a[lPos]<<10)+(a[lPos+1]<<2);
					s.push(arrBase64[(t>>12)&0x3f] + arrBase64[(t>>6)&0x3f] + arrBase64[t&0x3f] + '=');
					break;
		}
		return s.join('');
}
