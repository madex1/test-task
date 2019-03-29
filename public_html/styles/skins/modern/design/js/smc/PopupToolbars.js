/** ChangeParentsToolbar */

var ChangeParentsToolbar = function(_oControl) {
	/** (Private properties) */
	var __self = this;
	var Control = _oControl;
	var HandleItem = null;
	var cDepth = parseInt(Control.container.style.zIndex) || 0;
	var IconsPath = Control.iconsPath;
	var DataSet = Control.dataSet;

	/** (Public properties) */
	this.highlight = null;
	this.element = null;
	this.buttons = [];

	/** (Private methods) */
	var __drawButtons = function() {
		var btns = document.createElement('div');
		btns.style.position = 'absolute';
		btns.style.display = 'none';
		btns.className = 'tree_toolbar';

		__appendButton(btns, {
			name: 'ico_move',
			icon: 'ico_move.png',
			hint: getLabel('js-move-hint'),
			label: getLabel('js-move-label'),
			init: function(button) {
				var e = button.element;
				if (HandleItem !== null && HandleItem.permissions & 8) {
					if (HandleItem.lockedBy) {
						var locker = HandleItem.lockedBy;
						var lname = typeof(locker['lname']) == 'string' ? locker['lname'] : '';
						var fname = typeof(locker['lname']) == 'string' ? locker['fname'] : '';
						button.icon = 'ico_locked.png';
						button.hint = getLabel('js-page-is-locked');
					}
					e.setAttribute('title', button.hint);
					e.style.background = "url('" + IconsPath + button.icon + "') no-repeat";

					e.setAttribute('href', '#');
					e.style.visibility = 'visible';
				} else {
					e.style.visibility = 'hidden';
				}
			},

			release: function(button) {
				if (HandleItem !== null) {
					if (HandleItem.lockedBy) {
						alert(getLabel('js-page-is-locked'));
					} else {
						Control.applyBehaviour(HandleItem);
					}
				}
				return false;
			}
		});

		__self.element = document.body.appendChild(btns);
	};

	var __initButtons = function() {
		for (var i = 0; i < __self.buttons.length; i++) {
			__self.buttons[i].init(__self.buttons[i]);
		}
	};

	var __appendButton = function(container, options) {
		var b = document.createElement('a');
		var name = options.name || 'toolbtn';
		var href = options.href || '#';
		var icon = options.icon || name + '.png';
		var init = options.init || function() {
		};
		var hint = options.hint || '';
		var label = options.label || '';

		var el = container.appendChild(b);
		var button = {
			'name': name,
			'href': href,
			'icon': icon,
			'init': init,
			'label': label,
			'hint': hint,
			'element': el
		};

		__self.buttons[__self.buttons.length] = button;

		el.setAttribute('href', href);
		el.setAttribute('title', hint);
		el.className = options.className || 'tree_toolbtn';
		el.appendChild(document.createTextNode(label));
		if (typeof(options.release) === 'function') {
			el.onclick = function() {
				if (!DataSet.isAvailable()) {
					return false;
				}
				return options.release(button);
			};
		} else {
			el.onclick = function() {
				if (!DataSet.isAvailable()) {
					return false;
				}
				if (HandleItem.focus) {
					HandleItem.focus();
				}
				return true;
			};
		}

		el.name = name;
		el.style.background = "url('" + icon + "') no-repeat";
	};

	var __draw = function() {
		var el = document.createElement('div');
		el.className = 'tree-highlight';
		el.style.display = 'none';
		el.style.position = 'absolute';
		el.style.zIndex = cDepth - 1;

		__self.highlight = Control.container.appendChild(el);

		__drawButtons();

	};

	/** (Public methods) */
	this.show = function(_HandleItem, bForce) {
		if (window.noToolBar) {
			return;
		}

		bForce = bForce || false;

		if (typeof(_HandleItem) === 'undefined' || (HandleItem === _HandleItem && !bForce)) {
			return false;
		}

		if (HandleItem) {
			if (HandleItem.isDefault) {
				HandleItem.labelControl.className.add('main-page');
			}
		}

		HandleItem = _HandleItem;

		if (HandleItem.isDefault) {
			HandleItem.labelControl.className.add('main-page');
		}

		__initButtons();

		var container = HandleItem.control.initContainer;
		var cpos = $(container).position();

		this.element.style.top = HandleItem.position.top + cpos.top + 'px';

		this.element.style.left = cpos.left + 500 + 'px';
		this.element.style.display = '';

	};

	this.hide = function() {
		if (HandleItem) {
			if (HandleItem.getSelected()) {
				HandleItem.labelControl.classList.add('selected');
			} else if (HandleItem.isVirtualCopy) {
				HandleItem.labelControl.classList.add('virtual');
			} else {
				HandleItem.labelControl.classList.remove('virtual');
				HandleItem.labelControl.classList.remove('selected');
			}

			if (HandleItem.isDefault) {
				HandleItem.labelControl.classList.add('main-page');
			}
			this.element.style.display = 'none';
			HandleItem = null;
		}
	};

	this.customize = function(options) {
		__options = options;
		__self.highlight = null;
		__draw;
	};

	if (typeof(Control) === 'object') {
		__draw();
	} else {
		alert('Can\'t create toolbar without control object');
	}
};

