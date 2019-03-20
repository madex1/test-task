function getArgs() {
	var args = new Object();
	var query = location.search.substring(1);
	var pairs = query.split("&");
	for(var i = 0; i < pairs.length; i++) {
		var pos = pairs[i].indexOf('=');
		if (pos == -1) continue;
		var argname = pairs[i].substring(0,pos);
		var value = pairs[i].substring(pos+1);
		args[argname] = unescape(value);
	}
	return args;
}
var args = getArgs();
function onClose() {				
	window.parent.$.closePopupLayer();
	return false;
}
var domainsLoaded  = false;
var settingsLoaded = false;
jQuery(function(){
	$.ajax({url      : "/admin/content/sitetree.xml",
			method   : "get",
			complete : function(r) {
								domainsLoaded = true;
								var domains = r.responseXML.getElementsByTagName('domain');												
								var select  = $('#domainSelect').selectize({
									allowEmptyOption: true,
									create: false/*,
									 hideSelected:true*/
								});
								select = select[0].selectize;
								select.lock();
								for(var i=0; i<domains.length; i++) {
									/*var option = new Option(domains[i].getAttribute('host'), domains[i].getAttribute('id'));
									option.innerHTML = domains[i].getAttribute('host');
									select.appendChild(option);*/
									select.addOption({text:domains[i].getAttribute('host'), value:domains[i].getAttribute('id')});
									if (i==0){
										select.addItem(domains[i].getAttribute('id'),true);
									}
								}
								select.unlock();
								createDomainTree();
							}
			});
});


var oDataSet  = null;
var oTree	  = null;
var oRoot     = null; 
var sModule   = args.module ? args.module : 'content'; 
var hTypes   = args.hierarchy_types ? args.hierarchy_types.split(',') : []; 

function createDomainTree() {
	if(!settingsLoaded || !domainsLoaded) return;
	
	oDataSet = new dataSet(sModule, true);
	var oDefaultFilter = new filter();
	oDataSet.setDefaultFilter(oDefaultFilter);
	oDefaultFilter.setLang(tmp_lang_id);
	oDefaultFilter.setViewMode(false);
	oDefaultFilter.setVirtualCopyChecking(false);
	
	oTree = new Control(oDataSet, TreeItem, {
		id 		  : 'tree_common0',
		toolbar   : null,
		iconsPath : '/images/cms/admin/mac/tree/',
		container : document.getElementById('tree_container1'),
		hasCheckboxes: false,
		isCanSelect: false,
		allowDrag : false,
		disableTooManyChildsNotification : true,
		onItemClick : function(Item) {
			createMenuItem(	Item.id, Item.name, Item.viewLink, Item.isActive);
			return false;
		}
	});

	oRoot = oTree.setRootNode({
		'id'         	 : 0,
		'allow-drag' 	 : false,
		'iconbase'   	 : '/images/cms/admin/mac/tree/ico_domain.png',
		'name'       	 : location.hostname,
		'is-active'      : '1',
		'allow-copy'     : false,
		'allow-activity' : false,
		'create-link' 	 : ''
	});

	var select = document.getElementById('domainSelect');

	var oInitFltr = new filter();
	oInitFltr.setParentElements(0);
	if(tmp_lang_id) oInitFltr.setLang(tmp_lang_id);
	if(hTypes) oInitFltr.setHTypes(hTypes);
	oDefaultFilter.setDomain(select.options[select.selectedIndex].value);
	oDefaultFilter.setLang(tmp_lang_id);
	oDefaultFilter.setViewMode(false);
	oDefaultFilter.setVirtualCopyChecking(false);
	oRoot.filter = oInitFltr;

	settingsLoaded = false;
	domainsLoaded  = false;

	for (var i = 0; i < Control.instances.length; i++) {
		Control.instances[i].init();
	}
}
function changeDomain() {
	var oInitFltr = new filter();
	oInitFltr.setParentElements(0);
	if(tmp_lang_id) oInitFltr.setLang(tmp_lang_id);
	var select = document.getElementById('domainSelect');
	var oDefaultFilter = new filter(); 
	oDefaultFilter.setDomain(select.options[select.selectedIndex].value);
	oDefaultFilter.setLang(tmp_lang_id);
	oDefaultFilter.setViewMode(false);
	oDefaultFilter.setVirtualCopyChecking(false);
	oDataSet.setDefaultFilter(oDefaultFilter);
	oTree.removeItem(oTree.getRootNodeId());
	oRoot = oTree.setRootNode({
		'id'         	 : 0,
		'allow-drag' 	 : false,
		'iconbase'   	 : '/images/cms/admin/mac/tree/ico_domain.png',
		'name'       	 : select.options[select.selectedIndex].text,
		'is-active'      : '1',
		'allow-copy'     : false,
		'allow-activity' : false,
		'create-link' 	 : ''
	});
	oRoot.filter = oInitFltr;
	oDataSet.clearFiltersCache();
}
settingsLoaded = true; createDomainTree();