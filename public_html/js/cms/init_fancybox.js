function fancybox_init() {
	$("a.fancybox-group").fancybox({
		'transitionIn'	     : 'elastic',
		'transitionOut'	     : 'elastic',
		'titlePosition'      : 'over',
		'speedIn'		     : 600,
		'speedOut'		     : 200,
		'cyclic'             : true,
		'hideOnOverlayClick' : true,
		'showCloseButton'    : true,
		'margin'             : 15,
		'width'              : 'auto',
		'height'             : 'auto',
		'onComplete'		 : function() {
			$("#fancybox-wrap").hover(function() {
				$("#fancybox-title").show();
			}, function() {
				$("#fancybox-title").hide();
			});
		}
	});
}

$(document).ready(fancybox_init);
