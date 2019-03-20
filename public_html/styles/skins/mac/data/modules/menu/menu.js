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
						<label >\
							<span>" + getLabel('js-label-menu-link') + "</span>\
							<a href='"+viewLink+"' target='_blank' class='text viewLink' >"+viewLink+"</a>\
						</label>\
					</p>";
	if(rel=='custom'){
		editLink = " <p>\
						<label >\
							<span>" + getLabel('js-label-menu-link') + "</span>\
							<input type='text' value='"+viewLink+"' class='text editeLink' name='editeLink' />\
						</label>\
					</p>";
	};
	var newItem = jQuery("<li/>", {
		"class": "dd-item",
		"data-rel": rel,
		"data-link": viewLink,
		"data-name": name,
		"data-isactive": IsActive,
		html: " <a href='#' class='menuItemDel'>X</a>\
				"+((IsActive) ? "<a href='#' class='menuItemEdit'>" + getLabel('js-label-menu-edit') + "</a>" :"")+"\
				<div class='dd-handle'>\
					<div class='menuItemName'>"+name+((!IsActive) ? getLabel('js-label-menu-page-no-active') : "")+"</div>\
				</div>\
				<div class='editBlock' style='display:none'>\
					<div class='editBlockWrap formClass'>\
						<p>\
							<label >\
								<span>" + getLabel('js-label-menu-link-title') + "</span>\
								<input type='text' value='"+name+"' class='text editeName' name='editeName' />\
							</label>\
						</p>\
						"+editLink+"\
						<p class='butonBlock'>\
							<input type='button' class='button cancel cancelEditBlock' value='" + getLabel('js-label-menu-button-cancel') + "'>\
							<input type='button' class='button save submitEditBlock' value='" + getLabel('js-label-menu-button-save') + "'>\
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
	var listObj = jQuery.parseJSON(jQuery('#menuhierarchy').val());

	if(typeof listObj =='object' && listObj !== null) menuDecode(listObj);
	

	
	
	
    jQuery('.dd').data('output', jQuery('#menuhierarchy'));	
	// сортировка меню
	jQuery('.dd').nestable().bind('change',updateOutput);
	
	// удаление пункта меню
	jQuery('.menuItemDel').unbind().live('click',function(){
		jQuery(this).parents('.dd-item:first').remove();
		updateOutput(jQuery('.dd').data('output', jQuery('#menuhierarchy')));
		return false;
	});
	
	//редактирование пункта меню
	jQuery('.menuItemEdit').unbind().live('click',function(){
		jQuery(this).parents('.dd-item:first').find('.editBlock:first').slideToggle('fast');
		updateOutput(jQuery('.dd').data('output', jQuery('#menuhierarchy')));
		return false;
	});
	
	// сохранение изменений пункта меню
	jQuery('.submitEditBlock').unbind().live('click',function(){
		var thisItem=jQuery(this).parents('.dd-item:first');
		var newName=thisItem.find('.editeName:first').val();
		var newLink=thisItem.find('.editeLink:first').val();
		
		thisItem.data('name',newName).data('link',newLink);
		thisItem.find('.menuItemName:first').text(newName);
		
		thisItem.find('.editBlock:first').slideUp('fast');
		updateOutput(jQuery('.dd').data('output', jQuery('#menuhierarchy')));
		return false;
	});
	// отмена изменений пункта меню
	jQuery('.cancelEditBlock').unbind().live('click',function(){
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
			if (!(link.indexOf("http://") >= 0)) link='http://'+link;
			createMenuItem('custom', name, link, true);
			jQuery('.add-custom-menu-block img').hide();
		}
		return false;
	})
	
	//добавление системной страницы
	jQuery('.systemLink').click(function(){
		var name='';
		var link='';
		
		name=jQuery.trim(jQuery(this).text());
		link=jQuery.trim(jQuery(this).attr('href'));
		
		createMenuItem('system',name, link, true);

		return false;
	})
	
});