/** AddCopyToolbar */

var AddCopyToolbar = function(_oControl) {
	/** (Private properties) */
	var __self = this;
	var Control = _oControl;
	var HandleItem = null;
	var cDepth = parseInt(Control.container.style.zIndex) || 0;
	var IconsPath = Control.iconsPath;
	var DataSet = Control.dataSet;

	/** (Public properties) */
	this.highlight = null;
	this.element = null;
	this.buttons = [];

	/** (Private methods) */
	var __drawButtons = function() {
		var btns = document.createElement('div');
		btns.style.position = 'absolute';
		btns.style.display = 'none';
		btns.className = 'tree_toolbar';

		__appendButton(btns, {
			name: 'ico_copy',
			icon: 'ico_copy.png',
			hint: getLabel('js-copy-hint'),
			label: getLabel('js-copy-label'),
			init: function(button) {
				var e = button.element;
				if (HandleItem !== null && HandleItem.permissions & 8) {
					if (HandleItem.lockedBy) {
						var locker = HandleItem.lockedBy;
						var lname = typeof(locker['lname']) == 'string' ? locker['lname'] : '';
						var fname = typeof(locker['lname']) == 'string' ? locker['fname'] : '';
						button.icon = 'ico_locked.png';
						button.hint = getLabel('js-page-is-locked');
					}
					e.setAttribute('title', button.hint);
					e.style.background = "url('" + IconsPath + button.icon + "') no-repeat";

					e.setAttribute('href', '#');
					e.style.visibility = 'visible';
				} else {
					e.style.visibility = 'hidden';
				}
			},

			release: function(button) {
				if (HandleItem !== null) {
					if (HandleItem.lockedBy) {
						alert(getLabel('js-page-is-locked'));
					} else {
						Control.applyBehaviour(HandleItem);
					}
				}
				return false;
			}
		});

		__self.element = document.body.appendChild(btns);
	};

	var __initButtons = function() {
		for (var i = 0; i < __self.buttons.length; i++) {
			__self.buttons[i].init(__self.buttons[i]);
		}
	};

	var __appendButton = function(container, options) {
		var b = document.createElement('a');
		var name = options.name || 'toolbtn';
		var href = options.href || '#';
		var icon = options.icon || name + '.png';
		var init = options.init || function() {
		};
		var hint = options.hint || '';
		var label = options.label || '';

		var el = container.appendChild(b);
		var button = {
			'name': name,
			'href': href,
			'icon': icon,
			'init': init,
			'label': label,
			'hint': hint,
			'element': el
		};

		__self.buttons[__self.buttons.length] = button;

		el.setAttribute('href', href);
		el.setAttribute('title', hint);
		el.className = options.className || 'tree_toolbtn';
		el.appendChild(document.createTextNode(label));
		if (typeof(options.release) === 'function') {
			el.onclick = function() {
				if (!DataSet.isAvailable()) {
					return false;
				}
				return options.release(button);
			};
		} else {
			el.onclick = function() {
				if (!DataSet.isAvailable()) {
					return false;
				}
				if (HandleItem.focus) {
					HandleItem.focus();
				}
				return true;
			};
		}

		el.name = name;
		el.style.background = "url('" + icon + "') no-repeat";
	};

	var __draw = function() {
		var el = document.createElement('div');
		el.className = 'tree-highlight';
		el.style.display = 'none';
		el.style.position = 'absolute';
		el.style.zIndex = cDepth - 1;

		__self.highlight = Control.container.appendChild(el);

		__drawButtons();

	};

	/** (Public methods) */
	this.show = function(_HandleItem, bForce) {
		bForce = bForce || false;

		if (typeof(_HandleItem) === 'undefined' || (HandleItem === _HandleItem && !bForce)) {
			return false;
		}

		if (HandleItem) {
			if (HandleItem.isDefault) {
				HandleItem.labelControl.classList.add('main-page');
			}
		}

		HandleItem = _HandleItem;

		if (HandleItem.isDefault) {
			HandleItem.labelControl.classList.add('main-page');
		}

		__initButtons();

		var container = HandleItem.control.initContainer;
		var cpos = $(container).position();

		this.element.style.top = HandleItem.position.top + cpos.top + 'px';

		this.element.style.left = cpos.left + 500 + 'px';
		this.element.style.display = '';

	};

	this.hide = function() {
		if (HandleItem) {
			if (HandleItem.getSelected()) {
				HandleItem.labelControl.classList.add('selected');
			} else if (HandleItem.isVirtualCopy) {
				HandleItem.labelControl.classList.add('virtual');
			} else {
				HandleItem.labelControl.classList.remove('virtual');
				HandleItem.labelControl.classList.remove('selected');
			}

			if (HandleItem.isDefault) {
				HandleItem.labelControl.className += ' main-page';
			}

			this.element.style.display = 'none';
			HandleItem = null;
		}
	};

	this.customize = function(options) {
		__options = options;
		__self.highlight = null;
		__draw;
	};

	if (typeof(Control) === 'object') {
		__draw();
	} else {
		alert('Can\'t create toolbar without control object');
	}
};
