/** https://gist.github.com/mudassir0909/ed7eceb5d20e65721f63 */
Selectize.define('clear_selection', function(options) {
	var self = this;

	//Overriding because, ideally you wouldn't use header & clear_selection simultaneously
	self.plugins.settings.dropdown_header = {
		title: getLabel('js-selectize-clear-selection')
	};
	this.require('dropdown_header');

	self.setup = (function() {
		var original = self.setup;

		return function() {
			original.apply(this, arguments);
			this.$dropdown.on('mousedown', '.selectize-dropdown-header', function(e) {
				self.setValue('');
				self.close();
				self.blur();

				return false;
			});
		}
	})();
});
