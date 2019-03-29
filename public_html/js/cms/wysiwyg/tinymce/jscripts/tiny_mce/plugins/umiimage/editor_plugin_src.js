/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function() {
	tinymce.create('tinymce.plugins.UmiImagePlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceUmiImage', function() {
				// Internal image object like a flash placeholder
				if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1)
					return;

				var qs = 'folder=./images/cms/data/&image=1';

				jQuery.ajax({
					url: "/admin/data/get_filemanager_info/",
					data: "folder=./images/cms/data/&file=",
					dataType: 'json',
					complete: function(data){
						data = eval('(' + data.responseText + ')');
						var folder_hash = data.folder_hash;
						var file_hash = data.file_hash;
						var lang = data.lang;
						var fm = data.filemanager;

						var functionName = 'draw' + fm + 'ImageEditor';
						eval(functionName + '(qs, lang, folder_hash, file_hash)');
					}
				});

				var drawflashImageEditor = function (qs, lang, folder_hash, file_hash) {
					jQuery.openPopupLayer({
						name   : "Filemanager",
						title  : getLabel('js-file-manager'),
						width  : 1200,
						height : 600,
						url    : "/styles/common/other/filebrowser/umifilebrowser.html?" + qs,
						afterClose : function (value) {
							if(value) {
								if(typeof value == 'object') value = value[0];
								newValue = value ? value.toString() : '';
								onFilemanagerFinish( value.toString() );
							}
						}
					});
				};

				var drawelfinderImageEditor = function (qs, lang, folder_hash, file_hash) {
					qs = qs + '&lang=' + lang + '&file_hash=' + file_hash + '&folder_hash=' + folder_hash;
					jQuery.openPopupLayer({
						name   : "Filemanager",
						title  : getLabel('js-file-manager'),
						width  : 1200,
						height : 600,
						url    : "/styles/common/other/elfinder/umifilebrowser.html?" + qs,
						afterClose : function (value) {
							if(value) {
								if(typeof value == 'object') value = value[0];
								newValue = value ? value.toString() : '';
								onFilemanagerFinish( value.toString() );
							}
						}
					});
					jQuery('#popupLayer_Filemanager .popupBody').append(uAdmin.wysiwyg.getFilemanagerFooter('elfinder'));
				};
				
				
				function onFilemanagerFinish(img_src) {
					var el, args = {};
					// Fixes crash in Safari
					if (tinymce.isWebKit) 
						ed.getWin().focus();
					
					args = {
						src : img_src
					};
					
					el = ed.selection.getNode();
					
					if (el && el.nodeName == 'IMG') {
						ed.dom.setAttribs(el, args);
					} else {
						ed.execCommand('mceInsertContent', false, '<img id="__mce_tmp" />', {skip_undo : 1});
						ed.dom.setAttribs('__mce_tmp', args);
						ed.dom.setAttrib('__mce_tmp', 'id', '');
						ed.undoManager.add();
					}
				}
				
			});

			// Register buttons
			ed.addButton('umiimage', {
				title : 'umiimage.image_desc',
				cmd : 'mceUmiImage'
			});
		},

		getInfo : function() {
			return {
				longname : 'Umi image (based on Advanced image)',
				author : 'Moxiecode Systems AB && Krutuzick',
				authorurl : 'http://tinymce.moxiecode.com && http://umi.ru',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/advimage && http://umi.ru',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('umiimage', tinymce.plugins.UmiImagePlugin);
})();