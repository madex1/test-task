uAdmin('.tickets', function (extend) {
	function uTickets() {
		/** @var {int} minTicketHeight Минимальная ширина элемента заметок */
		var minTicketHeight = 100;
		/** @param {string} defaultColor Цвет заметок по умолчанию */
		this.defaultColor = '#FFFFE1';
		/** @param {string} colorProperty Имя свойства, в котором хранится цвет для заметок */
		this.colorProperty = 'ticketsColor';
		this.buttonSelector = '#u-quickpanel #note, #quickpanel #note';

		/**
		 * Отображает сообщение об ошибке
		 * @param {string} message текст сообщения
		 */
		var showError = function(message) {
			jQuery.jGrowl(message, {
				'header': 'UMI.CMS',
				'life': 5000
			});
		};

		var ticket = function (params) {
			var self = this;
			self.params = params;

			(function init() {
				if (params.message)  {
					self.createMessage();
				}
				self.update();
			})();

		};

		/** Восстановливет предыдущие значения параметров заметки */
		ticket.prototype.rollbackParams = function () {
			if (this.params.oldWidth) {
				this.params.width = this.params.oldWidth;
			}

			if (this.params.oldHeight) {
				this.params.height = this.params.oldHeight;
			}

			if (this.params.oldX) {
				this.params.x = this.params.oldX;
			}

			if (this.params.oldY) {
				this.params.y = this.params.oldY;
			}

			if (this.params.oldMessage) {
				this.params.message.text = this.params.oldMessage;
			}
		};

		ticket.prototype.resetSelection = function () {
			if (document.selection && document.selection.empty) {
				document.selection.empty();
			}
			else if(window.getSelection) {
				var sel = window.getSelection();
				if(sel && sel.removeAllRanges) {
					sel.removeAllRanges();
				}
			}
		};

		ticket.prototype.createMessage = function () {
			var self = this;
			self.messageNode = jQuery('<div class="u-ticket-comment"><div /><textarea /><a /></div>').appendTo('body');
			self.messageNode.css('background-color', self.params.message.color);

			self.messageNode.draggable({
				scroll: false,
				containment: document.body,
				stop: function(event, ui) {
					self.params.oldX = self.params.x;
					self.params.oldY = self.params.y;
					self.params.x = ui.position.left;
					self.params.y = ui.position.top;
					self.save();
				}
			});

			if (self.params.message) {
				jQuery('div', self.messageNode).html('<span>' + self.params.message.authorName +
					' (' + self.params.message.authorLogin + ')</span>');
				jQuery('textarea', self.messageNode).prop('value', self.params.message.text);
			}

			self.messageNode.resizable({
				minWidth: jQuery('div:first-child span', self.messageNode).outerWidth(),
				minHeight: minTicketHeight,
				containment: document.body,
				start: function() {
					jQuery(self.messageNode).css('cursor', 'default');
				},
				stop: function(event, ui) {
					self.params.oldWidth = self.params.width;
					self.params.oldHeight = self.params.height;
					self.params.width = ui.size.width;
					self.params.height = ui.size.height;
					self.save();
					jQuery(self.messageNode).css('cursor', 'move');
				}
			});

			jQuery('a', self.messageNode).html(getLabel('js-ticket-delete'));

			jQuery('textarea', self.messageNode).bind('change', function () {
				self.save();
			});

			jQuery('textarea', self.messageNode).bind('focus', function () {
				self.params.oldMessage = jQuery(this).prop('value');
			});

			jQuery('a', self.messageNode).bind('click', function () {
				self.del();
			});
		};

		ticket.prototype.del = function () {
			var self = this;

			if (self.params.id) {
				var url = '/tickets/manage/delete/' + self.params.id + '/';
				jQuery.ajax({
					url: url,
					dataType: 'json',
					type: 'get',
					success: function(response) {
						if (response && typeof response.error == 'string') {
							showError(response.error);
							return;
						}

						removeNodes(self.node, self.messageNode);
					}
				});
			} else {
				removeNodes(self.node, self.messageNode);
			}

			function removeNodes(node, messageNode) {
				if (node && typeof node.remove == 'function') {
					node.remove();
				}
				if (messageNode && typeof messageNode.remove == 'function') {
					messageNode.remove();
				}
			}
		};

		ticket.prototype.save = function () {
			var self = this;
			var mode = self.params.id ? 'modify' : 'create';
			var url = '/tickets/manage/' + mode + '/' + self.params.id + '/';
			url += '?ts=' + Math.round(Math.random() * 1000);
			jQuery.ajax({
				type: 'POST',
				url: url,
				dataType: 'json',
				data: {
					x: self.params.x,
					y: self.params.y,
					width: self.params.width,
					height: self.params.height,
					message: jQuery('textarea', self.messageNode).prop('value'),
					color: self.params.message.color || uAdmin.tickets.defaultColor,
					referer: window.location.href.split('#')[0]
				},
				success: function (resp) {
					if (resp && typeof resp.error == 'string') {
						showError(resp.error);
						self.rollbackParams();
						self.update();
						return;
					}
					self.params.id = resp.id;
				}
			});
		};

		ticket.prototype.update = function () {
			var self = this;

			self.params.width = self.params.width || self.messageNode.outerWidth();
			self.params.height = self.params.height || self.messageNode.outerHeight();

			if (self.messageNode) {
				jQuery('textarea', self.messageNode).prop('value', self.params.message.text);

				self.messageNode.css({
					top: parseInt(self.params.y),
					left: parseInt(self.params.x),
					width: self.params.width,
					height: self.params.height
				});
			}

		};

		this.ticket = ticket;
	};

	uTickets.prototype.initNewTicket = function () {
		this.changeState(jQuery(this.buttonSelector).get(0));

		if(!uAdmin.tickets.created) {
			alert(getLabel('js-panel-note-add'));
			uAdmin.tickets.created = true;
		}

		if (uAdmin.tickets.isInit) return false;

		uAdmin.tickets.isInit = true;

		var self = this;
		var firstName = uAdmin.tickets.user.fname;
		var secondName = (uAdmin.tickets.user.lname == null) ? '' : ' ' + uAdmin.tickets.user.lname;
		jQuery(document).one('mousedown', function (event) {
			new uAdmin.tickets.ticket({
				x: event.pageX,
				y: event.pageY,
				message: {
					authorName: firstName + secondName,
					authorLogin: uAdmin.tickets.user.login,
					color: uAdmin.tickets.user[self.colorProperty],
					text: getLabel('js-ticket-empty')
				}
			});
			uAdmin.tickets.isInit = false;
			self.changeState(jQuery(self.buttonSelector).get(0));
		});
	};

	/**
	 * Изменяет визуальное состояние кнопки (Нажата/Не нажата)
	 * @param {HTMLElement} element элемент кнопки
	 */
	uTickets.prototype.changeState = function(element) {
		var $element = jQuery(element);

		if (typeof uAdmin.panel == 'object') {
			uAdmin.panel.changeAct(element);
		} else if (jQuery('#quickpanel').length > 0) {
			var actClass = 'act';

			if ($element.hasClass(actClass)) {
				$element.removeClass(actClass);
			} else {
				$element.addClass(actClass);
			}
		}

	};

	uTickets.prototype.swapVisible = function() {
		this.disabled ? this.enable() : this.disable();
	};

	uTickets.prototype.disable = function () {
		var self = this;
		jQuery('div.u-ticket, div.u-ticket-comment, ' + self.buttonSelector).hide();
		jQuery(document).unbind('keydown', self.bindEvents);
		jQuery(self.buttonSelector).unbind('click', self.bindEvents);
		self.disabled = true;
	};

	uTickets.prototype.enable = function () {
		var self = this;
		jQuery('div.u-ticket, div.u-ticket-comment, ' + self.buttonSelector).show();
		jQuery(document).bind('keydown', self.bindEvents);
		jQuery(self.buttonSelector).bind('click', self.bindEvents);
		self.disabled = false;
	};

	uTickets.prototype.bindEvents = function (event) {
		if (event.delegateTarget !== document) {
			event.preventDefault();
		}

		if ((event.shiftKey && (event.keyCode == 67 || event.keyCode == 99)  && (event.target.nodeName != 'INPUT' && event.target.nodeName != 'TEXTAREA')) || (event.type=='click' && document.getElementById('note').id=='note')) {
			uAdmin.tickets.initNewTicket();
		}
	};

	uTickets.prototype.draw = function(data) {
		var self = this;
		uAdmin.tickets.user = data.user;

		for (var tick in data.tickets.ticket) {
			tick = data.tickets.ticket[tick];
			var pos = tick.position,
				author = tick.author;

			var firstName = author.fname;
			var secondName = (author.lname == null) ? '' : ' ' + author.lname;

			var t = new this.ticket({
				id: tick.id,
				x: pos.x,
				y: pos.y,
				width: pos.width,
				height: pos.height,
				message: {
					authorName: firstName + secondName,
					authorLogin: author.login,
					color: author[self.colorProperty] || self.defaultColor,
					text: tick.message
				}
			});
			t.update();
		}
	};

	uTickets.prototype.disabled = true;

	uTickets.prototype.isInit = false;

	return extend(uTickets, this);
});
