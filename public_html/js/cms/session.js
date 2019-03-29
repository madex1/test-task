uAdmin('.session', function (extend) {
	function uSession() {
		var self = this;

		self.access = !!self.access;
		self.lifetime = parseInt(self.lifetime) || 10;

		self.pingInterval = Math.min((self.lifetime*60)/2, 300);
		self.awayTimeout = 0;
		self.lastPingedTime = new Date();

		self.currentMessage = null;

		this.lastActionTime = new Date();

		(function init() {
			jQuery(document).bind('click keydown mousedown', null, function(eventObject){
				self.eventHandler(eventObject);
			});

			setTimeout(function() {
				jQuery( 'iframe' ).each( function() {
					try {
						var d = this.contentWindow || this.contentDocument;
						if (d.document) {
							jQuery(d.document).bind('click keydown mousedown', function(eventObject){
								self.eventHandler(eventObject);
							});
						}
					} catch (e) {}
				});
			}, 5000);

			self.pingIntervalHandler = setInterval(function() {
				self.pingHandler();
			}, self.pingInterval * 1000);
		})();
	}

	uSession.prototype.sessionAjax = function (data, callback) {
		jQuery.get("/session.php", data, function(response) {
			if (typeof callback == 'function') callback(response);
		});
	}

	uSession.prototype.login = function (login, password, callback) {
		this.sessionAjax({'u-login': login, 'u-password': password, 'a': 'ping'}, function(response) {
			if (typeof callback == 'function') callback(response != "-1");
		});
	}
	uSession.prototype.check = function () {
		var self = this;

		this.sessionAjax(null, function(response){
			self.checkHandler(response);
		});
	}

	uSession.prototype.ping = function (force) {
		var self = this;

		// пингуем не чаще, чем раз в 5 секунд, либо принудительно
		if(((new Date()).getTime() - self.lastPingedTime.getTime() < 5000) && !force) { // < 2 sec
			return;
		}
		self.lastPingedTime = new Date();

		self.sessionAjax({'a': 'ping'}, function(response) {
			self.checkHandler(response);
		});
	}

	uSession.prototype.checkHandler = function(data) {
		switch(data) {
			case '-1': case '0': {
				this.showCloseMessage(true);
				break;
			}
			default: {
				this.awayTimeout = parseInt(data, 10);
				if (this.awayTimeout <= this.pingInterval + 10) {
					this.showWarningMessage();
				} else {
					if(!this.pingIntervalHandler) {
						var self = this;
						this.pingIntervalHandler = setInterval(function() {
							self.pingHandler();
						}, this.pingInterval * 1000);
					}
					this.closeMessage();
				}
				break;
			}
		}
	}

	uSession.prototype.isUserActive = function () {
		return (new Date().getTime() - this.lastActionTime.getTime())/1000 < this.pingInterval;
	}

	/** Event handler for users actions */
	uSession.prototype.eventHandler = function (eventObject) {
		this.lastActionTime = new Date();

		switch (this.currentMessage) {
			case 'warning': {
				this.closeMessage();
				this.ping(true);
			}
			case 'close': {
				this.ping();
			}
		}
	}

	uSession.prototype.pingHandler = function () {
		if (this.isUserActive()) {
			this.ping();
		} else {
			this.check();
		}
	}

	uSession.prototype.showWarningMessage = function(force) {
		var self = this;

		if (force) {
			clearInterval(self.timer);
			self.timer = null;
		}

		self.currentMessage = 'warning';

		if (self.timer){
			return;
		}

		self.timer = setInterval(function() {
			// отображается таймер
			if (self.awayTimeout > 0) {
				var timeRemains = self.awayTimeout;

				var minRemains = parseInt(timeRemains/60);
				var secRemains = timeRemains%60;

				if (secRemains < 10) secRemains = "0" + secRemains;

				var msg = minRemains + ":" + secRemains;

				var timeNoUserHereMin = parseInt((self.lifetime * 60 - self.awayTimeout) / 60);

				self.message(
					getLabel("js-session-is-away") + " " + timeNoUserHereMin + " " + getLabel("js-minutes") + ". " +
					getLabel("js-session-warning") + "<br/>" + msg
				);
				self.awayTimeout--;
			} else {
				self.closeMessage();
				self.check();
			}
		}, 1000);


	};

	uSession.prototype.showCloseMessage = function(force) {
		var self = this;

		if (force) {
			clearInterval(self.timer);
			self.timer = null;
		}

		if (self.currentMessage == "close") {
			return;
		}

		self.currentMessage = "close";

		if (self.pingIntervalHandler) {
			clearInterval(self.pingIntervalHandler);
			self.pingIntervalHandler = 0;
		}

		var msg = jQuery('<div><br />' + getLabel("js-session-was-away") + " " + self.lifetime + " " + getLabel("js-minutes") + ", " +
			getLabel("js-session-close") + '<br/><br/></div>');

		var form = jQuery('\n\
			<form>\n\
				<table cellspacing="5" width="100%">\n\
					<tr>\n\
						<td>' + getLabel("js-label-login") + ': </td>\n\
						<td><input type="text" name="session_contorl_login" /></td>\n\
					</tr>\n\
					<tr>\n\
						<td>' + getLabel("js-label-password") + ':</td>\n\
						<td><input type="password" name="session_contorl_passsword" /></td>\n\
					</tr>\n\
				</table>\n\
				<br/>\n\
				<input type="submit" value="' + getLabel("js-label-login-do") + '">\n\
			</form>\n\
		').appendTo(msg);

		if (self.access) {
			jQuery("<br/> <br/><a href='/admin/config/main/' target='_blank'>" + getLabel("js-session-lifetime-configure") + "</a>").appendTo(form);
		}

		form.submit(function() {
			var login  = jQuery.trim(this.session_contorl_login.value),
				passwd = jQuery.trim(this.session_contorl_passsword.value);

			if (login && passwd) {
				self.login(login, passwd, function(response) {
					if (response) {
						jQuery.getJSON('/admin/config/main/.json', {}, function(r) {
							if (uAdmin.csrf && uAdmin.csrf != r.csrf) {
								uAdmin.csrf = r.csrf;
							} else if (csrfProtection && csrfProtection.getToken() != r.csrf) {
								csrfProtection.changeToken(r.csrf);
							}
						});

						self.closeMessage();

						self.pingIntervalHandler = setInterval(function() {
							self.pingHandler();
						}, self.pingInterval * 1000);

						jQuery.jGrowl(getLabel("js-session-restored"), {
							'header': 'UMI.CMS',
							'life': 5000
						});
					} else {
						jQuery.jGrowl(getLabel("js-label-text-error"), {
							'header': 'UMI.CMS',
							'life': 5000
						});
					}
				});
			} else {
				jQuery.jGrowl(getLabel("js-label-text-error"), {
					'header': 'UMI.CMS',
					'life': 5000
				});
			}

			return false;
		});

		self.message(msg);
	};

	uSession.prototype.message = function(msg) {
		var self = this;

		if (typeof msg == 'string') {
			msg = '<br/><p> ' + msg + ' </p>';
		}

		if (!self.jgrowl) {
			self.jgrowl = -1;
			jQuery.jGrowl(msg, {
				header: 'UMI.CMS',
				dont_close: true,
				beforeOpen: function(domObject) {
					self.jgrowl = jQuery(domObject);
				},
				close: function() {
					self.closeMessage();
					self.ping(true);
				}
			});
			return;
		} else {
			if (self.jgrowl != -1) {
				var jGrowlMessage = self.jgrowl.find('.jGrowl-message');
				if (typeof msg == 'string') {
					jGrowlMessage.html(msg);
				} else {
					jGrowlMessage.html("");
					jGrowlMessage.append(msg);
				}
			}
		}
	};

	uSession.prototype.closeMessage = function() {
		this.currentMessage = null;

		if (this.timer) {
			clearInterval(this.timer);
			this.timer = null;
		}

		if (this.jgrowl && this.jgrowl != -1) {
			this.jgrowl.mouseout();
			this.jgrowl.remove();
			this.jgrowl = null;
		}
	};

	uSession.prototype.destroy = function() {
		if (this.pingIntervalHandler) {
			clearInterval(this.pingIntervalHandler);
			this.pingIntervalHandler = 0;
		}

		if (this.timer) {
			clearInterval(this.timer);
			this.timer = null;
		}

		return true;
	};

	return extend(uSession, this);
});