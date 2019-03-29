/** Класс хранилище кастомных кнопок тулбара smc-таблицы */

var TTCustomizer = {
	buttons: {},

	menu: [],

	extendDefault: false,

	extend: function(func) {
		if (_.keys(func).length > 0) {
			_.extend(this.buttons, func);
			return true;
		} else {
			return false;
		}
	}
};
