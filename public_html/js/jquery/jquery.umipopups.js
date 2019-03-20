/**
 * Based on jmpopups (http://jmpopups.googlecode.com/)
 */
(function(jQuery) {
	var denyFrameId = "SiteFrame",
		openedPopups = [],
		popupLayerScreenLocker = false,
		focusableElement = [],
		setupJqueryMPopups = {
			screenLockerBackground: "#000",
			screenLockerOpacity: "0.5"
		};

	jQuery.setupUMIPopups = function(settings) {
		setupJqueryMPopups = jQuery.extend(setupJqueryMPopups, settings);
		return this;
	}

	jQuery.openPopupLayer = function(settings) {
		if (typeof(settings.name) != "undefined" && !checkIfItExists(settings.name)) {
			settings = jQuery.extend({
				width: "auto",
				height: "auto",
				parameters: {},
				target: "",
				data  : "",
				title : "",
				closeable : true,
				success: function() {},
				error: function() {},
				beforeClose: function() {},
				afterClose: function() {},
				reloadSuccess: null,
				cache: false,
			}, settings);
			loadPopupLayerContent(settings, true);
			return this;
		}
	}
	
	jQuery.closePopupLayer = function(name, returnValue) {
		hideScreenLocker(name);

		if (name) {
			for (var i = 0; i < openedPopups.length; i++) {
				if (openedPopups[i].name == name) {
					var thisPopup = openedPopups[i];

					var popupLayer = (!frameElement || frameElement.id==denyFrameId) ? jQuery("#popupLayer_" + name) : window.parent.jQuery("#popupLayer_" + name);

					openedPopups.splice(i,1)
					thisPopup.beforeClose(returnValue);

					popupLayer.fadeOut();
					popupLayer.remove();

					focusableElement.pop();

					if (focusableElement.length > 0) {
						jQuery(focusableElement[focusableElement.length-1]).focus();
					}

					thisPopup.afterClose(returnValue);
					break;
				}
			}
		} else {
			if (openedPopups.length > 0) {
				jQuery.closePopupLayer(openedPopups[openedPopups.length-1].name, returnValue);
			}
		}

		return this;
	}
	
	jQuery.reloadPopupLayer = function(name, callback) {
		if (name) {
			for (var i = 0; i < openedPopups.length; i++) {
				if (openedPopups[i].name == name) {
					if (callback) {
						openedPopups[i].reloadSuccess = callback;
					}
					
					loadPopupLayerContent(openedPopups[i], false);
					break;
				}
			}
		} else {
			if (openedPopups.length > 0) {
				jQuery.reloadPopupLayer(openedPopups[openedPopups.length-1].name);
			}
		}
		
		return this;
	}

	function setScreenLockerSize() {
		if (popupLayerScreenLocker) {
			jQuery('#popupLayerScreenLocker').height(jQuery(document).height() + "px");
			jQuery('#popupLayerScreenLocker').width(jQuery(document.body).outerWidth(true) + "px");
		}
	}
	
	function checkIfItExists(name) {
		if (name) {
			for (var i = 0; i < openedPopups.length; i++) {
				if (openedPopups[i].name == name) {
					return true;
				}
			}
		}
		return false;
	}
	
	function showScreenLocker() {
		if (jQuery("#popupLayerScreenLocker").length) {
			if (openedPopups.length == 1) {
				popupLayerScreenLocker = true;
				setScreenLockerSize();
				jQuery('#popupLayerScreenLocker').fadeIn();
			}

			if (frameElement && frameElement.id!=denyFrameId) {
				window.parent.jQuery('#popupLayerScreenLocker').css("z-index",parseInt(openedPopups.length == 1 ? 999 : window.parent.jQuery("#popupLayer_" + openedPopups[openedPopups.length - 2].name).css("z-index")) + 1);
			}
			else {
				jQuery('#popupLayerScreenLocker').css("z-index",parseInt(openedPopups.length == 1 ? 999 : jQuery("#popupLayer_" + openedPopups[openedPopups.length - 2].name).css("z-index")) + 1);
			}
		} else {
			jQuery("body").append("<div id='popupLayerScreenLocker'><!-- --></div>");
			jQuery("#popupLayerScreenLocker").css({
				position: "fixed",
				background: setupJqueryMPopups.screenLockerBackground,
				left: "0",
				top: "0",
				opacity: setupJqueryMPopups.screenLockerOpacity,
				display: "none"
			});
			showScreenLocker();
		}
	}
	
	function hideScreenLocker(popupName) {
		var popupLayerScreenLocker = (!frameElement || frameElement.id==denyFrameId) ? jQuery("#popupLayerScreenLocker") : window.parent.jQuery("#popupLayerScreenLocker");
		screenlocker = false;
		popupLayerScreenLocker.hide();

		if (openedPopups.length > 0) {
			var popupLayer = (!frameElement || frameElement.id==denyFrameId) ? jQuery("#popupLayer_" + openedPopups[openedPopups.length - 1].name) : window.parent.jQuery("#popupLayer_" + openedPopups[openedPopups.length - 1].name);
			popupLayerScreenLocker.css("z-index",parseInt(popupLayer.css("z-index")) - 1);
		}
	}
	
	function setPopupLayersPosition(popupElement) {

		if (popupElement) {
			var windowPopup  = (frameElement && frameElement.id!=denyFrameId) ? window.parent : window;
			var leftPosition = (windowPopup.document.documentElement.offsetWidth - popupElement.width()) / 2;
			var windowHeight = windowPopup.innerHeight || windowPopup.document.documentElement.offsetHeight;
			var topPosition  = jQuery(windowPopup.document.documentElement).scrollTop() || jQuery(windowPopup.document).scrollTop();

			var margin = (windowHeight - popupElement.height()) / 2;
			margin = (margin < 0) ? 15 : margin;
			topPosition = topPosition + margin;

			var positions = {
				left: leftPosition + "px",
				top: topPosition + "px"
			};

			popupElement.css(positions);

			setScreenLockerSize();
		} else {
			for (var i = 0; i < openedPopups.length; i++) {
				setPopupLayersPosition(((frameElement && frameElement.id!=denyFrameId) ? window.parent.jQuery("#popupLayer_" + openedPopups[i].name) : jQuery("#popupLayer_" + openedPopups[i].name)), false);
			}
		}
	}

	function showPopupLayerContent(popupObject, newElement, data) {
		var baseZIndex = popupObject.zIndex || 400000;
		var idElement = "popupLayer_" + popupObject.name,
			popupElement, zIndex;

		if (newElement) {
			showScreenLocker();
			if (!frameElement || frameElement.id==denyFrameId) {
				jQuery("body").append("<div id='" + idElement + "' class='eip_win'><!-- --></div>");
				popupElement = jQuery("#" + idElement);
				zIndex = parseInt(openedPopups.length == 1 ? baseZIndex : jQuery("#popupLayer_" + openedPopups[openedPopups.length - 2].name).css("z-index")) + 2;
			}
			else {
				var parent_body = jQuery(frameElement).parents("body");
				popupElement = document.createElement("div");
				popupElement.id = idElement;
				popupElement.className = 'eip_win';
				parent_body[0].appendChild(popupElement);
				popupElement = jQuery(popupElement);
				zIndex = parseInt(openedPopups.length == 1 ? baseZIndex : window.parent.jQuery("#popupLayer_" + openedPopups[openedPopups.length - 2].name).css("z-index")) + 2;
			}
		}
		else {
			popupElement = jQuery("#" + idElement);
			zIndex = popupElement.css("z-index");
		}

		popupElement.css({
			"z-index": zIndex
		});

		if (popupObject.width != "auto") popupElement.css("width", (popupObject.width + 40) + "px");
		if (popupObject.height != "auto") popupElement.css("height", (popupObject.height + 40) + "px");

		var linkAtTop = "<a href='#' class='jmp-link-at-top' style='position:absolute; left:-9999px; top:-1px;'>&nbsp;</a><input class='jmp-link-at-top' style='position:absolute; left:-9999px; top:-1px;' />";
		var linkAtBottom = "<a href='#' class='jmp-link-at-bottom' style='position:absolute; left:-9999px; bottom:-1px;'>&nbsp;</a><input class='jmp-link-at-bottom' style='position:absolute; left:-9999px; top:-1px;' />";

		if (popupObject.target == "" && popupObject.data == "") {
			var style = "";
			if (popupObject.width != "auto") style = style + "width:" + popupObject.width + "px;";
			if (popupObject.height != "auto") style = style + "height:" + popupObject.height + "px;";
			if (style.length) style = "style='" + style + "'";
			var content = "<iframe class='umiPopupFrame' frameborder='0' "+style+" src='"+popupObject.url+"'></iframe>";
			data = '\n\
				<div class="eip_win_head popupHeader">\n\
					<div class="eip_win_close popupClose">&#160;</div>\n\
					<div class="eip_win_title">' + popupObject.title + '</div>\n\
				</div>\n\
				<div class="eip_win_body popupBody">' + content + '</div>\n\
			';
		}

		popupElement.html(linkAtTop + data + linkAtBottom);

		jQuery('.eip_win_head', popupElement).mousedown(function() {
			popupElement.draggable({containment: 'window'});
		});

		jQuery('.eip_win_body', popupElement).mousedown(function() {
			popupElement.draggable().draggable('destroy');
		});

		jQuery('.eip_win_close', popupElement).click(function() {
			jQuery.closePopupLayer();
		});

		if(popupObject.url && popupObject.url.indexOf('umifilebrowser') != -1) {
			jQuery("div.popupBody", popupElement).css("padding" , "0px");
			popupElement.css("width", popupObject.width + "px");
		}
		
		setPopupLayersPosition(popupElement, false);

		popupElement.css("display","none");
		popupElement.css("visibility","visible");
		
		if (newElement) {
			popupElement.show();
		} else {
			popupElement.show();
		}

		jQuery("#" + idElement + " .jmp-link-at-top, " +
		  "#" + idElement + " .jmp-link-at-bottom").focus(function(){
			jQuery(focusableElement[focusableElement.length-1]).focus();
		});
		
		var jFocusableElements = jQuery("#" + idElement + " a:visible:not(.jmp-link-at-top, .jmp-link-at-bottom), " +
								   "#" + idElement + " *:input:visible:not(.jmp-link-at-top, .jmp-link-at-bottom)");
						   
		if (jFocusableElements.length == 0) {
			var linkInsidePopup = "<a href='#' class='jmp-link-inside-popup' style='position:absolute; left:-9999px;'>&nbsp;</a>";
			popupElement.find(".jmp-link-at-top").after(linkInsidePopup);
			focusableElement.push(jQuery(popupElement).find(".jmp-link-inside-popup")[0]);
		} else {
			jFocusableElements.each(function(){
				if (!jQuery(this).hasClass("jmp-link-at-top") && !jQuery(this).hasClass("jmp-link-at-bottom")) {
					focusableElement.push(this);
					return false;
				}
			});
		}
		
		jQuery(focusableElement[focusableElement.length-1]).focus();

		popupObject.success();
		
		if (popupObject.reloadSuccess) {
			popupObject.reloadSuccess();
			popupObject.reloadSuccess = null;
		}
	}

	jQuery.pushPopup = function(popupObject) {
		openedPopups.push(popupObject);
	}

	jQuery.getOpenedPopups = function() {
		return openedPopups;
	}
	jQuery.centerPopupLayer = function(){
		setScreenLockerSize();
		setPopupLayersPosition();
	}

	if(frameElement && frameElement.id!=denyFrameId) openedPopups = window.parent.jQuery.getOpenedPopups();

	function loadPopupLayerContent(popupObject, newElement) {
		if (newElement) {
			if (!frameElement || frameElement.id==denyFrameId) jQuery.pushPopup(popupObject);
			else window.parent.jQuery.pushPopup(popupObject);
		}

		if(popupObject.data != "") {
			showPopupLayerContent(popupObject, newElement, popupObject.data);
		} else if (popupObject.target != "") {
            showPopupLayerContent(popupObject, newElement, jQuery("#" + popupObject.target).html());
        } else {
			showPopupLayerContent(popupObject, newElement, "");
		}
	}
	
	jQuery(window).resize(function(){
		setScreenLockerSize();
		setPopupLayersPosition();
	});
	
	jQuery(document).keydown(function(e){
		if (e.keyCode == 27) {
			jQuery.closePopupLayer();
		}
	});
})(jQuery);
