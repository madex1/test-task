$(function() {
	function getArgs() {
		var args = new Object();
		var query = location.search.substring(1);
		var pairs = query.split("&");
		for (var i = 0; i < pairs.length; i++) {
			var pos = pairs[i].indexOf('=');
			if (pos == -1) {
				continue;
			}
			var argname = pairs[i].substring(0, pos);
			var value = pairs[i].substring(pos + 1);
			args[argname] = unescape(value);
		}
		return args;
	}

	function retURL(pageId) {
		var oPopupParams = window.parent.tinymce.activeEditor.windowManager.getParams() || {},
			win = oPopupParams.window,
			sInputId = oPopupParams.input;
		win.document.getElementById(sInputId).value = "%content get_page_url(" + pageId + ")%";
		onClose();
	}

	function onClose() {
		window.parent.tinymce.activeEditor.windowManager.close();
		return false;
	}

	document.onkeydown = function(e) {
		var is_ie = !(navigator.appName.indexOf("Netscape") != -1);
		if (!is_ie) {
			event = e;
		}
		if (event.keyCode == 27) {
			onClose();
		}
	};

	$('#domainSelect').on('change', changeDomain);
	$('input.back').on('click', onClose);

	var domainsLoaded = false;
	var settingsLoaded = false;
	var requestUrl = "/admin/content/sitetree.xml";

	jQuery(function() {
		$.ajax({
			url: requestUrl,
			method: "get",
			complete: function(r) {
				domainsLoaded = true;
				var domains = r.responseXML.getElementsByTagName('domain');
				var select = document.getElementById('domainSelect');
				for (var i = 0; i < domains.length; i++) {
					var option = new Option(domains[i].getAttribute('host'), domains[i].getAttribute('id'));
					option.innerHTML = domains[i].getAttribute('host');
					select.appendChild(option);
				}
				createDomainTree();
			}
		});
	});

	var oDataSet = null;
	var oTree = null;
	var oRoot = null;
	var args = getArgs();
	var sModule = args.module ? args.module : 'content';

	function createDomainTree() {
		if (!settingsLoaded || !domainsLoaded) {
			return;
		}

		oDataSet = new dataSet(sModule, true);
		var oDefaultFilter = new filter();
		oDataSet.setDefaultFilter(oDefaultFilter);

		oDefaultFilter.setViewMode(false);
		oDefaultFilter.setVirtualCopyChecking(false);

		oTree = new Control(oDataSet, TreeItem, {
			id: 'tree_common0',
			toolbar: null,
			iconsPath: '/images/cms/admin/mac/tree/',
			container: document.getElementById('tree_container1'),
			allowDrag: false,
			disableTooManyChildsNotification: true,
			onItemClick: function(Item) {
				retURL(Item.id);
				return false;
			}
		});

		oRoot = oTree.setRootNode({
			'id': 0,
			'allow-drag': false,
			'iconbase': '/images/cms/admin/mac/tree/ico_domain.png',
			'name': location.hostname,
			'is-active': '1',
			'allow-copy': false,
			'allow-activity': false,
			'create-link': ''
		});

		var select = document.getElementById('domainSelect');

		var oInitFltr = new filter();
		oInitFltr.setParentElements(0);
		if (args.lang_id) {
			oInitFltr.setLang(args.lang_id);
		}
		oDefaultFilter.setDomain(select.options[select.selectedIndex].value);
		oDefaultFilter.setViewMode(false);
		oDefaultFilter.setVirtualCopyChecking(false);
		oRoot.filter = oInitFltr;

		settingsLoaded = false;
		domainsLoaded = false;

		for (var i = 0; i < Control.instances.length; i++) {
			Control.instances[i].init();
		}
	}

	function changeDomain() {
		var oInitFltr = new filter();
		oInitFltr.setParentElements(0);
		if (args.lang_id) {
			oInitFltr.setLang(args.lang_id);
		}
		var select = document.getElementById('domainSelect');
		var oDefaultFilter = new filter();
		oDefaultFilter.setDomain(select.options[select.selectedIndex].value);
		oDefaultFilter.setViewMode(false);
		oDefaultFilter.setVirtualCopyChecking(false);
		oDataSet.setDefaultFilter(oDefaultFilter);
		oTree.removeItem(oTree.getRootNodeId());
		oRoot = oTree.setRootNode({
			'id': 0,
			'allow-drag': false,
			'iconbase': '/images/cms/admin/mac/tree/ico_domain.png',
			'name': select.options[select.selectedIndex].text,
			'is-active': '1',
			'allow-copy': false,
			'allow-activity': false,
			'create-link': ''
		});
		oRoot.filter = oInitFltr;
		oDataSet.clearFiltersCache();
	}

	settingsLoaded = true;
	createDomainTree();
});
