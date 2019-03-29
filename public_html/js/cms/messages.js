uAdmin('.messages', function (extend) {
	function uMessages() {
		var self = this;
		(function init() {
			var self = this;
			self.inited = true;
			if (typeof uAdmin.panel == 'object') {
				jQuery('\n\
					<div id="message" title="">&#160;</div>\n\
				').insertAfter('div#u-panel-holder div#u-quickpanel div#help').click(function () {
					uAdmin.messages.render();
				});
			}
			setTimeout(function () {
				uAdmin.messages.refresh();
			}, 1500);
		})();
	};

	uMessages.prototype.refresh = function () {
		jQuery.ajax({
			url      : '/umess/inbox/.json',
			dataType : 'json',
			success  : function (data) {
				uAdmin.messages.count = 0;
				for (var message in data.messages.message) {
					message = data.messages.message[message];
					uAdmin.messages.messages['message_' + message.id] = message;
					++uAdmin.messages.count;
				}
				if (typeof uAdmin.panel == 'object' && uAdmin.messages.count) {
					uAdmin.messages.counter = jQuery('div#u-panel-holder div#u-quickpanel div#message sup');
					if (!uAdmin.messages.counter.length) uAdmin.messages.counter = jQuery('<sup />').appendTo('div#u-panel-holder div#u-quickpanel div#message');
					uAdmin.messages.counter.text(uAdmin.messages.count);
				}
				setTimeout(function() {
					uAdmin.messages.refresh();
				}, 10000);
			}
		});
	};

	uMessages.prototype.render = function () {
		var self = this, message;
		for (message in self.messages) {
			message = self.messages[message];
			jQuery.jGrowl('\n\
				<div id="umi-message-' + message.id + ' class="message priority-' + message.priority + '">\n\
					<div class="header">' + message.title + '</div>\n\
					<div class="content">' + message.content.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"') + '</div>\n\
					<div class="sender">' + self.convertDate(message.date.unix_timestamp) + " - " + (message.sender ? message.sender.name : 'system') + '</div>\n\
				</div>\n\
			', {
				'header': 'UMI.CMS',
				'life': 30000
			});
		}
	};

	uMessages.prototype.convertDate = function (time) {
		var dt = new Date(time * 1000);
		var hour = dt.getHours(), minute = dt.getMinutes(), second = dt.getSeconds();
		var monthnumber = dt.getMonth(), monthday = dt.getDate(), year = 1900 + dt.getYear();
		if (monthnumber < 10) monthnumber = "0" + monthnumber;
		if (monthday < 10) monthday = "0" + monthday;
		if (hour < 10) hour = "0" + hour;
		if (minute < 10) minute = "0" + minute;
		if (second < 10) second = "0" + second;
		return year + "." + monthnumber + "." + monthday + " " + hour + ":" + minute + ":" + second;
	};

	uMessages.prototype.messages = {};

	return extend(uMessages, this);
});
