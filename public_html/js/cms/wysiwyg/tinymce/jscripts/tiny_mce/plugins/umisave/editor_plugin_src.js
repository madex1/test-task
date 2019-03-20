(function () {
	tinymce.create('tinymce.plugins.umisave', {
		init : function (ed, url) {
			ed.addButton('umisave', {
				title : 'save.save_desc',
				'class' : 'mce_save',
				onclick : function () {
					uAdmin.eip.editor.finish(true);
				}});
		},
		getInfo : function () {
			return {
				longname : 'Umi save button',
				author : 'Anton Viller',
				authorurl : '',
				infourl : '',
				version : "0.1"
			};
		}
	});

	tinymce.PluginManager.add('umisave', tinymce.plugins.umisave);
}());