// обновление json информации
var updateOutput = function(e)
    {
        var list   = e.length ? e : $(e.target),
            output = list.data('output');
		if (output === undefined){
			list.data('output', jQuery('#menuhierarchy'));
			output = list.data('output');
		}
        if (window.JSON) {
            output.val(JSON.stringify(list.nestable('serialize')));//, null, 2));
        } else {
            output.val('JSON browser support required for this demo.');
        }
    };

// создание пункта меню
function drawMenuItem(rel, name, viewLink, IsActive, listObj) {
	if(!listObj) listObj = jQuery(".dd-list:first");
	var editLink = " <p>\
						<label class='title-edit'>\
							<span>" + getLabel('js-label-menu-link') + "</span>\
							<a href='"+viewLink+"' target='_blank' class='text viewLink' >"+viewLink+"</a>\
						</label>\
					</p>";
	if(rel=='custom'){
		editLink = " <p>\
						<label >\
							<span class='title-edit'>" + getLabel('js-label-menu-link') + "</span>\
							<input type='text' value='"+viewLink+"' class='default editeLink' name='editeLink' />\
						</label>\
					</p>";
	};
	var newItem = jQuery("<li/>", {
		"class": "dd-item",
		"data-rel": rel,
		"data-link": viewLink,
		"data-name": name,
		"data-isactive": IsActive,
		html: " <a href='#' class='menuItemDel'><i class='small-ico i-remove'></i></a>\
				"+((IsActive) ? "<a href='#' class='menuItemEdit'><i class='small-ico i-edit'></i></a>" :"")+"\
				<div class='dd-handle'>\
					<div class='menuItemName'>"+name+((!IsActive) ? getLabel('js-label-menu-page-no-active') : "")+"</div>\
				</div>\
				<div class='editBlock' style='display:none'>\
					<div class='editBlockWrap formClass'>\
						<p>\
							<label >\
								<span class='title-edit'>" + getLabel('js-label-menu-link-title') + "</span>\
								<input type='text' value='"+name+"' class='default editeName' name='editeName' />\
							</label>\
						</p>\
						"+editLink+"\
						<p class='butonBlock'>\
							<input type='button' class='cancel cancelEditBlock btn color-blue btn-small' value='" + getLabel('js-label-menu-button-cancel') + "'>\
							<input type='button' class='save submitEditBlock btn color-blue btn-small' value='" + getLabel('js-label-menu-button-save') + "'>\
						</p>\
					</div>\
				</div>"
	}).appendTo(listObj);
	return newItem;
}

// добавления пункта меню
function createMenuItem(rel,name,viewLink, IsActive, listObj) {
    var menuhierarchy =  jQuery('#menuhierarchy');
       /* menuhierarchyValueObj = (menuhierarchy.val() == '') ? {} : JSON.parse(menuhierarchy.val()),
        valid = validateRel(rel, menuhierarchyValueObj, viewLink);
        
    if(valid) return false; */
	var newItem = drawMenuItem(rel,name,viewLink,IsActive, listObj)
	updateOutput(jQuery('.dd').data('output', menuhierarchy));
	return newItem;
}

/*
 * @author - Farit Bashirov
 * @description - Validate rel in this object
 * @rel - validate rel
 * @param (object) currentObj
 */
function validateRel(rel, currentObj, link){
    var result = false;
    for(var i in currentObj){
        var value = currentObj[i];
        var children = (value.children == undefined ) ? false : value.children;
        if(rel == 'custom' || rel == 'system' ){
          if(link == value.link) return true;
       } else if(rel == value.rel) return true;
       
       if(children) {
           result = validateRel(rel, children, link);
       }
    }
    
    return result;
}

// отрисовка меню из json строчки obj в список List
menuDecode = function(obj,List)
	{
		jQuery.each( obj, function( key, value ) {
			var newItem = drawMenuItem(value.rel,value.name,value.link,value.isactive, List);
			if(value.children){
				var newList = jQuery("<ol/>", {
					"class": "dd-list"
				}).appendTo(newItem);
				menuDecode(value.children,newList);
			}
		});
	};

jQuery(document).ready(function(){
	// построение структуры меню при загрузке страницы
	var listObj = jQuery.parseJSON(jQuery('#menuhierarchy').val() || 'null');

	if(typeof listObj =='object' && listObj !== null) menuDecode(listObj);

    jQuery('.dd').data('output', jQuery('#menuhierarchy'));	
	// сортировка меню
	jQuery('.dd').nestable().bind('change',updateOutput);
	
	// удаление пункта меню
	jQuery('.menuItemDel').unbind();

	jQuery(document).on('click', '.menuItemDel', function() {
		jQuery(this).parents('.dd-item:first').remove();
		updateOutput(jQuery('.dd').data('output', jQuery('#menuhierarchy')));
		return false;
	});
	
	//редактирование пункта меню
	jQuery('.menuItemEdit').unbind();

	jQuery(document).on('click', '.menuItemEdit', function() {
		jQuery(this).parents('.dd-item:first').find('.editBlock:first').slideToggle('fast');
		updateOutput(jQuery('.dd').data('output', jQuery('#menuhierarchy')));
		return false;
	});

	// сохранение изменений пункта меню
	jQuery('.submitEditBlock').unbind();

	jQuery(document).on('click', '.submitEditBlock', function() {
		var thisItem=jQuery(this).parents('.dd-item:first');
		var newName=thisItem.find('.editeName:first').val();
		var newLink=thisItem.find('.editeLink:first').val();

		thisItem.data('name',newName);
		thisItem.data('link',newLink);
		thisItem.find('.menuItemName:first').text(newName);

		thisItem.find('.editBlock:first').slideUp('fast');
		updateOutput(jQuery('.dd').data('output', jQuery('#menuhierarchy')));
		return false;
	});

	// отмена изменений пункта меню
	jQuery('.cancelEditBlock').unbind();

	jQuery(document).on('click', '.cancelEditBlock', function() {
		var thisItem=jQuery(this).parents('.dd-item:first');
		var oldName=thisItem.data('name');
		var oldLink=thisItem.data('link');

		thisItem.find('.editeName:first').val(oldName);
		thisItem.find('.editeLink:first').val(oldLink);

		thisItem.find('.menuItemEdit:first').show();
		thisItem.find('.editBlock:first').slideUp('fast');
		return false;
	});

	//добавление произвольной ссылки
	jQuery('#submit_customlink').click(function(){
		var name='';
		var link='';
		
		name=jQuery('#custom-name').val();
		link=jQuery.trim(jQuery('#custom-url').val());
		
		if(link=='http://' || name=='' || link==''){
			alert(getLabel('js-label-menu-alert-no-link-title'));
		}else{
			jQuery('.add-custom-menu-block img').show();

			var protocol = link.match(/https?:\/\//);
			var linkProtocol = protocol ? protocol[0] : 'http://';

			if (!(link.indexOf(linkProtocol) >= 0) && !link.match(/^#/) && link.match(/[a-zA-Z0-9_-]{1,}\.[a-zA-Z0-9_-]{1,}/)) {
				link = linkProtocol + link;
			}
			createMenuItem('custom', name, link, true);
			jQuery('.add-custom-menu-block img').hide();
		}
		return false;
	});
	
	//добавление системной страницы
	jQuery('.systemLink').click(function(){
		var name='';
		var link='';
		
		name=jQuery.trim(jQuery(this).text());
		link=jQuery.trim(jQuery(this).attr('href'));
		
		createMenuItem('system',name, link, true);

		return false;
	});
	
});
