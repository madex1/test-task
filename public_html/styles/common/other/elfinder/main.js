/**
 * elFinder client options and main script for RequireJS
 *
 * Rename "main.default.js" to "main.js" and edit it if you need configure elFInder options or any things. And use that in elfinder.html.
 * e.g. `<script data-main="./main.js" src="./require.js"></script>`
 **/
(function(){
	"use strict";
	var // jQuery and jQueryUI version
		jqver = '3.3.1',
		uiver = '1.12.1',

		start = function(elFinder, editors, config) {

			elFinder.prototype.loadCss('//cdnjs.cloudflare.com/ajax/libs/jqueryui/' + uiver + '/themes/smoothness/jquery-ui.css');
			
			$(function() {
				var optEditors = {
						commandsOptions: {
							edit: {
								editors: Array.isArray(editors)? editors : []
							}
						}
					},
					opts = {};
				
				if (config && config.managers) {
					$.each(config.managers, function(id, mOpts) {
						opts = Object.assign(opts, config.defaultOpts || {});

						try {
							mOpts.commandsOptions.edit.editors = mOpts.commandsOptions.edit.editors.concat(editors || []);
						} catch(e) {
							Object.assign(mOpts, optEditors);
						}

						var watermark = window.parent.jQuery('#add_watermark').is(':checked') ? 1 : 0;
						var elf = $('#' + id).elfinder(
							$.extend(true, { customData: {water_mark: watermark} }, opts, mOpts || {})
						).elfinder('instance');

						if (window.parent) {
							if (window.parent.edition == 'demo') {
								window.parent.jQuery.jGrowl(window.parent.getLabel('js-filemanager-demo-notice'));
							}

							window.parent.jQuery('#add_watermark').change(function() {
								var watermark = jQuery(this).is(':checked') ? 1 : 0;
								Object.assign(elf.options.customData, {water_mark : watermark});
							});

							window.parent.jQuery('#remember_last_folder').change(function() {
								var remember_last_folder = jQuery(this).is(':checked') ? 1 : null;
								var oneDay = '1';
								setCookie('remember_last_folder', remember_last_folder, oneDay);
							});
						}
					});
				} else {
					alert('"elFinderConfig" object is wrong.');
				}
			});
		},
		
		load = function() {
			require(
				[
					'elfinder',
					'extras/editors.default.min',
					'elFinderConfig'
				],
				start,
				function(error) {
					alert(error.message);
				}
			);
		},
		
		old = (typeof window.addEventListener === 'undefined' && typeof document.getElementsByClassName === 'undefined')
		       ||
		      (!window.chrome && !document.unqueID && !window.opera && !window.sidebar && 'WebkitAppearance' in document.documentElement.style && document.body.style && typeof document.body.style.webkitFilter === 'undefined');

	require.config({
		baseUrl : 'js',
		paths : {
			'jquery'   : '//cdnjs.cloudflare.com/ajax/libs/jquery/' + (old ? '1.12.4' : jqver) + '/jquery.min',
			'jquery-ui': '//cdnjs.cloudflare.com/ajax/libs/jqueryui/' + uiver + '/jquery-ui.min',
			'elfinder' : 'elfinder.min'
		},
		waitSeconds : 10
	});

	load();
})();
