	
	jQuery(window).resize(function(){
		
		var windowHeight = jQuery(window).height();
		var windowWidth = jQuery(window).width();
		
		var finalWidth = 0.75 * windowWidth;
		var finalHeight = 0.75 * windowHeight;
		
		var left = (windowWidth - finalWidth) / 2;  
		var top = jQuery(document).scrollTop() + (windowHeight - finalHeight) / 2;
		
		jQuery('div.dashboardWrapper').css({top: top + 'px', left: left +'px', width: finalWidth + 'px'});
		
	});

	jQuery(document).ready(function(){
		jQuery("#mark_read").bind('click', markReadEvents);
		jQuery("#mark_unread").bind('click', markUnreadEvents);
	});
	
	function saveSettings() {

		var startDate = jQuery("#start_date").val();
		var endDate = jQuery("#end_date").val();

		jQuery.post(
			"/admin/events/saveSettings/",
			jQuery("#dashboard_settings").serialize()
		)
			.success(function() { reload('', startDate, endDate, '', ''); })
			.error(function(jqXHR, textStatus, errorThrown) { alert( textStatus + ': '+ errorThrown); })

		return false;
	}
	
	function createPreloader() {
		
		jQuery("div.tableDashboard").css('position', 'relative');
		
		var preloader = jQuery('<div class="preloader"><img src="/images/cms/admin/mac/ajax_loader.gif" alt="Loading..."/></div>');
		var table = jQuery(".tableDashboard table");
				
		preloader.css({
			'height' : table.height(),
			'width' : table.width(),
			'top' : table.position().top
		}).appendTo('div.tableDashboard');
		
		jQuery('img', preloader).position({
			my: "center center",
			at: "center center",
			collision : 'fit',
			of: preloader
		});
	}

	function reload(page, startDate, endDate, filter, onlyUnread) {
		
		createPreloader();
		
		var windowHeight = jQuery(window).height();
			
		jQuery.get(
			"/admin/events/feed/.json",
			{p: page, start_date: startDate, end_date: endDate, filter: filter, onlyUnread: onlyUnread},
			function(json){
				var table = jQuery(".tableDashboard tbody");
				
				jQuery(".preloader").remove();
				jQuery(".tableDashboard table").attr('style', '');
				jQuery(".tableDashboard table").parent().css('max-height', windowHeight - 400);
				table.html('');

				updatePaging(json.data.total, json.data.limit, json.data.offset, filter);
				
				jQuery('.tableDashboard .events_header span.amount').html(json.data.total);
				
				if (!parseInt(json.data.total)) {
					jQuery('.show_all').click();
				}
								
				if (!json.data.total) {
					table.html('<tr><td style="text-align:center;">' + getLabel('js-dashboard-nothing-new') + '</td></tr>');
				} else {					
					for (var eventId in json.data.events.event) {
						var event = json.data.events.event[eventId];
						var className = 'unread';
						if (event.read == 1) className = 'read';
						table.append('<tr class="' + className + '"><td><input type="checkbox" name="events[]" value="'+ event.id +'" onclick="javascript:changeReadEvents(this);" class="' + className + '"/></td><td><div style="white-space:nowrap;">' + event.date + '</div></td><td>' + event.value + '</td></tr>')
					}
				}
			},
			"json"
		)
			.error(function(jqXHR, textStatus, errorThrown) { alert( textStatus + ': '+ errorThrown); })

		clearInputs();

	}

	function clearInputs(type) {

		if (type != 'unread') {
			jQuery("#mark_read").attr('disabled', 'disabled');
			jQuery("#mark_read").parent().removeClass('act');
		}
		if (type != 'read') {
			jQuery("#mark_unread").attr('disabled', 'disabled');
			jQuery("#mark_unread").parent().removeClass('act');
		}

		jQuery("#select_all").removeAttr('checked');
	}

	function updatePaging(total, limit, offset, filter) {

		var pages_bar = jQuery(".pages-bar");

		pages_bar.html('');
		var pages = Math.ceil(total/limit);
		if (pages > 1) {
			var startDate = jQuery("#start_date").val();
			var endDate = jQuery("#end_date").val();
			pages_bar.append('<span class="pagesLabel">' + getLabel('js-dashboard-paging') + ': </span>');
			for (var i = 0; i < pages; i++) {
				var pagenum = i + 1;
				var aclass = '';
				if (offset == i*limit) aclass = 'current';
				var onClick = function (i, startDate, endDate, filter) {
					return function() {
						reload(i, startDate, endDate, filter);
						return false;
					}
				};
				jQuery('<a href="?p=' + i +'&start_date=' + startDate + '&end_date=' + endDate + '&filter=' + filter + '" class="' + aclass + '">' + pagenum + '</a>')
					.click(onClick(i, startDate, endDate, filter))
					.appendTo(pages_bar);
			}
		}
	}

	function filterType(typeId) {
		
		var body = jQuery('body');
		
		var parent = jQuery('#' + typeId);
		var position = parent.position();
		
		var initialHeight = parent.height(); 
		var initialWidth = parent.width(); 
		
		var windowHeight = jQuery(window).height();
		var windowWidth = jQuery(window).width();
		
		
		body.append('<div id="popupLayerScreenLocker" style="position: fixed; background: none repeat scroll 0% 0% rgb(0, 0, 0); left: 0px; top: 0px; opacity: 0.5; height: 100%; width: 1000%; z-index: 1000;"></div><div id="' + typeId + '-child" class="dashboardWrapper"><div class="tableDashboard" style="width:100%; background:white;"><div class="events_header"/><div style="max-height: 500px; overflow-y: auto;"><table style="height:70px"><tbody/></table></div></div></div>');
		
		var child = jQuery('#' + typeId + '-child');
		
		child.css({
			'position': 'absolute',
			'z-index': 1001,
			'background': 'white',
			'height' : initialHeight,
			'width': initialWidth,
			'top': position.top,
			'left': position.left
				
		});
		
		var finalWidth = 0.75 * windowWidth;
		var finalHeight = 0.75 * windowHeight;
		
		var left = (windowWidth - finalWidth) / 2;  
		var top = jQuery(document).scrollTop() + (windowHeight - finalHeight) / 2;

		child.animate({
			top: top + 'px',
			left: left +'px',
			width: finalWidth + 'px',
			opacity: '1'
		}, 600, 'swing', function() {
			
			jQuery('#' + typeId +'-child .tableDashboard .events_header').html(jQuery('#' + typeId +' .events_header').html());
			
			jQuery('#' + typeId +'-child .tableDashboard').append('<div class="cont_pages_bar"><a href="javascript:void(0);" onclick="javascript:showAll(\'' +  typeId + '\', \'' + initialHeight + '\', \'' + initialWidth + '\');" class="show_all"/><div class="pages-bar"/></div>');
			jQuery('#' + typeId +'-child .tableDashboard .events_header').after('\
			<div class="events_header">\
				<input type="checkbox" id="select_all" onclick="javascript:selectAllEvents(this);"/>\
				<label for="select_all">' + getLabel('js-dashboard-select-all') + '</label>\
				<div class="buttons">\
					<div>\
						<input type="button" id="mark_read" value="' + getLabel('js-dashboard-mark-read') + '" disabled="disabled" onclick="javascript:markReadEvents();"/>\
						<span class="l"></span>\
						<span class="r"></span>\
					</div>\
				</div>\
			</div>\
			');
			
			reload('', '', '', typeId, true);
			child.css({
				'min-width'	: '821px',
				'height'	: 'auto'
			});
		});

	}

	function showAll(typeId, height, width) {
		
		var position = jQuery('#' + typeId).position();
	
		jQuery('#' + typeId + '-child tbody').css('visibility', 'hidden');
		
		var eventsCount = jQuery('#' + typeId + '-child .events_header span.amount').html();
		if (parseInt(eventsCount)) {
			jQuery('#' + typeId + ' tbody').html('');
			jQuery('#' + typeId + '-child tbody tr:lt(3)').each(function(i){
				jQuery('#' + typeId + ' tbody').append('<tr><td class="date">' + jQuery(this).find('td:eq(1) div').html() + '</td><td>' + jQuery(this).find('td:eq(2)').html() + '</td></tr>');
			});
			
			jQuery('#' + typeId + ' .events_header span.amount').html(eventsCount);

		}

		jQuery('#' + typeId +'-child').css('min-width', '0');

		jQuery('#' + typeId +'-child').animate({
			width: width + 'px',
			height: height + 'px',
			top: position.top + 'px',
			left: position.left + 'px'
		}, 600, 'swing', function() {
			jQuery('#' + typeId +'-child').remove();
			jQuery('#popupLayerScreenLocker').remove();
			
			if (!parseInt(eventsCount)) {
			    jQuery('#' + typeId).css('margin', '0px');
				jQuery('#' + typeId).animate({opacity: 0, width: '0px', height: '0px'}, 1000, function(){
					var parent = this.parentNode;
					jQuery(this).remove();
					if (parent.children.length == 0) {
						parent.innerHTML = '<div class="nothing">' + getLabel('js-dashboard-nothing-new-events') + '</div>';
					}
				});
			}
		});
		
	}

	function changeReadEvents(obj) {

		var readButton = jQuery("#mark_read");
		var unreadButton = jQuery("#mark_unread");

		if (jQuery(obj).attr('checked') == 'checked') {

			if (jQuery(obj).attr('class') == 'unread') {
				readButton.removeAttr('disabled');
				readButton.parent().addClass('act');
			} else {
				unreadButton.removeAttr('disabled');
				unreadButton.parent().addClass('act');
			}

			jQuery(obj).parents('tr').addClass('selected');

		} else {
			if (jQuery(obj).attr('class') == 'unread' && jQuery('.unread:checked').length == 0) {
				readButton.attr('disabled', 'disabled');
				readButton.parent().removeClass('act');
			}
			if (jQuery(obj).attr('class') == 'read' && jQuery('.read:checked').length == 0) {
				unreadButton.attr('disabled', 'disabled');
				unreadButton.parent().removeClass('act');
			}

			jQuery(obj).parents('tr').removeClass('selected');
		}
	}

	function selectAllEvents(obj) {
		if (jQuery(obj).attr('checked') == 'checked') {
			jQuery('input.read:not(:checked), input.unread:not(:checked)').each(function(){
				jQuery(this).attr('checked', 'checked');
				changeReadEvents(this);
			});
		} else {
			jQuery('input.read:checked, input.unread:checked').each(function(){
				jQuery(this).removeAttr('checked');
				changeReadEvents(this);
			});
		}
	}

	function markReadEvents() {
		
		var id = jQuery('.tableDashboard').parents('div').attr('id');
		
		if(!id) {
			createPreloader();	
		}
				
		jQuery.post(
			"/admin/events/markReadEvents/",
			jQuery('input.unread:checked').serialize()
		)
			.success(function() {

				if (id) {
					var typeId = id.substring(0, id.length-6);
					reload('', '', '', typeId, true);
				} else {
					jQuery(".preloader").remove();

					var inputs = jQuery('input.unread:checked');

					inputs.parents('tr').removeClass('unread').removeClass('selected').addClass('read');
					inputs.attr('class', 'read').removeAttr('checked');
					clearInputs('read');
				}
								
			})
			.error(function(jqXHR, textStatus, errorThrown) { alert( textStatus + ': '+ errorThrown); })

	}

	function markUnreadEvents() {
		
		createPreloader();
		
		jQuery.post(
			"/admin/events/markUnreadEvents/",
			jQuery('input.read:checked').serialize()
		)
			.success(function() {

				var inputs = jQuery('input.read:checked');

				inputs.parents('tr').removeClass('read').removeClass('selected').addClass('unread');
				inputs.attr('class', 'unread').removeAttr('checked')
				jQuery(".preloader").remove();
				clearInputs('unread');
			})
			.error(function(jqXHR, textStatus, errorThrown) { alert( textStatus + ': '+ errorThrown); })
	}

