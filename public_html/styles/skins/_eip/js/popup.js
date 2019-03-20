// Скрипты для всплывающего EIP окна "Создание страницы" (styles/skins/_eip/layout.xsl)

// Кастомизирует ширину и высоту html-редактора
// Остальные настройки такие же, как дефолтные,
// @see WYSIWYG.prototype.tinymce47.settings
uAdmin('settings', {
	codemirror: {
		indentOnInit: true,
		path: 'codemirror',
		width: 700,
		height: 400,
		config: {
			lineNumbers: true,
			lineWrapping: true,
			autofocus: true,
		}
	}
},'wysiwyg');
uAdmin('type', 'tinymce47', 'wysiwyg');

function autoHeightIframe(mode) {
	var eip_page = document.getElementById('eip_page');
	var height = (mode == 'load') ? document.body.scrollHeight : eip_page.offsetHeight;
	if (jQuery(".wysiwyg").length) {
		height += 10;
	}
	height = (height > 500) ? 500 : height;
	frameElement.height = height;
	frameElement.style.height = height;
}

// noinspection JSUnusedGlobalSymbols (@see styles/skins/_eip/data/list.view.xsl)
function showSubtypes(block, sub_class) {
	var sub_block = document.getElementById('eip_page_subtype_' + sub_class);
	block.parentNode.style.display = 'none';
	sub_block.style.display = 'block';
	autoHeightIframe();
}

// noinspection JSUnusedGlobalSymbols (@see styles/skins/_eip/data/list.view.xsl)
function hideSubtypes(block) {
	var sub_block = document.getElementById('eip_page_types_choice');
	block.style.display = 'none';
	sub_block.style.display = 'block';
	autoHeightIframe();
}

// noinspection JSUnusedGlobalSymbols (@see styles/skins/_eip/data/list.view.xsl)
function submitAddPage(type_id) {
	csrfPart = uAdmin.csrf ? '&csrf=' + uAdmin.csrf : '';
	location.href = '?hierarchy-type-id=' + type_id + csrfPart;
}

// noinspection JSUnusedGlobalSymbols (@see styles/skins/_eip/data/form.modify.xsl)
function popupCancel() {
	window.parent.$.closePopupLayer(null, {});
}

jQuery(document).ready(function() {
	jQuery("fieldset legend a").click(function() {
		var i;
		if (i = this.href.indexOf('#')) {
			var id = this.href.substring(i + 1);
			jQuery("fieldset").children().filter("div").hide();
			jQuery("fieldset legend a").removeClass('eip_disable_link');
			jQuery('div#' + id).show();
			autoHeightIframe();
		}

		jQuery(this).addClass('eip_disable_link');
		return false;
	});

	uAdmin.wysiwyg.init();

	jQuery(':input[name=hierarchy-type-id]').click(function() {
		jQuery('.object-types').css('display', 'none');
		jQuery('#object-types-' + this.value).css('display', 'block');
		autoHeightIframe();
	});

	jQuery("img").on("load", function() {
		autoHeightIframe('load');
	});

	autoHeightIframe('load');
});
