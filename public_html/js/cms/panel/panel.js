// Панель быстрого редактирования EIP
uAdmin('.panel', function(extend) {

	function uPanel() {
		var self = this;
		$('html').addClass('u-eip');

		this.panelHolder = this.addPanelHolder();
		this.quickpanel = this.addQuickpanel();
		this.showHideBtn = this.addShowHideBtn();
		this.drawControls();

		$(document).bind('click', function(event) {
			if (!$(event.target).parents('#u-quickpanel').length) {
				self.changeAct();
			}
		});

		if (!$.cookie('eip-panel-state-first')) {
			this.quickpanel.css({'overflow': 'hidden', 'height': '0'});
			this.showHideBtn.addClass('collapse');

			this.quickpanel.delay(500).animate({
				height: '38px'
			}, 500, function() {
				$(this).css('overflow', 'visible');
				self.showHideBtn.removeClass('collapse');
			});

			this.quickpanel.fadeTo(300, 0.3);
			this.quickpanel.fadeTo(300, 1);

			$.cookie('eip-panel-state', '', {
				path: '/',
				expires: 0
			});

			var date = new Date();
			date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));

			$.cookie('eip-panel-state-first', 'Y', {
				path: '/',
				expires: date
			});
		}

		var url = self.getUrlPrefix() + '/admin/content/frontendPanel/.json?links';
		url += '&ts=' + Math.round(Math.random() * 1000);

		$.ajax({
			url: url,
			dataType: 'json',
			success: this.onLoadData
		});
	}

	/** Возвращает префикс для ссылок с учетом языка */
	uPanel.prototype.getUrlPrefix = function() {
		return uAdmin.lang_prefix ? '/' + uAdmin.lang_prefix : '';
	};

	uPanel.prototype.drawControls = function() {
		this.exitButton = this.addExitButton();
		this.helpButton = this.addHelpButton();
		this.butterfly = this.addButtrfly();
		this.eipHolder = this.addEipHolder();
		this.editMenu = this.addEditMenu();
		this.lastDoc = this.addLastDoc();
		this.changelogDd = this.addChangelogDd();
		this.note = this.addNote();
		this.metaHolder = this.addMetaHolder();
	};

	uPanel.prototype.addPanelHolder = function() {
		return $('<div id="u-panel-holder" />').appendTo('body');
	};

	uPanel.prototype.addQuickpanel = function() {
		if (!this.panelHolder.length) {
			return null;
		}
		return $('<div id="u-quickpanel" />').appendTo(this.panelHolder);
	};

	uPanel.prototype.addShowHideBtn = function() {
		if (!this.panelHolder.length) {
			return null;
		}

		var self = this;
		return $('<div id="u-show_hide_btn" />')
			.appendTo(this.panelHolder)
			.click(function() {
				self.swap(this);
			});
	};

	uPanel.prototype.addExitButton = function() {
		if (!this.quickpanel.length) {
			return null;
		}

		var self = this;
		return $('\n\
			<div id="exit" title="' + getLabel('js-panel-exit') + '">&#160;</div>\n\
		')
			.appendTo(this.quickpanel)
			.click(function() {
				window.location = self.getUrlPrefix() + '/users/logout/';
				return false;
			});
	};

	uPanel.prototype.addHelpButton = function() {
		if (!this.quickpanel.length) {
			return null;
		}
		return $('\n\
			<div id="help" title="' + getLabel('js-panel-documentation') + '">&#160;</div>\n\
		')
			.appendTo(this.quickpanel)
			.click(function() {
				window.open('http://help.docs.umi-cms.ru');
				return false;
			});
	};

	uPanel.prototype.addButtrfly = function() {
		if (!this.quickpanel.length) {
			return null;
		}

		return $('\n\
			<div id="butterfly">\n\
				<span class="in_ico_bg">&#160;</span>' + getLabel('js-panel-modules') + '\n\
				<div class="bg">\n\
					<ul id="u-mods-cont-left" />\n\
					<ul id="u-mods-cont-right" />\n\
					<div class="clear separate" />\n\
					<ul id="u-mods-utils" />\n\
					<ul id="u-mods-admin" />\n\
					<div class="clear" />\n\
				</div>\n\
			</div>\n\
		').appendTo(this.quickpanel);
	};

	uPanel.prototype.addEipHolder = function() {
		if (!this.quickpanel.length) {
			return null;
		}
		return $('<div />').attr({id: 'eip_holder'}).appendTo(this.quickpanel);
	};

	uPanel.prototype.addMetaHolder = function() {
		if (!this.quickpanel.length) {
			return null;
		}
		return $('<div />').attr({id: 'meta_holder'}).appendTo(this.quickpanel);
	};

	uPanel.prototype.addEditMenu = function() {
		if (!this.quickpanel.length) {
			return null;
		}
		return $('\n\
			<div id="edit_menu" title="' + getLabel('js-panel-edit-menu') + '">\n\
				<span class="in_ico_bg">&#160;</span>\n\
				<div>\n\
					<ul id="u-docs-edit"/>\n\
					<span class="clear" />\n\
				</div>\n\
			</div>\n\
		').appendTo(this.quickpanel);
	};

	uPanel.prototype.addLastDoc = function() {
		if (!this.quickpanel.length) {
			return null;
		}
		var self = this;
		return $('\n\
			<div id="last_doc">\n\
				<span class="in_ico_bg" />\n\
				' + getLabel('js-panel-last-documents') + '\n\
				<div>\n\
					<ul id="u-docs-recent" />\n\
					<span class="clear" />\n\
				</div>\n\
			</div>\n\
		')
			.appendTo(this.quickpanel)
			.click(function() {
				self.changeAct(this);
			});
	};

	uPanel.prototype.addChangelogDd = function() {
		if (!this.quickpanel.length) {
			return null;
		}
		var self = this;
		return $('\n\
			<div id="changelog_dd" style="display:none;">\n\
				<span class="in_ico_bg">&#160;</span>\n\
				' + getLabel('js-panel-history-changes') + '\n\
				<div>\n\
					<ul id="u-changelog" />\n\
					<span class="clear" />\n\
				</div>\n\
			</div>\n\
		')
			.appendTo(this.quickpanel)
			.click(function() {
				self.changeAct(this);
			});
	};

	uPanel.prototype.addNote = function() {
		if (!this.quickpanel.length) {
			return null;
		}
		return $('\n\
			<div id="note">\n\
				<span class="in_ico_bg">&#160;</span>\n\
				' + getLabel('js-panel-note') + '\n\
			</div>\n\
		').appendTo(this.quickpanel);
	};

	uPanel.prototype.addSeoButton = function() {
		if (!this.quickpanel.length) {
			return null;
		}

		var self = this;
		return $('\n\
			<div id="seo">\
				<span class="in_ico_bg">&#160;</span>\n\
				' + getLabel('module-seo') + '\n\
			</div>\n\
		').appendTo(this.quickpanel).click(function() {
			window.location = self.getUrlPrefix() + '/admin/seo/';
		});
	};

	uPanel.prototype.onLoadData = function(data) {
		var self = uAdmin.panel;
		if (!self) {
			return false;
		}

		$('<link type="text/css" rel="stylesheet" href="/styles/skins/_eip/css/theme.css" />').appendTo('head');
		var page, module;
		for (page in data.documents.recent.page) {
			page = data.documents.recent.page[page];
			$('ul#u-docs-recent', self.lastDoc).append('<li><a href="' + page.link + '">' + page.name + '</a></li>');
		}

		var i = 0;
		for (page in data.documents.editable.page) {
			page = data.documents.editable.page[page];

			if (typeof page !== 'object') {
				continue;
			}

			for (module in data.modules.module) {
				module = data.modules.module[module];
				if (module.name == page.basetype.module) {
					continue;
				}
			}
			$('ul#u-docs-edit', self.quickpanel || null).append('<li><a href="' + self.getUrlPrefix() + page['edit-link'] + '">' + page.name + '</a></li>');
			i++;
		}

		if (i && self.editMenu) {
			self.editMenu.click(function() {
				self.changeAct(this);
			});
		} else if (self.editMenu) {
			self.editMenu.hide(0);
		}

		i = 0;
		for (module in data.modules.module) {
			module = data.modules.module[module];
			var selector;
			switch (module.type) {
				case 'system':
					selector = 'ul#u-mods-utils';
					break;
				case 'util':
					selector = 'ul#u-mods-admin';
					break;
				default:
					selector = (++i % 2) ? 'ul#u-mods-cont-left' : 'ul#u-mods-cont-right';
			}
			$(selector, self.butterfly || null).append('<li><a href="' + self.getUrlPrefix() + '/admin/' + module.name + '/' + '">' + module.label + '</a></li>');
		}

		if (i && self.butterfly) {
			self.butterfly.click(function() {
				self.changeAct(this);
			}).addClass('butterfly_hover');
		}

		if (typeof data.changelog != 'undefined' && self.changelogDd) {
			for (var revision in data.changelog.revision) {
				revision = data.changelog.revision[revision];
				var label = revision.date.std + (revision.author.name ? ' - ' + revision.author.name : '') + (revision.active == 'active' ? '&nbsp;&nbsp;&nbsp;&larr;' : '');
				var link = revision.link + '?force-redirect=' + window.location.pathname;
				$('#u-changelog', self.changelogDd).append('<li><a href="' + link + '">' + label + '</a></li>');
			}
			self.changelogDd.css('display', '');
		} else if (self.changelogDd) {
			self.changelogDd.css('display', 'none');
		}

		if (typeof data.tickets != 'undefined' && typeof uAdmin.tickets == 'object') {
			uAdmin.tickets.draw(data);
		}
		else if (self.note) {
			self.note.remove();
		}
	};

	uPanel.prototype.swap = function(el) {
		var quickpanel_height = $('#u-quickpanel').css('height');
		if (quickpanel_height == '0px') {
			return this.expand(el);

		}
		else {
			if (uAdmin.eip.meta.enabled) {
				$('#u-quickpanel #meta').trigger('click');
			}
			return this.collapse(el);
		}
	};

	uPanel.prototype.expand = function(el) {
		var quickpanel = $('#u-quickpanel');
		quickpanel.css('overflow', 'visible');
		quickpanel.animate({height: '38px'}, 700);
		$(el).removeClass('collapse');

		$.cookie('eip-panel-state', '', {path: '/', expires: 0});
	};

	uPanel.prototype.collapse = function(el) {
		var quickpanel = $('#u-quickpanel');
		quickpanel.css('overflow', 'hidden');
		quickpanel.animate({height: '0'}, 700);
		$(el).addClass('collapse');

		var date = new Date();
		date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
		$.cookie('eip-panel-state', 'collapsed', {path: '/', expires: date});
	};

	uPanel.prototype.loadRes = function(type, src, callback) {
		var node;
		switch (type) {
			case 'js':
			case 'text/javascript':
				node = document.createElement('script');
				node.src = src;
				node.charset = 'utf-8';
				break;

			case 'css':
			case 'text/css':
				node = document.createElement('link');
				node.href = src;
				node.rel = 'stylesheet';
				break;
			default:
				return;
		}

		document.body.parentNode.firstChild.appendChild(node);
		if (typeof callback == 'function') {
			$(document).one('ready', callback);
		}
	};

	uPanel.prototype.changeAct = function(el) {
		var eCond = (uAdmin.eip && uAdmin.eip.enabled) ? '[id != \'edit\'][id != \'ieditor-switcher\']' : '',
			save_edit = $('#save_edit');

		if (!el) {
			$('#u-quickpanel .act div:first').hide();
			$('#u-quickpanel .act' + eCond).removeClass('act');
		}
		else if ($(el).hasClass('act')) {
			$('#u-quickpanel .act div:first').hide();
			$('#u-quickpanel .act' + eCond).removeClass('act');
			
			if (el.id == 'edit') {
				save_edit.css('display', 'none');
			}
		}
		else {
			var act_arr = $('#u-quickpanel .act'), opera_width = false;
			
			if (act_arr.length) {
				$('#u-quickpanel .act div:first').hide();
				$('#u-quickpanel .act' + eCond).removeClass('act');
				if (el.id == 'edit' && !eCond) {
					save_edit.css('display', 'none');
				}
			}
			
			if ($.browser.opera) {
				opera_width = $(el).width();
			}
			
			$(el).addClass('act');
			
			if (opera_width) {
				$(el).width(opera_width);
			}
			
			$('#u-quickpanel .act div:first').show();
			
			if (el.id == 'edit') {
				save_edit.css('display', 'block');
			}
		}

		if ($(el).attr('id') != 'meta' && uAdmin.eip && uAdmin.eip.meta.enabled) {
			$('#u-quickpanel #meta').addClass('act');
		}

	};

	uPanel.prototype.editInAdmin = function(type) {
		if (type == 'enable') {
			$(document).bind('keypress', this.editInAdmin.bindEvents);
		}
		
		if (type == 'disable') {
			$(document).unbind('keypress', this.editInAdmin.bindEvents);
		}
	};

	uPanel.prototype.editInAdmin.bindEvents = function(e) {
		if (e.shiftKey) {
			switch (e.charCode || e.keyCode) {
				case 68:// latin - D
				case 100:// latin - d
				case 1042:// russian - В
				case 1074:// russian - в
					$('#u-quickpanel #edit_menu').each(function(i, node) {
						uAdmin.panel.changeAct(node);
					});
					break;
			}
		}
	};

	return extend(uPanel, this);
});
